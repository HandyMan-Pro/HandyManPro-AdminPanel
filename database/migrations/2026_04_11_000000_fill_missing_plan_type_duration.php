<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fill missing plan_type and duration in provider_subscriptions from plans table
        $providerSubscriptions = DB::table('provider_subscriptions')
            ->where(function($query) {
                $query->whereNull('plan_type')
                      ->orWhereNull('duration');
            })
            ->whereNotNull('plan_id')
            ->get();

        foreach ($providerSubscriptions as $subscription) {
            $plan = DB::table('plans')
                ->where('id', $subscription->plan_id)
                ->first();

            if ($plan) {
                $updateData = [];
                
                // Update duration if it's currently NULL and plan has a duration
                if (is_null($subscription->duration) && isset($plan->duration)) {
                    $updateData['duration'] = $plan->duration;
                }
                
                // Update plan_type if it's currently NULL and plan has a plan_type
                if (is_null($subscription->plan_type) && isset($plan->plan_type)) {
                    $updateData['plan_type'] = $plan->plan_type;
                }
                
                // Perform update if there's data to update
                if (!empty($updateData)) {
                    DB::table('provider_subscriptions')
                        ->where('id', $subscription->id)
                        ->update($updateData);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed
    }
};
