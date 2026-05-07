@extends('landing-page.layouts.default')

@section('after_head')
    <style>
        /* Critical CSS - Load immediately to prevent FOUC */
        .shop-detail-top {
            opacity: 0;
            transition: opacity 0.2s ease-in;
        }
        .shop-detail-top.styles-loaded {
            opacity: 1;
        }
        .shop-detail-top .shop-image-wrapper {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            height: 180px;
        }
        .shop-detail-top .shop-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .shop-detail-top .status-badge-top {
            position: absolute;
            top: 12px;
            right: 12px;
            background: var(--bs-success);
            color: #fff;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            z-index: 10;
        }
        .shop-detail-top .status-badge-top.closed {
            background: var(--bh-closed-color);
        }
        .shop-detail-top .status-dot-top {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #fff;
            display: inline-block;
        }
        .shop-detail-top .today-status-card {
            background: var(--bh-card-bg);
            color: var(--bh-text-color);
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--bh-card-border);
        }
       
        .shop-detail-top .currently-open-badge {
            display: inline-block;
            background: var(--success-color);
            color: #fff;
            padding: 3px 8px;
            border-radius: 16px;
            font-size: 9px;
            font-weight: 700;
            margin-top: 4px;
        }
        .shop-detail-top .hours-bullet {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .shop-detail-top .hours-bullet.open {
            background-color: var(--bh-open-color);
        }
        .shop-detail-top .hours-bullet.closed {
            background-color: var(--bh-closed-color);
        }
        .shop-detail-top .about-section {
            background-color: var(--bh-card-bg);
            color: var(--bh-text-color);
            padding: 10px;
            border-radius: 8px;
            border-left: 3px solid var(--bs-primary);
        }
        /* =============================================
           Business Hours Card - CSS Variables
           ============================================= */
        :root {
            --bh-card-bg: var(--bs-white);
            --bh-card-border: var(--bs-border-color);
            --bh-header-bg: var(--bs-tertiary-bg);
            --bh-row-border: var(--bs-border-color);
            --bh-text-color: var(--bs-body-color);
            --bh-text-muted: var(--bs-gray);
            --bh-open-color: var(--bs-primary);
            --bh-closed-color: var(--bs-danger);
            --bh-break-color: var(--bs-gray);
            --bh-col-header-color: var(--bs-body-color);
            --bh-today-highlight: rgba(var(--bs-primary-rgb), 0.06);
            --bh-day-color: var(--bh-text-color);
            --bh-hours-time-color: var(--bh-open-color);
        }
        [data-bs-theme="dark"] {
            /* Hours table: match first image card style (Member Since / value colors, background) */
            --bh-card-bg: var(--bs-light-bg-subtle);
            --bh-card-border: var(--bs-border-color);
            --bh-header-bg: var(--bs-light-bg-subtle);
            --bh-row-border: var(--bs-border-color);
            --bh-text-color: var(--bs-body-color);
            --bh-text-muted: var(--bs-secondary-text-emphasis);
            --bh-open-color: var(--bs-primary);
            --bh-closed-color: var(--bs-danger-text-emphasis);
            --bh-break-color: var(--bs-secondary-text-emphasis);
            --bh-col-header-color: var(--bs-secondary-text-emphasis);
            --bh-today-highlight: rgba(var(--bs-primary-rgb), 0.12);
            /* Days column: same as Member Since label (muted bluish-purple) */
            --bh-day-color: var(--bs-link-color);
            /* Hours column times: same as value text (light gray) */
            --bh-hours-time-color: var(--bs-body-color);
        }

        /* Business Hours Card Wrapper */
        .bh-card {
            background: var(--bs-body-bg);
            border: var(--bs-border-width) var(--bs-border-style) var(--bs-border-color) !important;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }
        .bh-card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 16px 20px 10px;
            color: var(--bh-text-color);
            font-weight: 700;
            font-size: 0.95rem;
        }
        .bh-card-header i {
            color: var(--bs-primary);
            font-size: 1rem;
        }

        /* Column Header Row */
        .bh-col-header {
            display: flex;
            align-items: center;
            background: rgba(var(--bs-light-rgb));
            padding: 8px 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--bh-day-color);
            border-top: 1px solid var(--bh-row-border);
            border-bottom: 1px solid var(--bh-row-border);
        }

        /* Data Rows */
        .bh-rows-wrapper {
            padding: 0 20px 8px;
        }
        .bh-row {
            display: flex;
            align-items: flex-start;
            padding: 10px 0;
            border-bottom: 1px solid var(--bh-row-border);
            gap: 15px;
            color: var(--bh-text-color);
            font-size: 0.875rem;
        }
        .bh-row:last-child {
            border-bottom: none;
        }
        .bh-row.bh-today {
            background: var(--bh-today-highlight);
            margin: 0 -20px;
            padding: 10px 20px;
            border-radius: 6px;
        }

        /* Columns */
        .bh-col-day {
            flex: 0 0 90px;
            font-weight: 600;
            color: var(--bh-day-color);
        }
        .bh-col-hours {
            flex: 1 1 130px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 6px;
            flex-wrap: wrap;
        }
        .bh-col-break {
            flex: 1 1 120px;
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            flex-wrap: wrap;
            gap: 4px;
            color: var(--bh-hours-time-color);
        }
        .bh-col-header .bh-col-day,
        .bh-col-header .bh-col-hours,
        .bh-col-header .bh-col-break {
            flex: 0 0 90px;
        }
        .bh-col-header .bh-col-hours {
            flex: 1 1 130px;
            text-align: center;
        }
        .bh-col-header .bh-col-break {
            flex: 1 1 120px;
            text-align: center;
            color: var(--bh-day-color);
        }

        /* Status Indicators */
        .bh-bullet {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
            margin-top: 5px;
        }
        .bh-bullet.open  { background-color: var(--bh-open-color); }
        .bh-bullet.closed { background-color: var(--bh-closed-color); }
        .bh-open-text  { color: var(--bh-hours-time-color); font-weight: 500; }
        .bh-closed-text { color: var(--bh-closed-color); font-weight: 500; }
        .bh-no-break { color: var(--bh-closed-color); font-style: italic; }
        .bh-break-dash { color: var(--bh-hours-time-color); }
        .bh-break-item { color: var(--bh-hours-time-color); white-space: nowrap; }
        .bh-break-sep { color: var(--bh-hours-time-color); }

        /* Today Badge */
        .bh-today-badge {
            display: inline-block;
            background: var(--bs-primary);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 10px;
            margin-left: 5px;
            vertical-align: middle;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* Empty State */
        .bh-empty {
            text-align: center;
            padding: 24px 20px;
            color: var(--bh-text-muted);
            font-size: 0.875rem;
        }

        /* Mobile Responsive */
        @media (max-width: 575.98px) {
            .bh-card-header { padding: 14px 15px 8px; }
            .bh-col-header  { padding: 7px 15px; font-size: 0.7rem; }
            .bh-rows-wrapper { padding: 0 15px 6px; }
            .bh-row {
                flex-direction: column;
                gap: 4px;
                padding: 10px 0;
            }
            .bh-row.bh-today {
                margin: 0 -15px;
                padding: 10px 15px;
            }
            .bh-col-day   { flex: none; font-size: 0.875rem; }
            .bh-col-hours { flex: none; font-size: 0.8rem; }
            .bh-col-break { flex: none; font-size: 0.8rem; }
            .bh-col-header { display: none; }
            .bh-row::before {
                content: attr(data-day);
                display: none;
            }
        }
        .shop-detail-top .provider-image-wrapper {
            width: 100%;
            max-width: 260px;
        }
        .shop-detail-top .provider-image {
            height: 280px;
            object-position: center;
        }
        .shop-detail-top .font-size-small {
            font-size: 0.875rem;
        }
        .shop-detail-top .swiper-wrapper-initial {
            transform: translate3d(-349.5px, 0px, 0px);
            transition-duration: 0ms;
        }
        .shop-detail-top .swiper-slide-initial {
            width: 319.5px;
            margin-right: 30px;
        }
        .shop-detail-top .shop-meta-section {
            background-color: var(--bh-card-bg);
            color: var(--bh-text-color);
            padding: 12px;
            border-radius: 8px;
            border: 1px solid var(--bh-card-border);
        }
        /* Contact card and left column cards - use theme vars for dark mode */
        .shop-detail-top .card {
            background: var(--bs-body-bg);
            border: var(--bs-border-width) var(--bs-border-style) var(--bs-border-color) !important;
        }
        .shop-detail-top .card-body {
            color: var(--bh-text-color);
        }
        .shop-detail-top .card .text-muted {
            color: var(--bh-text-muted) !important;
        }
        .shop-detail-top .card a.text-body {
            color: var(--bh-text-color) !important;
        }
        .shop-detail-top .card a:not(.btn):hover {
            opacity: 0.9;
        }
        .shop-detail-top .currently-closed-badge {
            display: inline-block;
            background: var(--bh-closed-color);
            color: #fff;
            padding: 3px 8px;
            border-radius: 16px;
            font-size: 9px;
            font-weight: 700;
            margin-top: 4px;
        }
    </style>
    <script>
        // Prevent FOUC - show content once styles are loaded
        (function() {
            function showContent() {
                var shopDetailTop = document.querySelector('.shop-detail-top');
                if (shopDetailTop) {
                    shopDetailTop.classList.add('styles-loaded');
                    return true;
                }
                return false;
            }
            
            // Try immediately
            if (!showContent()) {
                // If element doesn't exist yet, wait for DOM
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', showContent);
                } else {
                    // DOM already loaded, try again after a short delay
                    setTimeout(showContent, 10);
                }
            }
            
            // Fallback: ensure visibility after all resources load
            window.addEventListener('load', function() {
                showContent();
            });
        })();
    </script>
