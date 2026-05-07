<?php

namespace App\Services;

use App\Models\Plans;
use App\Models\PlanLimit;

class PlanValidatorService
{
    /**
     * Validate new plan limits based on amount compared to existing plans
     * 
     * @param float $newPlanAmount
     * @param array $newPlanLimitation
     * @param int|null $excludePlanId (for updates, exclude current plan from comparison)
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validatePlanLimits($newPlanAmount, $newPlanLimitation, $excludePlanId = null)
    {
        $errors = [];

        // Get reference plans (Basic and Premium)
        $basicPlan = $this->getReferencePlan('basic', $excludePlanId);
        $premiumPlan = $this->getReferencePlan('premium', $excludePlanId);

        // Extract new plan limits
        $newLimits = $this->extractLimits($newPlanLimitation);

        // CASE 1: New plan amount > Premium Plan amount ($100)
        if ($basicPlan && $premiumPlan && $newPlanAmount > $premiumPlan->amount) {
            $premiumLimits = $this->getPlanLimits($premiumPlan);
            $errors = array_merge($errors, $this->validateHigherThanPremium($newLimits, $premiumLimits));
        }
        // CASE 2: New plan amount < Premium Plan amount ($100)
        elseif ($premiumPlan && $newPlanAmount < $premiumPlan->amount) {
            $premiumLimits = $this->getPlanLimits($premiumPlan);
            $errors = array_merge($errors, $this->validateLowerThanPremium($newLimits, $premiumLimits));

            // Additional check: if new plan amount > Basic Plan amount ($10)
            if ($basicPlan && $newPlanAmount > $basicPlan->amount) {
                $basicLimits = $this->getPlanLimits($basicPlan);
                $errors = array_merge($errors, $this->validateHigherThanBasic($newLimits, $basicLimits));
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get reference plan by identifier
     * 
     * @param string $identifier
     * @param int|null $excludePlanId
     * @return Plans|null
     */
    private function getReferencePlan($identifier, $excludePlanId = null)
    {
        $query = Plans::where('identifier', $identifier);
        
        if ($excludePlanId) {
            $query->where('id', '!=', $excludePlanId);
        }

        return $query->first();
    }

    /**
     * Extract limits from plan limitation array
     * 
     * @param array|null $planLimitation
     * @return array
     */
    private function extractLimits($planLimitation)
    {
        $limits = [
            'service' => null,
            'featured_service' => null,
            'handyman' => null
        ];

        if (!is_array($planLimitation)) {
            return $limits;
        }

        foreach ($limits as $key => $value) {
            if (isset($planLimitation[$key])) {
                $limitData = $planLimitation[$key];
                
                // Check if limit is enabled
                if (isset($limitData['is_checked']) && $limitData['is_checked'] === 'on') {
                    $limit = $limitData['limit'] ?? null;
                    
                    // Convert to integer if not null/empty
                    if ($limit !== null && $limit !== '') {
                        $limits[$key] = (int)$limit;
                    }
                }
            }
        }

        return $limits;
    }

    /**
     * Get plan limits from PlanLimit table
     * 
     * @param Plans $plan
     * @return array
     */
    private function getPlanLimits($plan)
    {
        $planLimit = PlanLimit::where('plan_id', $plan->id)->first();
        
        if (!$planLimit) {
            return [
                'service' => null,
                'featured_service' => null,
                'handyman' => null
            ];
        }

        return $this->extractLimits($planLimit->plan_limitation);
    }

