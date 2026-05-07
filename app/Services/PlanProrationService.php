<?php

namespace App\Services;

use App\Models\ProviderSubscription;
use App\Models\Plans;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PlanProrationService
{
    /**
     * Calculate proration details for plan upgrade
     * 
     * @param int $userId
     * @param int $newPlanId
     * @return array
     * @throws InvalidArgumentException
     */
    public function calculateProration(int $userId, int $newPlanId): array
    {
        // Validate input parameters
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID provided');
        }

        if ($newPlanId <= 0) {
            throw new InvalidArgumentException('Invalid plan ID provided');
        }

        try {
            // Get the latest ACTIVE subscription only (exclude cancelled subscriptions)
            // Cancelled subscriptions should not receive proration benefits
            $currentSubscription = ProviderSubscription::where('user_id', $userId)
                ->where('status', config('constant.SUBSCRIPTION_STATUS.ACTIVE'))
                ->orderBy('id', 'desc')
                ->first();

            // Get new plan details
            $newPlan = Plans::find($newPlanId);
            
            // Validate new plan exists
            if (!$newPlan) {
                Log::warning('Plan not found during proration calculation', [
                    'user_id' => $userId,
                    'plan_id' => $newPlanId
                ]);
                
                return [
                    'has_proration' => false,
                    'error' => 'Plan not found',
                    'purchase_type' => config('constant.SUBSCRIPTION_TYPE.NORMAL'),
                    'original_price' => 0,
                    'final_price' => 0,
                    'paid_amount' => 0,
                    'remaining_days' => config('constant.PLAN_DEFAULTS.NO_REMAINING_DAYS'),
                    'remaining_credit' => 0,
                    'new_plan' => null
                ];
            }

            // If no current subscription or subscription is expired, no proration
            if (!$currentSubscription || $this->isSubscriptionExpired($currentSubscription)) {
                return $this->buildNormalPurchaseResponse($newPlan);
            }

            // Validate subscription has required fields
            if (!$this->hasValidSubscriptionDates($currentSubscription)) {
                Log::warning('Subscription missing required date fields', [
                    'user_id' => $userId,
                    'subscription_id' => $currentSubscription->id ?? null
                ]);
                
                return $this->buildNormalPurchaseResponse($newPlan);
            }

            // Calculate total days of previous plan in current configured timezone
            $timezone = $this->getCurrentTimezone();
            $now = Carbon::now($timezone);
            Log::info('now', ['now' => $now]);
            $endDate = Carbon::parse($currentSubscription->end_at, $timezone);
            Log::info('endDate', ['endDate' => $endDate]);
            $startDate = Carbon::parse($currentSubscription->start_at, $timezone);
            Log::info('startDate', ['startDate' => $startDate]);
            $totalPlanDays = $startDate->diffInDays($endDate);
            Log::info('totalPlanDays', ['totalPlanDays' => $totalPlanDays]);
            // Prevent division by zero
            if ($totalPlanDays <= config('constant.PLAN_DEFAULTS.NO_REMAINING_DAYS')) {
                Log::warning('Invalid plan duration detected, defaulting to 1 day', [
                    'user_id' => $userId,
                    'subscription_id' => $currentSubscription->id,
                    'start_at' => $currentSubscription->start_at,
                    'end_at' => $currentSubscription->end_at
                ]);
                $totalPlanDays = config('constant.PLAN_DEFAULTS.MIN_PLAN_DAYS');
            }

            // Validate previous plan price
            $previousPlanPrice = $this->getValidAmount($currentSubscription->amount);

            // 24-hour billing periods from subscription start_at (not calendar midnight).
            // Period 1: [start, start+24h) → one day of plan value consumed.
            // Period 2 begins at start+24h exactly (e.g. purchase 6 Apr 18:12 → second period from 7 Apr 18:12).
            $elapsedSeconds = max(0, $now->getTimestamp() - $startDate->getTimestamp());
            $usedDaysRaw = (int) floor($elapsedSeconds / 86400) + 1;
            $usedDays = min($totalPlanDays, max(config('constant.PLAN_DEFAULTS.MIN_PLAN_DAYS'), $usedDaysRaw));

            // Remaining full periods (integer) for display / credit alignment.
            $remainingDays = max(config('constant.PLAN_DEFAULTS.NO_REMAINING_DAYS'), (int) ($totalPlanDays - $usedDays));

            // Calculate used amount and remaining credit.
            // per_day_price = plan_price / total_plan_days (kept as decimal)
            // used_amount = per_day_price × used_days, then apply custom rounding to final amount
            // Example: plan_price=100, days=365 → per_day=0.2740 → used_amount=0.2740×183=50.142 → rounded=50
            $perDayRaw = $totalPlanDays > 0 ? ($previousPlanPrice / $totalPlanDays) : 0.0;
            $usedAmountRaw = $perDayRaw * $usedDays;
            $usedAmount = $this->roundUsedAmount($usedAmountRaw);
            Log::info('perDayRaw', ['perDayRaw' => $perDayRaw]);
            Log::info('usedDays', ['usedDays' => $usedDays]);
            Log::info('usedAmountRaw', ['usedAmountRaw' => $usedAmountRaw]);
            Log::info('usedAmount (after rounding)', ['usedAmount' => $usedAmount]);
            $remainingValue = max(0, $previousPlanPrice - $usedAmount);
            Log::info('remainingValue', ['remainingValue' => $remainingValue]);
            // Calculate final price to pay
            $newPlanAmount = $this->getValidAmount($newPlan->amount);
            $finalPrice = $newPlanAmount - $remainingValue;
            
            // Ensure final price is not negative
            $finalPrice = max(0, $finalPrice);

            // Round to 2 decimal places
            $usedAmount = round($usedAmount, 2);
            $remainingValue = round($remainingValue, 2);
            $finalPrice = round($finalPrice, 2);
            Log::info('finalPrice', ['finalPrice' => $finalPrice]);
            return [
                'has_proration' => true,
                'purchase_type' => config('constant.SUBSCRIPTION_TYPE.UPGRADE'),
                'previous_subscription' => $currentSubscription,
                'previous_plan_name' => $currentSubscription->title ?? 'Unknown Plan',
                'previous_plan_price' => $previousPlanPrice,
                'original_price' => $newPlanAmount,
                'final_price' => $finalPrice,
                'paid_amount' => $finalPrice,
                'used_days' => $usedDays,
                'used_amount' => $usedAmount,
                'credit_applied' => $remainingValue,
                'remaining_credit' => $remainingValue,
                'remaining_days' => $remainingDays,
                'total_plan_days' => $totalPlanDays,
                'reason' => __('messages.plan_upgrade_prorated_adjustment'),
                'new_plan' => $newPlan
            ];

        } catch (\Exception $e) {
            Log::error('Error calculating proration', [
                'user_id' => $userId,
                'plan_id' => $newPlanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return safe default response on error
            return [
                'has_proration' => false,
                'error' => 'Error calculating proration',
                'purchase_type' => config('constant.SUBSCRIPTION_TYPE.NORMAL'),
                'original_price' => 0,
                'final_price' => 0,
                'paid_amount' => 0,
                'remaining_days' => config('constant.PLAN_DEFAULTS.NO_REMAINING_DAYS'),
                'remaining_credit' => 0,
                'new_plan' => null
            ];
        }
    }

    /**
     * Check if subscription is expired
     * 
     * @param ProviderSubscription|null $subscription
     * @return bool
     */
    private function isSubscriptionExpired(?ProviderSubscription $subscription): bool
    {
        if (!$subscription) {
            return true;
        }

        if (!$subscription->end_at) {
            return true;
        }

        try {
            $timezone = $this->getCurrentTimezone();
            $now = Carbon::now($timezone);
            $endDate = Carbon::parse($subscription->end_at, $timezone);
            
            return $now->greaterThan($endDate);
        } catch (\Exception $e) {
            Log::warning('Error parsing subscription end date', [
                'subscription_id' => $subscription->id ?? null,
                'end_at' => $subscription->end_at,
                'error' => $e->getMessage()
            ]);
            
            return true;
        }
    }

    /**
     * Validate subscription has required date fields
     * 
     * @param ProviderSubscription|null $subscription
     * @return bool
     */
    private function hasValidSubscriptionDates(?ProviderSubscription $subscription): bool
    {
        if (!$subscription) {
            return false;
        }

        return !empty($subscription->start_at) && !empty($subscription->end_at);
    }

    /**
     * Resolve current timezone from site setup.
     */
    private function getCurrentTimezone(): string
    {
        try {
            $siteSetupValue = Setting::where('type', 'site-setup')
                ->where('key', 'site-setup')
                ->value('value');

            if (!empty($siteSetupValue)) {
                $siteSetup = json_decode((string) $siteSetupValue, true);
                $timezone = $siteSetup['time_zone'] ?? null;
                if (!empty($timezone) && is_string($timezone)) {
                    return $timezone;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to resolve site timezone, fallback to app timezone', [
                'error' => $e->getMessage(),
            ]);
        }

        return (string) config('app.timezone', date_default_timezone_get() ?: 'UTC');
    }

    /**
     * Get valid amount, ensuring it's a positive number
     * 
     * @param mixed $amount
     * @return float
     */
    private function getValidAmount($amount): float
    {
        if (is_null($amount)) {
            return 0.0;
        }

        $amount = (float) $amount;
        
        return max(0, $amount);
    }

    /**
     * Round used amount per business rule:
     * - If decimal part is less than 0.50 → round down (floor to whole integer).
     * - If decimal part is 0.50 or greater → round up (ceil to next integer).
     *
     * Examples: 50.142 → 50, 50.50 → 51, 50.75 → 51
     * Plan example: 100 / 365 × 183 ≈ 50.142 → 50
     */
    private function roundUsedAmount(float $usedAmount): float
    {
        if ($usedAmount <= 0) {
            return 0.0;
        }

        $normalized = round($usedAmount, 4);
        $whole = floor($normalized);
        $fraction = $normalized - $whole;

        if ($fraction < 0.5) {
            return (float) $whole;
        }

        return (float) ($whole + 1.0);
    }

    /**
     * Build normal purchase response (no proration)
     * 
     * @param Plans|null $newPlan
     * @return array
     */
    private function buildNormalPurchaseResponse(?Plans $newPlan): array
    {
        $planAmount = $newPlan ? $this->getValidAmount($newPlan->amount) : 0;

        return [
            'has_proration' => false,
            'purchase_type' => config('constant.SUBSCRIPTION_TYPE.NORMAL'),
            'original_price' => $planAmount,
            'final_price' => $planAmount,
            'paid_amount' => $planAmount,
            'remaining_days' => config('constant.PLAN_DEFAULTS.NO_REMAINING_DAYS'),
            'remaining_credit' => 0,
            'new_plan' => $newPlan
        ];
    }

    /**
     * Build other_detail JSON for subscription record
     * 
     * @param array $prorationData
     * @return array|null
     */
    public function buildOtherDetail(array $prorationData): ?array
    {
        // Validate required keys exist
        if (!isset($prorationData['has_proration'])) {
            Log::warning('Missing has_proration key in proration data');
            return null;
        }

        // If no proration (expired or no previous subscription), return null
        // other_detail should only contain data when an upgrade happens during active subscription
        if (!$prorationData['has_proration']) {
            return null;
        }

        // Validate all required keys for proration data
        $requiredKeys = [
            'previous_plan_name',
            'previous_plan_price',
            'original_price',
            'paid_amount',
            'credit_applied',
            'remaining_days',
            'reason'
        ];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $prorationData)) {
                Log::warning('Missing required key in proration data', [
                    'missing_key' => $key,
                    'available_keys' => array_keys($prorationData)
                ]);
                return null;
            }
        }

        // Upgrade with proration - only return data when proration is active
        return [
            'purchase_type' => config('constant.SUBSCRIPTION_TYPE.UPGRADE'),
            'previous_plan' => $prorationData['previous_plan_name'],
            'previous_plan_price' => $this->getValidAmount($prorationData['previous_plan_price']),
            'original_price' => $this->getValidAmount($prorationData['original_price']),
            'paid_amount' => $this->getValidAmount($prorationData['paid_amount']),
            'credit_applied' => $this->getValidAmount($prorationData['credit_applied']),
            'remaining_days' => max(config('constant.PLAN_DEFAULTS.NO_REMAINING_DAYS'), (int) $prorationData['remaining_days']),
            'reason' => (string) $prorationData['reason']
        ];
    }

    /**
     * Get adjusted plan list with proration for a user
     * 
     * @param int $userId
     * @param \Illuminate\Database\Eloquent\Collection $plans
     * @return array
     * @throws InvalidArgumentException
     */
    public function getAdjustedPlanList(int $userId, $plans): array
    {
        // Validate user ID
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID provided');
        }

        // Validate plans collection
        if (!$plans || $plans->isEmpty()) {
            Log::info('Empty plans collection provided for proration calculation', [
                'user_id' => $userId
            ]);
            return [];
        }

        $adjustedPlans = [];

        foreach ($plans as $plan) {
            // Skip invalid plans
            if (!$plan || !isset($plan->id)) {
                Log::warning('Invalid plan object in collection', [
                    'user_id' => $userId
                ]);
                continue;
            }

            try {
                $prorationData = $this->calculateProration($userId, $plan->id);
                
                // Validate proration data structure
                if (!is_array($prorationData)) {
                    Log::warning('Invalid proration data returned', [
                        'user_id' => $userId,
                        'plan_id' => $plan->id
                    ]);
                    continue;
                }

                $planAmount = $this->getValidAmount($plan->amount ?? 0);
                $finalPrice = isset($prorationData['final_price']) 
                    ? $this->getValidAmount($prorationData['final_price']) 
                    : $planAmount;
                $hasProration = isset($prorationData['has_proration']) 
                    ? (bool) $prorationData['has_proration'] 
                    : false;

                $planData = [
                    'plan' => $plan,
                    'original_price' => $planAmount,
                    'price' => $finalPrice,
                    'has_proration' => $hasProration
                ];

                // Add proration details if applicable
                if ($hasProration) {
                    $planData['remaining_days'] = isset($prorationData['remaining_days']) 
                        ? max(0, (int) $prorationData['remaining_days']) 
                        : 0;
                    $planData['remaining_credit'] = isset($prorationData['remaining_credit']) 
                        ? $this->getValidAmount($prorationData['remaining_credit']) 
                        : 0;
                    $planData['upgrade_from_plan'] = $prorationData['previous_plan_name'] ?? 'Unknown Plan';
                    $planData['discount_reason'] = $prorationData['reason'] ?? __('messages.plan_upgrade_prorated_adjustment');
                }

                $adjustedPlans[] = $planData;

            } catch (\Exception $e) {
                Log::error('Error processing plan in adjusted list', [
                    'user_id' => $userId,
                    'plan_id' => $plan->id ?? null,
                    'error' => $e->getMessage()
                ]);
                
                // Add plan with no proration on error
                $adjustedPlans[] = [
                    'plan' => $plan,
                    'original_price' => $this->getValidAmount($plan->amount ?? 0),
                    'price' => $this->getValidAmount($plan->amount ?? 0),
                    'has_proration' => false
                ];
            }
        }

        return $adjustedPlans;
    }
}
