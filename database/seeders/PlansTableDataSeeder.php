<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PlansTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('plans')->delete();
        \DB::table('plans')->insert(array (
            0 =>
            array (
                'id' => 1,
                'title' => 'Free plan',
                'identifier' => 'free',
                'type' => 'weekly',
                'amount' => 0,
                'trial_period' => 0,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'duration' => 1,
                'plan_type' => 'limited',
                'playstore_identifier' => 'free_plan',
                'appstore_identifier' => 'free_plan',
            ),
            1 =>
            array (
                'id' => 2,
                'title' => 'Basic plan',
                'identifier' => 'basic',
                'type' => 'monthly',
                'amount' => 19,
                'trial_period' => 7,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'duration' => 1,
                'plan_type' => 'limited',
                'playstore_identifier' => 'basic_plan',
                'appstore_identifier' => 'basic_plan',
            ),
            2 =>
            array (
                'id' => 3,
                'title' => 'Premium plan',
                'identifier' => 'premium',
                'type' => 'yearly',
                'amount' => 49,
                'trial_period' => 14,
                'status' => 1,
                'created_at' =>  now(),
                'updated_at' =>  now(),
                'duration' => 1,
                'plan_type' => 'limited',
                'playstore_identifier' => 'premium_plan',
                'appstore_identifier' => 'premium_plan',
            ),
        ));
    }
}
