<x-master-layout>
    <main class="main-area">
        <div class="main-content">
            <div class="container-fluid">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ __('messages.subscription_detail') }}</h5>
                            @if(auth()->user()->hasRole('provider'))
                                <a href="{{ route('provider.my-billing') }}" class="float-end btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            @else
                                <a href="{{ route('provider.pending', ['status' => 'subscribe']) }}" class="float-end btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
               
                <div class="subscription-detail card">
                    <div class="card-body">
                        <div class="page-wrapper">
     
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                <div>
                                <h2 class="mb-2">
                                    <a href="{{ route('provider.show', $provider->id) }}" class="text-decoration-none">
                                        {{ $provider->display_name ?? '-' }}
                                    </a>
                                </h2>
                                <div class="user-meta">
                                    {{ $provider->email ?? '-' }} <span>·</span> {{ $currentPlanText ?? '-' }}
                                </div>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="badge badge-pill {{ $subscriptionBadgeClass }}">{{ $subscriptionStatusText }}</span>
                                        @if($hasPaymentStatus)
                                            <span class="badge badge-pill {{ $paymentBadgeClass }}">{{ $paymentStatusText }}</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('provider-subscription.download-invoice', encrypt($subscription->id)) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-file-invoice"></i> {{ __('messages.invoice') }}
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Stats -->
                            <div class="stats-card">
                                <div class="progress-bar-multi">
                                    <div style="background:var(--bs-info)"></div>
                                    <div style="background:var(--bs-success)"></div>
                                    <div style="background:var(--bs-danger)"></div>
                                <div style="background:var(--bs-warning)"></div>
                                </div>
                                <div class="stats-grid" style="grid-template-columns: repeat({{ $isExpired ? '3' : '4' }}, 1fr);">
                                <div class="stat-item">
                                    <div class="stat-label">{{ __('messages.amount') }}</div>
                                    <div class="stat-value">{{ $amountText }}</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">{{ __('messages.subscription_plan_type') }}</div>
                                    <div class="stat-value">{{ $planTypeText }}</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-label">{{ __('messages.subscription_duration') }}</div>
                                    <div class="stat-value">{{ $durationText }}</div>
                                </div>
                                @if(!$isExpired)
                                <div class="stat-item">
                                    <div class="stat-label">{{ __('messages.subscription_remaining') }}</div>
                                    <div class="stat-value">{{ $remainingDaysText }}</div>
                                </div>
                                @endif
                                </div>
                                
                                @php
                                    $planDescription = trim((string)($subscription->description ?? optional($subscription->plan)->description ?? ''));
                                @endphp
                                @if(!empty($planDescription))
                                    <div style="border-top:1px solid var(--bs-border-color); padding: 18px 24px;">
                                        <div class="reason-label">{{ __('messages.description') }}</div>
                                        {{-- Clamp text visually to the box width/height.
                                             Ellipsis shows only when the text overflows. --}}
                                        <div class="reason-text"
                                            style="display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:1; overflow:hidden; text-overflow:ellipsis; white-space:normal; word-break:break-word; overflow-wrap:anywhere;">
                                            {{ $planDescription }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Timeline -->
                            <div class="timeline-card">
                                <div class="date-block">
                                <div class="date-sub">{{ __('messages.start_date') }}</div>
                                <div class="date-val">{{ $startDateText }}</div>
                                </div>
                            
                                <div class="timeline-track">
                                <div class="timeline-label">{{ $totalDaysText }}</div>
                                <div class="track-bar">
                                    <div class="track-thumb track-thumb-left"></div>
                                    <div class="track-fill" style="width: {{ $progressPercent }}%;">
                                    </div>
                                    <div class="track-thumb track-thumb-right"></div>
                                </div>
                                </div>
                            
                                <div class="date-end">
                                <div class="date-sub">{{ __('messages.end_date') }}</div>
                                <div class="date-val">{{ $endDateText }}</div>
                                </div>
                            </div>
                            
                            <!-- Bottom -->
                            <div class="bottom-grid">
                            
                                <!-- Proration -->
                                @if($showProrationSection)
                                    <div class="section-card">
                                    <div class="section-title">{{ __('messages.proration_details') }}</div>
                                
                                    <div class="pro-row">
                                        <span class="label">{{ __('messages.purchase_type') }}</span>
                                        <span class="value">{{ $purchaseTypeText }}</span>
                                    </div>
                                    <div class="pro-row">
                                        <span class="label">{{ __('messages.previous_plan') }}</span>
                                        <span class="value">{{ $previousPlanText }}</span>
                                    </div>
                                    <div class="pro-row">
                                        <span class="label">{{ __('messages.previous_price') }}</span>
                                        <span class="value">{{ $previousPlanPriceText }}</span>
                                    </div>
                                    <div class="pro-row">
                                        <span class="label">{{ __('messages.original_price') }}</span>
                                        <span class="value">{{ $originalPriceText }}</span>
                                    </div>
                                    <div class="pro-row">
                                        <span class="label">{{ __('messages.credit_applied') }}</span>
                                        <span class="value">{{ $creditAppliedText }}</span>
                                    </div>
                                    <div class="pro-row total">
                                        <span class="label">{{ __('messages.paid_amount') }}</span>
                                        <span class="value">{{ $paidAmountText }}</span>
                                    </div>
                                    </div>
                                @endif
                              
                            
                                <!-- Plan limits + Reason -->
                                <div class="section-card">
                                <div class="section-title">{{ __('messages.plan_limits') }}</div>
                                <div class="limits-grid">
                                    <div class="limit-box">
                                    <div class="limit-num">{{ $featuredLimitText }}</div>
                                    <div class="limit-lbl">{{ __('messages.featured_services') }}</div>
                                    </div>
                                    <div class="limit-box">
                                    <div class="limit-num">{{ $handymanLimitText }}</div>
                                    <div class="limit-lbl">{{ __('messages.handyman') }}</div>
                                    </div>
                                    <div class="limit-box">
                                    <div class="limit-num">{{ $serviceLimitText }}</div>
                                    <div class="limit-lbl">{{ __('messages.services') }}</div>
                                    </div>
                                </div>
                            
                                @if($showReasonSection)
                                    <div class="reason-box">
                                        <div class="reason-text">
                                            <span class="reason-line-label"><strong>{{ __('messages.reason_title') }}:</strong></span>
                                            <span class="reason-line-value">{{ $reasonText }}</span>
                                        </div>
                                        @if($showReasonDetail)
                                            <div class="reason-detail">
                                                <div class="reason-lines">
                                                    <div class="reason-line">
                                                        <span class="reason-line-label"> <strong>{{ __('messages.previous_plan') }}:</strong></span>
                                                        <span class="reason-line-value">{{ $previousPlanText }} ({{ $previousPlanPriceText }})</span>
                                                    </div>
                                                    <div class="reason-line">
                                                        <span class="reason-line-label"><strong>{{ __('messages.upgraded_plan') }}:</strong></span>
                                                        <span class="reason-line-value">{{ $currentPlanText }} ({{ $originalPriceText }})</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                </div>
                            
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </main>
</x-master-layout>