    /**
     * CASE 1: Validate new plan amount > Premium Plan amount
     * All limits MUST BE > premium limits
     * 
     * @param array $newLimits
     * @param array $premiumLimits
     * @return array
     */
    private function validateHigherThanPremium($newLimits, $premiumLimits)
    {
        $errors = [];

        // Service limit must be > premium service limit
        if ($premiumLimits['service'] !== null) {
            if ($newLimits['service'] === null || $newLimits['service'] <= $premiumLimits['service']) {
                $errors[] = __('messages.plan_service_limit_must_exceed_premium', [
                    'premium_limit' => $premiumLimits['service'],
                    'new_limit' => $newLimits['service'] ?? 'not set'
                ]);
            }
        }

        // Featured service limit must be > premium featured service limit
        if ($premiumLimits['featured_service'] !== null) {
            if ($newLimits['featured_service'] === null || $newLimits['featured_service'] <= $premiumLimits['featured_service']) {
                $errors[] = __('messages.plan_featured_service_limit_must_exceed_premium', [
                    'premium_limit' => $premiumLimits['featured_service'],
                    'new_limit' => $newLimits['featured_service'] ?? 'not set'
                ]);
            }
        }

        // Handyman limit must be > premium handyman limit
        if ($premiumLimits['handyman'] !== null) {
            if ($newLimits['handyman'] === null || $newLimits['handyman'] <= $premiumLimits['handyman']) {
                $errors[] = __('messages.plan_handyman_limit_must_exceed_premium', [
                    'premium_limit' => $premiumLimits['handyman'],
                    'new_limit' => $newLimits['handyman'] ?? 'not set'
                ]);
            }
        }

        return $errors;
    }

    /**
     * CASE 2: Validate new plan amount < Premium Plan amount
     * All limits MUST BE < premium limits
     * 
     * @param array $newLimits
     * @param array $premiumLimits
     * @return array
     */
    private function validateLowerThanPremium($newLimits, $premiumLimits)
    {
        $errors = [];

        // Service limit must be < premium service limit
        if ($premiumLimits['service'] !== null) {
            if ($newLimits['service'] !== null && $newLimits['service'] >= $premiumLimits['service']) {
                $errors[] = __('messages.plan_service_limit_must_be_less_than_premium', [
                    'premium_limit' => $premiumLimits['service'],
                    'new_limit' => $newLimits['service']
                ]);
            }
        }

        // Featured service limit must be < premium featured service limit
        if ($premiumLimits['featured_service'] !== null) {
            if ($newLimits['featured_service'] !== null && $newLimits['featured_service'] >= $premiumLimits['featured_service']) {
                $errors[] = __('messages.plan_featured_service_limit_must_be_less_than_premium', [
                    'premium_limit' => $premiumLimits['featured_service'],
                    'new_limit' => $newLimits['featured_service']
                ]);
            }
        }

        // Handyman limit must be < premium handyman limit
        if ($premiumLimits['handyman'] !== null) {
            if ($newLimits['handyman'] !== null && $newLimits['handyman'] >= $premiumLimits['handyman']) {
                $errors[] = __('messages.plan_handyman_limit_must_be_less_than_premium', [
                    'premium_limit' => $premiumLimits['handyman'],
                    'new_limit' => $newLimits['handyman']
                ]);
            }
        }

        return $errors;
    }

    /**
     * CASE 2 (Additional): Validate new plan amount > Basic Plan amount
     * All limits MUST BE > basic limits
     * 
     * @param array $newLimits
     * @param array $basicLimits
     * @return array
     */
    private function validateHigherThanBasic($newLimits, $basicLimits)
    {
        $errors = [];

        // Service limit must be > basic service limit
        if ($basicLimits['service'] !== null) {
            if ($newLimits['service'] === null || $newLimits['service'] <= $basicLimits['service']) {
                $errors[] = __('messages.plan_service_limit_must_exceed_basic', [
                    'basic_limit' => $basicLimits['service'],
                    'new_limit' => $newLimits['service'] ?? 'not set'
                ]);
            }
        }

        // Featured service limit must be > basic featured service limit
        if ($basicLimits['featured_service'] !== null) {
            if ($newLimits['featured_service'] === null || $newLimits['featured_service'] <= $basicLimits['featured_service']) {
                $errors[] = __('messages.plan_featured_service_limit_must_exceed_basic', [
                    'basic_limit' => $basicLimits['featured_service'],
                    'new_limit' => $newLimits['featured_service'] ?? 'not set'
                ]);
            }
        }

        // Handyman limit must be > basic handyman limit
        if ($basicLimits['handyman'] !== null) {
            if ($newLimits['handyman'] === null || $newLimits['handyman'] <= $basicLimits['handyman']) {
                $errors[] = __('messages.plan_handyman_limit_must_exceed_basic', [
                    'basic_limit' => $basicLimits['handyman'],
                    'new_limit' => $newLimits['handyman'] ?? 'not set'
                ]);
            }
        }

        return $errors;
    }
}
