<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PlanLimitsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('plan_limits')->delete();
        
        \DB::table('plan_limits')->insert(array (
            0 => 
            array (
                'id' => 1,
                'plan_id' => 1,
                'plan_limitation' => '{"service":{"is_checked":"on","limit":"1"},"handyman":{"is_checked":"on","limit":"1"},"featured_service":{"is_checked":"off","limit":"0"}}',
                'created_at' => now(),
                'updated_at' => now(),
            ),
            1 => 
            array (
                'id' => 2,
                'plan_id' => 2,
                'plan_limitation' => '{"service":{"is_checked":"on","limit":"5"},"handyman":{"is_checked":"on","limit":"2"},"featured_service":{"is_checked":"on","limit":"1"}}',
                'updated_at' => now(),
                'created_at' => now(),
            ),
            2 => 
            array (
                'id' => 3,
                'plan_id' => 3,
                'plan_limitation' => '{"service":{"is_checked":"on","limit":"15"},"handyman":{"is_checked":"on","limit":"5"},"featured_service":{"is_checked":"on","limit":"3"}}',
                'created_at' => now(),
                'updated_at' => now(),
            ),
        ));
        
        
    }
}