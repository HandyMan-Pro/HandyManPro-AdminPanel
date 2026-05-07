<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Subscription Invoice #{{ $subscription->id }}</title>
    <style>
        /* Using Project Color Variables */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1C1F34;
            font-size: 12px;
            line-height: 1.6;
            margin: 0;
            padding: 30px;
        }
        .header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #E0E0E0;
        }
        .logo-section {
            display: inline-block;
            vertical-align: middle;
        }
        .invoice-info {
            float: right;
            text-align: right;
            color: #6C757D;
        }
        .invoice-info .date {
            margin-bottom: 4px;
        }
        .invoice-info .id {
            color: #5F60B9;
            font-weight: 600;
        }
        .thank-you-message {
            margin: 20px 0;
            color: #6C757D;
            font-size: 13px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #1C1F34;
            margin-bottom: 10px;
            margin-top: 20px;
        }
        .info-box {
            background-color: #F6F7F9;
            padding: 15px;
            margin-bottom: 15px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            color: #6C757D;
            display: inline-block;
            width: 140px;
        }
        .info-value {
            color: #1C1F34;
        }
        .text-success {
            color: #3CAE5C;
        }
        .text-danger {
            color: #FB2F2F;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table thead {
            background-color: #F6F7F9;
        }
        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            border: 1px solid #E0E0E0;
        }
        table td {
            padding: 12px;
            border: 1px solid #E0E0E0;
        }
        .summary-table {
            margin-top: 30px;
            background-color: #F6F7F9;
        }
        .summary-table td {
            padding: 12px;
            border: none;
        }
        .summary-label {
            text-align: right;
            color: #6C757D;
            width: 70%;
        }
        .summary-value {
            text-align: right;
            font-weight: 600;
            width: 30%;
        }
        .grand-total {
            border-top: 1px solid #E0E0E0;
            font-size: 14px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #E0E0E0;
            text-align: center;
            color: #6C757D;
        }
        .org-info {
            float: right;
            text-align: right;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>
    @php
        use App\Models\Setting;
        $settings = Setting::whereIn('type', ['site-setup', 'general-setting', 'theme-setup'])
            ->whereIn('key', ['site-setup', 'general-setting', 'theme-setup'])
            ->get()
            ->keyBy('key');
        $app = isset($settings['site-setup']) ? json_decode($settings['site-setup']->value) : null;
        $generaldata = isset($settings['general-setting']) ? json_decode($settings['general-setting']->value) : null;
        $themeSetup = isset($settings['theme-setup']) ? $settings['theme-setup'] : null;
        
        // Get dynamic site name
        $siteName = $generaldata->site_name ?? 'Handyman Provider';
    @endphp

    <!-- Header -->
    <div class="header clearfix">
        <div class="logo-section">
            @php
                // Get logo from theme-setup using Spatie Media Library (collection name is 'logo')
                $logoMedia = null;
                if ($themeSetup) {
                    $logoMedia = $themeSetup->getFirstMedia('logo');
                }
                
                $logoDisplayed = false;
                
                // Try to display the logo from media library
                if ($logoMedia && file_exists($logoMedia->getPath())) {
                    try {
                        $imageData = base64_encode(file_get_contents($logoMedia->getPath()));
                        $mimeType = $logoMedia->mime_type ?? 'image/png';
                        echo '<img src="data:' . $mimeType . ';base64,' . $imageData . '" style="height: 32px; width: auto; max-width: 100px; vertical-align: middle; margin-right: 10px;">';
                        $logoDisplayed = true;
                    } catch (\Exception $e) {
                        // Continue to fallback
                    }
                }
                
                // Fallback to default logo if custom logo not found
                if (!$logoDisplayed) {
                    $defaultLogoPath = public_path('images/logo.png');
                    if (file_exists($defaultLogoPath)) {
                        $imageData = base64_encode(file_get_contents($defaultLogoPath));
                        $mimeType = 'image/png';
                        echo '<img src="data:' . $mimeType . ';base64,' . $imageData . '" style="height: 32px; width: auto; max-width: 100px; vertical-align: middle; margin-right: 10px;">';
                    }
                }
            @endphp
            <span style="font-size: 16px; font-weight: 600; vertical-align: middle;">{{ $siteName }}</span>
        </div>
        <div class="invoice-info">
            <div class="date">{{ __('messages.invoice_date') }}: {{ \Carbon\Carbon::parse($subscription->created_at)->format('Y-m-d') }}</div>
            <div>{{ __('messages.invoice_id') }}: <span class="id">#{{ $subscription->id }}</span></div>
        </div>
    </div>

    <!-- Thank You Message -->
    <div class="thank-you-message">
        {{ __('messages.invoice_thank_you_message') }}
    </div>

    <!-- Organization Information -->
    <div class="clearfix" style="margin-bottom: 20px;">
        <div style="float: left; width: 60%;">
            <div class="section-title">{{ __('messages.organization_information') }}:</div>
            <div style="color: #6C757D; font-size: 12px;">
                {{ __('messages.invoice_contact_message') }}
            </div>
        </div>
        <div class="org-info">
            <div style="margin-bottom: 5px;">{{ $generaldata->inquriy_email ?? 'hello@iqonic.design' }}</div>
            <div>{{ $generaldata->helpline_number ?? '+15265897485' }}</div>
        </div>
    </div>

    <!-- Payment Information -->
    @if($subscription->amount > 0)
    <div class="section-title">{{ __('messages.payment_information') }}:</div>
    <div class="info-box">
        <span style="color: #6C757D;">{{ __('messages.payment_method') }}:</span>
        <span style="color: #1C1F34; font-weight: 500;">
            {{ isset($subscription->payment) ? ucfirst($subscription->payment->payment_type) : '-' }}
        </span>
        <span style="margin: 0 10px;">|</span>
        <span style="color: #6C757D;">{{ __('messages.payment_status') }}:</span>
        @if(isset($subscription->payment) && $subscription->payment->payment_status)
            <span class="text-success" style="font-weight: 500;">
                {{ str_replace('_', ' ', ucfirst($subscription->payment->payment_status)) }}
            </span>
        @else
            <span class="text-danger" style="font-weight: 500;">{{ __('messages.pending') }}</span>
        @endif
    </div>
    @endif

    <!-- Provider Information -->
    <div class="section-title">{{ __('messages.provider') }}:</div>
    <div class="info-box">
        <div class="info-row">
            <span class="info-label">{{ __('messages.name') }}:</span>
            <span class="info-value">{{ optional($subscription->provider)->display_name ?? '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">{{ __('messages.email') }}:</span>
            <span class="info-value">{{ optional($subscription->provider)->email ?? '-' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">{{ __('messages.contact_number') }}:</span>
            <span class="info-value">{{ optional($subscription->provider)->contact_number ?? '-' }}</span>
        </div>
        @if(optional($subscription->provider)->address)
        <div class="info-row">
            <span class="info-label">{{ __('messages.address') }}:</span>
            <span class="info-value">{{ optional($subscription->provider)->address ?? '-' }}</span>
        </div>
        @endif
    </div>

    <!-- Subscription Details Table -->
    @php
        $rawPlanType = strtolower((string)($subscription->plan_type ?? optional($subscription->plan)->plan_type ?? ''));
        if ($rawPlanType === 'limited') {
            $displayPlanType = 'Limited';
        } elseif (in_array($rawPlanType, ['unlimited', 'free'], true) || $rawPlanType === '') {
            $displayPlanType = 'Unlimited';
        } else {
            $displayPlanType = ucfirst($rawPlanType);
        }
    @endphp
     <div class="section-title">{{ __('messages.plan_detail') }}:</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('messages.plan_name') }}</th>
                <th>{{ __('messages.plan_type') }}</th>
                <th>{{ __('messages.duration') }}</th>
                <th style="text-align: right;">{{ __('messages.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ optional($subscription->plan)->title ?? $subscription->title ?? '-' }}</td>
                <td>{{ $displayPlanType }}</td>
                <td>
                    {{ \Carbon\Carbon::parse($subscription->start_at)->format('M d, Y') }} - 
                    {{ \Carbon\Carbon::parse($subscription->end_at)->format('M d, Y') }}
                    ({{ ucfirst($subscription->type) }})
                </td>
                <td style="text-align: right; font-weight: 600;">
                    ${{ number_format(ceil($subscription->amount), 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    @php
        $planDescription = trim((string)($subscription->description ?? optional($subscription->plan)->description ?? ''));
        if ($planDescription !== '') {
            $planDescription = mb_strimwidth($planDescription, 0, 250, '...');
        }
    @endphp

    @if(!empty($planDescription))
        <div class="section-title">{{ __('messages.plan_description') }}:</div>
        <div class="info-box">
            <div class="info-row" style="margin-bottom: 0;">
                <span class="info-value" style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    {{ $planDescription }}
                </span>
            </div>
        </div>
    @endif

    @php
        $planLimits = null;
        if ($subscription->plan_limitation) {
            if (is_string($subscription->plan_limitation)) {
                $planLimits = json_decode($subscription->plan_limitation, true);
            } elseif (is_array($subscription->plan_limitation)) {
                $planLimits = $subscription->plan_limitation;
            }
        }
        if (!is_array($planLimits)) {
            $planLimits = [];
        }
        $planLimits = array_replace([
            'service' => ['is_checked' => 'off', 'limit' => null],
            'handyman' => ['is_checked' => 'off', 'limit' => null],
            'featured_service' => ['is_checked' => 'off', 'limit' => null],
        ], $planLimits);
        
        $otherDetail = null;
        if ($subscription->other_detail) {
            if (is_string($subscription->other_detail)) {
                $otherDetail = json_decode($subscription->other_detail, true);
            } elseif (is_array($subscription->other_detail)) {
                $otherDetail = $subscription->other_detail;
            }
        }
        
        // Always show limits in invoice. For unlimited/no limits, render "Unlimited" rows.
        $showPlanLimits = true;
        $showProration = $otherDetail && is_array($otherDetail) && count($otherDetail) > 0;
    @endphp

    <!-- Plan Limits and Proration Details -->
    @if($showPlanLimits || $showProration)
        <div style="margin-top: 20px; width: 100%;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    @if($showPlanLimits)
                        <td style="width: 48%; vertical-align: top; padding: 0; margin: 0; border: none;">
                            <div class="section-title">{{ __('messages.plan_limits') }}:</div>
                            <table style="width: 100%; background-color: #F6F7F9; border-collapse: collapse;">
                                @if(isset($planLimits['service']))
                                    <tr>
                                        <td style="padding: 10px 15px; border: none; color: #6C757D;">{{ __('messages.services') }}:</td>
                                        <td style="padding: 10px 15px; border: none; text-align: right; color: #1C1F34; font-weight: 500;">
                                            @if(isset($planLimits['service']['is_checked']) && $planLimits['service']['is_checked'] === 'on')
                                                @if(isset($planLimits['service']['limit']) && $planLimits['service']['limit'] !== null && $planLimits['service']['limit'] != -1)
                                                    {{ $planLimits['service']['limit'] }}
                                                @else
                                                    {{ __('messages.unlimited') }}
                                                @endif
                                            @else
                                                {{ __('messages.unlimited') }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if(isset($planLimits['handyman']))
                                    <tr>
                                        <td style="padding: 10px 15px; border: none; color: #6C757D;">{{ __('messages.handyman') }}:</td>
                                        <td style="padding: 10px 15px; border: none; text-align: right; color: #1C1F34; font-weight: 500;">
                                            @if(isset($planLimits['handyman']['is_checked']) && $planLimits['handyman']['is_checked'] === 'on')
                                                @if(isset($planLimits['handyman']['limit']) && $planLimits['handyman']['limit'] !== null && $planLimits['handyman']['limit'] != -1)
                                                    {{ $planLimits['handyman']['limit'] }}
                                                @else
                                                    {{ __('messages.unlimited') }}
                                                @endif
                                            @else
                                                {{ __('messages.unlimited') }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @if(isset($planLimits['featured_service']))
                                    <tr>
                                        <td style="padding: 10px 15px; border: none; color: #6C757D;">{{ __('messages.featured_services') }}:</td>
                                        <td style="padding: 10px 15px; border: none; text-align: right; color: #1C1F34; font-weight: 500;">
                                            @php
                                                $isChecked = isset($planLimits['featured_service']['is_checked']) && $planLimits['featured_service']['is_checked'] === 'on';
                                                $limit = $planLimits['featured_service']['limit'] ?? '';
                                            @endphp
                                            @if(!$isChecked)
                                                0
                                            @elseif($limit === '' || $limit === '0' || $limit === 0 || $limit === null || $limit == -1)
                                                {{ __('messages.unlimited') }}
                                            @else
                                                {{ $limit }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </td>
                    @endif

                    @if($showPlanLimits && $showProration)
                        <td style="width: 4%; border: none;"></td>
                    @endif

                    @if($showProration)
                        <td style="width: 48%; vertical-align: top; padding: 0; margin: 0; border: none;">
                            <div class="section-title">{{ __('messages.proration_details') }}:</div>
                            <table style="width: 100%; background-color: #F6F7F9; border-collapse: collapse;">
                                @if(isset($otherDetail['purchase_type']))
                                    <tr>
                                        <td style="padding: 10px 15px; border: none; color: #6C757D;">{{ __('messages.purchase_type') }}:</td>
                                        <td style="padding: 10px 15px; border: none; text-align: right; color: #1C1F34; font-weight: 500;">
                                            {{ ucfirst($otherDetail['purchase_type']) }}
                                        </td>
                                    </tr>
                                @endif
                                @if(isset($otherDetail['previous_plan_title']) || isset($otherDetail['previous_plan']))
                                    <tr>
                                        <td style="padding: 10px 15px; border: none; color: #6C757D;">{{ __('messages.previous_plan') }}:</td>
                                        <td style="padding: 10px 15px; border: none; text-align: right; color: #1C1F34; font-weight: 500;">
                                            {{ $otherDetail['previous_plan_title'] ?? $otherDetail['previous_plan'] ?? '-' }}
                                        </td>
                                    </tr>
                                @endif
                                @if(isset($otherDetail['previous_plan_price']) || isset($otherDetail['previous_price']))
                                    <tr>
                                        <td style="padding: 10px 15px; border: none; color: #6C757D;">{{ __('messages.previous_price') }}:</td>
                                        <td style="padding: 10px 15px; border: none; text-align: right; color: #1C1F34; font-weight: 500;">
                                            ${{ number_format($otherDetail['previous_plan_price'] ?? $otherDetail['previous_price'] ?? 0, 2) }}
                                        </td>
                                    </tr>
                                @endif
                                @if(isset($otherDetail['original_price']))
                                    <tr>
                                        <td style="padding: 10px 15px; border: none; color: #6C757D;">{{ __('messages.original_price') }}:</td>
                                        <td style="padding: 10px 15px; border: none; text-align: right; color: #1C1F34; font-weight: 500;">
                                            ${{ number_format($otherDetail['original_price'], 2) }}
                                        </td>
                                    </tr>
                                @endif
                                @if(isset($otherDetail['credit_applied']))
                                    <tr>
                                        <td style="padding: 10px 15px; border: none; color: #6C757D;">{{ __('messages.credit_applied') }}:</td>
                                        <td style="padding: 10px 15px; border: none; text-align: right; color: #FB2F2F; font-weight: 500;">
                                            -${{ number_format(floor($otherDetail['credit_applied']), 2) }}
                                        </td>
                                    </tr>
                                @endif
                                @if(isset($otherDetail['reason']))
                                    <tr>
                                        <td style="padding: 10px 15px; border: none; color: #6C757D;">{{ __('messages.reason') }}:</td>
                                        <td style="padding: 10px 15px; border: none; text-align: right; color: #1C1F34; font-weight: 500;">
                                            {{ $otherDetail['reason'] }}
                                        </td>
                                    </tr>
                                @endif
                                <tr style="border-top: 1px solid #D0D0D0;">
                                    <td style="padding: 12px 15px; border: none; color: #1C1F34; font-weight: 600;">{{ __('messages.paid_amount') }}:</td>
                                    <td style="padding: 12px 15px; border: none; text-align: right; color: #1C1F34; font-weight: 600;">
                                        @if(isset($otherDetail['paid_amount']))
                                            ${{ number_format(ceil($otherDetail['paid_amount']), 2) }}
                                        @else
                                            ${{ number_format(ceil($subscription->amount), 2) }}
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    @endif

    <!-- Summary -->
    <table class="summary-table">
        <tr class="grand-total">
            <td class="summary-label">{{ __('messages.grand_total') }}</td>
            <td class="summary-value">${{ number_format(ceil($subscription->amount), 2) }}</td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <div style="margin-bottom: 10px;">
            <span style="color: #6C757D;">For more information, visit our website: </span>
            <a href="{{ $generaldata->website ?? '#' }}" style="color: #5F60B9; text-decoration: none;">{{ $generaldata->website ?? 'www.handyman.com' }}</a>
        </div>
        <div style="color: #6C757D;">
            {{ $app->site_copyright ?? '© 2025 All Rights Reserved by IQONIC Design' }}
        </div>
    </div>
</body>
</html>

