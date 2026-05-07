@extends('landing-page.layouts.default')

@section('before_head')
<!-- Leaflet CSS (local) -->
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
<!-- Leaflet MarkerCluster CSS (local) -->
<link rel="stylesheet" href="{{ asset('vendor/leaflet.markercluster/MarkerCluster.css') }}" />
<link rel="stylesheet" href="{{ asset('vendor/leaflet.markercluster/MarkerCluster.Default.css') }}" />
<!-- Select2 CSS (local) -->
<link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}" />
<style>
    /* Select2 theme compatibility for light/dark mode */
    .select2-container--default .select2-selection--single {
        background-color: #fff !important;
        color: var(--bs-body-color, #212529) !important;
        border: 1px solid #ddd !important;
        border-radius: 6px !important;
        min-height: 38px;
        transition: background 0.2s;
        box-shadow: none !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--bs-body-color, #212529) !important;
        background: transparent !important;
    }
    .select2-container--default .select2-dropdown {
        background-color: var(--bs-body-bg, #fff) !important;
        color: var(--bs-body-color, #212529) !important;
        border-radius: 0 0 6px 6px;
        border: 1px solid #ddd;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--bs-primary, #7c3aed) !important;
        color: #fff !important;
    }
    [data-bs-theme="dark"] .select2-container--default .select2-selection--single {
        background-color: var(--bs-body-bg, #181a1b) !important;
        color: var(--bs-body-color, #f8f9fa) !important;
        border: 1px solid #333 !important;
    }
    [data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--bs-body-color, #f8f9fa) !important;
    }
    [data-bs-theme="dark"] .select2-container--default .select2-dropdown {
        background-color: var(--bs-body-bg, #181a1b) !important;
        color: var(--bs-body-color, #f8f9fa) !important;
        border: 1px solid #333;
    }
    [data-bs-theme="dark"] .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--bs-primary, #7c3aed) !important;
        color: #fff !important;
    }
    /* ── Page layout ─────────────────────────────────────────── */
    .nearby-page {
        padding: 40px 0 60px;
    }
    .nearby-page-inner {
        max-width: 1440px;
        margin: 0 auto;
        padding: 0 32px;
    }
    .nearby-page-title {
        font-size: 22px;
        font-weight: 700;
        color: var(--bs-body-color);
        margin-bottom: 6px;
    }
    .nearby-page-subtitle {
        font-size: 14px;
        color: var(--bs-secondary-color, #888);
        margin-bottom: 28px;
    }
    /* ── Controls bar ───────────────────────────────────────── */
    .map-filter-bar {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 20px;
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-radius: 10px;
        padding: 12px 16px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    }
    /* Search input wrapper */
    .filter-search-wrap {
        position: relative;
        flex: 1 1 200px;
        min-width: 180px;
    }
    .filter-search-wrap .search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
        pointer-events: none;
    }
    #provider-search {
        padding-left: 34px;
        border-radius: 6px;
        height: 38px;
    }
    #provider-search:focus {
        box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.15);
    }
    /* Autocomplete dropdown */
    .search-autocomplete {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 9999;
        background: var(--bs-body-bg, #fff);
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-radius: 8px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        max-height: 230px;
        overflow-y: auto;
        display: none;
    }
    .search-autocomplete .sa-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 14px;
        cursor: pointer;
        font-size: 13px;
        transition: background 0.15s;
        border-bottom: 1px solid var(--bs-border-color, #f0f0f0);
    }
    .search-autocomplete .sa-item:last-child { border-bottom: none; }
    .search-autocomplete .sa-item:hover,
    .search-autocomplete .sa-item.active { background: var(--bs-primary); color: #fff; }
    .search-autocomplete .sa-item img {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        background: #eee;
    }
    .search-autocomplete .sa-item .sa-name { flex: 1; font-weight: 500; }
    .search-autocomplete .sa-item .sa-dist { font-size: 11px; color: #888; }
    .search-autocomplete .sa-item:hover .sa-dist,
    .search-autocomplete .sa-item.active .sa-dist { color: rgba(255,255,255,0.8); }
    .search-autocomplete .sa-empty { padding: 12px 14px; color: #999; font-size: 13px; text-align: center; }
    /* Category select */
    .filter-category-wrap {
        flex: 0 0 auto;
        min-width: 170px;
    }
    .filter-category-wrap .select2-container { width: 100% !important; }
    /* Radius group */
    .radius-group {
        display: flex;
        align-items: center;
        gap: 10px;
        border-left: 1px solid var(--bs-border-color, #dee2e6);
        padding-left: 14px;
        flex-shrink: 0;
        font-size: 14px;
    }
    .radius-group label {
        margin: 0;
        font-weight: 600;
        white-space: nowrap;
        color: var(--bs-body-color);
    }
    .radius-group input[type=range] {
        cursor: pointer;
        accent-color: var(--bs-primary);
        width: 130px;
        vertical-align: middle;
    }
    #radius-label {
        min-width: 52px;
        font-weight: 700;
        color: var(--bs-primary);
        text-align: right;
    }
    /* ── Map wrapper ────────────────────────────────────────── */
    .map-wrapper {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(0,0,0,0.12);
        border: 1px solid var(--bs-border-color, #e5e7eb);
    }
    #nearby-map {
        width: 100%;
        height: 680px;
        min-height: 400px;
        z-index: 1;
        display: block;
    }
    /* Provider popup - styled like service card */
    .provider-popup {
        min-width: 280px;
        max-width: 320px;
        font-family: inherit;
    }
    .provider-popup .pp-service-image {
        width: 100%;
        height: 160px;
        object-fit: cover;
        border-radius: 8px 8px 0 0;
        display: block;
    }
    .provider-popup .pp-category-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: var(--bs-primary);
        color: #fff;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .provider-popup .pp-content {
        padding: 16px;
        background: var(--bs-body-bg, #fff);
        border-radius: 0 0 8px 8px;
    }
    .provider-popup .pp-title {
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 8px;
        color: var(--bs-body-color, #333);
        line-height: 1.3;
    }
    .provider-popup .pp-price-row {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }
    .provider-popup .pp-price {
        font-weight: 700;
        font-size: 20px;
        color: var(--bs-primary);
    }
    .provider-popup .pp-rating-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--bs-border-color, #f0f0f0);
    }
    .provider-popup .pp-rating {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .provider-popup .pp-rating-value {
        color: #ffa000;
        font-weight: 600;
        font-size: 14px;
    }
    /* ── Distance label inside popup ──────────────────────────── */
    .provider-popup .pp-distance {
        font-size: 12px;
        color: var(--bs-secondary-color, #666);
    }
    .provider-popup .pp-btn {
        display: block;
        width: 100%;
        padding: 10px 20px;
        background: var(--bs-primary);
        color: #fff;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s ease;
    }
    .provider-popup .pp-btn:hover {
        background: var(--bs-primary);
        opacity: 0.9;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    #map-loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 2000;
        background: var(--bs-body-bg, #fff);
        padding: 18px 32px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        font-size: 15px;
        font-weight: 600;
        color: var(--bs-primary);
        display: none;
    }
</style>
@endsection

@section('content')
<section class="nearby-page">
    <div class="nearby-page-inner">

        <!-- Controls bar -->
        <div class="map-filter-bar">
            <!-- Provider name search -->
            <div class="filter-search-wrap">
                <span class="search-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/>
                    </svg>
                </span>
                <input type="text" id="provider-search" class="form-control"
                       placeholder="{{ __('messages.search_providers') }}" autocomplete="off">
                <div id="search-autocomplete" class="search-autocomplete"></div>
            </div>
            <!-- Category filter -->
            <div class="filter-category-wrap">
                <select id="category-filter" class="form-select">
                    <option value="">{{ __('messages.all') }} {{ __('messages.category') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <!-- Radius slider -->
            <div class="radius-group">
                <label for="radius-slider">{{ __('messages.radius') }}</label>
                <input type="range" id="radius-slider" min="1" max="{{ $maxRadius }}" value="{{ $initialRadius }}" step="1">
                <span id="radius-label">{{ $initialRadius }} {{ $distanceUnit }}</span>
            </div>
            <!-- Locate Me button -->
            <button id="locate-me-btn" type="button" title="{{ __('messages.nearby_provider') }}" style="
                display:flex;align-items:center;justify-content:center;
                width:38px;height:38px;border-radius:8px;border:1px solid var(--bs-primary);
                background:var(--bs-primary);color:#fff;
                cursor:pointer;flex-shrink:0;padding:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 2v3M12 19v3M2 12h3M19 12h3" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
                    <circle cx="12" cy="12" r="7" fill="none" stroke="currentColor" stroke-width="2"/>
                </svg>
            </button>
        </div>

        <!-- Map -->
        <div class="map-wrapper">
            <div id="nearby-map"></div>
            <!-- Loading indicator -->
            <div id="map-loading">&#9685; {{ __('messages.loading_providers') }}</div>
        </div>

    </div>
</section>
@endsection

@section('after_script')
<!-- Leaflet JS (local) -->
<script src="{{ asset('vendor/leaflet/leaflet.min.js') }}"></script>
<!-- Leaflet MarkerCluster JS (local) -->
<script src="{{ asset('vendor/leaflet.markercluster/leaflet.markercluster.min.js') }}"></script>

<script>
// Initialize Select2 synchronously while vendor.js jQuery (with Select2) is still active.
// The layout re-loads a fresh CDN jQuery AFTER @yield('after_script'), overwriting $.
// By calling this synchronously here we capture the Select2-capable jQuery reference.
(function($$) {
    if ($$ && $$.fn && $$.fn.select2) {
        $$('#category-filter').select2({
            width: '100%',
            dropdownAutoWidth: false,
            minimumResultsForSearch: Infinity,
        });
    }
})(window.jQuery);

(function () {
    const BASE_URL      = '{{ url('/') }}';
    const API_URL       = BASE_URL + '/api/nearby-providers';
    const DETAIL_URL    = BASE_URL + '/provider-detail/';
    const DISTANCE_UNIT = '{{ $distanceUnit }}';
    const DEFAULT_IMG   = '{{ asset('images/default_profile.png') }}';

    // ── Localized strings (from Laravel __() — all languages supported) ──────
    const LANG = {
        youAreHere      : '{{ __('messages.you_are_here') }}',
        noProviders     : '{{ __('messages.no_providers_found') }}',
        provider        : '{{ __('messages.provider') }}',
        services        : '{{ __('messages.services') }}',
        away            : '{{ __('messages.away') }}',
        viewInfo        : '{{ __('messages.view_info') }}',
    };

    const serverLat  = {{ $latitude ? (float)$latitude : 'null' }};
    const serverLng  = {{ $longitude ? (float)$longitude : 'null' }};
    const DEFAULT_LAT = {{ $defaultLat }};
    const DEFAULT_LNG = {{ $defaultLng }};

    let currentLat      = serverLat || DEFAULT_LAT;
    let currentLng      = serverLng || DEFAULT_LNG;
    let currentRadius   = parseInt(document.getElementById('radius-slider').value);
    let currentCategory = '';
    let currentSearch   = '';
    let allProviders    = [];   // cache from last API fetch
    let markerMap       = {};   // provider id → L.Marker (for focussing)
    let fetchTimer      = null;
    let userMarker      = null;
    let radiusCircle    = null;

    // ── Map init ──────────────────────────────────────────────────────────────
    const map = L.map('nearby-map', { zoomControl: true, attributionControl: false })
                 .setView([currentLat, currentLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const clusterGroup = L.markerClusterGroup({
        maxClusterRadius: 60,
        iconCreateFunction: function (cluster) {
            return L.divIcon({
                html: '<div class="cluster-inner">' + cluster.getChildCount() + '</div>',
                className: 'custom-cluster',
                iconSize: L.point(44, 44),
            });
        },
    });
    map.addLayer(clusterGroup);

    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--bs-primary').trim() || '#7c3aed';

    const clusterStyle = document.createElement('style');
    clusterStyle.textContent = `.custom-cluster{background:transparent}.cluster-inner{width:44px;height:44px;border-radius:50%;background:${primaryColor};color:#fff;font-size:15px;font-weight:700;display:flex;align-items:center;justify-content:center;border:3px solid rgba(0,0,0,.1);box-shadow:0 2px 8px rgba(0,0,0,.2)}`;
    document.head.appendChild(clusterStyle);

    // ── Marker icon (SVG pin with provider photo) ─────────────────────────────
    let _mid = 0;
    function createProviderMarkerIcon(imageUrl) {
        const src = imageUrl || DEFAULT_IMG;
        const uid = 'mp' + (++_mid);
        const html = `<svg width="60" height="60" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="overflow:visible;filter:drop-shadow(0 3px 8px rgba(0,0,0,.4))"><defs><clipPath id="${uid}"><circle cx="60" cy="48" r="38"/></clipPath></defs><path d="M60 117 Q12 72 12 48 A48 48 0 0 1 108 48 Q108 72 60 117 Z" fill="${primaryColor}"/><circle cx="60" cy="48" r="42" fill="white"/><image href="${src}" x="22" y="10" width="76" height="76" clip-path="url(#${uid})" preserveAspectRatio="xMidYMid slice" onerror="this.setAttribute('href','${DEFAULT_IMG}')"/></svg>`;
        return L.divIcon({ html, className: '', iconSize:[60,60], iconAnchor:[30,60], popupAnchor:[0,-60] });
    }

    // ── Popup builder ─────────────────────────────────────────────────────────
    function buildPopup(p) {
        const rating = p.avg_rating || 0;
        const stars  = rating > 0 ? '⭐'.repeat(Math.min(5, Math.floor(rating))) : '☆☆☆☆☆';
        const img    = p.profile_image || DEFAULT_IMG;
        return `<div class="provider-popup"><div style="position:relative;"><img src="${img}" class="pp-service-image" onerror="this.setAttribute('src','${DEFAULT_IMG}')"/><span class="pp-category-badge">${LANG.provider.toUpperCase()}</span></div><div class="pp-content"><div class="pp-title">${p.display_name||LANG.provider}</div><div class="pp-price-row"><span class="pp-price">${p.total_services} ${LANG.services}</span></div><div class="pp-rating-row"><div class="pp-rating"><span class="pp-rating-value">${stars} ${rating.toFixed(1)}</span></div><span class="pp-distance">📍 ${p.distance} ${DISTANCE_UNIT} ${LANG.away}</span></div><a href="${DETAIL_URL+p.id}" class="pp-btn" target="_blank">${LANG.viewInfo}</a></div></div>`;
    }

    // ── User location marker + radius circle ──────────────────────────────────
    function placeUserMarker(lat, lng) {
        if (userMarker)   map.removeLayer(userMarker);
        if (radiusCircle) map.removeLayer(radiusCircle);
        userMarker   = L.circleMarker([lat, lng], { radius:8, fillColor:primaryColor, color:'#fff', weight:2, opacity:1, fillOpacity:0.9 }).bindTooltip(LANG.youAreHere).addTo(map);
        radiusCircle = L.circle([lat, lng], { radius: currentRadius * 1000, color:primaryColor, fillColor:primaryColor, fillOpacity:0.06, weight:1.5, dashArray:'6' }).addTo(map);
    }

    // ── Render a list of providers onto the map ───────────────────────────────
    function renderProviders(list) {
        clusterGroup.clearLayers();
        markerMap = {};
        (list || []).forEach(function(p) {
            if (!p.latitude || !p.longitude) return;
            const m = L.marker([p.latitude, p.longitude], { icon: createProviderMarkerIcon(p.profile_image) })
                       .bindPopup(buildPopup(p), { maxWidth:320, className:'custom-popup' });
            clusterGroup.addLayer(m);
            markerMap[p.id] = m;
        });
    }

    // filter allProviders by current search term then render
    function renderWithSearch() {
        const q = currentSearch.toLowerCase().trim();
        renderProviders(q ? allProviders.filter(p => (p.display_name||'').toLowerCase().includes(q)) : allProviders);
    }

    // ── Fetch from API, store in allProviders, then render ────────────────────
    function fetchProviders() {
        document.getElementById('map-loading').style.display = 'block';
        const params = new URLSearchParams({ latitude: currentLat, longitude: currentLng, distance: currentRadius, unit: DISTANCE_UNIT, per_page: 'all' });
        if (currentCategory) params.append('category_id', currentCategory);

        fetch(API_URL + '?' + params.toString())
            .then(r => r.json())
            .then(res => {
                document.getElementById('map-loading').style.display = 'none';
                allProviders = res.data || [];
                renderWithSearch();
                // refresh autocomplete if search box has text
                const sv = document.getElementById('provider-search').value;
                if (sv) updateAutocomplete(sv);
            })
            .catch(() => { document.getElementById('map-loading').style.display = 'none'; });
    }

    function debouncedFetch(delay) {
        clearTimeout(fetchTimer);
        fetchTimer = setTimeout(fetchProviders, delay || 400);
    }

    // ── Autocomplete ──────────────────────────────────────────────────────────
    const searchInput = document.getElementById('provider-search');
    const acDropdown  = document.getElementById('search-autocomplete');

    function updateAutocomplete(val) {
        const q = (val || '').toLowerCase().trim();
        if (!q) { acDropdown.style.display = 'none'; acDropdown.innerHTML = ''; return; }
        const matches = allProviders.filter(p => (p.display_name||'').toLowerCase().includes(q)).slice(0, 8);
        if (!matches.length) {
            acDropdown.innerHTML = '<div class="sa-empty">' + LANG.noProviders + '</div>';
        } else {
            acDropdown.innerHTML = matches.map(p =>
                `<div class="sa-item" data-id="${p.id}" data-lat="${p.latitude}" data-lng="${p.longitude}">` +
                `<img src="${p.profile_image || DEFAULT_IMG}" onerror="this.src='${DEFAULT_IMG}'">` +
                `<span class="sa-name">${p.display_name}</span>` +
                `<span class="sa-dist">${p.distance} ${DISTANCE_UNIT}</span></div>`
            ).join('');
            acDropdown.querySelectorAll('.sa-item').forEach(function(item) {
                item.addEventListener('mousedown', function(e) {
                    e.preventDefault(); // prevent input blur
                    const lat = parseFloat(this.dataset.lat);
                    const lng = parseFloat(this.dataset.lng);
                    const id  = parseInt(this.dataset.id);
                    searchInput.value = this.querySelector('.sa-name').textContent;
                    currentSearch = searchInput.value;
                    acDropdown.style.display = 'none';
                    renderWithSearch();
                    map.setView([lat, lng], 16);
                    const target = markerMap[id];
                    if (target) setTimeout(() => { clusterGroup.zoomToShowLayer(target, () => target.openPopup()); }, 300);
                });
            });
        }
        acDropdown.style.display = 'block';
    }

    searchInput.addEventListener('input', function() {
        currentSearch = this.value;
        renderWithSearch();
        updateAutocomplete(this.value);
    });

    searchInput.addEventListener('keydown', function(e) {
        const items = Array.from(acDropdown.querySelectorAll('.sa-item'));
        if (!items.length) return;
        const idx = items.findIndex(i => i.classList.contains('active'));
        if (e.key === 'ArrowDown')  { e.preventDefault(); items.forEach(i=>i.classList.remove('active')); items[idx < items.length-1 ? idx+1 : 0].classList.add('active'); }
        else if (e.key === 'ArrowUp')   { e.preventDefault(); items.forEach(i=>i.classList.remove('active')); items[idx > 0 ? idx-1 : items.length-1].classList.add('active'); }
        else if (e.key === 'Enter') { const a = acDropdown.querySelector('.sa-item.active'); if (a) a.dispatchEvent(new MouseEvent('mousedown')); else { acDropdown.style.display='none'; renderWithSearch(); } }
        else if (e.key === 'Escape') { acDropdown.style.display='none'; }
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !acDropdown.contains(e.target)) acDropdown.style.display = 'none';
    });

    // ── Slider / category / locate controls ──────────────────────────────────
    const slider      = document.getElementById('radius-slider');
    const radiusLabel = document.getElementById('radius-label');

    slider.addEventListener('input', function() {
        currentRadius = parseInt(this.value);
        radiusLabel.textContent = currentRadius + ' ' + DISTANCE_UNIT;
        if (radiusCircle) radiusCircle.setRadius(currentRadius * 1000);
        debouncedFetch(600);
    });

    // Select2 fires a jQuery synthetic event, NOT a native DOM change event.
    // Use jQuery .on('change') via the still-active vendor.js jQuery reference.
    (window.jQuery || window.$)('#category-filter').on('change', function() {
        currentCategory = this.value;   // native .value — no jQuery dependency at call-time
        debouncedFetch(300);
    });

    map.on('click', function(e) {
        currentLat = e.latlng.lat;
        currentLng = e.latlng.lng;
        map.setView([currentLat, currentLng], map.getZoom());
        placeUserMarker(currentLat, currentLng);
        debouncedFetch(400);
    });

    // ── Locate Me button ──────────────────────────────────────────────────────
    document.getElementById('locate-me-btn').addEventListener('click', function() {
        var btn = this;
        btn.disabled = true;
        btn.style.opacity = '0.6';
        function restoreBtn() { btn.disabled = false; btn.style.opacity = '1'; }
        tryGps(
            function(lat, lng) { currentLat=lat; currentLng=lng; map.setView([lat,lng],14); placeUserMarker(lat,lng); fetchProviders(); restoreBtn(); },
            function() {
                // GPS blocked on HTTP — try IP geo as last resort for button only
                tryIpGeo(
                    function(lat, lng) { currentLat=lat; currentLng=lng; map.setView([lat,lng],13); placeUserMarker(lat,lng); fetchProviders(); restoreBtn(); },
                    function() { restoreBtn(); }
                );
            }
        );
    });

    // ── Boot ──────────────────────────────────────────────────────────────────
    // tryGps: wraps navigator.geolocation with a 5s safety timeout.
    function tryGps(onSuccess, onFail) {
        if (!navigator.geolocation) { onFail(); return; }
        var done = false;
        var timer = setTimeout(function() {
            if (!done) { done = true; onFail(); }
        }, 5000);
        navigator.geolocation.getCurrentPosition(
            function(pos) {
                if (done) return; done = true; clearTimeout(timer);
                onSuccess(pos.coords.latitude, pos.coords.longitude);
            },
            function() {
                if (done) return; done = true; clearTimeout(timer);
                onFail();
            },
            { timeout: 6000, maximumAge: 60000 }
        );
    }

    // IP-based geolocation — used only by the Locate Me button as last resort.
    function tryIpGeo(onSuccess, onFail) {
        fetch('https://ipapi.co/json/')
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d && d.latitude && d.longitude) {
                    onSuccess(parseFloat(d.latitude), parseFloat(d.longitude));
                } else { onFail(); }
            })
            .catch(function() { onFail(); });
    }

    function boot() {
        // Always show providers immediately using site-setup default (NYC).
        placeUserMarker(currentLat, currentLng);
        fetchProviders();

        // Silently try GPS in background; if granted, silently re-center + re-fetch.
        // No IP geo here — VPN/proxy exit nodes give wrong countries.
        tryGps(
            function(lat, lng) {
                currentLat = lat; currentLng = lng;
                map.setView([lat, lng], 14);
                placeUserMarker(lat, lng);
                debouncedFetch(300);
            },
            function() { /* GPS blocked — keep site-default view, providers already shown */ }
        );
    }

    boot();
})();
</script>
@endsection