@endsection

@section('content')
    @php
        // Get time format from admin settings (passed from controller)
        // Use the exact format saved by admin in database
        $adminTimeFormat = $timeFormatDisplay ?? ($date_time->time_format ?? 'H:i');
        
        // Build time formats array based on admin's configured format
        $timeFormats = [];
        // Add the admin's configured format first (highest priority)
        if (!empty($adminTimeFormat)) {
            $timeFormats[] = $adminTimeFormat;
        }
        // Add common variations as fallback
        $timeFormats = array_merge($timeFormats, ['H:i:s', 'H:i', 'g:i A', 'g:i:s A', 'h:i A', 'h:i:s A', 'G:i', 'G:i:s']);
        // Remove duplicates while preserving order
        $timeFormats = array_values(array_unique($timeFormats));
        
        // Get timezone from admin settings
        $targetTimezone = isset($date_time->time_zone) ? trim((string) $date_time->time_zone) : 'UTC';
        
        $currentDayKey = strtolower(\Carbon\Carbon::now($targetTimezone)->format('l'));
        $todayHours = $shopHoursOrdered->firstWhere('day', $currentDayKey);
        $now = \Carbon\Carbon::now($targetTimezone);
        $isCurrentlyOpen = false;
        $isOpenToday = false;
        
        // Check if shop is open today (not a holiday and has hours)
        if ($todayHours && !$todayHours['is_holiday'] && $todayHours['start_time'] && $todayHours['end_time']) {
            $isOpenToday = true;
            
            // Parse start and end times using admin's configured format first
            $startTime = null;
            $endTime = null;
            
            foreach ($timeFormats as $format) {
                try {
                    $startTime = \Carbon\Carbon::createFromFormat($format, $todayHours['start_time'], $targetTimezone);
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // If format parsing failed, use parse() which handles various formats automatically
            if (!$startTime) {
                try {
                    $startTime = \Carbon\Carbon::parse($todayHours['start_time'], $targetTimezone);
                } catch (\Exception $e) {
                    $startTime = null;
                }
            }
            
            foreach ($timeFormats as $format) {
                try {
                    $endTime = \Carbon\Carbon::createFromFormat($format, $todayHours['end_time'], $targetTimezone);
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            // If format parsing failed, use parse() which handles various formats automatically
            if (!$endTime) {
                try {
                    $endTime = \Carbon\Carbon::parse($todayHours['end_time'], $targetTimezone);
                } catch (\Exception $e) {
                    $endTime = null;
                }
            }
            
            if ($startTime && $endTime) {
                // Normalize all times to today's date for comparison
                $startTime->setDate($now->year, $now->month, $now->day);
                $endTime->setDate($now->year, $now->month, $now->day);
                
                // Ensure all times are in the same timezone
                $startTime->setTimezone($now->timezone);
                $endTime->setTimezone($now->timezone);
                
                // Handle case where end time is next day (e.g., 23:00 - 02:00)
                if ($endTime->lessThan($startTime)) {
                    $endTime->addDay();
                }
                
                // Check if current time is within business hours
                // Use lessThan (not lessThanOrEqualTo) for end time - shop closes at end time, so it's closed at end time
                if ($now->greaterThanOrEqualTo($startTime) && $now->lessThan($endTime)) {
                    // Check if current time is not within any break period
                    $isInBreak = false;
                    if (!empty($todayHours['breaks']) && is_array($todayHours['breaks'])) {
                        foreach ($todayHours['breaks'] as $break) {
                            if (!empty($break['start']) && !empty($break['end'])) {
                                $breakStart = null;
                                $breakEnd = null;
                                
                                // Try different time formats for break times
                                foreach ($timeFormats as $format) {
                                    try {
                                        $breakStart = \Carbon\Carbon::createFromFormat($format, $break['start']);
                                        break;
                                    } catch (\Exception $e) {
                                        continue;
                                    }
                                }
                                
                                // Fallback to parse() if format parsing failed
                                if (!$breakStart) {
                                    try {
                                        $breakStart = \Carbon\Carbon::parse($break['start']);
                                    } catch (\Exception $e) {
                                        continue;
                                    }
                                }
                                
                                foreach ($timeFormats as $format) {
                                    try {
                                        $breakEnd = \Carbon\Carbon::createFromFormat($format, $break['end']);
                                        break;
                                    } catch (\Exception $e) {
                                        continue;
                                    }
                                }
                                
                                // Fallback to parse() if format parsing failed
                                if (!$breakEnd) {
                                    try {
                                        $breakEnd = \Carbon\Carbon::parse($break['end']);
                                    } catch (\Exception $e) {
                                        continue;
                                    }
                                }
                                
                                if (!$breakStart || !$breakEnd) {
                                    continue;
                                }
                                
                                // Normalize break times to today's date
                                $breakStart->setDate($now->year, $now->month, $now->day);
                                $breakEnd->setDate($now->year, $now->month, $now->day);
                                
                                // Handle break spanning midnight
                                if ($breakEnd->lessThan($breakStart)) {
                                    $breakEnd->addDay();
                                }
                                
                                if ($now->greaterThanOrEqualTo($breakStart) && $now->lessThanOrEqualTo($breakEnd)) {
                                    $isInBreak = true;
                                    break;
                                }
                            }
                        }
                    }
                    
                    // Shop is currently open if not in break period
                    $isCurrentlyOpen = !$isInBreak;
                }
            }
        }
        
        $fullAddress = trim(implode(', ', array_filter([$shop->address ?? '', $shop->city->name ?? null, $shop->state->name ?? null, $shop->country->name ?? null])));
        $mapsUrl = (!empty($shop->lat) && !empty($shop->long))
            ? 'https://www.google.com/maps?q=' . rawurlencode($shop->lat . ',' . $shop->long)
            : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($fullAddress);
        // $aboutText = !empty(trim($shop->provider->description ?? '')) ? $shop->provider->description : null;
    @endphp
    <div class="section-padding position-relative px-0 shop-detail-top">
        <div class="container">
            <div class="row g-4">
                {{-- LEFT COLUMN: Shop Summary Card --}}
                <div class="col-12 col-lg-4">
                    <div class="card rounded-3 shadow-sm mb-3">
                        <div class="card-body p-3">
                            {{-- Shop Image with Open Badge --}}
                            <div class="shop-image-wrapper mb-3">
                                <img src="{{ getSingleMedia($shop, 'shop_attachment', null) }}"
                                    alt="{{ $shop->translate('shop_name') }}" loading="lazy">
                                @if($isCurrentlyOpen)
                                    <div class="status-badge-top">
                                        <span class="status-dot-top"></span>
                                        {{ __('messages.open') }}
                                    </div>
                                @else
                                    <div class="status-badge-top closed">
                                        <span class="status-dot-top"></span>
                                        {{ __('messages.closed') }}
                                    </div>  
                                @endif
                            </div>

                            {{-- Shop Name --}}
                            <h4 class="h4 mb-3">{{ $shop->translate('shop_name') }}</h4>

                            {{-- 
                            @if($aboutText)
                                <div class="about-section bg-light mb-3">
                                    <p class="m-0 font-size-small"><strong>{{ __('landingpage.about_shop') }}:</strong> {{ $aboutText }}</p>
                                </div>
                            @endif --}}

                            {{-- Since and Manager --}}
                            <div class="shop-meta-section bg-light d-flex flex-column gap-2 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary">Shop Since</span>
                                    <span class="m-0 font-size-small">{{ date("$date_time->date_format", strtotime($shop->provider->created_at)) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary">{{ __('messages.shop_managed_by') }}</span>
                                    <span class="m-0 font-size-small">{{ $shop->provider->display_name }}</span>
                                </div>
                            </div>

                            {{-- Today Status Card --}}
                            <div class="today-status-card bg-light mb-3">
                                <div class="small opacity-75 mb-2">{{ __('messages.today') }} ({{ ucfirst($currentDayKey) }})</div>
                                @if($todayHours && !$todayHours['is_holiday'] && $todayHours['start_time'] && $todayHours['end_time'])
                                    @if($isCurrentlyOpen)
                                        <div class="fw-bold fs-5 mb-1">{{ __('messages.open') }}</div>
                                        <div class="small mb-2">{{ $todayHours['start_time'] }} – {{ $todayHours['end_time'] }}</div>
                                        <!-- <span class="currently-open-badge">{{ __('messages.currently_open') }}</span> -->
                                    @else
                                        <div class="fw-bold fs-5 mb-1">{{ __('messages.closed') }}</div>
                                        <div class="small mb-2">{{ $todayHours['start_time'] }} – {{ $todayHours['end_time'] }}</div>
                                        <!-- <div class="currently-closed-badge opacity-75">{{ __('messages.closed') }}</div> -->
                                    @endif
                                @else
                                    <div class="fw-bold fs-5 mb-1">{{ __('messages.closed') }}</div>
                                    <div class="small opacity-75">—</div>
                                @endif
                            </div>

                            {{-- Call Now Button --}}
                            <a href="tel:{{ $shop->contact_country_code }}{{ $shop->contact_number }}" class="btn btn-primary w-100 rounded-3">
                                <i class="fas fa-phone me-2"></i>{{ __('messages.call_now') }}
                            </a>
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN: Contact & Business Hours --}}
                <div class="col-12 col-lg-8">
                    <div class="d-flex flex-column gap-1">
                        {{-- Contact Information Card --}}
                        <div class="card rounded-3 shadow-sm">
                            <div class="card-body p-3 p-md-4">
                                <h6 class="mb-3 d-flex align-items-center fw-bold">
                                    <i class="fas fa-user-alt me-2 text-primary"></i>
                                    {{ __('messages.contact_information') }}
                                </h6>
                                <div class="d-flex flex-column gap-3">
                                    {{-- Phone and Email in same row --}}
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="fas fa-phone text-primary mt-1"></i>
                                                <div class="flex-grow-1">
                                                    <h6  class="mb-2 text-primary font-size-small">{{ __('messages.phone') }}</h6>
                                                      <p class="m-0 font-size-small"> {{ $shop->contact_country_code }} {{ $shop->contact_number }} </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start gap-2">
                                                <i class="fas fa-envelope text-primary mt-1"></i>
                                                <div class="flex-grow-1">
                                                    <h6  class="mb-2 text-primary font-size-small">{{ __('messages.email') }}</h6>
                                                    <p class="m-0 font-size-small">  {{ $shop->email }} </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Address --}}
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="fas fa-map-marker-alt text-primary mt-1"></i>
                                        <div class="flex-grow-1">
                                            <h6  class="mb-2 text-primary font-size-small">{{ __('messages.address') }}</h6>
                                            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="text-body text-decoration-none">
                                                {{ $fullAddress }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Business Hours Card --}}
                        <div class="bh-card">
                            {{-- Card Title --}}
                            <div class="bh-card-header">
                                <i class="fas fa-clock"></i>
                                <span>{{ __('messages.business_hours') }}</span>
                            </div>

                            @if($shopHoursOrdered->isNotEmpty())
                                {{-- Column Headers (hidden on mobile via CSS) --}}
                                <div class="bh-col-header">
                                    <div class="bh-col-day">{{ __('messages.day') }}</div>
                                    <div class="bh-col-hours">{{ __('messages.hours') }}</div>
                                    <div class="bh-col-break">{{ __('messages.break') }}</div>
                                </div>

                                {{-- Hours Rows --}}
                                <div class="bh-rows-wrapper">
                                    @foreach($shopHoursOrdered as $index => $h)
                                        @php $isToday = ($h['day'] === $currentDayKey); @endphp
                                        <div class="bh-row {{ $isToday ? 'bh-today' : '' }}">
                                            {{-- Day Name --}}
                                            <div class="bh-col-day">
                                                {{ ucfirst($h['day']) }}
                                                @if($isToday)
                                                    <span class="bh-today-badge">{{ __('messages.today') }}</span>
                                                @endif
                                            </div>

                                            {{-- Working Hours --}}
                                            <div class="bh-col-hours">
                                                <span class="d-md-none text-muted small me-2">{{ __('messages.hours') }}:</span>
                                                @if($h['is_holiday'] || (!$h['start_time'] || !$h['end_time']))
                                                    <span class="bh-bullet closed"></span>
                                                    <span class="bh-closed-text">{{ __('messages.closed') }}</span>
                                                @else
                                                    <span class="bh-bullet open"></span>
                                                    <span class="bh-open-text">{{ $h['start_time'] }} – {{ $h['end_time'] }}</span>
                                                @endif
                                            </div>

                                            {{-- Break Times --}}
                                            <div class="bh-col-break">
                                                <span class="d-md-none text-muted small me-2">{{ __('messages.break') }}:</span>
                                                @if($h['is_holiday'] || (!$h['start_time'] || !$h['end_time']))
                                                    <span class="bh-break-dash">—</span>
                                                @elseif(!empty($h['breaks']) && count($h['breaks']) > 0)
                                                    @foreach($h['breaks'] as $breakIndex => $b)
                                                        <span class="bh-break-item">{{ $b['start'] }} – {{ $b['end'] }}</span>
                                                        @if($breakIndex < count($h['breaks']) - 1)
                                                            <span class="bh-break-sep">•</span>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <span class="bh-no-break">{{ __('landingpage.no_breaks') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="bh-empty">
                                    <i class="fas fa-calendar-times mb-2 d-block" style="font-size:1.5rem; opacity:0.4;"></i>
                                    {{ __('messages.business_hours_not_available') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 mt-lg-4 mt-3 mb-4">
                    <h4 class="mb-3">{{ __('landingpage.about_provider') }}</h4>
                    <div class="p-3 p-lg-4 border rounded-3 about-provider-box">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 gap-lg-4">
                            <div class="flex-shrink-0 mx-auto mx-lg-0 provider-image-wrapper">
                                <img src="{{ getSingleMedia($shop->provider, 'profile_image', null) }}" alt="provider-user"
                                    class="img-fluid object-fit-cover rounded-3 w-100 provider-image">
                            </div>
                            <div class="flex-grow-1">
                                <div class="mb-3 pb-3 border-bottom">
                                    <a href="{{ route('provider.detail', $shop->provider->id ?? '') }}"
                                        class="text-decoration-none">
                                        <h4 class="mb-1 text-capitalize">
                                            {{ $shop->provider->display_name ?? __('messages.provider_name_not_available') }}
                                        </h4>
                                    </a>
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                                        <div class="star-rating font-size-small">
                                            <rating-component :readonly="true" :showrating="false"
                                                :ratingvalue="{{ $shop->provider->providers_service_rating ?? 0 }}" />
                                        </div>
                                        <h5 class="lh-sm mb-0 font-size-small">
                                            {{ round($shop->provider->providers_service_rating ?? 0, 1) }}</h5>
                                        <a href="{{ route('rating.all', ['provider_id' => $shop->provider->id]) }}"
                                            class="text-decoration-none font-size-small">
                                            ({{ $shop->provider->total_service_rating ?? 0 }}
                                            {{ __('messages.reviews') }})
                                        </a>
                                    </div>
                                    @if (!empty($shop->provider->description))
                                        <p class="mb-0 text-primary font-size-small">
                                            {{ $shop->provider->description }}</p>
                                    @endif
                                </div>
                                <div class="row g-3">
                                    <div class="mt-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <div class="rounded p-3 bg-light">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fas fa-phone text-primary"></i>
                                                            <span class="font-size-small">
                                                                {{ $shop->provider->contact_number }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <div class="rounded p-3 bg-light">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fas fa-envelope text-primary"></i>
                                                            <span class="font-size-small">{{ $shop->provider->email }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-4">
                                        <div class="bg-light p-3 rounded h-100">
                                            <h6 class="mb-2 text-primary font-size-small">
                                                {{ __('landingpage.member_since') }}:</h6>
                                            <p class="m-0 font-size-small">
                                                {{ date("$date_time->date_format", strtotime($shop->provider->created_at)) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-4">
                                        <div class="bg-light p-3 rounded h-100">
                                            <h6 class="mb-2 text-primary font-size-small">
                                                {{ __('landingpage.complet_project') }}:</h6>
                                            <p class="m-0 font-size-small">{{ $completed_services }}
                                                {{ __('landingpage.msg_complete_project') }}</p>
                                        </div>
                                    </div>
                                    @php
                                        $knownLanguages = [];
                                        if (!empty($shop->provider->known_languages)) {
                                            $decoded = is_array($shop->provider->known_languages)
                                                ? $shop->provider->known_languages
                                                : json_decode($shop->provider->known_languages, true);
                                            $knownLanguages = is_array($decoded) ? array_filter($decoded) : [];
                                        }
                                    @endphp
                                    @if (!empty($knownLanguages))
                                    <div class="col-sm-6 col-lg-4">
                                        <div class="bg-light p-3 rounded h-100">
                                            <h6 class="mb-2 text-primary font-size-small">
                                                {{ __('landingpage.languages') }}:</h6>
                                            <p class="m-0 font-size-small">{{ implode(', ', $knownLanguages) }}</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            @if($shop->services->isNotEmpty())
                <div class="row align-items-center pt-3">
                    <div class="col-sm-9">
                        <h4 class="text-capitalize mb-0">{{ __('messages.services') }}</h4>
                    </div>
                    @if(count($shop->services) >= 4)
                        <div class="col-sm-3 mt-sm-0 mt-5 text-sm-end">
                            <a href="{{ route('service.list', ['shop_id' => $shop->id]) }}">{{ __('messages.view_all') }}</a>
                        </div>
                    @endif
                    <div class="swiper-container">
                        <div class="swiper-wrapper swiper-wrapper-initial">
                            @foreach ($shop->services as $service)
                                <div class="swiper-slide swiper-slide-initial">
                                    <div class="mt-5 justify-content-center service-slide-items-4">
                                        <div class="col">
                                            <div class="service-box-card bg-light rounded-3 mb-5">
                                                <div class="iq-image position-relative">
                                                    <a href="{{ route('service.detail', ['id' => $service->id, 'shop_id' => $shop->id]) }}"
                                                        class="service-img">
                                                        <img src="{{ getSingleMedia($service, 'service_attachment', null) }}"
                                                            alt="service"
                                                            class="service-img w-100 object-cover img-fluid rounded-3">
                                                    </a>
                                                    @php
                                                        $isFavourite = false;
                                                        if (
                                                            auth()->check() &&
                                                            $service->relationLoaded('getUserFavouriteService') &&
                                                            $service->getUserFavouriteService->isNotEmpty()
                                                        ) {
                                                            $isFavourite = $service->getUserFavouriteService->contains(
                                                                'user_id',
                                                                auth()->id(),
                                                            );
                                                        }
                                                    @endphp

                                                    @if (auth()->check() && auth()->user()->hasRole('user'))
                                                        @if (!$isFavourite)
                                                            <form method="POST" class="favoriteForm">
                                                                @csrf
                                                                <input type="hidden" name="service_id" class="service_id"
                                                                    value="{{ $service->id }}"
                                                                    data-service-id="{{ $service->id }}">
                                                                @if (!empty(auth()->user()))
                                                                    <input type="hidden" name="user_id" id="user_id"
                                                                        value="{{ Auth::user()->id }}">
                                                                @endif
                                                                <button type="button"
                                                                    class="btn-link serv-whishlist text-primary save_fav">
                                                                    <svg width="12" height="13" viewBox="0 0 12 13"
                                                                        fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z"
                                                                            stroke="currentColor" stroke-width="1.5"
                                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                                        <path
                                                                            d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098"
                                                                            stroke="currentColor" stroke-width="1.5"
                                                                            stroke-linecap="round" stroke-linejoin="round" />
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <form method="POST" class="favoriteForm">
                                                                @csrf
                                                                <input type="hidden" name="service_id" class="service_id"
                                                                    value="{{ $service->id }}"
                                                                    data-service-id="{{ $service->id }}">
                                                                @if (!empty(auth()->user()))
                                                                    <input type="hidden" name="user_id" id="user_id"
                                                                        value="{{ Auth::user()->id }}">
                                                                @endif
                                                                <button type="button"
                                                                    class="btn-link serv-whishlist text-primary delete_fav">
                                                                    <svg width="12" height="13" viewBox="0 0 12 13"
                                                                        fill="currentColor"
                                                                        xmlns="http://www.w3.org/2000/svg">
                                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                                            d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z"
                                                                            stroke="currentColor" stroke-width="1.5"
                                                                            stroke-linecap="round" stroke-linejoin="round">
                                                                        </path>
                                                                        <path
                                                                            d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098"
                                                                            stroke="currentColor" stroke-width="1.5"
                                                                            stroke-linecap="round" stroke-linejoin="round">
                                                                        </path>
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    @else
                                                        <form method="GET" class="favoriteForm"
                                                            action="{{ route('user.login') }}">
                                                            @csrf
                                                            <button type="submit"
                                                                class="btn-link serv-whishlist text-primary">
                                                                <svg width="12" height="13" viewBox="0 0 12 13"
                                                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                                                        d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                                    <path
                                                                        d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098"
                                                                        stroke="currentColor" stroke-width="1.5"
                                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                                <a href="{{ route('service.detail', $service->id) }}"
                                                    class="service-heading mt-4 d-block p-0">
                                                    <h5 class="service-title font-size-18 line-count-2">{{ $service->name }}
                                                    </h5>
                                                </a>
                                                <ul class="list-inline p-0 mt-1 mb-0 price-content">
                                                    <li
                                                        class="text-primary fw-500 d-inline-block position-relative font-size-18">
                                                        <span>{{ getPriceFormat($service->price) }}</span></li>
                                                    <li class="d-inline-block fw-500 position-relative service-price">
                                                        ({{ $service->duration }} Min)</li>
                                                </ul>
                                                <div class="mt-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="{{ getSingleMedia($service->providers, 'profile_image', null) }}"
                                                            alt="provider" class="img-fluid rounded-3 object-cover avatar-24">
                                                        <a href="{{ route('provider.detail', $service->providers->id) }}"
                                                            class="text-decoration-none">
                                                            <span
                                                                class="font-size-14 service-user-name">{{ $service->providers->display_name }}</span>
                                                        </a>
                                                    </div>

                                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                                        <div class="star-rating">
                                                            <rating-component :readonly="true" :showrating="false"
                                                                :ratingvalue="{{ $service->providers->providers_service_rating ?? 0 }}" />
                                                        </div>
                                                        <h6 class="lh-sm mb-0">
                                                            {{ round($service->providers->providers_service_rating ?? 0, 1) }}
                                                        </h6>
                                                        <a href="{{ route('rating.all', $service->providers->id) }}"
                                                            class="text-decoration-none">
                                                            ({{ $service->providers->total_service_rating ?? 0 }}
                                                            {{ __('messages.reviews') }})
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Swiper
            const swiper = new Swiper('.swiper-container', {
                loop: false,
                speed: 600,
                spaceBetween: 30,
                slidesPerView: 4,
                breakpoints: {
                    0: {
                        slidesPerView: 1
                    },
                    576: {
                        slidesPerView: 2
                    },
                    768: {
                        slidesPerView: 3
                    },
                    1200: {
                        slidesPerView: 4
                    },
                }
            });

            const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Handle Save Favourite
            document.addEventListener('click', function(e) {
                if (e.target.closest('.save_fav')) {
                    e.preventDefault();
                    const button = e.target.closest('.save_fav');
                    const form = button.closest('form');
                    const serviceId = form.querySelector('.service_id').dataset.serviceId;
                    const userId = document.getElementById('user_id').value;
                    fetch(`${baseUrl}/api/save-favourite`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                service_id: serviceId,
                                user_id: userId
                            })
                        })
                        .then(res => res.json())
                        .then(response => {
                            Swal.fire({
                                title: 'Added to Favourites',
                                text: response.message,
                                icon: 'success',
                                iconColor: '#5F60B9',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    button.classList.remove('save_fav');
                                    button.classList.add('delete_fav');
                                    button.innerHTML = `
                                    <svg width="12" height="13" viewBox="0 0 12 13" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                        <path d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>`;
                                }
                            });
                        });
                }
            });
            // Handle Delete Favourite
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete_fav')) {
                    e.preventDefault();
                    const button = e.target.closest('.delete_fav');
                    const form = button.closest('form');
                    const serviceId = form.querySelector('.service_id').dataset.serviceId;
                    const userId = document.getElementById('user_id').value;
                    fetch(`${baseUrl}/api/delete-favourite`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                service_id: serviceId,
                                user_id: userId
                            })
                        })
                        .then(res => res.json())
                        .then(response => {
                            Swal.fire({
                                title: 'Removed from Favourites',
                                text: response.message,
                                icon: 'success',
                                iconColor: '#5F60B9',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    button.classList.remove('delete_fav');
                                    button.classList.add('save_fav');
                                    button.innerHTML = `
                                    <svg width="12" height="13" viewBox="0 0 12 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>`;
                                }
                            });
                        });
                }
            });
        });
    </script>

@endsection
