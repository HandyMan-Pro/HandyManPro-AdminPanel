<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plans;
use App\Models\ProviderSubscription;
use App\Http\Resources\API\PlanResource;
use App\Services\PlanProrationService;
use App\Http\Resources\API\ProviderSubscribeResource;


class PlanController extends Controller
{
    protected $prorationService;

    public function __construct(PlanProrationService $prorationService)
    {
        $this->prorationService = $prorationService;
    }

    public function planList(Request $request)
    {
        $plans = Plans::where('status',1);
        $get_user_free_plan = ProviderSubscription::where('user_id',auth()->id())->first();
        if(!empty( $get_user_free_plan)){
            $plans =  $plans->whereNotIn('identifier',['free']);
        }
        // Prefer ACTIVE subscription first, then fallback to CANCELLED.
        $get_current_plan = ProviderSubscription::where('user_id', auth()->id())
            ->whereIn('status', ['active', 'cancelled'])
            ->orderBy('id', 'desc')
            ->first();
        if (empty($get_current_plan)) {
            $get_current_plan = ProviderSubscription::where('user_id', auth()->id())
                ->where('status', 'cancelled')
                ->orderBy('id', 'desc')
                ->first();
        }
       
        // Treat cancelled as inactive in this flow.
        $isCurrentPlanInactive = !empty($get_current_plan) && $get_current_plan->status === 'cancelled';
        
        // Get all active plans to check if current plan amount is highest
        $allActivePlans = $plans->get();
        $maxPlanAmount = $allActivePlans->max('amount');
        
        // Always prefer plan master amount for comparison/filtering.
        // If plan row is deleted, use subscription other_detail.original_price.
        // Final fallback is subscription amount.
        $currentPlanAmount = 0;
        if (!empty($get_current_plan) && !empty($get_current_plan->plan)) {
            $currentPlanAmount = (float) ($get_current_plan->plan->amount ?? 0);
        }
        if ($currentPlanAmount <= 0 && !empty($get_current_plan)) {
            $otherDetail = $get_current_plan->other_detail;
            if (is_string($otherDetail)) {
                $otherDetail = json_decode($otherDetail, true);
            }
            if (is_array($otherDetail) && isset($otherDetail['original_price'])) {
                $currentPlanAmount = (float) $otherDetail['original_price'];
            }
        }
        if ($currentPlanAmount <= 0 && !empty($get_current_plan)) {
            $currentPlanAmount = (float) ($get_current_plan->amount ?? 0);
        }

        // Check if current plan amount is highest and current plan is inactive
        $isCurrentPlanHighestAndInactive = false;
        if (!empty($get_current_plan) && $isCurrentPlanInactive) {
            if ($currentPlanAmount >= $maxPlanAmount) {
                $isCurrentPlanHighestAndInactive = true;
            }
        }
        
        // If current plan is highest and inactive, set current_plan to null
        if ($isCurrentPlanHighestAndInactive) {
            $get_current_plan = null;
        }
        
        if(!empty( $get_current_plan)){
            if (!empty($get_current_plan->plan_id)) {
                $plans =  $plans->whereNotIn('id', [$get_current_plan->plan_id]);
            }
            // Filter plans with amount greater than current plan
            if ($currentPlanAmount > 0) {
                $plans = $plans->where('amount', '>', $currentPlanAmount);
            }
        }
        $orderBy = $request->orderby ? $request->orderby: 'asc';
       
        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page === 'all' ){
                $per_page = $plans->count();
            }
        }   
        
        $plans = $plans->orderBy('amount',$orderBy)->paginate($per_page);
        
        // Apply proration to amount field only if current subscription is ACTIVE
        // Do NOT apply proration for cancelled subscriptions - show original price
        $shouldApplyProration = !empty($get_current_plan) && $get_current_plan->status === 'active';
        
        $plansWithProration = $plans->getCollection()->map(function ($plan) use ($shouldApplyProration) {
            // Store original amount before proration
            $plan->original_amount = $plan->amount;
            
            // Only apply proration if user has an ACTIVE subscription
            if ($shouldApplyProration) {
                $prorationData = $this->prorationService->calculateProration(auth()->id(), $plan->id);
                \Log::info(" prorationData ".json_encode($prorationData));
                
                // Only modify the amount field with prorated price
                if ($prorationData['has_proration']) {
                    $plan->amount = $prorationData['final_price'];
                }
            }
            // If subscription is cancelled or no subscription, show original plan price
            
            return $plan;
        });
        
        $plans->setCollection($plansWithProration);
        if(isset($get_current_plan) && !empty($get_current_plan)){
           $currentPlan = new ProviderSubscribeResource($get_current_plan);
        }else{
            $currentPlan = null;
        }
        $items = PlanResource::collection($plans);
       
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
            'current_plan'=>$currentPlan
        ];
        
        return comman_custom_response($response);
    }
}
