<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: Inter;
        }

        .column {
            float: left;
            width: 30%;
            padding: 0 10px;
        }

        .row {
            margin: 0 -5px;
        }

        .row:after {
            content: "";
            display: table;
            clear: both;
        }

        .card {
            padding: 16px;
            text-align: center;
            background-color: #F6F7F9;
        }
        table tr td{
            font-size: 14px;
        }
        table thead th{
            font-size: 14px;
        }

        .booking-details-box {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px 20px 16px;
            margin-top: 8px;
            background: #fff;
        }

        .bd-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .bd-table thead th {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.03em;
            color: #1C1F34;
            text-transform: uppercase;
            padding: 10px 12px 10px 0;
            border-bottom: 1px solid #ddd;
            vertical-align: bottom;
        }

        .bd-table thead th.bd-col-service {
            text-align: left;
            width: 42%;
        }

        .bd-table thead th.bd-col-num {
            text-align: right;
            white-space: nowrap;
        }

        .bd-table tbody td {
            padding: 14px 12px 14px 0;
            vertical-align: top;
            color: #1C1F34;
        }

        .bd-table tbody td.bd-col-num {
            text-align: right;
            white-space: nowrap;
        }

        .bd-service-name {
            font-weight: 600;
            color: #1C1F34;
            margin-bottom: 4px;
        }

        .bd-service-meta {
            font-size: 11px;
            color: #8B9399;
            line-height: 1.45;
        }

        .bd-row-divider td {
            border-top: 1px dashed #ddd;
            padding-top: 12px;
        }

        .bd-extra-label {
            color: #1C1F34;
            font-weight: 500;
        }

        .bd-amount-strong {
            font-weight: 700;
            color: #1C1F34;
        }

        .bd-hr-dotted {
            width: 100%;
            border: 0;
            border-top: 1px dashed #ccc;
            margin: 16px 0 0 0;
            height: 0;
        }

        .bd-summary-wrap {
            width: 100%;
            margin-top: 12px;
        }

        .bd-summary-table {
            border-collapse: collapse;
            margin-left: auto;
            min-width: 300px;
            font-size: 13px;
        }

        .bd-summary-table td {
            padding: 8px 0 8px 16px;
            vertical-align: top;
        }

        .bd-summary-table td.bd-sum-label {
            text-align: left;
            color: #6C757D;
            white-space: nowrap;
        }

        .bd-summary-table td.bd-sum-value {
            text-align: right;
            font-weight: 700;
            color: #1C1F34;
            min-width: 120px;
        }

        .bd-summary-table td.bd-sum-value.bd-sum-discount {
            color: #219653;
        }

        .bd-summary-table td.bd-sum-value.bd-sum-tax {
            color: #C62828;
            font-weight: 700;
        }

        .bd-summary-table tr.bd-grand-total td {
            padding-top: 12px;
            font-size: 14px;
        }

        .bd-summary-table tr.bd-grand-total td.bd-sum-label,
        .bd-summary-table tr.bd-grand-total td.bd-sum-value {
            font-weight: 700;
            color: #1C1F34;
        }

        .bd-summary-table tr.bd-advance-block td {
            border-top: 1px solid #eee;
            font-weight: 700;
        }
    </style>
</head>
<?php
    use App\Models\Setting;
    $settings = Setting::whereIn('type', ['site-setup', 'general-setting'])
        ->whereIn('key', ['site-setup', 'general-setting'])
        ->get()
        ->keyBy('key');

    $app = isset($settings['site-setup']) ? json_decode($settings['site-setup']->value) : null;
    $generaldata = isset($settings['general-setting']) ? json_decode($settings['general-setting']->value) : null;

    $extraValue = 0;
