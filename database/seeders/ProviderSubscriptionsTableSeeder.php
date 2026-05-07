<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProviderSubscriptionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        $now = now();

        \DB::table('provider_subscriptions')->delete();
        
        \DB::table('provider_subscriptions')->insert(array (
            0 => 
            array (
                'amount' => 19,
                'created_at' => $now,
                'description' => NULL,
                'duration' => 1,
                'id' => 1,
                'identifier' => 'basic',
                'payment_id' => '1',
                'plan_id' => 2,
                'plan_limitation' => '{"service":{"is_checked":"on","limit":"5"},"handyman":{"is_checked":"on","limit":"2"},"featured_service":{"is_checked":"on","limit":"1"}}',
                'plan_type' => 'limited',
                'start_at' => $now,
                'end_at' => $now->copy()->addMonth(),
                'status' => 'active',
                'title' => 'Basic plan',
                'type' => 'monthly',
                'updated_at' => $now,
                'user_id' => 4,
            ),
             1 => 
            array (
                'amount' => 0,
                'created_at' => $now,
                'description' => NULL,
                'duration' => 1,
                'id' => 2,
                'identifier' => 'free',
                'payment_id' => '2',
                'plan_id' => 1,
                'plan_limitation' => '{"service":{"is_checked":"on","limit":"1"},"handyman":{"is_checked":"on","limit":"1"},"featured_service":{"is_checked":"off","limit":"0"}}',
                'plan_type' => 'limited',
                'start_at' => $now,
                'end_at' => $now->copy()->addDays(7),
                'status' => 'active',
                'title' => 'Free plan',
                'type' => 'weekly',
                'updated_at' => $now,
                'user_id' => 6,
            ),
             2 => 
            array (
                'amount' => 49,
                'created_at' => $now,
                'description' => NULL,
                'duration' => 1,
                'id' => 3,
                'identifier' => 'premium',
                'payment_id' => '3',
                'plan_id' => 3,
                'plan_limitation' => '{"service":{"is_checked":"on","limit":"15"},"handyman":{"is_checked":"on","limit":"5"},"featured_service":{"is_checked":"on","limit":"3"}}',
                'plan_type' => 'limited',
                'start_at' => $now,
                'end_at' => $now->copy()->addYear(),
                'status' => 'active',
                'title' => 'Premium plan',
                'type' => 'yearly',
                'updated_at' => $now,
                'user_id' => 7,
            ),
            3 => 
            array (
                'amount' => 0,
                'created_at' => $now,
                'description' => NULL,
                'duration' => 1,
                'id' => 4,
                'identifier' => 'free',
                'payment_id' => '4',
                'plan_id' => 1,
                'plan_limitation' => '{"service":{"is_checked":"on","limit":"1"},"handyman":{"is_checked":"on","limit":"1"},"featured_service":{"is_checked":"off","limit":"0"}}',
                'plan_type' => 'limited',
                'start_at' => $now,
                'end_at' => $now->copy()->addDays(7),
                'status' => 'active',
                'title' => 'Free plan',
                'type' => 'weekly',
                'updated_at' => $now,
                'user_id' => 8,
            ),
        ));
        
        
    }
}