<?php

namespace App\Services;

use App\Models\ProviderSubscription;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanUpgradeService
{
    /**
     * Handle plan upgrade/change logic with intelligent limit comparison
     * 
     * Logic:
     * - If first subscription → keep resources active
     * - If new limits >= previous limits → keep resources active
     * - If new limits < previous limits → deactivate all resources
     * - If unlimited → limited → deactivate all resources
     *
     * @param int $userId
     * @param int $currentSubscriptionId
     * @return array
     */
    public function handlePlanUpgrade($userId, $currentSubscriptionId)
    {
        try {
            Log::info('=== PLAN UPGRADE HANDLER STARTED ===', [
                'provider_id' => $userId,
                'subscription_id' => $currentSubscriptionId
            ]);

            // Step 1: Fetch current active subscription
            $currentSubscription = getCurrentSubscription($userId, $currentSubscriptionId);
            
            if (!$currentSubscription) {
                
                return ['success' => false, 'message' => 'Current subscription not found'];
            }

           

            // Step 2: Fetch previous subscription
            $previousSubscription = getPreviousSubscription($userId, $currentSubscriptionId);
            
            // Step 3: If first subscription, keep all resources active
            if (!$previousSubscription) {
                
                return [
                    'success' => true,
                    'is_first_subscription' => true,
                    'should_deactivate' => false,
                    'message' => 'First subscription - resources remain active'
                ];
            }

            Log::info('Previous subscription found', [
                'provider_id' => $userId,
                'previous_plan' => $previousSubscription->title,
                'previous_plan_type' => $previousSubscription->plan_type,
                'current_plan' => $currentSubscription->title,
                'current_plan_type' => $currentSubscription->plan_type
            ]);

            // Step 4: Compare plan limits
            $previousLimits = parsePlanLimitation($previousSubscription->plan_limitation);
            $currentLimits = parsePlanLimitation($currentSubscription->plan_limitation);

            

            $shouldDeactivate = $this->comparePlanLimits($previousLimits, $currentLimits);

          
            // Step 5: Deactivate resources if new limits are lower
            if ($shouldDeactivate) {
                Log::info('Limit downgrade detected - deactivating all resources', [
                    'provider_id' => $userId
                ]);
                
                $this->deactivateAllResources($userId);
                
                return [
                    'success' => true,
                    'is_upgrade' => false,
                    'is_downgrade' => true,
                    'should_deactivate' => true,
                    'message' => 'Plan limits decreased or changed from unlimited to limited. All resources deactivated. Provider must manually reactivate within new limits.'
                ];
            } else {
                
                return [
                    'success' => true,
                    'is_upgrade' => true,
                    'is_downgrade' => false,
                    'should_deactivate' => false,
                    'message' => 'Plan limits increased or remained same. Resources remain active.'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Plan upgrade handler failed', [
                'provider_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to handle plan upgrade: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Compare previous and current plan limits to determine if resources should be deactivated
     * 
     * Returns TRUE if resources should be deactivated (limit downgrade)
     * Returns FALSE if resources should remain active (limit upgrade or same)
     * 
     * Logic:
     * - If ANY resource type has lower limit in new plan → deactivate
     * - If previous was unlimited and current is limited → deactivate
     * - Otherwise → keep active
     *
     * @param array $previousLimits Previous plan limitations
     * @param array $currentLimits Current plan limitations
     * @return bool TRUE if resources should be deactivated, FALSE otherwise
     */
    private function comparePlanLimits($previousLimits, $currentLimits)
    {
        $resourceTypes = ['service', 'handyman', 'featured_service'];
        
        foreach ($resourceTypes as $type) {
            // Extract limit values for comparison
            $previousLimit = $this->extractLimitValue($previousLimits, $type);
            $currentLimit = $this->extractLimitValue($currentLimits, $type);

            Log::info("Comparing {$type} limits", [
                'resource_type' => $type,
                'previous_limit' => $previousLimit === PHP_INT_MAX ? 'unlimited' : $previousLimit,
                'current_limit' => $currentLimit === PHP_INT_MAX ? 'unlimited' : $currentLimit
            ]);

            // Case 1: Previous was unlimited, current is limited → DOWNGRADE
            if ($previousLimit === PHP_INT_MAX && $currentLimit !== PHP_INT_MAX) {
                Log::info("Downgrade detected: {$type} changed from unlimited to limited", [
                    'resource_type' => $type,
                    'previous' => 'unlimited',
                    'current' => $currentLimit
                ]);
                return true;
            }

            // Case 2: Both are limited, but current is lower → DOWNGRADE
            if ($previousLimit !== PHP_INT_MAX && $currentLimit !== PHP_INT_MAX) {
                if ($currentLimit < $previousLimit) {
                    Log::info("Downgrade detected: {$type} limit decreased", [
                        'resource_type' => $type,
                        'previous' => $previousLimit,
                        'current' => $currentLimit,
                        'difference' => $previousLimit - $currentLimit
                    ]);
                    return true;
                }
            }

            // Case 3: Current >= Previous or Previous limited → Current unlimited → UPGRADE (continue checking)
        }

        // No downgrades detected for any resource type
        Log::info('No limit downgrades detected - resources will remain active');
        return false;
    }

    /**
     * Extract numeric limit value for a specific resource type
     * 
     * Returns PHP_INT_MAX for unlimited resources
     * Returns 0 if resource is not enabled in plan
     * Returns actual limit value otherwise
     *
     * @param array $limits Plan limitation array
     * @param string $type Resource type (service, handyman, featured_service)
     * @return int Limit value or PHP_INT_MAX for unlimited
     */
    private function extractLimitValue($limits, $type)
    {
        // Check if limits array is empty or null (unlimited plan)
        if (empty($limits) || !is_array($limits)) {
            Log::info("Plan has no limitations - treating as unlimited", [
                'resource_type' => $type,
                'limits' => $limits
            ]);
            return PHP_INT_MAX;
        }

        // Check if resource type exists in plan
        if (!isset($limits[$type])) {
            Log::info("Resource type not found in plan limitations - treating as unlimited", [
                'resource_type' => $type
            ]);
            return PHP_INT_MAX;
        }

        $resourceLimit = $limits[$type];

        // Check if resource is enabled in plan - handle both "on" and 1 values
        $isChecked = $resourceLimit['is_checked'] ?? null;
        if (!in_array($isChecked, ['on', 1, '1', true], true)) {
            Log::info("Resource not checked in plan - treating as not available", [
                'resource_type' => $type,
                'is_checked' => $isChecked
            ]);
            return 0;
        }

        // Get limit value
        $limit = $resourceLimit['limit'] ?? null;

        // Use helper function to normalize limit value
        // Returns PHP_INT_MAX for unlimited (null, 0, 'unlimited')
        $limitValue = getPlanLimitValue($limit);
        
        Log::info("Extracted limit value", [
            'resource_type' => $type,
            'raw_limit' => $limit,
            'normalized_limit' => $limitValue === PHP_INT_MAX ? 'unlimited' : $limitValue
        ]);

        return $limitValue;
    }

    /**
     * Get numeric limit value, treating null/empty/0 as unlimited
     *
     * @param mixed $limit
     * @return int
     */
    private function getLimitValue($limit)
    {
        return getPlanLimitValue($limit);
    }

    /**
     * Deactivate ALL resources (services and handymen) for the provider
     * This is the first step before activating resources up to new plan limits
     *
     * @param int $userId
     * @return void
     */
    private function deactivateAllResources($userId)
    {
        DB::beginTransaction();
        try {
            \Log::info('=== STARTING RESOURCE DEACTIVATION ===', [
                'provider_id' => $userId,
                'timestamp' => now()
            ]);
            
            // ===== DEACTIVATE SERVICES =====
            
            // Get count before deactivation
            $servicesCountBefore = Service::where('provider_id', $userId)->count();
            $activeServicesCountBefore = Service::where('provider_id', $userId)->where('status', config('constant.SERVICE_STATUS.ACTIVE'))->count();
            
            // Deactivate all services and remove featured flag
            $servicesUpdated = Service::where('provider_id', $userId)
                ->update([
                    'status' => config('constant.SERVICE_STATUS.INACTIVE'),
                    'is_featured' => config('constant.SERVICE_STATUS.INACTIVE'),
                    'updated_at' => now()
                ]);

            
            // Verify services are deactivated
            $activeServicesAfter = Service::where('provider_id', $userId)->where('status', config('constant.SERVICE_STATUS.ACTIVE'))->count();
            
            \Log::info('Services verification after deactivation', [
                'provider_id' => $userId,
                'active_services_after' => $activeServicesAfter,
                'deactivation_successful' => $activeServicesAfter === 0
            ]);
            
            // ===== DEACTIVATE HANDYMEN =====
            
            // Get count before deactivation - IMPORTANT: Filter by user_type = 'handyman'
            $handymenCountBefore = User::where('provider_id', $userId)
                ->where('user_type', config('constant.USER_TYPE.HANDYMAN'))
                ->count();
            $activeHandymenCountBefore = User::where('provider_id', $userId)
                ->where('user_type', config('constant.USER_TYPE.HANDYMAN'))
                ->where('status', config('constant.USER_STATUS.ACTIVE'))
                ->count();
            
         
            // Deactivate all handymen - IMPORTANT: Filter by user_type = 'handyman'
            $handymenUpdated = User::where('provider_id', $userId)
                ->where('user_type', config('constant.USER_TYPE.HANDYMAN'))
                ->update([
                    'status' => config('constant.USER_STATUS.INACTIVE'),
                    'updated_at' => now()
                ]);

            \Log::info('Handymen deactivated', [
                'provider_id' => $userId,
                'handymen_updated' => $handymenUpdated
            ]);

            // Verify handymen are deactivated
            $activeHandymenAfter = User::where('provider_id', $userId)
                ->where('user_type', config('constant.USER_TYPE.HANDYMAN'))
                ->where('status', config('constant.USER_STATUS.ACTIVE'))
                ->count();
            
           

            DB::commit();

            \Log::info('=== RESOURCE DEACTIVATION COMPLETED SUCCESSFULLY ===', [
                'provider_id' => $userId,
                'services_deactivated' => $servicesUpdated,
                'handymen_deactivated' => $handymenUpdated,
                'services_active_after' => $activeServicesAfter,
                'handymen_active_after' => $activeHandymenAfter
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('=== RESOURCE DEACTIVATION FAILED ===', [
                'provider_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Activate services up to plan limit
     * Deactivates excess services beyond the limit
     *
     * @param int $userId
     * @param array $limits
     * @return int
     */
    private function activateServicesUpToLimit($userId, $limits)
    {
        DB::beginTransaction();
        try {
            if (!isset($limits['service']) || $limits['service']['is_checked'] !== config('constant.PLAN_LIMIT.CHECKED')) {
                DB::commit();
                return 0;
            }

            $serviceLimit = $this->getLimitValue($limits['service']['limit'] ?? null);
            
            // Get all services ordered by creation date
            $allServices = Service::where('provider_id', $userId)
                ->orderBy('created_at', 'asc')
                ->get();

            $totalServices = $allServices->count();
            $activatedCount = 0;
            $deactivatedCount = 0;

            // First, deactivate ALL services
            Service::where('provider_id', $userId)
                ->update([
                    'status' => config('constant.SERVICE_STATUS.INACTIVE'),
                    'is_featured' => config('constant.SERVICE_STATUS.INACTIVE'),
                    'updated_at' => now()
                ]);

            // Then activate only the oldest services up to the limit
            $servicesToActivate = Service::where('provider_id', $userId)
                ->orderBy('created_at', 'asc')
                ->limit($serviceLimit)
                ->get();

            foreach ($servicesToActivate as $service) {
                $service->update(['status' => config('constant.SERVICE_STATUS.ACTIVE'), 'updated_at' => now()]);
                $activatedCount++;
            }

            $deactivatedCount = $totalServices - $activatedCount;

            DB::commit();

           

            return $activatedCount + $deactivatedCount;

        } catch (\Exception $e) {
            DB::rollBack();
           
            throw $e;
        }
    }

    /**
     * Set featured flag on services up to plan limit
     * Featured services must:
     * 1. Only be set on ACTIVE services (status = 1)
     * 2. Not exceed featured_service_limit
     * 3. Not exceed active_services count
     *
     * NOTE: This does NOT activate new services, only sets featured flag on existing active services
     *
     * @param int $userId
     * @param array $limits
     * @return int
     */
    private function setFeaturedFlagOnActiveServices($userId, $limits)
    {
        DB::beginTransaction();
        try {
            if (!isset($limits['featured_service']) || $limits['featured_service']['is_checked'] !== config('constant.PLAN_LIMIT.CHECKED')) {
                DB::commit();
                return 0;
            }

            $featuredServiceLimit = $this->getLimitValue($limits['featured_service']['limit'] ?? null);
            
            // Get count of active services
            $activeServicesCount = Service::where('provider_id', $userId)
                ->where('status', config('constant.SERVICE_STATUS.ACTIVE'))
                ->count();

            // Featured services cannot exceed active services
            $maxFeaturedAllowed = min($featuredServiceLimit, $activeServicesCount);

            // First, remove featured flag from ALL services
            Service::where('provider_id', $userId)
                ->update([
                    'is_featured' => config('constant.SERVICE_STATUS.INACTIVE'),
                    'updated_at' => now()
                ]);

            // Then set featured flag on the oldest active services up to the limit
            $servicesToFeature = Service::where('provider_id', $userId)
                ->where('status', config('constant.SERVICE_STATUS.ACTIVE'))
                ->orderBy('created_at', 'asc')
                ->limit($maxFeaturedAllowed)
                ->get();

            $featuredCount = 0;
            foreach ($servicesToFeature as $service) {
                $service->update(['is_featured' => config('constant.SERVICE_STATUS.ACTIVE'), 'updated_at' => now()]);
                $featuredCount++;
            }

            DB::commit();

          
            return $featuredCount;

        } catch (\Exception $e) {
            DB::rollBack();
          
            throw $e;
        }
    }

    /**
     * Activate handymen up to plan limit
     * Deactivates excess handymen beyond the limit
     *
     * @param int $userId
     * @param array $limits
     * @return int
     */
    private function activateHandymenUpToLimit($userId, $limits)
    {
        DB::beginTransaction();
        try {
            if (!isset($limits['handyman']) || $limits['handyman']['is_checked'] !== config('constant.PLAN_LIMIT.CHECKED')) {
                DB::commit();
                return 0;
            }

            $handymanLimit = $this->getLimitValue($limits['handyman']['limit'] ?? null);
            
            // Get all handymen for this provider (user_type = 'handyman')
            $allHandymen = User::where('provider_id', $userId)
                ->where('user_type', config('constant.USER_TYPE.HANDYMAN'))
                ->orderBy('created_at', 'asc')
                ->get();

            $totalHandymen = $allHandymen->count();
            $activatedCount = 0;
            $deactivatedCount = 0;

         

            // First, deactivate ALL handymen for this provider
            $deactivatedCount = User::where('provider_id', $userId)
                ->where('user_type', config('constant.USER_TYPE.HANDYMAN'))
                ->update([
                    'status' => config('constant.USER_STATUS.INACTIVE'),
                    'updated_at' => now()
                ]);

          

            // Then activate only the oldest handymen up to the limit
            $handymenToActivate = User::where('provider_id', $userId)
                ->where('user_type', config('constant.USER_TYPE.HANDYMAN'))
                ->orderBy('created_at', 'asc')
                ->limit($handymanLimit)
                ->get();

            foreach ($handymenToActivate as $handyman) {
                $handyman->update(['status' => config('constant.USER_STATUS.ACTIVE'), 'updated_at' => now()]);
                $activatedCount++;
            }

            DB::commit();


            return $activatedCount + $deactivatedCount;

        } catch (\Exception $e) {
            DB::rollBack();
           
            throw $e;
        }
    }
}
