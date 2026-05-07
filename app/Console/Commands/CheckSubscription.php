<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ProviderSubscription;
use App\Models\Plans;
use App\Traits\NotificationTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckSubscription extends Command
{
    use NotificationTrait;

    protected $signature = 'check:subscription';
    protected $description = 'Check and expire user subscriptions';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        $activeUsers = $this->getActiveUsersWithSubscriptions();
        if ($activeUsers->isEmpty()) {
            Log::info('Not found any active user');
            return 0;
        }

        $userIds = $activeUsers->pluck('id')->toArray();
     
        $subscriptions = $this->getLatestSubscriptionsForUsers($userIds);
     
        $planIds = $subscriptions->pluck('plan_id')->unique()->filter()->toArray();
        $plans = $this->getPlansById($planIds);

        foreach ($activeUsers as $user) {
            $this->processUserSubscription($user, $subscriptions, $plans, $today, $tomorrow);
        }

        return 0;
    }

    private function getActiveUsersWithSubscriptions()
    {
        return User::where('is_subscribe', config('constant.USER_STATUS.ACTIVE'))
            ->with('subscriptionPackage')
            ->get();
    }

    private function getLatestSubscriptionsForUsers(array $userIds)
    {
        return ProviderSubscription::whereIn('user_id', $userIds)
            ->whereIn('status', [
                config('constant.SUBSCRIPTION_STATUS.ACTIVE'),
                config('constant.SUBSCRIPTION_STATUS.CANCELLED')
            ])
            ->whereIn('id', function ($query) use ($userIds) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('provider_subscriptions')
                    ->whereIn('user_id', $userIds)
                    ->whereIn('status', [
                        config('constant.SUBSCRIPTION_STATUS.ACTIVE'),
                        config('constant.SUBSCRIPTION_STATUS.CANCELLED')
                    ])
                    ->groupBy('user_id');
            })
            ->get()
            ->keyBy('user_id');
    }

    private function getPlansById(array $planIds)
    {
        if (empty($planIds)) {
            return collect();
        }

        return Plans::whereIn('id', $planIds)->get()->keyBy('id');
    }

    private function processUserSubscription($user, $subscriptions, $plans, Carbon $today, Carbon $tomorrow)
    {
        $package = $subscriptions->get($user->id);
 
        if (!$package) {
            Log::info('No package found for user', ['user_id' => $user->id]);
            return;
        }

        // Use the package's end_at date directly (not from relationship)
        $expiredDate = Carbon::parse($package->end_at)->startOfDay();
        $todayDate = $today->copy()->startOfDay();

    
        if ($expiredDate->lt($todayDate)) {
            $this->expireSubscription($user, $package);
        }

        $tomorrowDate = $tomorrow->copy()->startOfDay();

        // Send reminder if free plan expires tomorrow
        if ($expiredDate->equalTo($tomorrowDate) && $package->identifier === 'free') {
            $this->sendFreePlanExpiryReminder($user, $package, $plans, $expiredDate);
        }
    }

    private function expireSubscription($user, $package)
    {
      
        $user->is_subscribe = config('constant.USER_STATUS.INACTIVE');
        $user->save();

        if ($package !== null) {
            $package->status = config('constant.SUBSCRIPTION_STATUS.INACTIVE');
            $package->save();
            
            // Send expiration notification
            $this->sendExpirationNotification($user, $package);
        }
    }

    private function sendExpirationNotification($user, $package)
    {
        try {
            $active_data = [
                'activity_type' => 'subscription_expired',
                'user_id' => $user->id,
                'provider_name' => $user->display_name ?? $user->username ?? 'Provider',
                'plan_name' => $package->title ?? 'Plan',
                'expiry_date' => date('d-m-Y'),
            ];

            $this->sendNotification($active_data);
        } catch (\Throwable $e) {
            Log::error('Subscription expiration notification failed: ' . $e->getMessage());
        }
    }

    private function sendFreePlanExpiryReminder($user, $package, $plans, Carbon $expiredDate)
    {
        try {
            $plan = $plans->get($package->plan_id);

            $active_data = [
                'activity_type' => 'free_plan_expiry_reminder',
                'user_id' => $user->id,
                'provider_name' => $user->display_name ?? $user->username ?? 'Provider',
                'plan_title' => $package->title ?? 'Free Plan',
                'expiry_date' => $expiredDate->format('d-m-Y'),
                'plan_duration' => $package->duration ?? ($plan ? $plan->duration : ''),
                'plan_type' => $package->type ?? ($plan ? $plan->type : ''),
                'plan_description' => $package->description ?? ($plan ? $plan->description : ''),
            ];

            $this->sendNotification($active_data);
        } catch (\Throwable $e) {
            Log::error('Free plan expire notification failed: ' . $e->getMessage());
        }
    }
}