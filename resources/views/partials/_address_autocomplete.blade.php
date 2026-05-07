@php
    $google_map_key = json_decode(App\Models\Setting::where('type', 'site-setup')->where('key', 'site-setup')->first()->value, true)['google_map_keys'] ?? '';
    $othersetting   = \App\Models\Setting::where('type', 'OTHER_SETTING')->where('key', 'OTHER_SETTING')->first();
    $nearby_provider = 0;
    if ($othersetting) {
        $decoded = json_decode($othersetting->value);
        $nearby_provider = $decoded->nearby_provider ?? 0;
    }
    $address_field_id = $id       ?? 'address';
    $lat_field_id     = $lat_id   ?? 'latitude';
    $lng_field_id     = $long_id  ?? 'longitude';
@endphp

@if($nearby_provider && $google_map_key)
<style>
    .pac-container { z-index: 9999 !important; }
</style>
<script>
// Defined globally so Google Maps callback can reach it after async load
function _initAddressAutocomplete() {
    var addressInput = document.getElementById('{{ $address_field_id }}');
    if (!addressInput || !window.google || !window.google.maps) return;

    var autocomplete = new google.maps.places.Autocomplete(addressInput, {
        types: ['geocode']
    });

    autocomplete.addListener('place_changed', function() {
        var place = autocomplete.getPlace();
        if (!place || !place.geometry) return;

        // Save lat / lng into hidden fields
        document.getElementById('{{ $lat_field_id }}').value  = place.geometry.location.lat();
        document.getElementById('{{ $lng_field_id }}').value = place.geometry.location.lng();

        // Also update the visible address textarea with the formatted address
        addressInput.value = place.formatted_address || addressInput.value;

        // Extract country / state / city from address_components
        var country = '', state = '', city = '';
        (place.address_components || []).forEach(function(c) {
            var t = c.types[0];
            if (t === 'country')                       country = c.long_name;
            else if (t === 'administrative_area_level_1') state = c.long_name;
            else if (t === 'locality')                  city   = c.long_name;
            else if (t === 'administrative_area_level_2' && !city) city = c.long_name;
        });

        if (!country && !state && !city) return;

        // Look up DB IDs and populate country/state/city Select2 dropdowns
        jQuery.ajax({
            url: '{{ route('get-location-ids') }}',
            type: 'GET',
            data: { country: country, state: state, city: city },
            success: function(res) {
                if (res.country_id && jQuery('#country_id').length) {
                    // Append option and trigger select2 + the stateName cascade
                    var $c = jQuery('#country_id');
                    if (!$c.find('option[value="' + res.country_id + '"]').length) {
                        $c.append(new Option(country, res.country_id, true, true));
                    }
                    $c.val(res.country_id).trigger('change'); // triggers stateName()

                    setTimeout(function() {
                        if (res.state_id && jQuery('#state_id').length) {
                            var $s = jQuery('#state_id');
                            if (!$s.find('option[value="' + res.state_id + '"]').length) {
                                $s.append(new Option(state, res.state_id, true, true));
                            }
                            $s.val(res.state_id).trigger('change'); // triggers cityName()

                            setTimeout(function() {
                                if (res.city_id && jQuery('#city_id').length) {
                                    var $ci = jQuery('#city_id');
                                    if (!$ci.find('option[value="' + res.city_id + '"]').length) {
                                        $ci.append(new Option(city, res.city_id, true, true));
                                    }
                                    $ci.val(res.city_id).trigger('change');
                                }
                            }, 800);
                        }
                    }, 800);
                }
            }
        });
    });
}

// Load Google Maps only once — guard against duplicate <script> tags (AJAX re-renders)
(function() {
    if (window.google && window.google.maps && window.google.maps.places) {
        // Already loaded — init immediately
        _initAddressAutocomplete();
        return;
    }
    if (window._googleMapsLoading) {
        // Script is already in-flight; just register our callback
        var prev = window._googleMapsReady;
        window._googleMapsReady = function() { if (prev) prev(); _initAddressAutocomplete(); };
        return;
    }
    window._googleMapsLoading = true;
    window._googleMapsReady   = _initAddressAutocomplete;
    window._googleMapsCallback = function() {
        window._googleMapsLoading = false;
        if (typeof window._googleMapsReady === 'function') window._googleMapsReady();
    };
    var s  = document.createElement('script');
    s.src  = 'https://maps.googleapis.com/maps/api/js?key={{ $google_map_key }}&libraries=places&callback=_googleMapsCallback';
    s.async = true;
    s.defer = true;
    document.head.appendChild(s);
})();
</script>
@endif
