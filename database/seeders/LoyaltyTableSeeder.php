<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoyaltyEarnRule;
use App\Models\LoyaltyEarnServiceMapping;
use App\Models\LoyaltyRedeemRule;
use App\Models\LoyaltyRedeemServiceMapping;
use App\Models\LoyaltyReferralRule;
use App\Models\Service;
use App\Models\ServicePackage;

class LoyaltyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $all_service = Service::where('status', 1)->pluck('id')->toArray();
        $all_package = ServicePackage::where('status', 1)->pluck('id')->toArray();

        // Seed Referral Rule
        LoyaltyReferralRule::create([
            'referrer_points' => 50,
            'referred_user_points' => 20,
            'max_referrals_per_user' => 10,
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'status' => true,
        ]);

        // Loyalty Earn Rules
        $loyaltyEarnRules = [
            [
                'loyalty_type' => 'service',
                'service_ids' => $all_service,
                'minimum_amount' => 15,
                'maximum_amount' => 60,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'points' => 20,
                'status' => 1,
                'is_stackable' => 1,
            ],
            [
                'loyalty_type' => 'package_service',
                'service_ids' => $all_package,
                'minimum_amount' => 25,
                'maximum_amount' => 100,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'points' => 30,
                'status' => 1,
                'is_stackable' => 1,
            ],
        ];

        foreach ($loyaltyEarnRules as $ruleData) {
            $serviceIds = $ruleData['service_ids'];
            unset($ruleData['service_ids']);

            if (empty($serviceIds)) {
                continue; 
            }

            $rule = LoyaltyEarnRule::create($ruleData);

            $mappings = array_map(fn($id) => [
                'loyalty_earn_id' => $rule->id,
                'service_id' => $id,
                'loyalty_type' => $rule->loyalty_type,
            ], $serviceIds);

            LoyaltyEarnServiceMapping::insert($mappings);
        }

        // Loyalty Redeem Rules
        $loyaltyRedeemRules = [
            [
                'loyalty_type' => 'service',
                'service_ids' => $all_service,
                'redeem_type' => 'full',
                'threshold_points' => 40,
                'max_discount' => 2,
                'status' => 1,
                'is_stackable' => 1,
            ],
            [
                'loyalty_type' => 'package_service',
                'service_ids' => $all_package,
                'redeem_type' => 'full',
                'threshold_points' => 50,
                'max_discount' => 2,
                'status' => 1,
                'is_stackable' => 1,
            ]
        ];

        foreach ($loyaltyRedeemRules as $ruleData) {
            $serviceIds = $ruleData['service_ids'];
            unset($ruleData['service_ids']);

            if (empty($serviceIds)) {
                continue;
            }

            $rule = LoyaltyRedeemRule::create($ruleData);

            $mappings = array_map(fn($id) => [
                'loyalty_redeem_id' => $rule->id,
                'service_id' => $id,
                'loyalty_type' => $rule->loyalty_type,
            ], $serviceIds);

            LoyaltyRedeemServiceMapping::insert($mappings);
        }
    }
}
