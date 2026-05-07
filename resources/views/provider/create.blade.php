<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? __('messages.list') }}</h5>
                            <a href="{{ route('provider.index') }}" class=" float-end btn btn-sm btn-primary"><i
                                    class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            @if($auth_user->can('provider list'))
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ html()->form('POST', route('provider.store'))->id('provider')->attribute('enctype',
                        'multipart/form-data')->attribute('data-toggle', 'validator')->open() }}
                        {{ html()->hidden('id',$providerdata->id ?? null) }}
                        {{ html()->hidden('user_type','provider') }}
                        <div class="row">
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.first_name') . ' <span class="text-danger">*</span>',
                                'first_name')->class('form-control-label') }}
                                {{
                                html()->text('first_name',$providerdata->first_name)->placeholder(__('messages.first_name'))->class('form-control')->required()
                                }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.last_name') . ' <span class="text-danger">*</span>',
                                'last_name')->class('form-control-label') }}
                                {{ html()->text('last_name',
                                $providerdata->last_name)->placeholder(__('messages.last_name'))->class('form-control')->required()
                                }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.username') . ' <span class="text-danger">*</span>',
                                'username')->class('form-control-label') }}
                                {{ html()->text('username',
                                $providerdata->username)->placeholder(__('messages.username'))->class('form-control')->required()
                                }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.email') . ' <span class="text-danger">*</span>',
                                'email')->class('form-control-label') }}
                                {{ html()->email('email',
                                $providerdata->email)->placeholder(__('messages.email'))->class('form-control')->required()->attribute('pattern'
                                ,'[^@]+@[^@]+\.[a-zA-Z]{2,}')->attribute('title', 'Please enter a valid email
                                address')}}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            @if (!isset($providerdata->id) || $providerdata->id == null)
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.password') . ' <span class="text-danger">*</span>',
                                'password')->class('form-control-label') }}
                                {{
                                html()->password('password')->class('form-control')->placeholder(__('messages.password'))->required()->autocomplete('new-password')
                                }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>
                            @endif

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.designation'), 'designation')->class('form-control-label')
                                }}
                                {{ html()->text('designation',
                                $providerdata->designation)->placeholder(__('messages.designation'))->class('form-control')
                                }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.providertype')]) .
                                ' <span class="text-danger">*</span>', 'providertype_id')->class('form-control-label')
                                }}
                                <br />
                                {{ html()->select('providertype_id', [optional($providerdata->providertype)->id =>
                                optional($providerdata->providertype)->name], optional($providerdata->providertype)->id)
                                ->class('select2js form-group providertype')
                                ->required()
                                ->attribute('data-placeholder', __('messages.select_name', ['select' =>
                                __('messages.providertype')]))
                                ->attribute('data-ajax--url', route('ajax-list', ['type' => 'providertype'])) }}
                            </div>
              

                            <div class="form-group col-md-12">
                                {{ html()->label(__('messages.address') . ($nearby_provider ? ' <span class="text-danger">*</span>' : ''), 'address')->class('form-control-label') }}
                                {{ html()->textarea('address', $providerdata->address)->class("form-control
                                textarea")->rows(3)->id('address')->placeholder(__('messages.address'))->required($nearby_provider ? true : false) }}
                                {{ html()->hidden('latitude', $providerdata->latitude)->id('latitude') }}
                                {{ html()->hidden('longitude', $providerdata->longitude)->id('longitude') }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.country')]) . ($nearby_provider ? ' <span class="text-danger">*</span>' : ''),
                                'country_id')->class('form-control-label') }}
                                <br />
                                {{ html()->select('country_id', [optional($providerdata->country)->id =>
                                optional($providerdata->country)->name], optional($providerdata->country)->id)
                                ->class('select2js form-group country')
                                ->required($nearby_provider ? true : false)
                                ->attribute('data-placeholder', __('messages.select_name', ['select' =>
                                __('messages.country')]))
                                ->attribute('data-ajax--url', route('ajax-list', ['type' => 'country'])) }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.state')]) . ($nearby_provider ? ' <span class="text-danger">*</span>' : ''),
                                'state_id')->class('form-control-label') }}
                                <br />
                                {{ html()->select('state_id', [])
                                ->class('select2js form-group state_id')
                                ->required($nearby_provider ? true : false)
                                ->attribute('data-placeholder', __('messages.select_name', ['select' =>
                                __('messages.state')])) }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.city')]) . ($nearby_provider ? ' <span class="text-danger">*</span>' : ''),
                                'city_id')->class('form-control-label') }}
                                <br />
                                {{ html()->select('city_id', [], old('city_id'))
                                ->class('select2js form-group city_id')
                                ->required($nearby_provider ? true : false)
                                ->attribute('data-placeholder', __('messages.select_name', ['select' =>
                                __('messages.city')])) }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.tax')]),
                                'tax_id')->class('form-control-label') }}
                                <br />
                                {{ html()->select('tax_id[]', [], old('tax_id'))
                                ->class('select2js form-group tax_id')
                                ->id('tax_id')
                                ->multiple()
                                ->attribute('data-placeholder', __('messages.select_name', ['select' =>
                                __('messages.tax')])) }}
                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.service_zone')]),
                                'service_zones')->class('form-control-label') }}
                                <br />
                                {{ html()->select('service_zones[]', $serviceZones->pluck('name', 'id'), $selectedZones)
                                ->class('select2js form-group')
                                ->id('service_zones')
                                ->multiple()
                                ->attribute('data-placeholder', __('messages.select_name', ['select' =>
                                __('messages.service_zone')])) }}
                            </div>
                            <!-- <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.zone')]), 'zone_id')->class('form-control-label') }}
                                <br />
                                {{ html()->select('zone_id[]', [], old('zone_id'))
                                    ->class('select2js form-group zone_id')
                                    ->id('zone_id')
                                    ->multiple()
                                    ->attribute('data-placeholder', __('messages.select_name', ['select' => __('messages.zone')])) }}
                            </div> -->
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.contact_number') . ' <span class="text-danger">*</span>',
                                'contact_number')->class('form-control-label') }}
                                {{
                                html()->text('contact_number',$providerdata->contact_number)->placeholder(__('messages.contact_number'))->class('form-control
                                contact_number')->id('contact_number')->required() }}
                                <input type="hidden" name="country_code" id="country_code"
                                    value="{{ $providerdata->country_code ?? '' }}">
                                <small id="contact_number-error" class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.status') . ' <span class="text-danger">*</span>',
                                'status')->class('form-control-label') }}
                                {{ html()->select('status', ['1' => __('messages.active'), '0' =>
                                __('messages.inactive')], $providerdata->status)->class('form-select
                                select2js')->required() }}
                            </div>

                            @php
                                $profileImageExists = isset($providerdata->id) && $providerdata->id && getMediaFileExit($providerdata, 'profile_image');
                            @endphp
                            <div class="form-group col-md-4">
                                <label class="form-control-label" for="profile_image">{{ __('messages.profile_image') }}
                                </label>
                                <div class="custom-file">
                                    <input type="file" name="profile_image" class="custom-file-input" accept="image/*">
                                    <label class="custom-file-label upload-label">{{ __('messages.choose_file',['file'
                                        => __('messages.profile_image') ]) }}</label>
                                </div>
                                <!-- <span class="selected_file"></span> -->
                            </div>

                            @if(getMediaFileExit($providerdata, 'profile_image'))
                            <div class="col-md-2 mb-2 position-relative">
                                <img id="profile_image_preview" src="{{getSingleMedia($providerdata,'profile_image')}}"
                                    alt="#" class="attachment-image mt-1">
                                <a class="text-danger remove-file"
                                    href="{{ route('remove.file', ['id' => $providerdata->id, 'type' => 'profile_image']) }}"
                                    data--submit="confirm_form" data--confirmation='true' data--ajax="true"
                                    data-toggle="tooltip"
                                    title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.image") ]) }}'
                                    data-title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.image") ]) }}'
                                    data-message='{{ __("messages.remove_file_msg") }}'>
                                    <i class="ri-close-circle-line"></i>
                                </a>
                            </div>
                            @endif

                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <div class="custom-control custom-switch custom-control-inline">
                                    {{ html()->checkbox('is_featured',
                                    $providerdata->is_featured)->class('custom-control-input')->id('is_featured') }}
                                    <label class="custom-control-label" for="is_featured">{{
                                        __('messages.set_as_featured') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        {{ html()->submit(__('messages.save'))->class('btn btn-md btn-primary float-end') }}
                        {{ html()->form()->close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://www.gstatic.com/firebasejs/6.0.2/firebase.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/css/intlTelInput.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.19/js/intlTelInput.min.js"></script>
    @php
    $data = '';
    if ($providerdata && $providerdata->providerTaxMapping()) {
    $data = $providerdata->providerTaxMapping()->pluck('tax_id')->implode(',');
    }
    @endphp
    @section('bottom_script')
    @include('partials._address_autocomplete')
    <script src="{{ asset('js/phone-input-handler.js') }}"></script>
    <script type="text/javascript">
        (function($) {
        "use strict";
        $(document).ready(function() {

            var country_id = "{{ isset($providerdata->country_id) ? $providerdata->country_id : 0 }}";
            var state_id = "{{ isset($providerdata->state_id) ? $providerdata->state_id : 0 }}";
            var city_id = "{{ isset($providerdata->city_id) ? $providerdata->city_id : 0 }}";

            var provider_id = "{{ isset($providerdata->id) ? $providerdata->id : '' }}";
            var provider_tax_id = "{{ isset($data) ? $data : [] }}";

            // Initialize select2 for service zones
            $('#service_zones').select2({
                width: '100%',
                placeholder: "{{ __('messages.select_name', ['select' => __('messages.service_zone')]) }}"
            });

            getTax(provider_id, provider_tax_id)
            stateName(country_id, state_id);
            $(document).on('change', '#country_id', function() {
                var country = $(this).val();
                $('#state_id').empty();
                $('#city_id').empty();
                stateName(country);
            })
            $(document).on('change', '#state_id', function() {
                var state = $(this).val();
                $('#city_id').empty();
                cityName(state, city_id);
            })

            // Initialize phone input handler - ensure intl-tel-input is loaded
            if (typeof window.intlTelInput === 'undefined') {
                console.warn('intl-tel-input library not loaded');
                return;
            }
            
            // Initialize with a small delay to ensure DOM is fully ready
            setTimeout(function() {
                if (typeof PhoneInputHandler !== 'undefined') {
                    PhoneInputHandler.init({
                        inputSelector: '#contact_number',
                        countryCodeSelector: '#country_code',
                        errorSelector: '#contact_number-error',
                        formSelector: '#provider',
                        initialCountry: 'in'
                    });
                } else {
                    console.warn('PhoneInputHandler not defined');
                }
            }, 100);

            function stateName(country, state = "") {
                var state_route = "{{ route('ajax-list', [ 'type' => 'state','country_id' =>'']) }}" + country;
                state_route = state_route.replace('amp;', '');

                $.ajax({
                    url: state_route,
                    success: function(result) {
                        $('#state_id').select2({
                            width: '100%',
                            placeholder: "{{ trans('messages.select_name',['select' => trans('messages.state')]) }}",
                            data: result.results
                        });
                        if (state != null) {
                            $("#state_id").val(state).trigger('change');
                        }
                    }
                });
            }

            function cityName(state, city = "") {
                var city_route = "{{ route('ajax-list', [ 'type' => 'city' ,'state_id' =>'']) }}" + state;
                city_route = city_route.replace('amp;', '');

                $.ajax({
                    url: city_route,
                    success: function(result) {
                        $('#city_id').select2({
                            width: '100%',
                            placeholder: "{{ trans('messages.select_name',['select' => trans('messages.city')]) }}",
                            data: result.results
                        });
                        if (city != null || city != 0) {
                            $("#city_id").val(city).trigger('change');
                        }
                    }
                });
            }

            function getTax(provider_id, provider_tax_id = "") {
                var provider_tax_route = "{{ route('ajax-list', [ 'type' => 'provider_tax','provider_id' =>'']) }}" +
                    provider_id;
                provider_tax_route = provider_tax_route.replace('amp;', '');

                $.ajax({
                    url: provider_tax_route,
                    success: function(result) {
                        $('#tax_id').select2({
                            width: '100%',
                            placeholder: "{{ trans('messages.select_name',['select' => trans('messages.tax')]) }}",
                            data: result.results
                        });
                        if (provider_tax_id != "") {
                            $('#tax_id').val(provider_tax_id.split(',')).trigger('change');
                        }
                    }
                });
            }
        });
        })(jQuery);

    // When the existing image is removed via the remove-file link,
    // make the file input required so the user must upload a new one.
    $(document).on('ajaxComplete', function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('remove-file') !== -1) {
            // Profile image is now optional, no need to add required attribute
            // Re-evaluate the validator so it picks up the new state.
            var $form = $('#provider');
            if (typeof $form.validator === 'function') {
                $form.validator('update');
            }
        }
    });
    </script>
    @endsection
</x-master-layout>
