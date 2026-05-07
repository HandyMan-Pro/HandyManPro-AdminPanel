<!DOCTYPE html>
<!-- Critical Dark Mode Handling -->
<script>
    (function() {
        if (localStorage.getItem('dark') === 'true') {
            document.write('<style>html { background: #1a1a1a !important; }</style>');
        }
    })();
</script>
@php
    $rtlLocales = ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa'];
    $dir = session()->has('dir') ? session()->get('dir') : (in_array(app()->getLocale(), $rtlLocales, true) ? 'rtl' : 'ltr');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ $dir }}">

<head>
    <!-- Immediate Dark Mode Styles -->
    <style>
        html[data-bs-theme="dark"] {
            background-color: #1a1a1a !important;
            color: #ffffff !important;
        }
        html[data-bs-theme="dark"] body {
            background-color: #1a1a1a !important;
            color: #ffffff !important;
        }
    </style>
    <script>
        if (localStorage.getItem('dark') === 'true') {
            document.documentElement.setAttribute('data-bs-theme', 'dark');
        }
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="baseUrl" content="{{ env('APP_URL') }}" />

    {{-- <title>{{ config('app.name', 'Laravel') }}</title> --}}

    @php
        $generalSetting = \DB::table('settings')->where('key', 'general-setting')->value('value');
        $siteName = $generalSetting ? json_decode($generalSetting, true)['site_name'] ?? null : null;

        // Get theme color from database
        $themeSetup = \DB::table('settings')->where('type', 'theme-setup')->where('key', 'theme-setup')->first();
        $themeData = $themeSetup ? json_decode($themeSetup->value, true) : null;
        $primaryColorFromDB = $themeData['primary_color'] ?? null;
    @endphp

    {{-- <title>{{ $siteName ?? 'Laravel' }}</title>

    <title>{{ $pageTitle ?? 'Default Title' }}</title> --}}

    <title>{{ $pageTitle ?? 'Default Title' }}</title>


    <script>
        const storedTheme = localStorage.getItem('data-bs-theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    </script>

    <!-- Dynamic Theme Colors -->
    <script>
        // Set primary color immediately on page load from database
        const savedPrimaryColorFromDB = @json($primaryColorFromDB);
        const savedPrimaryColorFromLS = localStorage.getItem('primaryColor');

        // Prioritize database value over localStorage
        const primaryColorToUse = savedPrimaryColorFromDB || savedPrimaryColorFromLS;

        if (primaryColorToUse) {
            const root = document.documentElement;

            // Convert HEX to RGB for primary-rgb
            const hex = primaryColorToUse.replace('#', '');
            const r = parseInt(hex.substring(0, 2), 16);
            const g = parseInt(hex.substring(2, 4), 16);
            const b = parseInt(hex.substring(4, 6), 16);

            // Set CSS variables for primary color
            root.style.setProperty('--bs-primary', primaryColorToUse);
            root.style.setProperty('--bs-primary-rgb', `${r}, ${g}, ${b}`);
            root.style.setProperty('--bs-primary-bg-subtle', `rgba(${r}, ${g}, ${b}, 0.09)`);
            root.style.setProperty('--bs-primary-border-subtle', `rgba(${r}, ${g}, ${b}, 0.09)`);
            root.style.setProperty('--bs-primary-hover-bg', `rgba(${r}, ${g}, ${b}, 0.75)`);
            root.style.setProperty('--bs-primary-hover-border', `rgba(${r}, ${g}, ${b}, 0.75)`);
            root.style.setProperty('--bs-primary-active-bg', `rgba(${r}, ${g}, ${b}, 0.75)`);
            root.style.setProperty('--bs-primary-active-border', `rgba(${r}, ${g}, ${b}, 0.75)`);

            // Update localStorage to match database value
            localStorage.setItem('primaryColor', primaryColorToUse);
        }
    </script>

    @include('partials._head') <!-- Your other head includes like CSS files -->
    <script src="https://www.gstatic.com/firebasejs/6.0.2/firebase.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>

<style>
    /* Override Snackbar action button color for better visibility */
    .snackbar-container .action {
        color: var(--bs-green-200, #90EE90) !important;
    }
    
    /* Fix Snackbar positioning to ensure it's always visible in viewport */
    .snackbar-container {
        position: fixed !important;
        z-index: 99999 !important;
        bottom: 20px !important;
        top: auto !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
    }
    
    .snackbar-pos.bottom-center {
        position: fixed !important;
        bottom: 20px !important;
        top: auto !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
    }

    /* Mirror intl-tel-input layout in RTL locales (Arabic, etc.) */
    html[dir="rtl"] .iti {
        width: 100%;
        direction: rtl;
    }

    html[dir="rtl"] .iti__flag-container {
        right: 0;
        left: auto;
    }

    html[dir="rtl"] .iti input,
    html[dir="rtl"] .iti input[type="tel"],
    html[dir="rtl"] .iti input[type="text"] {
        padding-right: 56px !important;
        padding-left: 12px !important;
        text-align: right;
    }

    html[dir="rtl"] .iti--separate-dial-code .iti__selected-flag {
        margin-right: 0;
        margin-left: 6px;
    }
     .iti {
            width: 100%;
        }

        html[dir="rtl"] .iti--separate-dial-code .iti__selected-flag {
            margin-left: 8px;
        }

        html[dir="rtl"] #contact_number {
            direction: ltr;
            text-align: right;
            unicode-bidi: isolate;
            padding-right: 94px !important;
            padding-left: 12px !important;
        }

        html[dir="rtl"] .iti__selected-dial-code {
            direction: ltr;
            unicode-bidi: isolate;
        }
</style>
</head>

<body class="" id="app">
    @include('partials._body') <!-- Your body content -->
    <script src="{{ asset('js/fslightbox.js') }}"></script>
</body>

{{-- <script>
    jQuery('.select2').select2({
         width: '100%'
    });
</script> --}}

</html>
