<?php

namespace App\Services;

use App\Models\ProviderSubscription;
use App\Models\Setting;
use App\Models\SubscriptionTransaction;
use Carbon\Carbon;

class ProviderSubscriptionDetailPresenter
{
    public function build(ProviderSubscription $subscription): array
    {
        $provider = $subscription->provider;

        $latestTransaction = SubscriptionTransaction::where('subscription_plan_id', $subscription->id)
            ->orderByDesc('id')
            ->first();
       

        $siteSetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
        $dateTime = $siteSetup ? json_decode($siteSetup->value) : null;
        $dateFormat = optional($dateTime)->date_format ?? 'M d, Y';
        $timeFormat = optional($dateTime)->time_format ?? 'g:i A';

        $startAt = !empty($subscription->start_at) ? Carbon::parse($subscription->start_at) : null;
        $endAt = !empty($subscription->end_at) ? Carbon::parse($subscription->end_at) : null;

        $startAtDay = $startAt ? $startAt->copy()->startOfDay() : null;
        $endAtDay = $endAt ? $endAt->copy()->startOfDay() : null;
        $today = Carbon::now()->startOfDay();

        $totalDays = ($startAtDay && $endAtDay && $startAtDay->lessThan($endAtDay))
            ? $startAtDay->diffInDays($endAtDay)
            : 0;

        $remainingDays = ($endAtDay && $today->lessThan($endAtDay))
            ? $today->diffInDays($endAtDay)
            : 0;

        $isExpired = $endAtDay && $today->greaterThanOrEqualTo($endAtDay);

        $usedDays = max(0, $totalDays - $remainingDays);
        $progressPercent = $totalDays > 0 ? min(100, max(0, ($usedDays / $totalDays) * 100)) : 0;

        $subscriptionStatus = strtolower((string) ($subscription->status ?? 'inactive'));
        $subscriptionBadgeClass = $this->resolveSubscriptionBadgeClass($subscriptionStatus);

        $hasPaymentStatus = !empty($latestTransaction);
        $paymentStatusText = '';
        $paymentBadgeClass = '';
        $paymentMethodText = '';

        // Only show payment status for paid plans (amount > 0)
        if ($hasPaymentStatus && $subscription->amount > 0) {
            $paymentStatus = strtolower((string) ($latestTransaction->payment_status ?? 'pending'));
            $paymentMethod = strtolower((string) ($latestTransaction->payment_type ?? ''));
            
            // If payment status is 'paid' and payment method exists, show "Payment via {method}"
            if ($paymentStatus === 'paid' && !empty($paymentMethod) && $paymentMethod !== '-') {
                // Try to get translated payment method name, fallback to ucfirst if not found
                $translatedMethod = __('messages.payment_method_' . $paymentMethod);
                if ($translatedMethod === 'messages.payment_method_' . $paymentMethod) {
                    // Translation not found, use original capitalized
                    $translatedMethod = ucfirst($paymentMethod);
                }
                $paymentStatusText = __('messages.payment_via', ['method' => $translatedMethod]);
            } else {
                $paymentStatusText = $paymentStatus ? __('messages.' . $paymentStatus) : '-';
            }
            
            $paymentBadgeClass = $this->resolvePaymentBadgeClass($paymentStatus);
            $paymentMethodText = ucfirst($paymentMethod);
        }
        
        // Override hasPaymentStatus to false for free plans
        if ($subscription->amount == 0) {
            $hasPaymentStatus = false;
        }

        $proration = is_array($subscription->other_detail ?? null) ? $subscription->other_detail : [];
        $limits = is_array($subscription->plan_limitation ?? null) ? $subscription->plan_limitation : [];

        $showProrationSection = $this->shouldShowProrationSection($proration);

        $planType = strtolower((string) ($subscription->plan_type ?? $subscription->type ?? ''));
        
        $featuredLimitText = $this->resolveLimitText($limits, 'featured_service', $planType);
        $handymanLimitText = $this->resolveLimitText($limits, 'handyman', $planType);
        $serviceLimitText = $this->resolveLimitText($limits, 'service', $planType);

        $durationText = $this->resolveDurationText($subscription->duration, $subscription->type);

        $purchaseType = strtolower((string) ($proration['purchase_type'] ?? ''));
        $purchaseTypeText = $purchaseType ? __('messages.' . $purchaseType) : '-';
        $previousPlanText = (string) ($proration['previous_plan'] ?? '-');
        $previousPlanPriceText = isset($proration['previous_plan_price']) ? getPriceFormat($proration['previous_plan_price']) : '-';
        $originalPriceText = isset($proration['original_price']) ? getPriceFormat($proration['original_price']) : '-';
        $creditAppliedText = isset($proration['credit_applied']) ? getPriceFormat(floor($proration['credit_applied'])) : '-';
        $paidAmountText = isset($proration['paid_amount']) ? getPriceFormat(ceil($proration['paid_amount'])) : '-';
        $reasonText = (string) ($proration['reason'] ?? '');
        // Normalize legacy proration reason text for consistent UI copy.
        if (
            $purchaseType === strtolower((string) config('constant.SUBSCRIPTION_TYPE.UPGRADE')) &&
            str_starts_with(strtolower(trim($reasonText)), 'prorated upgrade from')
        ) {
            $reasonText = __('messages.plan_upgrade_prorated_adjustment');
        }

        $planTitle = optional($subscription->plan)->title;
        $fallbackPlanTitle = $subscription->title;
        $currentPlanText = (string) ($planTitle ?? $fallbackPlanTitle ?? '-');

        $showReasonDetail = !empty($proration['previous_plan']) || !empty($planTitle) || !empty($fallbackPlanTitle);
        $showReasonSection = !empty(trim($reasonText)) || !empty($proration['previous_plan']);

        $planTypeTranslated = $planType ? __('messages.' . $planType) : '-';

        $subscriptionStatusTranslated = $subscriptionStatus ? __('messages.' . $subscriptionStatus) : '-';

        return [
            'subscription' => $subscription,
            'provider' => $provider,
            'planTypeText' => $planTypeTranslated,
            'durationText' => $durationText,
            'amountText' => getPriceFormat(ceil($subscription->amount) ?? 0),
            'remainingDaysText' => $this->formatCountWithUnit($remainingDays, 'day'),
            'isExpired' => $isExpired,
            'startDateText' => $startAt ? $startAt->format($dateFormat . ' · ' . $timeFormat) : '-',
            'endDateText' => $endAt ? $endAt->format($dateFormat . ' · ' . $timeFormat) : '-',
            'totalDaysText' => $this->formatCountWithUnit($totalDays, 'day'),
            'progressPercent' => $progressPercent,
            'subscriptionStatusText' => $subscriptionStatusTranslated,
            'subscriptionBadgeClass' => $subscriptionBadgeClass,
            'hasPaymentStatus' => $hasPaymentStatus,
            'paymentStatusText' => $paymentStatusText,
            'paymentBadgeClass' => $paymentBadgeClass,
            'paymentMethodText' => $paymentMethodText,
            'purchaseTypeText' => $purchaseTypeText,
            'previousPlanText' => $previousPlanText,
            'previousPlanPriceText' => $previousPlanPriceText,
            'originalPriceText' => $originalPriceText,
            'creditAppliedText' => $creditAppliedText,
            'paidAmountText' => $paidAmountText,
            'showProrationSection' => $showProrationSection,
            'featuredLimitText' => $featuredLimitText,
            'handymanLimitText' => $handymanLimitText,
            'serviceLimitText' => $serviceLimitText,
            'reasonText' => $reasonText,
            'showReasonDetail' => $showReasonDetail,
            'showReasonSection' => $showReasonSection,
            'currentPlanText' => $currentPlanText,
            'transactionDetail' => $latestTransaction
        ];
    }

