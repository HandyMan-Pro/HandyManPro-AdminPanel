<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProviderSubscription;
use App\Models\User;
use App\Models\SubscriptionTransaction;
use App\Http\Resources\API\ProviderSubscribeResource;
use App\Http\Requests\ProviderSubscriptionRequest;
use App\Traits\NotificationTrait;
use App\Services\PlanDowngradeService;
use App\Services\PlanUpgradeService;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    use NotificationTrait;

    protected $planDowngradeService;
    protected $planUpgradeService;
    protected $prorationService;

    public function __construct(
        PlanDowngradeService $planDowngradeService, 
        PlanUpgradeService $planUpgradeService,
        \App\Services\PlanProrationService $prorationService
    )
    {
        $this->planDowngradeService = $planDowngradeService;
        $this->planUpgradeService = $planUpgradeService;
        $this->prorationService = $prorationService;
    }

    /**
     * Provider subscription - Main orchestration method
     * 
     * @param ProviderSubscriptionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function providerSubscribe(ProviderSubscriptionRequest $request)
    {
        date_default_timezone_set(getTimeZone());

        // 1. Prepare subscription data
        $subscriptionData = $this->prepareSubscriptionData($request);
        
        // 2. Apply proration if applicable
        $subscriptionData = $this->applyProration($subscriptionData);
        
        // 3. Handle existing subscription
        $subscriptionData = $this->handleExistingSubscription($subscriptionData, $request);
        
        // 4. Create subscription record
        $subscription = $this->createSubscription($subscriptionData);
        
        // 5. Create subscription transaction
        $payment = $this->createSubscriptionTransaction($subscription, $request);
        
        // 6. Activate subscription if paid
        $message = $this->activateSubscriptionIfPaid($subscription, $payment, $subscriptionData['user']);
        
        // 7. Handle plan upgrade or downgrade
        $message = $this->handlePlanUpgradeOrDowngrade($subscription, $subscriptionData['user_id'], $message);
        
        // 8. Send notification
        $this->sendSubscriptionNotification($subscription);
        
        // 9. Build and return response
        return $this->buildSubscriptionResponse($subscription);
    }

    /**
     * Prepare subscription data from request
     * 
     * @param ProviderSubscriptionRequest $request
     * @return array
     */
    private function prepareSubscriptionData(ProviderSubscriptionRequest $request): array
    {
        $userId = $request->user_id ?? auth()->id();
        $user = User::findOrFail($userId);
        
        $data = $request->all();
        $data['user_id'] = $userId;
        $data['status'] = config('constant.SUBSCRIPTION_STATUS.PENDING');
        $data['start_at'] = date('Y-m-d H:i:s');
        
        // Load plan details
        if (isset($data['plan_id'])) {
            $plan = \App\Models\Plans::find($data['plan_id']);
            if ($plan) {
                $data['description'] = $data['description'] ?? $plan->description;
                $data['plan_type'] = $data['plan_type'] ?? $plan->plan_type;
            }
        }
        
        return [
            'data' => $data,
            'user_id' => $userId,
            'user' => $user,
            'original_amount' => $data['amount']
        ];
    }

    /**
     * Apply proration to subscription data
     * 
     * @param array $subscriptionData
     * @return array
     */
    private function applyProration(array $subscriptionData): array
    {
        $data = $subscriptionData['data'];
        $userId = $subscriptionData['user_id'];
        
        // Calculate proration
        $prorationData = $this->prorationService->calculateProration($userId, $data['plan_id']);
        
        // Apply prorated price if applicable
        if ($prorationData['has_proration']) {
            $data['amount'] = $prorationData['original_price'];
            
            Log::info('Proration applied', [
                'user_id' => $userId,
                'original_amount' => $subscriptionData['original_amount'],
                'prorated_amount' => $data['amount'],
                'credit_applied' => $prorationData['credit_applied']
            ]);
        }
        
        // Build other_detail JSON
        $data['other_detail'] = $this->prorationService->buildOtherDetail($prorationData);
        
        $subscriptionData['data'] = $data;
        $subscriptionData['proration_data'] = $prorationData;
        
        return $subscriptionData;
    }

    /**
     * Handle existing subscription (deactivate if different plan)
     * 
     * @param array $subscriptionData
     * @param ProviderSubscriptionRequest $request
     * @return array
     */
    private function handleExistingSubscription(array $subscriptionData, ProviderSubscriptionRequest $request): array
    {
        $data = $subscriptionData['data'];
        $userId = $subscriptionData['user_id'];
        
        $existingPlan = get_user_active_plan($userId);
        $activePlanLeftDays = 0;
        
        if ($existingPlan) {
            $activePlanLeftDays = check_days_left_plan($existingPlan, $data);
            
            // Deactivate existing plan if different identifier
            if ($request->identifier != $existingPlan->identifier) {
                $existingPlan->update([
                    'status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE')
                ]);
            }
        }
        
        // Calculate end date
        $data['end_at'] = get_plan_expiration_date(
            $data['start_at'],
            $data['type'],
            $activePlanLeftDays,
            $data['duration']
        );
        
        $subscriptionData['data'] = $data;
        $subscriptionData['existing_plan'] = $existingPlan;
        
        return $subscriptionData;
    }

    /**
     * Create subscription record
     * 
     * @param array $subscriptionData
     * @return ProviderSubscription
     */
    private function createSubscription(array $subscriptionData): ProviderSubscription
    {
        return ProviderSubscription::create($subscriptionData['data']);
    }

    /**
     * Create subscription transaction
     * 
     * @param ProviderSubscription $subscription
     * @param ProviderSubscriptionRequest $request
     * @return SubscriptionTransaction
     */
    private function createSubscriptionTransaction(
        ProviderSubscription $subscription, 
        ProviderSubscriptionRequest $request
    ): SubscriptionTransaction
    {
        $paymentData = [
            'subscription_plan_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'amount' => $subscription->amount,
            'payment_status' => $request->payment_status,
            'payment_type' => $request->payment_type
        ];
        
        return SubscriptionTransaction::create($paymentData);
    }

    /**
     * Activate subscription if payment is completed
     * 
     * @param ProviderSubscription $subscription
     * @param SubscriptionTransaction $payment
     * @param User $user
     * @return string
     */
    private function activateSubscriptionIfPaid(
        ProviderSubscription $subscription, 
        SubscriptionTransaction $payment,
        User $user
    ): string
    {
        $message = __('messages.save_form', ['form' => __('messages.subscription')]);
        
        if ($payment->payment_status === config('constant.PAYMENT_STATUS.PAID')) {
            // Update subscription status
            $subscription->status = config('constant.SUBSCRIPTION_STATUS.ACTIVE');
            $subscription->payment_id = $payment->id;
            $subscription->save();
            
            // Update user subscription status
            $user->is_subscribe = config('constant.USER_STATUS.ACTIVE');
            $user->save();
            
            $message = __('messages.payment_completed');
            
            Log::info('=== SUBSCRIPTION PAYMENT COMPLETED ===', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan_type' => $subscription->plan_type,
                'plan_limitation' => $subscription->plan_limitation
            ]);
            
            // Enforce plan limits for this provider after subscription activation
            $this->enforcePlanLimitsForProvider($user->id, $subscription);
        }
        
        return $message;
    }
    
    /**
     * Enforce plan limits for a specific provider after plan purchase
     * 
     * @param int $providerId
     * @param ProviderSubscription $subscription
     * @return void
     */
    private function enforcePlanLimitsForProvider(int $providerId, ProviderSubscription $subscription): void
    {
        $planLimitation = is_array($subscription->plan_limitation) 
            ? $subscription->plan_limitation 
            : json_decode($subscription->plan_limitation, true);
        
        if (!$planLimitation) {
            return; // Skip if no plan limitation data
        }
        
        Log::info('Enforcing plan limits for provider', [
            'provider_id' => $providerId,
            'subscription_id' => $subscription->id,
            'plan_limitation' => $planLimitation
        ]);
        
        // 1. Enforce SERVICE limit
        if (isset($planLimitation['service'])) {
            $serviceData = $planLimitation['service'];
            $isChecked = $serviceData['is_checked'] ?? 'off';
            $limit = isset($serviceData['limit']) ? (int)$serviceData['limit'] : 0;
            
            // Get all services for this provider
            $services = \App\Models\Service::where('provider_id', $providerId)
                ->orderBy('id', 'asc')
                ->get();
            
            if ($isChecked === 'off' || ($isChecked === 'on' && $limit == 0)) {
                // Feature disabled or limit is 0 - deactivate ALL services
                foreach ($services as $service) {
                    $service->update(['status' => 0]);
                }
                Log::info('Deactivated all services (limit disabled or 0)', ['provider_id' => $providerId]);
            } elseif ($isChecked === 'on' && $limit > 0) {
                // Limit is set - keep first N active, deactivate rest
                foreach ($services as $index => $service) {
                    if ($index < $limit) {
                        $service->update(['status' => 1]);
                    } else {
                        $service->update(['status' => 0]);
                    }
                }
                Log::info('Enforced service limit', [
                    'provider_id' => $providerId,
                    'limit' => $limit,
                    'total_services' => $services->count()
                ]);
            }
        }
        
        // 2. Enforce HANDYMAN limit
        if (isset($planLimitation['handyman'])) {
            $handymanData = $planLimitation['handyman'];
            $isChecked = $handymanData['is_checked'] ?? 'off';
            $limit = isset($handymanData['limit']) ? (int)$handymanData['limit'] : 0;
            
            // Get all handymen for this provider
            $handymen = \App\Models\User::where('user_type', 'handyman')
                ->where('provider_id', $providerId)
                ->orderBy('id', 'asc')
                ->get();
            
            if ($isChecked === 'off' || ($isChecked === 'on' && $limit == 0)) {
                // Feature disabled or limit is 0 - deactivate ALL handymen
                foreach ($handymen as $handyman) {
                    $handyman->update(['status' => 0]);
                }
                Log::info('Deactivated all handymen (limit disabled or 0)', ['provider_id' => $providerId]);
            } elseif ($isChecked === 'on' && $limit > 0) {
                // Limit is set - keep first N active, deactivate rest
                foreach ($handymen as $index => $handyman) {
                    if ($index < $limit) {
                        $handyman->update(['status' => 1]);
                    } else {
                        $handyman->update(['status' => 0]);
                    }
                }
                Log::info('Enforced handyman limit', [
                    'provider_id' => $providerId,
                    'limit' => $limit,
                    'total_handymen' => $handymen->count()
                ]);
            }
        }
        
        // 3. Enforce FEATURED SERVICE limit
        if (isset($planLimitation['featured_service'])) {
            $featuredData = $planLimitation['featured_service'];
            $isChecked = $featuredData['is_checked'] ?? 'off';
            $limit = isset($featuredData['limit']) ? (int)$featuredData['limit'] : 0;
            
            // Get all featured services for this provider
            $featuredServices = \App\Models\Service::where('provider_id', $providerId)
                ->where('is_featured', 1)
                ->orderBy('id', 'asc')
                ->get();
            
            if ($isChecked === 'off' || ($isChecked === 'on' && $limit == 0)) {
                // Feature disabled or limit is 0 - remove featured status from ALL services
                foreach ($featuredServices as $service) {
                    $service->update(['is_featured' => 0]);
                }
                Log::info('Removed all featured services (limit disabled or 0)', ['provider_id' => $providerId]);
            } elseif ($isChecked === 'on' && $limit > 0) {
                // Limit is set - keep first N featured, remove featured status from rest
                foreach ($featuredServices as $index => $service) {
                    if ($index >= $limit) {
                        $service->update(['is_featured' => 0]);
                    }
                }
                Log::info('Enforced featured service limit', [
                    'provider_id' => $providerId,
                    'limit' => $limit,
                    'total_featured' => $featuredServices->count()
                ]);
            }
        }
    }

    /**
     * Handle plan upgrade or downgrade logic
     * 
     * @param ProviderSubscription $subscription
     * @param int $userId
     * @param string $defaultMessage
     * @return string
     */
    private function handlePlanUpgradeOrDowngrade(
        ProviderSubscription $subscription, 
        int $userId, 
        string $defaultMessage
    ): string
    {
        // Only process if subscription is active
        if ($subscription->status !== config('constant.SUBSCRIPTION_STATUS.ACTIVE')) {
            return $defaultMessage;
        }
        
        $message = $defaultMessage;
        
        // Handle plan upgrade
        Log::info('Calling plan upgrade service', [
            'user_id' => $userId,
            'subscription_id' => $subscription->id
        ]);
        
        $upgradeResult = $this->planUpgradeService->handlePlanUpgrade($userId, $subscription->id);
        
        Log::info('Plan upgrade service response', [
            'user_id' => $userId,
            'upgrade_result' => $upgradeResult
        ]);
        
        if (isset($upgradeResult['is_upgrade']) && $upgradeResult['is_upgrade']) {
            $message = __('messages.plan_upgraded_successfully', ['plan' => $subscription->title]);
            
            Log::info('Plan upgrade detected and resources activated', [
                'user_id' => $userId,
                'subscription_id' => $subscription->id,
                'upgrade_result' => $upgradeResult
            ]);
        } elseif (isset($upgradeResult['is_first_subscription']) && $upgradeResult['is_first_subscription']) {
            // First subscription - no upgrade or downgrade needed
            $message = __('messages.plan_upgraded_successfully', ['plan' => $subscription->title]);
            
            Log::info('First subscription assigned', [
                'user_id' => $userId,
                'subscription_id' => $subscription->id
            ]);
        } else {
            // Handle plan downgrade only if not an upgrade or first subscription
            $downgradeResult = $this->planDowngradeService->handlePlanDowngrade($userId, $subscription->id);
            
            Log::info('Plan downgrade service response', [
                'user_id' => $userId,
                'downgrade_result' => $downgradeResult
            ]);
            
            if (isset($downgradeResult['is_downgrade']) && $downgradeResult['is_downgrade']) {
                $message = __('messages.plan_downgrade', ['plan' => $subscription->title]);
                
                Log::info('Plan downgrade detected and handled', [
                    'user_id' => $userId,
                    'subscription_id' => $subscription->id,
                    'downgrade_result' => $downgradeResult
                ]);
            }
        }
        
        return $message;
    }

    /**
     * Send subscription notification
     * 
     * @param ProviderSubscription $subscription
     * @return void
     */
    private function sendSubscriptionNotification(ProviderSubscription $subscription): void
    {
        $activityData = [
            'activity_type' => 'subscription_add',
            'subscription_data' => $subscription,
        ];
        
        $this->sendNotification($activityData);
    }

    /**
     * Build subscription response
     * 
     * @param ProviderSubscription $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    private function buildSubscriptionResponse(ProviderSubscription $subscription): \Illuminate\Http\JsonResponse
    {
        $resource = new ProviderSubscribeResource($subscription);
        
        $response = [
            'data' => $resource,
        ];
        
        return comman_custom_response($response);
    }

    public function cancelSubscription(Request $request){
        $user_id = $request->user_id ? $request->user_id : auth()->id();
        $plan_id  = $request->id;
        $provider_subscription = ProviderSubscription::where('id', $plan_id )->where('user_id',$user_id)->first();
        $user = User::where('id', $user_id)->first();
        if($provider_subscription){
            $provider_subscription->status = config('constant.SUBSCRIPTION_STATUS.CANCELLED');
            $provider_subscription->save();
            // $user->is_subscribe = 0; //  This code comment after change follow of is_subscribed 0 when plan is expire not cancel time on 10-3-26
            $user->save();
            $message = __('messages.cancelled_plan',['plan'=> $provider_subscription->title]);
            
            // Send cancellation notification
            $this->sendCancellationNotification($provider_subscription, $user);
        }
        return comman_message_response($message);
    }

    /**
     * Send subscription cancellation notification
     * 
     * @param ProviderSubscription $subscription
     * @param User $user
     * @return void
     */
    private function sendCancellationNotification(ProviderSubscription $subscription, User $user): void
    {
        try {
            $activityData = [
                'activity_type' => 'subscription_cancelled',
                'user_id' => $user->id,
                'provider_name' => $user->display_name ?? $user->username ?? 'Provider',
                'plan_name' => $subscription->title ?? 'Plan',
                'cancellation_date' => date('d-m-Y'),
            ];
            
            $this->sendNotification($activityData);
        } catch (\Throwable $e) {
            Log::error('Subscription cancellation notification failed: ' . $e->getMessage());
        }
    }

    public function getHistory(Request $request){
        $user_id = auth()->id();
        $subscription_history = ProviderSubscription::where('user_id',$user_id);
        $per_page = config('constant.PER_PAGE_LIMIT');

        $orderBy = $request->orderby ? $request->orderby: 'asc';

        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page === 'all' ){
                $per_page = $subscription_history->count();
            }
        }

        $subscription_history = $subscription_history->orderBy('id',$orderBy)->paginate($per_page);
        $items = ProviderSubscribeResource::collection($subscription_history);

        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
        ];

        return comman_custom_response($response);

    }

    /**
     * Download subscription invoice PDF for mobile app
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function downloadSubscriptionInvoice(Request $request)
    {
        try {
            $subscriptionId = $request->subscription_id;
            
            if (!$subscriptionId) {
                return comman_message_response(__('messages.subscription_id_required'), 400);
            }
            
            $subscription = ProviderSubscription::with(['provider', 'plan', 'payment'])->find($subscriptionId);
            
            if (!$subscription) {
                return comman_message_response(__('messages.subscription_not_found'), 404);
            }

            // Security check - provider can only download their own invoice
            $auth_user = auth()->user();
            if ($auth_user->user_type === 'provider' && $subscription->user_id !== $auth_user->id) {
                \Log::warning('Unauthorized API invoice access attempt', [
                    'user_id' => $auth_user->id,
                    'subscription_id' => $subscriptionId,
                    'subscription_owner' => $subscription->user_id
                ]);
                return comman_message_response(__('messages.unauthorized_access'), 403);
            }

            // Get current locale (already set by LanguageTranslator middleware)
            $locale = app()->getLocale();
            
            // Log the language being used
            \Log::info('API Invoice Download - Language Detection', [
                'current_locale' => $locale,
                'language_header' => $request->header('language-code'),
                'user_language' => auth()->check() ? auth()->user()->language_option : null,
                'subscription_id' => $subscriptionId
            ]);
            
            // Force English for Hindi to avoid rendering issues with DejaVu Sans font
            // Hindi characters would show as boxes in PDF
            if ($locale === 'hi') {
                \Log::info('API Invoice - Hindi detected, forcing English for PDF compatibility');
                app()->setLocale('en');
            }
            
            \Log::info('API Invoice - Final locale for PDF', [
                'locale' => app()->getLocale()
            ]);
            
            // Generate PDF
            $pdf = \PDF::loadView('provider.subscription-invoice-pdf', [
                'subscription' => $subscription
            ]);

            // Log successful download
            \Log::info('API Invoice downloaded', [
                'user_id' => $auth_user->id,
                'subscription_id' => $subscriptionId,
                'user_type' => $auth_user->user_type
            ]);

            // Return PDF for download
            return $pdf->download('subscription_invoice_' . $subscription->id . '.pdf');

        } catch (\Exception $e) {
            \Log::error('API Invoice download error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return comman_message_response(__('messages.error_generating_invoice'), 500);
        }
    }

    // public function providerSubscriptionDetail($id){
    //     // $user_id = auth()->id();
    //     $subscription = ProviderSubscription::where('user_id', $id)->get();

    //     return($subscription);
    // }
}

