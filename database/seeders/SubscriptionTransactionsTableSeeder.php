<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionTransactionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('subscription_transactions')->delete();
        
        DB::table('subscription_transactions')->insert([
            [
                'id' => 1,
                'subscription_plan_id' => 1, // Links to provider_subscriptions
                'user_id' => 3,
                'amount' => 19.00,
                'payment_type' => 'stripe',
                'txn_id' => null,
                'payment_status' => 'paid',
                'other_transaction_detail' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'subscription_plan_id' => 2, // Links to provider_subscriptions
                'user_id' => 37,
                'amount' => 0.00,
                'payment_type' => 'cash',
                'txn_id' => null,
                'payment_status' => 'paid',
                'other_transaction_detail' =>null,
                'created_at' => now(),
                'updated_at' => now(),
            ],            
            [
                'id' => 3,
                'subscription_plan_id' => 3, // Links to provider_subscriptions
                'user_id' => 38,
                'amount' => 49.00,
                'payment_type' => 'stripe',
                'txn_id' => null,
                'payment_status' => 'paid',
                'other_transaction_detail' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],            
            [
                'id' => 4,
                'subscription_plan_id' => 4, // Links to provider_subscriptions
                'user_id' => 39,
                'amount' => 0.00,
                'payment_type' => 'cash',
                'txn_id' => null,
                'payment_status' => 'paid',
                'other_transaction_detail' => null,
                'created_at' => now()->subDays(15),
                'updated_at' => now()->subDays(15),
            ],              
        ]);
    }
}
