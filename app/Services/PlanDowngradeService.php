<?php

namespace App\Services;

use App\Models\ProviderSubscription;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanDowngradeService
{
    /**
     * Handle plan downgrade logic after subscription purchase
     *
     * @param int $userId
     * @param int $currentSubscriptionId
     * @return array
     */
    public function handlePlanDowngrade($userId, $currentSubscriptionId)
    {
        try {
            // 1. Fetch current active subscription
            $currentSubscription = getCurrentSubscription($userId, $currentSubscriptionId);
            
            if (!$currentSubscription) {
                return ['success' => false, 'message' => 'Current subscription not found'];
            }

            // 2. Fetch previous subscription (last record excluding current)
            $previousSubscription = getPreviousSubscription($userId, $currentSubscriptionId);
            
            if (!$previousSubscription) {
                // No previous subscription, this is first subscription - no downgrade
                return [
                    'success' => true, 
                    'message' => 'First subscription, no downgrade check needed', 
                    'is_downgrade' => false
                ];
            }

            // 3. Compare plan limitations and detect downgrade
            $downgradeDetection = $this->detectDowngrade(
                $previousSubscription->plan_limitation,
                $currentSubscription->plan_limitation
            );

            if (!$downgradeDetection['is_downgrade']) {
                return [
                    'success' => true, 
                    'message' => 'No downgrade detected',
                    'is_downgrade' => false
                ];
            }

            // 4. Handle service deactivation for downgraded plans
            $deactivationResult = $this->deactivateAllServices($userId);
            
            // 5. Handle featured service deactivation for downgraded plans
            $featuredServiceDeactivationResult = $this->deactivateAllFeaturedServices($userId);
            
            // 6. Handle handyman deactivation for downgraded plans
            $handymanDeactivationResult = $this->deactivateAllHandymen($userId);

            return [
                'success' => true,
                'is_downgrade' => true,
                'downgraded_limits' => $downgradeDetection['downgraded_limits'],
                'services_deactivated' => $deactivationResult['services_deactivated'],
                'featured_services_deactivated' => $featuredServiceDeactivationResult['featured_services_deactivated'],
                'handymen_deactivated' => $handymanDeactivationResult['handymen_deactivated'],
                'message' => 'Plan downgrade detected and all services, featured services and handymen deactivated'
            ];

        } catch (\Exception $e) {
            Log::error('Plan downgrade handling failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'subscription_id' => $currentSubscriptionId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to handle plan downgrade: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Detect if current plan is a downgrade from previous plan
     * Based on plan_limitation JSON comparison
     *
     * @param array|string|null $previousLimitation
     * @param array|string|null $currentLimitation
     * @return array
     */
    private function detectDowngrade($previousLimitation, $currentLimitation)
    {
        // Parse JSON if needed
        $previousLimits = parsePlanLimitation($previousLimitation);
        $currentLimits = parsePlanLimitation($currentLimitation);

        \Log::info('Downgrade detection - parsed limits', [
            'previous_limits' => $previousLimits,
            'current_limits' => $currentLimits
        ]);

        // If either is empty or null, no downgrade
        if (empty($previousLimits) || empty($currentLimits)) {
            \Log::info('Downgrade detection - empty limits, no downgrade');
            return ['is_downgrade' => false, 'downgraded_limits' => []];
        }

        $downgraded = [];
        $limitTypes = ['service', 'featured_service', 'handyman'];

        foreach ($limitTypes as $limitType) {
            // Check if both plans have this limit type
            if (!isset($previousLimits[$limitType]) || !isset($currentLimits[$limitType])) {
                \Log::info('Downgrade detection - limit type not in both plans', [
                    'limit_type' => $limitType,
                    'in_previous' => isset($previousLimits[$limitType]),
                    'in_current' => isset($currentLimits[$limitType])
                ]);
                continue;
            }

            $prevLimit = $previousLimits[$limitType];
            $currLimit = $currentLimits[$limitType];

            // Check if enabled - handle both "on" and 1
            $prevChecked = isset($prevLimit['is_checked']) && in_array($prevLimit['is_checked'], ['on', 1, '1', true], true);
            $currChecked = isset($currLimit['is_checked']) && in_array($currLimit['is_checked'], ['on', 1, '1', true], true);

            \Log::info('Downgrade detection - checking limit type', [
                'limit_type' => $limitType,
                'prev_checked' => $prevChecked,
                'curr_checked' => $currChecked,
                'prev_is_checked_value' => $prevLimit['is_checked'] ?? 'not set',
                'curr_is_checked_value' => $currLimit['is_checked'] ?? 'not set'
            ]);

            if (!$prevChecked || !$currChecked) {
                continue;
            }

            // Get numeric limits
            $prevLimitValue = getPlanLimitValue($prevLimit['limit'] ?? null);
            $currLimitValue = getPlanLimitValue($currLimit['limit'] ?? null);

            \Log::info('Downgrade detection - comparing limits', [
                'limit_type' => $limitType,
                'prev_limit_value' => $prevLimitValue,
                'curr_limit_value' => $currLimitValue,
                'is_downgrade' => $currLimitValue < $prevLimitValue
            ]);

            // Detect downgrade: current limit is lower than previous
            if ($currLimitValue < $prevLimitValue) {
                $downgraded[] = [
                    'type' => $limitType,
                    'previous_limit' => $prevLimitValue === PHP_INT_MAX ? 'unlimited' : $prevLimitValue,
                    'current_limit' => $currLimitValue === PHP_INT_MAX ? 'unlimited' : $currLimitValue
                ];
            }
        }

        \Log::info('Downgrade detection - final result', [
            'is_downgrade' => !empty($downgraded),
            'downgraded_limits' => $downgraded
        ]);

        return [
            'is_downgrade' => !empty($downgraded),
            'downgraded_limits' => $downgraded
        ];
    }

    /**
     * Deactivate ALL active services for the provider when downgrade is detected
     *
     * @param int $userId
     * @return array
     */
    private function deactivateAllServices($userId)
    {
        DB::beginTransaction();
        try {
            // Get count of active services before deactivation
            $activeServicesCount = Service::where('provider_id', $userId)
                ->where('status', config('constant.SERVICE_STATUS.ACTIVE'))
                ->count();

            if ($activeServicesCount === 0) {
                DB::commit();
                return [
                    'services_deactivated' => 0,
                    'message' => 'No active services to deactivate'
                ];
            }

            // Deactivate all active services and remove featured flag
            Service::where('provider_id', $userId)
                ->where('status', config('constant.SERVICE_STATUS.ACTIVE'))
                ->update([
                    'status' => config('constant.SERVICE_STATUS.INACTIVE'),
                    'is_featured' => config('constant.SERVICE_STATUS.INACTIVE'),
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('Plan downgrade: All services deactivated', [
                'user_id' => $userId,
                'services_deactivated' => $activeServicesCount
            ]);

            return [
                'services_deactivated' => $activeServicesCount,
                'message' => "Deactivated all {$activeServicesCount} services due to plan downgrade"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Service deactivation failed: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Remove featured flag from ALL services for the provider when downgrade is detected
     * NOTE: Set is_featured = 0 for ALL services, not just featured ones
     * Services remain active but are no longer featured
     *
     * @param int $userId
     * @return array
     */
    private function deactivateAllFeaturedServices($userId)
    {
        DB::beginTransaction();
        try {
            // Get count of featured services before removal
            $featuredServicesCount = Service::where('provider_id', $userId)
                ->where('is_featured', config('constant.SERVICE_STATUS.ACTIVE'))
                ->count();

            // Remove featured flag from ALL services (set is_featured = 0 for all)
            $allServicesUpdated = Service::where('provider_id', $userId)
                ->update([
                    'is_featured' => config('constant.SERVICE_STATUS.INACTIVE'),
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('Plan downgrade: All services featured flag removed', [
                'user_id' => $userId,
                'featured_services_count_before' => $featuredServicesCount,
                'all_services_updated' => $allServicesUpdated
            ]);

            return [
                'featured_services_deactivated' => $featuredServicesCount,
                'message' => "Removed featured flag from all {$allServicesUpdated} services due to plan downgrade"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Featured service flag removal failed: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Deactivate ALL active handymen for the provider when downgrade is detected
     *
     * @param int $userId
     * @return array
     */
    private function deactivateAllHandymen($userId)
    {
        DB::beginTransaction();
        try {
            // Get count of active handymen before deactivation
            $activeHandymenCount = User::where('provider_id', $userId)
                ->where('status', config('constant.USER_STATUS.ACTIVE'))
                ->count();

            if ($activeHandymenCount === 0) {
                DB::commit();
                return [
                    'handymen_deactivated' => 0,
                    'message' => 'No active handymen to deactivate'
                ];
            }

            // Deactivate all active handymen
            User::where('provider_id', $userId)
                ->where('status', config('constant.USER_STATUS.ACTIVE'))
                ->update([
                    'status' => config('constant.USER_STATUS.INACTIVE'),
                    'updated_at' => now()
                ]);

            DB::commit();

            Log::info('Plan downgrade: All handymen deactivated', [
                'user_id' => $userId,
                'handymen_deactivated' => $activeHandymenCount
            ]);

            return [
                'handymen_deactivated' => $activeHandymenCount,
                'message' => "Deactivated all {$activeHandymenCount} handymen due to plan downgrade"
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Handyman deactivation failed: ' . $e->getMessage(), [
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * Check if provider's active services exceed new plan limit
     *
     * @param int $userId
     * @param array $planLimitation
     * @return bool
     */
    public function servicesExceedLimit($userId, $planLimitation)
    {
        $limits = parsePlanLimitation($planLimitation);
        
        // Check service limit
        if (isset($limits['service']) && $limits['service']['is_checked'] === config('constant.PLAN_LIMIT.CHECKED')) {
            $serviceLimit = getPlanLimitValue($limits['service']['limit'] ?? null);
            
            if ($serviceLimit !== config('constant.PLAN_DEFAULTS.UNLIMITED_LIMIT')) {
                $activeServicesCount = Service::where('provider_id', $userId)
                    ->where('status', config('constant.SERVICE_STATUS.ACTIVE'))
                    ->count();
                
                if ($activeServicesCount > $serviceLimit) {
                    return true;
                }
            }
        }

        return false;
    }
}