?>
<body>
    <div style="padding: 24px 0 0;">
        <div style="padding-bottom: 16px; margin-bottom: 16px; border-bottom:  1px solid #ccc;">
            <div style="overflow: hidden;">
                <div style="float: left; display: inline-block;">
                    {{-- <img style="height: 32px; width: 32px; "
                        src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJQAAACUCAMAAABC4vDmAAAAY1BMVEVVYIDn7O3///9TXn/r8PBPW31BTnRLV3pGU3fIztV+h53u8/PW3OBfaYddZ4b09PaOlqikqbh7gppmcIzo6e25vsiGjaKZnrBxepPDxs+ytsPe4Oalrbnh5uiaorLJy9XT1d0+l9ETAAAHqklEQVR4nMWciY6rOgyGQ0NIKEtatrJ0evr+T3kDdKUsv9PCtTTS0dEMfDiO4zh22O4b0Vlzzc+nokzjmLE4TsvidM6vTaa/eiyzB/KPRRkJpaQU3Ahj5ocLKZUSUVkcfXswO6isOnHPMzDMsHxKB+d5/FRlW0FldRIpOUozYJMqSmoLLipUlpeeAoAeYMoryVw0qKaIlMCJehEqKpq1oHSeeoKgpFcuL80Jdg9D6TqVZCW9YMm0hrFAKJ3Hnp2SHsK9GMXCoP6lluP2jiXTfz+DaopvtfTA8hLE5Jeh9JF/YUtDEfy4PIaLUGGqfofUisqv30L9VE29CH5ZUNY8VLb3fo3UitrP+/hZKF/8XE29CDE7DeegjsiqaydcHq2g9OHHFv4u6jBtWJNQupRrMjEmy0mqKagmXcmcniLSKUc6AZVFK+upo4omJuE4VBgT9NTG5VKI/kdSFkkRj/vRUagMZeJCeSpNDuc6z6sqz+vzIUnNf6Fkgo3qagyqiTAmEyMVdegEQeAGbifmH0HghHWBxl4iGrOrESiN2bj09n5oeJwPMWRhtVeQVcoUgtIlwiTZxRkDeoL9XWIES4x4hk+oA/AorvbhDNGNK9wj7lcelqGOwIMEq+a09NRWxQCtq48VZwj1D9CTiPxgGamVwEfmjByuzgOoDJjMZsYAaropC5nJXGRzUDoBHhH7MJOh8mPgM/dzUBfAoDx07G4jWAFxonechroCjlgWJCZDVSDTOZyCQrymjkIak/EMETCAqZ6AQryBvBAM6kZ1AVT15hdeoBpkFfX+6FB/yO6DN6NQBeBSREK0qFYCZOESxRjUP+R7ZE1WlIGqkeXG+/cJpVMoBvLpTI7jI0/mT1t/QNXIks7TxgYqhD5Y5kMoDTheA1XaMDlOCT081gOoGtqfi72FSZn5t4fCRi9/hwItShR2UMjEfrGqG1SO7ajWhXpY1Q0K3HquO3xmsXmFasCMz8pQzGteoED1rg51c+sdVBZhf7M6FO838h0UtAxsAcVU/YCCdnqbQInyDpXBic3VoZiX3aDg0dsASuU3qATO3qwPxZMeCp57W0Cxdv4ZqApPuG4ApaoO6oRnEjeAkqcOiuMJwQ2gOG+hNOGkYwMo5mkD5VOgEjsoIEXxhPIN1JGQnJaU3MYLlE95x9FAoRFC+/u1xa6vlQDalvRiIgWmoaC+E17+2TE5zh8Wbvdv0YzgOuXFUlFGVUg+4QYVZazBjwhUZWVRrbg57KE5b9gV9+eenZl3UIQ5rq4M/4TNoHJ2xufFRlDyzAgr31ZQJ0ZwUxtBiYLhbmorKJ4w3KttBpWyGP7lzaBiBuWlNoWi6Gk7KJJsB0UYPpXbL8iEhcMMH2EAxcEe6kCIPVOKS2DR8hntuLghHiC1LoHgPJk42UaeyMH04y0lZZYxpm5z4OC4LpZ7vkMVlAW5/QOL4NN1KAbVLciE0IW1Z/9kqOAsaMU8JnShzFUj3pU6gAG1Xs0EeYRwuBV5JKqK7stNOEzYOLQiEqKiXJpB9RsHwharF+L4ISfI71Bmi0XYjHZC3PwFtInE+s0oZdveU5GgXMLa2ku7bSclOFpROWH8sJPaN+kSHNTZwUmmTjQOdksFUZJmnUh8907JtjygNDG92IlIcasiW9QtvUhJxPYCW5VLtVf2SMQSUta9CDBP5YZkpEfKmuw+UV8FVW4MhN+S+4RjkLsIJAR1Laz8cQyyIwYKDFsBXd+mreVxYIQfrT0ESMm6FoP3crSGH0I+RS3uAZECsw95HkJajJ/Zbs1DuaFV7Xg3eveDbfLoy2UoC4t6PdgmRwprQb2WAMDFEmtDvRVL0E19FajezB9QFdUsV4EaFOCApUrrQg1LlXY50arWgBoWde000SusAMWjYfkbWtZ1l2XnSfcyH4WC1AkolnbK5FhKjJRU7q4kq1oM1P+oXsZsGD6hSG6ds6Xg073QoMbLdHcNYQehFvMcRKPiEwXNlOogIEoPkEry51fWu3Eo2NZVChWAE7oW7wvMCFSDPUAcsKJ09wK35vLrJNTuvDwDuVdW6GbU9fceVqA703ix2y0VpXBZ1khz0Z3Kve6BJqP5FpVdNn6pxh1J8TOxncB1/GRJWwvNPMaFzjxAxpfMImMdhMm8tuSwH/KjQWzSLwhVhISR+9DW5BAsN4hN5TuE2IfWx0VGW9f91ExEWul2Ovmk4l5aOdaHfR2WO6GtsXbksZ7RYVs0l2luN3ADbRWfvfJge6aZgu/V6dJMOfuRe8UytjVovIUbWdsw9EnVNYf+AqnDGmhLxKOt5OPN0fdWQd5Oua8H7g3rVVsiDkdfP9FGrlPZGdM3U24KKMAPvbZkNNFyP9Vwnxlrl7H/3ZSbwnL8UnFj48SGeyN777IKUocV1LEqJ189c4lDtRJRj3U9WVziYOTn5vQqcxeWzF4Mov8fpqV7XVYyKnf+rUuXzawyhMHCS5fvCvo90+IrgVuVfqysJTVhUD+1rAVrIkD9Dgu7qgu90+wn3gG91Ay//e1rLPz6N8o9efqLQXQpNzESbxS0LeqivYV89yJdXSQl2UERuehEllAtF2T2geWVnvaXjO504E6qzHVtgb6EurNp7d7p2uuC9HdXsbbyH8oqgTWWktC8AAAAAElFTkSuQmCC"> --}}

                        <?php
                        // Get site name and logo from settings table (not app_settings)
                        $themeSetup = \App\Models\Setting::where('type', 'theme-setup')
                            ->where('key', 'theme-setup')
                            ->first();
                        
                        $siteName = 'Handyman Service'; // Default
                        
                        // Get site name from general-setting in settings table
                        if ($generaldata && isset($generaldata->site_name)) {
                            $siteName = $generaldata->site_name;
                        }
                        
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
                                echo '<img src="data:' . $mimeType . ';base64,' . $imageData . '" style="height: 40px; width: auto; max-width: 150px;">';
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
                                echo '<img src="data:' . $mimeType . ';base64,' . $imageData . '" style="height: 40px; width: auto; max-width: 150px;">';
                            }
                        }
                        ?>

                    <span
                        style="display: inline-block; line-height: normal; vertical-align: super; padding-left: 12px;">
                        <span style="color: #1C1F34; vertical-align: inherit; font-size: 18px; font-weight: 600;">{{ $siteName }}</span>
                    </span>
                </div>
                <div style="float:right; text-align:right;">
                    <span style="color:#6C757D;">{{ __('messages.invoice_date') }}:</span><span style="color: #1C1F34; padding-right: 60px;">
                        {{ \Carbon\Carbon::parse($bookingdata->date)->format('Y-m-d') ?? '-' }}</span>
                    <span style="color:#6C757D;">  {{ __('messages.invoice_id') }}-</span><span style="color: #5F60B9;"> {{ '#' . $bookingdata->id ?? '-'}}</span>
                </div>
            </div>
        </div>
        <div>
            <p style="color: #6C757D; margin-bottom: 16px;">Thanks, you have already completed the payment for this
                invoice</p>
        </div>
        <div style="margin-bottom: 16px;">
            <div style="overflow: hidden;">
                <div style="float: left; width: 75%; display: inline-block;">
                    <h5 style="color: #1C1F34; margin: 0;">Organization information:</h5>
                    <p style="color: #6C757D;  margin-top: 12px; margin-bottom: 0;">For any questions or support
                        regarding this invoice or our services, please contact us via phone or email</p>
                </div>
                <div style="float:left; width: 25%; text-align:right;">
                    <span style="color: #1C1F34; margin-bottom: 12px;">{{ $generaldata->inquriy_email}}</span>
                    <p style="color: #1C1F34;  margin-top: 12px; margin-bottom: 0;">{{ $generaldata->helpline_number}}</p>
                </div>
            </div>
        </div>
        {{-- PAYMENT INFORMATION --}}
        <div>
            <h5 style="color: #1C1F34; margin-top: 0;">{{ __('messages.payment_info') }} :</h5>
            <div style="background: #F6F7F9; padding:8px 24px;">
                <span style="color: #1C1F34;">{{ __('messages.payment_method') }}:</span>
                <span style="color: #6C757D; margin-left: 16px;">{{ isset($payment) ? ucfirst($payment->payment_type) : '-' }}</span>
                <span style="color: #1C1F34; margin-left: 30px;">{{ __('messages.payment_status') }} :</span>
                @if(isset($payment) && $payment->payment_status)
                    <span style="color: {{ in_array($payment->payment_status, ['paid', 'advanced_paid'], true) ? '#219653' : '#FB2F2F' }}; margin-left: 16px;">
                        {{ booking_payment_status_label($payment) }}
                    </span>
                @else
                    <span style="color: #FB2F2F; margin-left: 16px;">
                        {{ __('messages.pending') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- PERSON INFORMATION --}}

        <div style="padding: 16px 0;">
            <div class="row">
                @if ($bookingdata->customer)

                <div class="column">
                    <h5 style="margin: 8px 0;">{{__('messages.customer')}}:</h5>
                    <div class="card" style="text-align: start;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tbody style="background: #F6F7F9;">
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34">{{ __('messages.name') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{optional($bookingdata->customer)->display_name ?? '-'}}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34;">{{ __('messages.contact_number') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{ optional($bookingdata->customer)->contact_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34;">{{ __('messages.address') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{optional($bookingdata->customer)->address ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                @if ($bookingdata->provider)
                <div class="column">
                    <h5 style="margin: 8px 0;">{{__('messages.provider')}}:</h5>
                    <div class="card">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tbody style="background: #F6F7F9;">
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34">{{ __('messages.name') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{optional($bookingdata->provider)->display_name ?? '-'}}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34;">{{ __('messages.contact_number') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{ optional($bookingdata->provider)->contact_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34;">{{ __('messages.address') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{ optional($bookingdata->provider)->address ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                @if(count($bookingdata->handymanAdded) > 0)
                @foreach($bookingdata->handymanAdded as $booking)
                <div class="column">
                    <h5 style="margin: 8px 0;">{{__('messages.handyman')}}:</h5>
                    <div class="card">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tbody style="background: #F6F7F9;">
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34; width:50%;">{{ __('messages.name') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{optional($booking->handyman)->display_name ?? '-'}}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34; width:50%;">{{ __('messages.contact_number') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{optional($booking->handyman)->contact_number ?? '-'}}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34; width:50%;">{{ __('messages.address') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{optional($booking->handyman)->address ?? '-'}}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
                @endif
                @if ($bookingdata->shop)
                <div class="column">
                    <h5 style="margin: 8px 0;">{{__('messages.shop')}}:</h5>
                    <div class="card">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tbody style="background: #F6F7F9;">
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34; width:50%;">{{ __('messages.name') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{optional($bookingdata->shop)->shop_name ?? '-'}}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34; width:50%;">{{ __('messages.contact_number') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{optional($bookingdata->shop)->contact_number ?? '-'}}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px; text-align: start; color: #1C1F34; width:50%;">{{ __('messages.address') }}:</td>
                                    <td style="padding:4px; text-align: start; color: #6B6B6B;">{{optional($bookingdata->shop)->address ?? '-'}}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>

        @php
            $invoiceCurrencySymbol = '$';
            $siteSetupCurrency = \App\Models\Setting::getValueByKey('site-setup', 'site-setup');
            if ($siteSetupCurrency && !empty($siteSetupCurrency->default_currency)) {
                $cCountry = \App\Models\Country::find($siteSetupCurrency->default_currency);
                if ($cCountry) {
                    $invoiceCurrencySymbol = $cCountry->symbol;
                }
            }
            $formatted_duration = null;
            if (optional($bookingdata->service)->type == 'hourly') {
                $formatted_duration = convertToHoursMins($bookingdata->duration_diff);
            }
            if ($bookingdata->type == 'service' && $bookingdata->service) {
                if ($bookingdata->service->type === 'fixed') {
                    $lineMainSubTotal = ($bookingdata->amount) * ($bookingdata->quantity);
                } else {
                    $lineMainSubTotal = $bookingdata->final_total_service_price;
                }
            } else {
                $lineMainSubTotal = $bookingdata->amount;
            }
            $taxCalcBase = (float) $bookingdata->getSubTotalValue();
            foreach ($bookingdata->bookingExtraCharge as $_ec) {
                $taxCalcBase += (float) $_ec->price * (int) $_ec->qty;
            }
            $taxCalcBase += (float) $bookingdata->bookingAddonService->sum('price');
        @endphp

        {{-- Line items + payment summary --}}
        <div class="booking-details-box">
            @php
                $invoiceAddressDisplay = $bookingdata->address ?? '';
                $invoiceAddressDisplay = trim(preg_replace('/,?\s*\b(Gb|GB)\b\.?$/iu', '', $invoiceAddressDisplay));
                $invoiceAddressDisplay = trim(preg_replace('/^\s*\b(Gb|GB)\b\.?\s*,?\s*/iu', '', $invoiceAddressDisplay));
                $invoiceAddressDisplay = preg_replace('/\s+/', ' ', $invoiceAddressDisplay);
            @endphp
            <table class="bd-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th class="bd-col-service"><strong>{{ strtoupper(__('messages.service')) }}</strong></th>
                        <th class="bd-col-num"><strong>{{ strtoupper(__('messages.Price')) }} ({{ $invoiceCurrencySymbol }})</strong></th>
                        @if(optional($bookingdata->service)->type == 'hourly')
                            <th class="bd-col-num"><strong>{{ strtoupper(__('messages.hour')) }}</strong></th>
                        @else
                            <th class="bd-col-num"><strong>{{ strtoupper(__('messages.Qty')) }}</strong></th>
                        @endif
                        <th class="bd-col-num"><strong>{{ strtoupper(__('messages.amount')) }} ({{ $invoiceCurrencySymbol }})</strong></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="bd-service-name">{{ optional($bookingdata->service)->name ?? '-' }}</div>
                            <div class="bd-service-meta">
                                {{ __('messages.date') }}: {{ \Carbon\Carbon::parse($bookingdata->date)->format('Y-m-d') }}
                                @if($invoiceAddressDisplay !== '')
                                    <br>{{ __('messages.address') }}: {{ $invoiceAddressDisplay }}
                                @endif
                            </div>
                        </td>
                        <td class="bd-col-num">{{ isset($bookingdata->amount) ? getPriceFormat($bookingdata->amount) : getPriceFormat(0) }}</td>
                        @if(optional($bookingdata->service)->type == 'hourly')
                            <td class="bd-col-num">{{ !empty($formatted_duration) ? $formatted_duration : '00:00:00' }}</td>
                        @else
                            <td class="bd-col-num">{{ !empty($bookingdata->quantity) ? $bookingdata->quantity : 0 }}</td>
                        @endif
                        <td class="bd-col-num bd-amount-strong">{{ !empty($lineMainSubTotal) ? getPriceFormat($lineMainSubTotal) : getPriceFormat(0) }}</td>
                    </tr>

                    @foreach($bookingdata->bookingExtraCharge as $charge)
                        @php
                            $lineExtra = (float) $charge->price * (int) $charge->qty;
                        @endphp
                        <tr class="bd-row-divider">
                            <td>
                                <span class="bd-extra-label">{{ __('messages.extra_charge') }}: {{ $charge->title }}</span>
                            </td>
                            <td class="bd-col-num">{{ getPriceFormat($charge->price) }}</td>
                            <td class="bd-col-num">{{ $charge->qty }}</td>
                            <td class="bd-col-num bd-amount-strong">{{ getPriceFormat($lineExtra) }}</td>
                        </tr>
                    @endforeach

                    @foreach($bookingdata->bookingAddonService as $addonservice)
                        <tr class="bd-row-divider">
                            <td>
                                <span class="bd-extra-label">{{ __('messages.add_ons') }}: {{ $addonservice->name }}</span>
                            </td>
                            <td class="bd-col-num">{{ getPriceFormat($addonservice->price) }}</td>
                            <td class="bd-col-num">1</td>
                            <td class="bd-col-num bd-amount-strong">{{ getPriceFormat($addonservice->price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <hr class="bd-hr-dotted" />

            <div class="bd-summary-wrap">
                <table class="bd-summary-table" align="right" cellspacing="0" cellpadding="0" width="320">
                    <tbody>
                        <tr>
                            <td class="bd-sum-label">{{ __('messages.Price') }}</td>
                            <td class="bd-sum-value">
                                @if(optional($bookingdata->service)->type == 'hourly')
                                    {{ getPriceFormat($bookingdata->amount) }} × {{ $bookingdata->quantity }} / hr = {{ getPriceFormat($bookingdata->final_total_service_price) }}
                                @else
                                    {{ getPriceFormat($bookingdata->amount) }} × {{ $bookingdata->quantity }} = {{ getPriceFormat($bookingdata->amount * $bookingdata->quantity) }}
                                @endif
                            </td>
                        </tr>

                        @if($bookingdata->bookingPackage == null && $bookingdata->discount > 0)
                            <tr>
                                <td class="bd-sum-label">{{ __('messages.discount') }} ({{ $bookingdata->discount }}% {{ __('messages.off') }})</td>
                                <td class="bd-sum-value bd-sum-discount">-{{ getPriceFormat($bookingdata->final_discount_amount) }}</td>
                            </tr>
                        @endif

                        @if($bookingdata->redeemed_points != null && $bookingdata->redeemed_discount != null)
                            <tr>
                                <td class="bd-sum-label">{{ __('messages.redeem_discount') }} ({{ $bookingdata->redeemed_points }} pts)</td>
                                <td class="bd-sum-value bd-sum-discount">-{{ getPriceFormat($bookingdata->redeemed_discount) }}</td>
                            </tr>
                        @endif

                        @if($bookingdata->couponAdded != null)
                            <tr>
                                <td class="bd-sum-label">{{ __('messages.coupon') }} ({{ $bookingdata->couponAdded->code }})</td>
                                <td class="bd-sum-value bd-sum-discount">-{{ getPriceFormat($bookingdata->final_coupon_discount_amount) }}</td>
                            </tr>
                        @endif

                        <tr>
                            <td class="bd-sum-label">{{ __('messages.sub_total') }}</td>
                            <td class="bd-sum-value">{{ !empty($bookingdata->final_sub_total) ? getPriceFormat($bookingdata->final_sub_total) : getPriceFormat(0) }}</td>
                        </tr>

                        @if($bookingdata->tax != '')
                            @foreach(json_decode($bookingdata->tax) as $taxLine)
                                @php
                                    if ($taxLine->type === 'percent') {
                                        $taxLineAmount = $taxCalcBase * ((float) $taxLine->value / 100);
                                        $taxLineLabel = $taxLine->title . ' (' . $taxLine->value . '%)';
                                    } else {
                                        $taxLineAmount = (float) $taxLine->value;
                                        $taxLineLabel = $taxLine->title . ' (' . getPriceFormat($taxLine->value) . ')';
                                    }
                                @endphp
                                <tr>
                                    <td class="bd-sum-label">{{ __('messages.Tax') }}: {{ $taxLineLabel }}</td>
                                    <td class="bd-sum-value bd-sum-tax">{{ getPriceFormat($taxLineAmount) }}</td>
                                </tr>
                            @endforeach
                        @endif

                        <tr class="bd-grand-total-sep">
                            <td colspan="2" style="border-top:1px solid #ccc; padding-top:10px; font-size:1px; line-height:0;">&nbsp;</td>
                        </tr>
                        <tr class="bd-grand-total">
                            <td class="bd-sum-label">{{ __('messages.grand_total') }}</td>
                            <td class="bd-sum-value">{{ getPriceFormat($bookingdata->total_amount) ?? getPriceFormat(0) }}</td>
                        </tr>

                        @if(optional($bookingdata->service)->is_enable_advance_payment == 1)
                            <tr class="bd-advance-block">
                                <td class="bd-sum-label">{{ __('messages.advance_payment_amount') }} ({{ $bookingdata->service->advance_payment_amount }}%)</td>
                                <td class="bd-sum-value">{{ getPriceFormat($bookingdata->advance_paid_amount) }}</td>
                            </tr>
                            @if($bookingdata->status !== 'cancelled')
                                <tr>
                                    <td class="bd-sum-label">
                                        {{ __('messages.remaining_amount') }}
                                        @if($payment != null && $payment->payment_status !== 'paid')
                                            <span style="color:#b7791f;font-size:11px;">({{ __('messages.pending') }})</span>
                                        @endif
                                    </td>
                                    <td class="bd-sum-value">
                                        @if($payment != null && $payment->payment_status == 'paid')
                                            {{ __('messages.paid') }}
                                        @else
                                            {{ getPriceFormat($bookingdata->total_amount - $bookingdata->advance_paid_amount) }}
                                        @endif
                                    </td>
                                </tr>
                            @endif

                            @if($bookingdata->status === 'cancelled')
                                <tr>
                                    <td class="bd-sum-label">{{ __('messages.cancellation_charge') }} ({{ $bookingdata->cancellation_charge }}%)</td>
                                    <td class="bd-sum-value">{{ getPriceFormat($bookingdata->cancellation_charge_amount) ?? getPriceFormat(0) }}</td>
                                </tr>
                                @if($bookingdata->advance_paid_amount > 0)
                                    @php
                                        $refundamount = $bookingdata->advance_paid_amount - $bookingdata->cancellation_charge_amount;
                                    @endphp
                                    @if($refundamount > 0)
                                        <tr>
                                            <td class="bd-sum-label">{{ __('messages.refund_amount') }}</td>
                                            <td class="bd-sum-value">{{ getPriceFormat($refundamount) ?? getPriceFormat(0) }}</td>
                                        </tr>
                                    @endif
                                @endif
                            @endif
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bottom-section">
            <h4 style="margin-bottom: 8px;">{{ __('landingpage.terms_conditions') }}</h4>
            <p style="margin:8px 0; font-size: 14px;">Payment is due upon receipt. By making a booking, you agree to our service terms, including payment
                policies, warranties, and liability limitations. Cancellations within 24 hours of the service may
                incur
                a fee. Any issues with workmanship are covered under our 30-day warranty. Contact us for details at
                <a href="mailto:{{ $generaldata->inquriy_email ?? 'support@handyman.com' }}" style="text-decoration: none; color: #5F60B9;">{{ $generaldata->inquriy_email ?? 'support@handyman.com' }}</a>
            </p>
        </div>
        <footer style="margin-top: 8px;">
            <div style="display: inline; vertical-align: middle; margin-right: 10px;">
                <h5 style="display: inline;">For more information, visit our website:</h5>
                <a href="{{ $generaldata->website ?? '#' }}" style="color: #5F60B9;">{{ $generaldata->website ?? 'www.handyman.com' }}</a>
                <h5 style="display: block; margin: 8px 0 0;">{{ $app->site_copyright ?? '© 2024 All Rights Reserved by IQONIC Design' }}</h5>
            </div>
        </footer>
    </div>
</body>

</html>