    private function resolveSubscriptionBadgeClass(string $status): string
    {
        if ($status === 'active') {
            return 'badge-active text-success bg-success-subtle';
        }

        if ($status === 'cancelled') {
            return 'badge text-danger bg-danger-subtle';
        }

        return 'badge text-secondary bg-secondary-subtle';
    }

    private function resolvePaymentBadgeClass(string $paymentStatus): string
    {
        if ($paymentStatus === 'paid') {
            return 'badge-active text-success bg-success-subtle';
        }

        if ($paymentStatus === 'failed') {
            return 'badge text-danger bg-danger-subtle';
        }

        return 'badge text-warning bg-warning-subtle';
    }

    private function shouldShowProrationSection(array $proration): bool
    {
        $keys = [
            'purchase_type',
            'previous_plan',
            'previous_plan_price',
            'original_price',
            'credit_applied',
            'paid_amount',
            'reason',
        ];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $proration)) {
                continue;
            }

            $value = $proration[$key];
            if (is_array($value) && !empty($value)) {
                return true;
            }

            if (is_numeric($value) && (float) $value != 0.0) {
                return true;
            }

            if (!is_array($value) && !is_numeric($value) && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function resolveLimitText(array $limits, string $key, string $planType): string
    {
        // If plan type is unlimited, return "Unlimited" text
        if ($planType === 'unlimited') {
            return __('messages.unlimited');
        }

        if (!isset($limits[$key]) || !is_array($limits[$key])) {
            return '-';
        }

        $limitData = $limits[$key];
        $isChecked = ($limitData['is_checked'] ?? null) === 'on';
        $value = $limitData['limit'] ?? '';

        // If feature is disabled (is_checked = 'off'), return '0' regardless of limit value
        if (!$isChecked) {
            return '0';
        }

        // If feature is enabled (is_checked = 'on') and limit is 0, return 'Unlimited'
        if ($value === '' || $value === '0' || $value === 0) {
            return __('messages.unlimited');
        }

        return (string) $value;
    }

    private function resolveDurationText($durationRaw, $subscriptionTypeRaw): string
    {
        if ($durationRaw === null || $durationRaw === '') {
            return '-';
        }

        if (!is_numeric($durationRaw)) {
            return (string) $durationRaw;
        }

        $count = max(0, (int) $durationRaw);
        $subscriptionType = strtolower((string) ($subscriptionTypeRaw ?? ''));
        $unit = 'day';

        if ($subscriptionType === 'weekly' || $subscriptionType === 'week') {
            $unit = 'week';
        } elseif ($subscriptionType === 'monthly' || $subscriptionType === 'month') {
            $unit = 'month';
        } elseif ($subscriptionType === 'yearly' || $subscriptionType === 'year') {
            $unit = 'year';
        }

        return $this->formatCountWithUnit($count, $unit);
    }

    private function formatCountWithUnit(int $count, string $unit): string
    {
        if ($unit === 'week') {
            return trans_choice('messages.subscription_unit_week', $count, ['count' => $count]);
        }

        if ($unit === 'month') {
            return trans_choice('messages.subscription_unit_month', $count, ['count' => $count]);
        }

        if ($unit === 'year') {
            return trans_choice('messages.subscription_unit_year', $count, ['count' => $count]);
        }

        return trans_choice('messages.subscription_unit_day', $count, ['count' => $count]);
    }
}
