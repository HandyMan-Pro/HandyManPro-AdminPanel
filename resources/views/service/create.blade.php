<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? __('messages.list') }}</h5>
                            <a href="{{ url()->previous() === route('service.provider-service-request') ? route('service.provider-service-request') : route('service.index') }}"
                                class="float-end btn btn-sm btn-primary">
                                <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                            </a>
                            @if ($auth_user->can('service list'))
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        
                        {{ html()->form('POST', route('service.store'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('data-language-required-mode', 'active')->id('service')->open() }}
                        {{ html()->hidden('id', $servicedata->id ?? null) }}

                        @include('partials._language_toggale')
                        @foreach($language_array as $language)
                        <div id="form-language-{{ $language['id'] }}" class="language-form" style="display: {{ $language['id'] == app()->getLocale() ? 'block' : 'none' }};">
                            <div class="row">
                                @foreach(['name' => __('messages.name'), 'description' => __('messages.description')] as $field => $label)
                                <div class="form-group col-md-{{ $field === 'name' ? '4' : '12' }}">
                                    {{ html()->label($label . ($field === 'name' ? ' <span class="text-danger">*</span>' : ''), $field)->class('form-control-label language-label') }}
                                    @php
                                        $defaultLanguage = getDefaultLanguage();
                                        
                                        // Get translation for this language
                                        if ($servicedata && $servicedata->id) {
                                            $translation = $servicedata->translations()
                                                ->where('attribute', $field)
                                                ->where('locale', $language['id'])
                                                ->value('value');
                                        } else {
                                            $translation = null;
                                        }
                                        
                                        // Determine the value to show
                                        if ($language['id'] === 'en') {
                                            $value = $servicedata->getAttribute($field) ?? '';
                                        } elseif ($translation !== null && $translation !== '') {
                                            $value = $translation;
                                        } else {
                                            $value = '';
                                        }
                                        
                                        $name = $language['id'] === 'en' ? $field : "translations[{$language['id']}][$field]";
                                    @endphp

                            @if($field === 'name')
    {{ html()->text($name, $value)
        ->placeholder($label)
        ->class('form-control')
        ->attribute('title', 'Please enter alphabetic characters and spaces only')
        ->attribute('data-required', 'true')
        ->attribute('data-lang', $language['id']) }}
@elseif($field === 'description')
    {{ html()->textarea($name, $value)
        ->class('form-control textarea description-field')
        ->attribute('maxlength', 250)
        ->rows(3)
        ->placeholder($label)
        ->attribute('data-lang', $language['id']) }}

    <small class="text-muted">
        <span class="char-count" id="char-count-{{ $language['id'] }}">{{ strlen($value ?? '') }}</span>/250
    </small>
@endif

                                    <small class="help-block with-errors text-danger"></small>
                                </div>
                                @endforeach

                                <!-- Category Selection -->
                                <div class="form-group col-md-4">
                                    {{ html()->label(__('messages.select_name', ['select' => __('messages.category')]) . ' <span class="text-danger">*</span>', 'category_id')->class('form-control-label') }}
                                    <select name="category_id"
                                            id="category_id_{{ $language['id'] }}"
                                            class="form-select select2js-category"
                                            data-select2-type="category"
                                            data-selected-id="{{ $servicedata->category_id ?? '' }}"
                                            data-language-id="{{ $language['id'] }}"
                                            data-ajax--url="{{ route('ajax-list', ['type' => 'category', 'language_id' => $language['id']]) }}"
                                            data-placeholder="{{ __('messages.select_name', ['select' => __('messages.category')]) }}">
                                        </select>
                                        <small class="help-block with-errors text-danger"></small>
                                    </div>

                                    <!-- SubCategory Selection -->
                                    <div class="form-group col-md-4">
                                        {{ html()->label(__('messages.select_name', ['select' => __('messages.subcategory')]), 'category_id')->class('form-control-label') }}
                                        <select name="subcategory_id" id="subcategory_id_{{ $language['id'] }}"
                                            class="form-select select2js-subcategory subcategory_id"
                                            data-select2-type="subcategory"
                                            data-selected-id="{{ $servicedata->subcategory_id ?? '' }}"
                                            data-language-id="{{ $language['id'] }}"
                                            data-ajax--url="{{ route('ajax-list', ['type' => 'subcategory', 'category_id' => $servicedata->category_id ?? '', 'language_id' => $language['id']]) }}"
                                            data-placeholder="{{ __('messages.select_name', ['select' => __('messages.subcategory')]) }}">
                                        </select>
                                        <small class="help-block with-errors text-danger"></small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="row">
                            <!-- <div class="form-group col-md-4">
                                {{ html()->label(__('messages.name') . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                                {{ html()->text('name', $servicedata->name)->placeholder(__('messages.name'))->class('form-control')->attributes(['title' => 'Please enter alphabetic characters and spaces only']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.category')]) . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                                <br />
                                {{ html()->select(
                                        'category_id',
                                        [optional($servicedata->category)->id => optional($servicedata->category)->name],
                                        optional($servicedata->category)->id,
                                    )->class('select2js form-group category')->required()->id('category_id')->attribute('data-placeholder', __('messages.select_name', ['select' => __('messages.category')]))->attribute('data-ajax--url', route('ajax-list', ['type' => 'category'])) }}

                            </div>
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.subcategory')]), 'subcategory_id')->class('form-control-label') }}
                                <br />
                                {{ html()->select('subcategory_id', [])->class('select2js form-group subcategory_id')->attribute('data-placeholder', __('messages.select_name', ['select' => __('messages.subcategory')])) }}
                            </div> -->

                            @if (auth()->user()->hasAnyRole(['admin', 'demo_admin']))
                                <div class="form-group col-md-4">
                                    {{ html()->label(__('messages.select_name', ['select' => __('messages.provider')]) . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                                    <br />
                                    {{ html()->select(
                                            'provider_id',
                                            [optional($servicedata->providers)->id => optional($servicedata->providers)->display_name],
                                            optional($servicedata->providers)->id,
                                        )->class('select2js form-group')->id('provider_id')->attribute('onchange', 'selectprovider(this)')->required()->attribute('data-placeholder', __('messages.select_name', ['select' => __('messages.provider')]))->attribute('data-ajax--url', route('ajax-list', ['type' => 'provider'])) }}
                                </div>
                            @endif
                            @if (auth()->user()->hasRole('provider'))
                                <input type="hidden" id="provider_id" value="{{ auth()->id() }}">
                            @endif

                            <!-- Zone Selection -->
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.zone')]) . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                                <br />
                                {{ html()->select('service_zones[]', [], old('service_zones', $selectedZones ?? []))->class('select2js form-group zone_id')->id('service_zones')->multiple()->required()->attribute('data-placeholder', __('messages.select_name', ['select' => __('messages.zone')])) }}
                            </div>



                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.price_type') . ' <span class="text-danger">*</span>', 'type')->class('form-control-label') }}
                                {{ html()->select('type', ['fixed' => __('messages.fixed'), 'hourly' => __('messages.hourly'), 'free' => __('messages.free')], $servicedata->type)->class('form-select select2js')->required()->id('price_type') }}
                            </div>
                            <div class="form-group col-md-4" id="price_div">
                                {{ html()->label(__('messages.price') . ' <span class="text-danger">*</span>', 'price')->class('form-control-label') }}
                                {{ html()->text('price', null)->attributes(['min' => 1, 'step' => 'any', 'pattern' => '^\\d+(\\.\\d{1,2})?$'])->placeholder(__('messages.price'))->class('form-control')->required()->id('price') }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4" id="discount_div">
                                {{ html()->label(__('messages.discount') . ' %', 'discount')->class('form-control-label') }}
                                {{ html()->number('discount', null)->attributes(['min' => 0, 'max' => 99, 'step' => 'any'])->placeholder(__('messages.discount'))->class('form-control')->id('discount') }}

                                <span id="discount-error" class="text-danger"></span>
                            </div>


                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.duration') . ' (hours) ', 'duration')->class('form-control-label') }}
                                {{ html()->text('duration', $servicedata->duration)->placeholder(__('messages.duration'))->class('form-control min-datetimepicker-time') }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.status') . ' <span class="text-danger">*</span>', 'status')->class('form-control-label') }}
                                {{ html()->select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], $servicedata->status)->class('form-select select2js')->required() }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.visit_type').' ', 'visit_type')->class('form-control-label') }}
                                <br />
                                {{ html()->select('visit_type', $visittype, $servicedata->visit_type)->id('visit_type')->class('form-select select2js')->required() }}
                            </div>

                        <div class="form-group col-md-8 mb-3 {{ old('visit_type', $servicedata->visit_type ?? '') === 'on_shop' ? '' : 'd-none' }}" id="shop-select-wrapper">
                            <label for="shop_ids" class="form-label">{{ __('messages.select_shops') }}</label>
                            <select name="shop_ids[]" id="shop_ids" class="form-select select2js" data-placeholder="{{ __('messages.select_shops') }}" multiple>
                                @if(isset($servicedata))
                                    @foreach($servicedata->shops as $shop)
                                        <option value="{{ $shop->id }}" selected>{{ $shop->shop_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="invalid-feedback">At least one shop must be selected.</div>
                        </div>

                            <!-- File Input -->
                        <div class="form-group col-md-4">
                            <label class="form-control-label" for="service_attachment">
                                {{ __('messages.image') }} <span class="text-danger">*</span>
                            </label>
                            <div class="custom-file">
                                <input type="file"
                                    name="service_attachment[]"
                                    class="custom-file-input"
                                    id="service_attachment_input"
                                    onchange="previewServiceImage(event)"
                                    accept="image/*"
                                    @if(!getMediaFileExit($servicedata, 'service_attachment')) required @endif
                                     multiple>
                                <label class="custom-file-label upload-label" id="service_attachment_label">
                                    {{ $servicedata && getMediaFileExit($servicedata, 'service_attachment')
                                        ? $servicedata->getFirstMedia('service_attachment')->file_name
                                        : __('messages.choose_file',['file' => __('messages.image')]) }}
                                </label>
                            </div>
                            <small class="help-block with-errors text-danger"></small>
                            @if ($errors->has('service_attachment'))
                                <span class="text-danger">{{ $errors->first('service_attachment') }}</span>
                            @endif
                        </div>
                        <div id="service_attachment_preview_container" class="d-flex flex-wrap">
                            @if(getMediaFileExit($servicedata, 'service_attachment'))
                                @foreach($servicedata->getMedia('service_attachment') as $media)
                                    <div class="col-md-2 mb-2 db-upload">
                                        <div class="image-preview-container ">
                                            <img id="service_attachment_preview_{{$media->id}}" src="{{ $media->getUrl() }}"
                                                alt="Image preview"
                                                class="attachment-image mt-1"
                                                style="width:150px;  {{ $media->getUrl()  ? '' : 'display:none;' }}">
                                            <a class="text-danger remove-file" id="removeButton"
                                                    href="{{ route('remove.file', ['id' => $media->id, 'type' => 'service_attachment']) }}"
                                                    data--submit="confirm_form" data--confirmation='true'
                                                    data--ajax="true" data-toggle="tooltip"
                                                    title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.attachments") ] ) }}'
                                                    data-title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.attachments") ] ) }}'
                                                    data-message='{{ __("messages.remove_file_msg") }}'
                                                    style="{{ $media->getUrl() ? 'display: inline;' : 'display: none;' }}" >
                                                    <i class="ri-close-circle-line"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <!-- Preview + Remove -->
                        {{-- <div class="col-md-2 mb-2">
                            <div class="image-preview-container">
                                <img id="service_attachment_preview"
                                    src="{{ getMediaFileExit($servicedata, 'service_attachment')
                                        ? getSingleMedia($servicedata, 'service_attachment') : '' }}"
                                    alt="Image preview"
                                    class="attachment-image mt-1"
                                    style="width:150px; {{ getMediaFileExit($servicedata, 'service_attachment') ? '' : 'display:none;' }}">
                                <a class="text-danger remove-file"
                                id="removeServiceAttachmentBtn"
                                href="javascript:void(0);"
                                style="{{ getMediaFileExit($servicedata, 'service_attachment') ? 'display:inline;' : 'display:none;' }}">
                                    <i class="ri-close-circle-line"></i>
                                </a>
                            </div>
                        </div> --}}



                        </div>

                        <!-- SEO Enable/Disable Switch -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <!-- @php
                                            $seoEnabled = !empty($servicedata->meta_title)
                                                || !empty($servicedata->meta_description)
                                                || !empty($servicedata->meta_keywords)
                                                || !empty($servicedata->slug)
                                        @endphp -->
                                        {{ html()->checkbox('seo_enabled', $servicedata->seo_enabled)->class('custom-control-input')->id('seo_enabled') }}
                                        <label class="custom-control-label" for="seo_enabled">{{ __('messages.set_seo') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SEO Fields Section for this language -->
                        <!-- SEO Fields Section for this language -->
                        <div id="seo_fields_section" style="{{ isset($servicedata->seo_enable) && $servicedata->seo_enable ? '' : 'display:none;' }}">
                            <div class="row">
                                <div class="form-group col-md-6 mb-3">
                                    {{ html()->label(__('messages.seo_image'), 'seo_image')->class('form-control-label') }}
                                    <div class="custom-file">
                                    @php
                                        $seoImageUrl = (isset($servicedata->id) && getMediaFileExit($servicedata, 'seo_image')) ? $servicedata->getFirstMediaUrl('seo_image') : '';
                                        $seoImageHas = !empty($seoImageUrl) ? '1' : '0';
                                    @endphp
                                    <input type="file" name="seo_image" class="custom-file-input" id="seo_image"
                                        accept=".jpg,.jpeg,.png"
                                        onchange="previewSeoImage(event)"
                                        data-has-image="{{ $seoImageHas }}"

                                        >
                                        <label class="custom-file-label upload-label">{{ __('messages.choose_file', ['file' => __('messages.seo_image')]) }}</label>
                                    </div>
                                    <small id="seo_image_error" class="text-danger"></small> <!-- Error message container -->
                                    <small class="text-muted d-block mt-1">{{ __('messages.only_jpg_png_jpeg_allowed') }}</small> <!-- Note for allowed image types -->


                                    <img id="seo_image_preview" src="{{ $seoImageUrl }}" alt="SEO Image Preview" style="max-width: 100px; margin-top: 10px; @if(empty($seoImageUrl)) display: none; @endif" />
                                </div>
                                @foreach ($language_array as $language)
                                    <div id="seo-form-language-{{ $language['id'] }}" class="language-form" style="display: {{ $language['id'] == app()->getLocale() ? 'block' : 'none' }};">

                                        {{-- Meta Title --}}
                                        <div class="form-group col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                {{ html()->label(__('messages.meta_title') . ' <span class="text-danger">*</span>')->class('form-control-label language-label') }}
                                                <span class="text-muted" style="font-size: 12px;">
                                                    @php
                                                        $defaultLanguage = getDefaultLanguage();
                                                        $metaTitleVal = $servicedata ? $servicedata->translate('meta_title', $language['id']) : '';
                                                    @endphp
                                                    <span id="meta-title-count-{{ $language['id'] }}">{{ strlen((string) $metaTitleVal) }}</span>/100
                                                </span>
                                            </div>
                                            @php
                                                $metaTitleName = $language['id'] === $defaultLanguage
                                                    ? 'meta_title'
                                                    : "translations[{$language['id']}][meta_title]";
                                            @endphp
                                            <input
                                                type="text"
                                                name="{{ $metaTitleName }}"
                                                id="meta_title_{{ $language['id'] }}"
                                                class="form-control"
                                                maxlength="100"
                                                placeholder="{{ __('messages.enter_meta_title') }}"
                                                value="{{ $metaTitleVal }}"
                                                data-lang="{{ $language['id'] }}"
                                                data-required="true"
                                            >
                                            <small class="help-block with-errors text-danger"></small>
                                        </div>

                                        {{-- Meta Keywords --}}
                                        <div class="form-group col-md-6 mb-3">
                                            {{ html()->label(__('messages.meta_keywords') . ' <span class="text-danger">*</span>', "meta_keywords_{$language['id']}")->class('form-control-label language-label') }}
                                            @php
                                                $metaKeywordsVal = $servicedata ? $servicedata->translate('meta_keywords', $language['id']) : '';
                                                // Convert array to comma-separated string if needed
                                                if (is_array($metaKeywordsVal)) {
                                                    $metaKeywordsVal = implode(',', $metaKeywordsVal);
                                                }

                                                $metaKeywordsName = $language['id'] === $defaultLanguage
                                                    ? 'meta_keywords'
                                                    : "translations[{$language['id']}][meta_keywords]";
                                            @endphp
                                            <input
                                                type="text"
                                                name="{{ $metaKeywordsName }}"
                                                id="meta_keywords_{{ $language['id'] }}"
                                                class="form-control tagify-input"
                                                value="{{ $metaKeywordsVal }}"
                                                placeholder="{{ __('messages.type_and_press_enter') }}"
                                                data-lang="{{ $language['id'] }}"
                                                data-required="true"
                                            >
                                            <small class="help-block with-errors text-danger"></small>
                                            <small class="text-muted">{{ __('messages.type_and_press_enter') }}</small>
                                        </div>

                                        {{-- Meta Description --}}
                                        <div class="form-group col-12 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                {{ html()->label(__('messages.meta_description') . ' <span class="text-danger">*</span>', "meta_description_{$language['id']}")->class('form-control-label language-label') }}
                                                <span class="text-muted" style="font-size: 12px;">
                                                    @php
                                                        $metaDescVal = $servicedata ? $servicedata->translate('meta_description', $language['id']) : '';
                                                    @endphp
                                                    <span id="meta-desc-count-{{ $language['id'] }}">{{ strlen((string) $metaDescVal) }}</span>/200
                                                </span>
                                            </div>
                                            @php
                                                $metaDescName = $language['id'] === $defaultLanguage
                                                    ? 'meta_description'
                                                    : "translations[{$language['id']}][meta_description]";
                                            @endphp
                                            <textarea
                                                name="{{ $metaDescName }}"
                                                id="meta_description_{{ $language['id'] }}"
                                                class="form-control"
                                                rows="4"
                                                maxlength="200"
                                                placeholder="{{ __('messages.enter_meta_description') }}"
                                                style="min-height: 120px; resize: vertical;"
                                                data-lang="{{ $language['id'] }}"
                                                data-required="true"
                                            >{{ $metaDescVal }}</textarea>
                                            <small class="help-block with-errors text-danger"></small>
                                        </div>

                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="row">
                            <!-- <div class="form-group col-md-12">
                                    {{ html()->label(__('messages.description'), 'description')->class('form-control-label') }}
                                    {{ html()->textarea('description', $servicedata->description)->class('form-control textarea')->rows(3)->placeholder(__('messages.description')) }}
                                </div> -->
                            @if (!empty($slotservice) && $slotservice == 1)
                                <div class="form-group col-md-3">
                                    <div class="custom-control custom-switch">
                                        {{ html()->checkbox('is_slot', $servicedata->is_slot)->class('custom-control-input')->id('is_slot') }}
                                        <label class="custom-control-label"
                                            for="is_slot">{{ __('messages.slot') }}</label>
                                    </div>
                                </div>
                            @endif
                            <div class="form-group col-md-3">
                                <div class="custom-control custom-switch">
                                    {{ html()->checkbox('is_featured', $servicedata->is_featured)->class('custom-control-input')->id('is_featured') }}
                                    <label class="custom-control-label"
                                        for="is_featured">{{ __('messages.set_as_featured') }}</label>
                                </div>
                            </div>
                            <!-- @if (!empty($digitalservicedata) && $digitalservicedata->value == 1)
<div class="form-group col-md-3">
                                <div class="custom-control custom-switch">
                                    {{ Form::checkbox('digital_service', $servicedata->digital_service, null, ['class' => 'custom-control-input', 'id' => 'digital_service']) }}
                                    <label class="custom-control-label"
                                        for="digital_service">{{ __('messages.digital_service') }}</label>
                                </div>
                            </div>
@endif -->
                            @if (!empty($advancedPaymentSetting) && $advancedPaymentSetting == 1)
                                <div class="form-group col-md-3" id="is_enable_advance">
                                    <div class="custom-control custom-switch">
                                        {{ html()->checkbox('is_enable_advance_payment', $servicedata->is_enable_advance_payment)->class('custom-control-input')->id('is_enable_advance_payment') }}
                                        <label class="custom-control-label"
                                            for="is_enable_advance_payment">{{ __('messages.enable_advanced_payment') }}
                                        </label>
                                    </div>
                                </div>
                            @endif
                            <div class="form-group col-md-4" id="amount">
                                {{ html()->label(__('messages.advance_payment_amount') . ' <span class="text-danger">*</span> (%)', 'advance_payment_amount')->class('form-control-label') }}
                                {{ html()->number('advance_payment_amount', $servicedata->advance_payment_amount)->placeholder(__('messages.amount'))->class('form-control')->id('advance_payment_amount')->attributes(['min' => 1, 'max' => 99]) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>
                            @if (isset($servicedata->service_request_status) &&
                                    $servicedata->service_request_status == 'reject' &&
                                    !empty($servicedata->reject_reason))
                                <div class="form-group col-md-12 d-flex align-items-center">
                                    <label class="form-control-label mb-0 me-2 text-danger" for="reason">
                                        {{ __('messages.reason') }}:
                                    </label>
                                    <span>{{ $servicedata->reject_reason }}</span>
                                </div>
                            @endif
                        </div>


                        @if (auth()->user()->hasAnyRole(['admin', 'demo_admin']) &&
                                isset($servicedata) &&
                                $servicedata->is_service_request == 1 &&
                                (is_null($servicedata->service_request_status) || $servicedata->service_request_status == 'pending'))
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-light text-dark float-end"
                                    onclick="showRejectionConfirmation('{{ $servicedata->id }}', 'rejected')">Reject</button>
                                <button type="button" class="btn btn-sm btn-primary float-end me-3"
                                    onclick="showApprovalConfirmation('{{ $servicedata->id }}', 'approved')">Approve</button>
                            </div>
                        @elseif(auth()->user()->hasAnyRole(['admin', 'demo_admin']) &&
                                isset($servicedata->is_service_request) &&
                                ($servicedata->is_service_request == 1 || is_null($servicedata->is_service_request)) &&
                                $servicedata->service_request_status == 'reject')
                        @else
                            {{ html()->submit(__('messages.save'))->class('btn btn-md btn-primary float-end')->id('saveButton') }}
                        @endif
                        
                        {{ html()->form()->close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @php
        $data = $servicedata->providerServiceAddress->pluck('provider_address_id')->implode(',');
    @endphp
    @section('bottom_script')
        <script type="text/javascript">

        $(document).on('change', 'input[name="service_attachment[]"]', function () {
            if (this.files.length > 0) {
                $('input[name="service_attachment[]"]').removeAttr('required');
                $('#saveButton').prop('disabled', false);
            }
        });
            function preview() {
                var fileInput = event.target;
                var previewElement = document.getElementById('service_attachment_preview');
                if (fileInput.files && fileInput.files[0]) {
                    previewElement.src = URL.createObjectURL(fileInput.files[0]);
                    previewElement.style.display = 'block';
                } else {
                    previewElement.style.display = 'none';
                }
            }
            function previewSeoImage(event) {
                const preview = document.getElementById('seo_image_preview');
                const file = event.target.files[0];
                if (preview && file) {
                    preview.src = URL.createObjectURL(file);
                    preview.style.display = 'block';
                }
            }
            var discountInput = document.getElementById('discount');
            var discountError = document.getElementById('discount-error');


            document.addEventListener('DOMContentLoaded', function() {
                if (typeof renderedDataTable === 'undefined') {
                    renderedDataTable = $('#datatable').DataTable();
                }

                var initialProviderId = document.getElementById('provider_id').value;
                selectprovider({
                    value: initialProviderId
                });

                  const textareas = document.querySelectorAll('.description-field');

        textareas.forEach(function (textarea) {
            textarea.addEventListener('input', function () {
                const langId = textarea.getAttribute('data-lang');
                const countSpan = document.getElementById('char-count-' + langId);

                if (countSpan) {
                    countSpan.textContent = textarea.value.length;
                }
            });
        });


                 const addLink = document.getElementById('add_provider_address_link');

    if (addLink) {
        addLink.addEventListener('click', function(event) {
            event.preventDefault();

            const providerId = document.getElementById('provider_id').value;
            let providerAddressCreateUrl = "{{ route('provideraddress.create', ['provideraddress' => '']) }}";

            providerAddressCreateUrl = providerAddressCreateUrl.replace('provideraddress=',
                'provideraddress=' + providerId);

            window.location.href = providerAddressCreateUrl;
        });
    }

            });

            function updateServiceStatus(serviceId, status, reason = '') {
                $.ajax({
                    url: '{{ route('service.updateStatus') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: serviceId,
                        status: status,
                        reason: reason
                    },
                    success: function(response) {
                        if (response.success) {
                            if (status === 'approved') {
                                window.location.href = '{{ route('service.provider-service-request') }}';
                            } else {
                                var badge = '<span class="badge badge-danger">Rejected</span>';
                                var row = $('#datatable-row-' + serviceId);
                                row.find('.service-status').html(badge);
                                window.location.href = '{{ route('service.provider-service-request') }}';
                                renderedDataTable.ajax.reload();
                            }
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred while updating the status.',
                                icon: 'error',
                                confirmButtonText: 'Try Again'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'An error occurred while processing the request.',
                            icon: 'error',
                            confirmButtonText: 'Try Again'
                        });
                    }
                });
            }

            function showApprovalConfirmation(serviceId, status) {
                Swal.fire({
                    icon: 'success',
                    title: '',
                    html: '<span style="color: #333; font-weight: 550; font-size: 20px;">' +
                        '{{ __('messages.are_you_sure_you_want_to') }} ' +
                        (status === "approved" ?
                            '{{ __('messages.approve_this_service_into_list') }}' :
                            '{{ __('messages.reject_this_service_into_list') }}') +
                        '</span>',
                    showCancelButton: true,
                    cancelButtonText: '<span style="color: black; font-weight: 500;">{{ __('messages.cancel') }}</span>', // Black text, medium weight
                    confirmButtonText: '{{ __('messages.approve') }}',
                    confirmButtonColor: '#6366F1',
                    cancelButtonColor: '#E5E7EB',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateServiceStatus(serviceId, status);
                    }
                });
            }

            function showRejectionConfirmation(serviceId) {
                Swal.fire({
                    title: `<h2 style="font-size: 20px; font-weight: bold; margin-bottom: 15px;">{{ __('messages.reject_service_confirmation_title') }}</h2>`,
                    text: '{{ __('messages.provide_rejection_reason') }}',
                    html: `
                    <div style="text-align: left; margin-top: 5px; background-color: #f0f0f0; padding: 20px; border-radius: 10px;">
                        <label for="reject-reason" style="font-size: 14px; font-weight: bold; display: block; margin-bottom: 5px;">
                            Provide the reason for rejection
                        </label>
                        <textarea id="reject-reason" placeholder="e.g. Insufficient details"
                            style="width: 100%; height: 100px; background-color: #ffffff; border: 1px solid #ccc;
                            border-radius: 8px; padding: 10px; font-size: 14px; resize: none;"></textarea>
                    </div>
                    `,
                    icon: 'error',
                    inputAttributes: {
                        'aria-label': '{{ __('messages.rejection_reason_aria') }}'
                    },
                    showCancelButton: true,
                    confirmButtonText: '<span style="font-size: 14px; font-weight: bold;">{{ __('messages.reject') }}</span>',
                    cancelButtonText: '<span style="font-size: 14px; font-weight: bold; color: black;">{{ __('messages.cancel') }}</span>',
                    cancelButtonColor: '#f0f0f0',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        var rejectionReason = document.getElementById('reject-reason').value;
                        if (rejectionReason.trim() !== "") {
                            updateServiceStatus(serviceId, 'rejected', rejectionReason);
                        } else {
                            Swal.fire({
                                title: '{{ __('messages.error') }}',
                                text: '{{ __('messages.rejection_reason_required') }}',
                                icon: 'error',
                                confirmButtonText: '{{ __('messages.okay') }}'
                            });
                        }
                    }
                });

            }

            function selectprovider(selectElement) {
                var providerId = selectElement.value;
                var zoneDropdown = $('#service_zones');

                if (providerId) {
                    // Immediately clear the zone selection when provider changes
                    zoneDropdown.val(null).trigger('change');
                    
                    // Load zones for the selected provider
                    $.ajax({
                        url: "{{ route('ajax-list', ['type' => 'zone']) }}",
                        data: {
                            provider_id: providerId
                        },
                        success: function(result) {
                            // Clear existing options
                            zoneDropdown.empty();

                            // Add new options from the response
                            if (result.results && result.results.length > 0) {
                                $.each(result.results, function(index, item) {
                                    var option = new Option(item.text, item.id, false, false);
                                    zoneDropdown.append(option);
                                });
                            }

                            // Initialize Select2
                            zoneDropdown.select2({
                                width: '100%',
                                placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.zone')]) }}",
                                allowClear: true
                            });

                            // If we have selected zones from editing AND this is the initial load (not a provider change)
                            // Only set selected zones if the provider hasn't actually changed
                            @if (isset($selectedZones) && !empty($selectedZones) && isset($servicedata->provider_id))
                                var selectedZones = @json($selectedZones);
                                var originalProviderId = "{{ $servicedata->provider_id ?? '' }}";
                                
                                // Only restore selected zones if we're loading the original provider
                                if (selectedZones && selectedZones.length > 0 && providerId == originalProviderId) {
                                    zoneDropdown.val(selectedZones).trigger('change');
                                }
                            @endif
                        }
                    });
                } else {
                    zoneDropdown.empty().trigger('change');
                }
            }

            // Initialize Select2 for service zones on page load
            // $(document).ready(function() {
            //     // Initialize the zone dropdown
            //     $('#service_zones').select2({
            //         width: '100%',
            //         placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.zone')]) }}",
            //     });

            //     // Initialize provider dropdown with Select2
            //     $('#provider_id').select2({
            //         width: '100%',
            //         placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.provider')]) }}",
            //         allowClear: true
            //     });

            //     // Always call selectprovider on load
            //     var initialProviderId = $('#provider_id').val();
            //     if (initialProviderId) {
            //         selectprovider({
            //             value: initialProviderId
            //         });
            //     }
            // });

            // Initialize Select2 for service zones on page load
$(document).ready(function() {
    // Initialize the zone dropdown
    $('#service_zones').select2({
        width: '100%',
        placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.zone')]) }}",
        allowClear: true  // <-- Add this
    });

    // Initialize provider dropdown with Select2
    $('#provider_id').select2({
        width: '100%',
        placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.provider')]) }}",
        allowClear: true
    });

    // Always call selectprovider on load
    var initialProviderId = $('#provider_id').val();
    if (initialProviderId) {
        selectprovider({
            value: initialProviderId
        });
    }
});


            // Preview selected image
function previewServiceImage(event) {
    const fileInput = event.target;
    const previewContainer = document.getElementById("service_attachment_preview_container");
    const errorBlock = fileInput.parentElement.querySelector('.help-block.with-errors.text-danger');

    // Clear previously added previews (but keep DB images already rendered in Blade)
    const newPreviews = previewContainer.querySelectorAll(".new-upload");
    newPreviews.forEach(el => el.remove());

    // Reset error message
    if (errorBlock) errorBlock.textContent = '';

    if (fileInput.files) {
        // Allowed file types
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        let hasInvalidFiles = false;
        let errorMessage = '';

        // Check each file
        for (let i = 0; i < fileInput.files.length; i++) {
            const file = fileInput.files[i];

            // Check file size (10MB = 10 * 1024 * 1024 bytes)
            if (file.size > 10 * 1024 * 1024) {
                errorMessage = 'Each image must be less than 10MB.';
                hasInvalidFiles = true;
                break;
            }

            // Check file type
            if (!allowedTypes.includes(file.type)) {
                errorMessage = 'Please upload a valid image in .jpg , .png , .gif or .jpeg format';
                hasInvalidFiles = true;
                break;
            }
        }

        // If any file is invalid, clear the input and show error
        if (hasInvalidFiles) {
            fileInput.value = '';
            if (errorBlock) {
                errorBlock.textContent = errorMessage;
            } else {
                // Use Snackbar instead of alert
                Snackbar.show({
                    text: errorMessage,
                    pos: 'bottom-center',
                    backgroundColor: '#dc3545',
                    actionTextColor: 'white'
                });
            }
            return;
        }

        // Process valid files
        Array.from(fileInput.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                // Create wrapper
                const col = document.createElement("div");
                col.classList.add("col-md-2", "mb-2", "new-upload");

                // Create preview container
                const container = document.createElement("div");
                container.classList.add("image-preview-container");

                // Image element
                const img = document.createElement("img");
                img.src = e.target.result;
                img.classList.add("attachment-image", "mt-1");
                img.style.width = "150px";

                // Remove button
                const removeBtn = document.createElement("a");
                removeBtn.href = "javascript:void(0)";
                removeBtn.classList.add("text-danger", "remove-file");
                removeBtn.innerHTML = '<i class="ri-close-circle-line"></i>';
                removeBtn.onclick = function () {
                    col.remove();

                // If user removes everything, also clear file input
                    if (
                        previewContainer.querySelectorAll(".new-upload").length === 0 &&
                        previewContainer.querySelectorAll(".db-upload").length === 0
                    ) {
                        const saveButton = document.getElementById('saveButton');
                        saveButton.disabled = true;
                        fileInput.value = "";
                        $('input[name="service_attachment[]"]').attr("required", true);
                    }
                };

                // Append
                container.appendChild(img);
                container.appendChild(removeBtn);
                col.appendChild(container);
                previewContainer.appendChild(col);
            };

            reader.readAsDataURL(file);
        });
    }
}



            discountInput.addEventListener('input', function() {
                var discountValue = parseFloat(discountInput.value);
                if (isNaN(discountValue) || discountValue < 0 || discountValue > 99) {
                    discountError.textContent = "{{ __('Discount value should be between 0 to 99') }}";
                } else {
                    discountError.textContent = "";
                }
            });

            var isEnableAdvancePayment = $("input[name='is_enable_advance_payment']").prop('checked');

            var priceType = $("#price_type").val();

            enableAdvancePayment(priceType);
            checkEnablePayment(isEnableAdvancePayment);

            $("#is_enable_advance_payment").change(function() {
                isEnableAdvancePayment = $(this).prop('checked');
                checkEnablePayment(isEnableAdvancePayment);
                updateAmountVisibility(priceType, isEnableAdvancePayment);
            });

            $("#price_type").change(function() {
                priceType = $(this).val();
                enableAdvancePayment(priceType);
                updateAmountVisibility(priceType, isEnableAdvancePayment);
            });

            function checkEnablePayment(value) {
                $("#amount").toggleClass('d-none', !value);
                $('#advance_payment_amount').prop('required', false);
            }

            function enableAdvancePayment(type) {
                $("#is_enable_advance").toggleClass('d-none', type !== 'fixed');
                
                // When switching to hourly or free, uncheck the toggle and clear the amount
                if (type !== 'fixed') {
                    $("#is_enable_advance_payment").prop('checked', false);
                    $("#advance_payment_amount").val('');
                    $("#advance_payment_amount").prop('required', false);
                    $("#amount").addClass('d-none');
                }
            }

            function updateAmountVisibility(type, isEnableAdvancePayment) {
                if (type === 'fixed' && !$("#is_enable_advance").hasClass('d-none') && isEnableAdvancePayment) {
                    $("#amount").removeClass('d-none');
                } else {
                    $("#amount").addClass('d-none');
                }
            }

            (function($) {
                "use strict";
                $(document).ready(function() {
                    var provider_id = "{{ isset($servicedata->provider_id) ? $servicedata->provider_id : '' }}";
                    var provider_address_id = "{{ isset($data) ? $data : [] }}";

                    var category_id = "{{ isset($servicedata->category_id) ? $servicedata->category_id : '' }}";
                    var subcategory_id =
                        "{{ isset($servicedata->subcategory_id) ? $servicedata->subcategory_id : '' }}";

                    var price_type = "{{ isset($servicedata->type) ? $servicedata->type : '' }}";

                    providerAddress(provider_id, provider_address_id)
                    getSubCategory(category_id, subcategory_id)
                    priceformat(price_type)

                    $(document).on('change', '#provider_id', function() {
                        var provider_id = $(this).val();
                        $('#provider_address_id').empty();
                        providerAddress(provider_id, provider_address_id);
                    })
                    $(document).on('change', '#category_id', function() {
                        var category_id = $(this).val();
                        $('#subcategory_id').empty();
                        getSubCategory(category_id, subcategory_id);
                    })
                    $(document).on('change', '#price_type', function() {
                        var price_type = $(this).val();
                        priceformat(price_type);
                    })


                    $('.galary').each(function(index, value) {
                        let galleryClass = $(value).attr('data-gallery');
                        $(galleryClass).magnificPopup({
                            delegate: 'a#attachment_files',
                            type: 'image',
                            gallery: {
                                enabled: true,
                                navigateByImgClick: true,
                                preload: [0,
                                    1
                                ] // Will preload 0 - before current, and 1 after the current image
                            },
                            callbacks: {
                                elementParse: function(item) {
                                    if (item.el[0].className.includes('video')) {
                                        item.type = 'iframe',
                                            item.iframe = {
                                                markup: '<div class="mfp-iframe-scaler">' +
                                                    '<div class="mfp-close"></div>' +
                                                    '<iframe class="mfp-iframe" frameborder="0" allowfullscreen></iframe>' +
                                                    '<div class="mfp-title">Some caption</div>' +
                                                    '</div>'
                                            }
                                    } else {
                                        item.type = 'image',
                                            item.tLoading = 'Loading image #%curr%...',
                                            item.mainClass = 'mfp-img-mobile',
                                            item.image = {
                                                tError: '<a href="%url%">The image #%curr%</a> could not be loaded.'
                                            }
                                    }
                                }
                            }
                        })
                    })
                })

                function providerAddress(provider_id, provider_address_id = "") {
                    var provider_address_route =
                        "{{ route('ajax-list', ['type' => 'provider_address', 'provider_id' => '']) }}" + provider_id;
                    provider_address_route = provider_address_route.replace('amp;', '');

                    $.ajax({
                        url: provider_address_route,
                        success: function(result) {
                            $('#provider_address_id').select2({
                                width: '100%',
                                placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.provider_address')]) }}",
                                data: result.results
                            });
                            if (provider_address_id != "") {
                                $('#provider_address_id').val(provider_address_id.split(',')).trigger('change');
                            }
                        }
                    });
                }

                function getSubCategory(category_id, subcategory_id = "") {
                    var get_subcategory_list =
                        "{{ route('ajax-list', ['type' => 'subcategory_list', 'category_id' => '']) }}" + category_id;
                    get_subcategory_list = get_subcategory_list.replace('amp;', '');

                    $.ajax({
                        url: get_subcategory_list,
                        success: function(result) {
                            $('#subcategory_id').select2({
                                width: '100%',
                                placeholder: "{{ trans('messages.select_name', ['select' => trans('messages.subcategory')]) }}",
                                data: result.results
                            });
                            if (subcategory_id != "") {
                                $('#subcategory_id').val(subcategory_id).trigger('change');
                            }
                        }
                    });
                }
                var price = "{{ isset($servicedata->price) ? $servicedata->price : '' }}";
                var discount = "{{ isset($servicedata->discount) ? $servicedata->discount : '' }}";

                function priceformat(value) {
                    if (value == 'free') {
                        $('#price').val(0);
                        $('#price').attr("readonly", true)

                        $('#discount').val(0);
                        $('#discount').attr("readonly", true)

                    } else {
                        $('#price').val(price);
                        $('#price').attr("readonly", false)
                        $('#discount').val(discount);
                        $('#discount').attr("readonly", false)
                    }
                }
            })(jQuery);

            document.addEventListener('DOMContentLoaded', function() {
                checkImage();
            });

            function checkImage() {
                var id = @json($servicedata->id);
                var route = "{{ route('check-image', ':id') }}";
                route = route.replace(':id', id);
                var type = 'service';

                $.ajax({
                    url: route,
                    type: 'GET',
                    data: {
                        type: type,
                    },
                    success: function(result) {
                        var attachments = result.results;

                        if (attachments && attachments.length === 0) {
                            $('input[name="service_attachment[]"]').attr('required', 'required');
                        } else {
                            $('input[name="service_attachment[]"]').removeAttr('required');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            }



            //     $(document).ready(function () {
            //     // Function to initialize Select2 for a given element
            //     function initializeSelect2($element) {
            //         const selectedId = $element.data('selected-id'); // Get the preselected ID
            //         const ajaxUrl = $element.data('ajax--url');
            //         const placeholder = $element.data('placeholder');

            //         $element.select2({
            //             placeholder: placeholder,
            //             ajax: {
            //                 url: ajaxUrl,
            //                 dataType: 'json',
            //                 delay: 250,
            //                 data: function (params) {
            //                     return {
            //                         q: params.term, // Search term
            //                     };
            //                 },
            //                 processResults: function (data) {
            //                     return {
            //                         results: data.map(function (item) {
            //                             return { id: item.id, text: item.text };
            //                         }),
            //                     };
            //                 },
            //                 cache: true,
            //             },
            //         });

            //         // Preselect the value during edit
            //         if (selectedId) {
            //             $.ajax({
            //                 url: ajaxUrl, // Fetch the preselected item
            //                 data: { id: selectedId },
            //                 dataType: 'json',
            //                 success: function (response) {
            //                     const selectedItem = response.find(item => item.id == selectedId);
            //                     if (selectedItem) {
            //                         // Create and append the selected option
            //                         const option = new Option(selectedItem.text, selectedItem.id, true, true);
            //                         $element.append(option).trigger('change');
            //                     }
            //                 },
            //                 error: function () {
            //                     console.error('Failed to fetch selected item for:', selectedId);
            //                 },
            //             });
            //         }
            //     }
            //     function synchronizeDropdowns(type, selectedId) {
            //         $(`.select2js-${type}`).each(function () {
            //             const $dropdown = $(this);

            //             // Fetch the translated value for the selected ID
            //             $.ajax({
            //                 url: $dropdown.data('ajax--url'),
            //                 data: { id: selectedId },
            //                 dataType: 'json',
            //                 success: function (response) {
            //                     const translatedItem = response.find(item => item.id == selectedId);
            //                     if (translatedItem) {
            //                         const option = new Option(translatedItem.text, translatedItem.id, true, true);
            //                         $dropdown.empty().append(option).trigger('change');
            //                     }
            //                 },
            //             });
            //         });
            //     }
            //     // Function to update subcategory dropdown based on category selection
            //     function updateSubcategoryDropdown($categoryDropdown, $subcategoryDropdown) {
            //     // Ensure a single change listener
            //     $categoryDropdown.off('change').on('change', function () {
            //         const categoryId = $(this).val();

            //         if (!categoryId) {
            //             $subcategoryDropdown.empty().trigger('change'); // Clear subcategory
            //             return;
            //         }

            //         const subcategoryAjaxUrl = $subcategoryDropdown
            //             .data('ajax--url')
            //             .replace(/category_id=[^&]*/, `category_id=${categoryId}`);

            //         // Safely destroy Select2 instance if initialized
            //         if ($subcategoryDropdown.hasClass('select2-hidden-accessible')) {
            //             $subcategoryDropdown.select2('destroy');
            //         }

            //         $subcategoryDropdown.empty(); // Clear current options

            //         // Update the AJAX URL dynamically
            //         $subcategoryDropdown.data('ajax--url', subcategoryAjaxUrl);

            //         // Reinitialize Select2 with the new URL
            //         initializeSelect2($subcategoryDropdown);
            //     });
            // }


            //     // Initialize Select2 for all category and subcategory dropdowns
            //     $('.select2js-category').each(function () {
            //         const $categoryDropdown = $(this);
            //         console.log("Dropdown data-selected-id:", $categoryDropdown.data('selected-id'));

            //         const languageId = $categoryDropdown.data('language-id');
            //         const $subcategoryDropdown = $(`#subcategory_id_${languageId}`);

            //         // Initialize subcategory dropdown first to avoid empty state issues
            //         updateSubcategoryDropdown($categoryDropdown, $subcategoryDropdown);

            //         // Then initialize the category dropdown
            //         initializeSelect2($categoryDropdown);
            //     });
            //     // Listen for changes and synchronize all dropdowns of the same type
            //     $('[data-select2-type]').on('select2:select', function (e) {
            //         const $dropdown = $(this);
            //         const selectedId = e.params.data.id;
            //         const type = $dropdown.data('select2-type');

            //         synchronizeDropdowns(type, selectedId);
            //     });


            //     // Handle language toggle
            //     $('.language-toggle').on('click', function () {
            //         const languageId = $(this).data('language-id');
            //         $('.language-form').hide();
            //         $(`#form-language-${languageId}`).show();
            //     });
            // });
            $(document).ready(function() {
                $('#is_enable_advance_payment').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#advance_payment_amount').prop('required', true);
                    } else {
                        $('#advance_payment_amount').prop('required', false);
                    }
                });
                if ($('#is_enable_advance_payment').is(':checked')) {
                    $('#advance_payment_amount').prop('required', true);
                }
            });
        </script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css">
        <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
        <script>

document.addEventListener('DOMContentLoaded', function () {
    // Character counter for all languages
    @foreach ($language_array as $language)
        const metaTitleInput{{ $language['id'] }} = document.getElementById('meta_title_{{ $language["id"] }}');
        const metaDescInput{{ $language['id'] }} = document.getElementById('meta_description_{{ $language["id"] }}');

        if (metaTitleInput{{ $language['id'] }}) {
            metaTitleInput{{ $language['id'] }}.addEventListener('input', function () {
                document.getElementById('meta-title-count-{{ $language["id"] }}').textContent = this.value.length;
            });
        }

        if (metaDescInput{{ $language['id'] }}) {
            metaDescInput{{ $language['id'] }}.addEventListener('input', function () {
                document.getElementById('meta-desc-count-{{ $language["id"] }}').textContent = this.value.length;
            });
        }
    @endforeach
});
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.querySelector('input[name=meta_keywords]');
        if (input) {
            new Tagify(input, {
                delimiters: ",",
                whitelist: [],
                dropdown: { enabled: 0 },
                originalInputValueFormat: valuesArr => JSON.stringify(valuesArr.map(item => item.value))
            });
        }

        // SEO Enable/Disable Switch functionality
        var seoEnabledSwitch = document.getElementById('seo_enabled');
        var seoFieldsSection = document.getElementById('seo_fields_section');
        var metaTitle = document.getElementById('meta_title');
        var metaTitleCount = document.getElementById('meta-title-count');
        var metaDesc = document.getElementById('meta_description');
        var metaDescCount = document.getElementById('meta-desc-count');
        var metaKeywords = document.getElementById('meta_keywords');
        var seoImage = document.querySelector('input[name="seo_image"]');

        function toggleSeoFields() {
            if (seoEnabledSwitch.checked) {
                seoFieldsSection.style.display = 'block';
                    var seoImageInput = document.querySelector('input[name="seo_image"]');
                    if (seoImageInput.getAttribute('data-has-image') == '0') {
                        seoImage.setAttribute('required', 'required');
                    }else{
                        seoImage.removeAttribute('required');
                    }
                    // Do not restore old data, keep fields as is (empty if just toggled on)
            } else {
                seoFieldsSection.style.display = 'none';
                // Clear SEO fields when disabling
                if (metaTitle) {
                    metaTitle.value = '';
                    if (metaTitleCount) metaTitleCount.textContent = '0';
                }
                if (metaDesc) {
                    metaDesc.value = '';
                    if (metaDescCount) metaDescCount.textContent = '0';
                }
                if (metaKeywords) {
                    metaKeywords.value = '';
                    if (metaKeywords.tagify) metaKeywords.tagify.removeAllTags();
                }
                if (seoImage) {
                    seoImage.value = '';
                    var seoImagePreview = document.getElementById('seo_image_preview');
                    if (seoImagePreview) {
                        seoImagePreview.src = '';
                        seoImagePreview.style.display = 'none';
                    }
                    seoImage.removeAttribute('required');
                }
            }
        }

        // Initial state: show/hide and populate fields based on backend data
        if (seoEnabledSwitch) {
            if (seoEnabledSwitch.checked) {
                seoFieldsSection.style.display = 'block';
                var seoImageInput = document.querySelector('input[name="seo_image"]');
                    if (seoImageInput.getAttribute('data-has-image') == '0') {
                        seoImage.setAttribute('required', 'required');
                    }else{
                        seoImage.removeAttribute('required');
                    }
                // The Blade template will have already populated the fields with $servicedata values
            } else {
                seoFieldsSection.style.display = 'none';
                // Clear fields (in case of browser autofill)
                if (metaTitle) metaTitle.value = '';
                if (metaDesc) metaDesc.value = '';
                if (metaKeywords) {
                    metaKeywords.value = '';
                    if (metaKeywords.tagify) metaKeywords.tagify.removeAllTags();
                }
                if (seoImage) {
                    seoImage.value = '';
                    var seoImagePreview = document.getElementById('seo_image_preview');
                    if (seoImagePreview) {
                        seoImagePreview.src = '';
                        seoImagePreview.style.display = 'none';
                    }
                    seoImage.removeAttribute('required');
                }
                if (metaTitleCount) metaTitleCount.textContent = '0';
                if (metaDescCount) metaDescCount.textContent = '0';
            }
            // Add event listener
            seoEnabledSwitch.addEventListener('change', toggleSeoFields);
        }
    });

document.addEventListener('DOMContentLoaded', function() {
    // SEO Image validation
    const seoImageInput = document.querySelector('input[name="seo_image"]');
    const seoImageError = document.getElementById('seo_image_error');
    if (seoImageInput) {
        seoImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    event.target.value = '';
                    seoImageError.textContent = 'Only JPG, JPEG, and PNG files are allowed.';
                    document.getElementById('seo_image_preview').style.display = 'none'; // Hide preview on error
                    seoImageInput.setAttribute('data-has-image', '0');
                } else {
                    seoImageError.textContent = '';
                }
            } else {
                seoImageInput.setAttribute('data-has-image', seoImageInput.value ? '1' : '0');
                seoImageError.textContent = '';
            }
        });
    }
    // Category Image validation
    const categoryImageInput = document.querySelector('input[name="category_image"]');
    const categoryImageError = document.getElementById('category_image_error');
    if (categoryImageInput) {
        categoryImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    event.target.value = '';
                    categoryImageError.textContent = 'Please upload a valid image in .jpg , .png , .gif or .jpeg format';
                    document.getElementById('category_image_preview').style.display = 'none'; // Hide preview on error
                } else {
                    categoryImageError.textContent = '';
                }
            } else {
                categoryImageError.textContent = '';
            }
        });
    }
    // Prevent form submit if file type error exists
    const form = document.getElementById('category-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (categoryImageError.textContent || seoImageError.textContent) {
                e.preventDefault();
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // 10MB in bytes
    const MAX_SIZE = 10 * 1024 * 1024;

    // Category Image validation (10MB limit)
    const categoryImageInput = document.querySelector('input[name="category_image"]');
    if (categoryImageInput) {
        categoryImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const errorBlock = document.getElementById('category_image_error');
            if (file) {
                if (file.size > MAX_SIZE) {
                    event.target.value = '';
                    if (errorBlock) {
                        errorBlock.textContent = '{{ __("messages.image_size_must_be_less_than_10mb") }}';
                    } else {
                        alert('{{ __("messages.image_size_must_be_less_than_10mb") }}');
                    }
                    var preview = document.getElementById('category_image_preview');
                    if (preview) preview.style.display = 'none';
                } else {
                    if (errorBlock) errorBlock.textContent = '';
                }
            } else {
                if (errorBlock) errorBlock.textContent = '';
            }
        });
    }

    // SEO Image validation (10MB limit)
    const seoImageInput = document.querySelector('input[name="seo_image"]');
    if (seoImageInput) {
        seoImageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            const errorBlock = document.getElementById('seo_image_error');
            if (file) {
                if (file.size > MAX_SIZE) {
                    event.target.value = '';
                    if (errorBlock) {
                        errorBlock.textContent = 'Image size must be less than 10MB.';
                    } else {
                        alert('Image size must be less than 10MB.');
                    }
                    var preview = document.getElementById('seo_image_preview');
                    if (preview) preview.style.display = 'none';
                    seoImageInput.setAttribute('data-has-image', '0');
                } else {
                    if (errorBlock) errorBlock.textContent = '';
                    seoImageInput.setAttribute('data-has-image', '1');
                }
            } else {
                seoImageInput.setAttribute('data-has-image', '1');
                if (errorBlock) errorBlock.textContent = '';

            }
        });
    }


});
</script>
        <script type="text/javascript">

    // Service Attachment validation (10MB limit per file and file type validation)
    const serviceAttachmentInputs = document.querySelectorAll('input[name="service_attachment[]"]');
    serviceAttachmentInputs.forEach(function(input) {
        input.addEventListener('change', function(event) {
            const files = event.target.files;
            const errorBlock = input.parentElement.querySelector('.help-block.with-errors.text-danger');
            let tooLarge = false;
            let invalidType = false;

            // Allowed file types
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];

            for (let i = 0; i < files.length; i++) {
                // Check file size (10MB = 10 * 1024 * 1024 bytes)
                if (files[i].size > 10 * 1024 * 1024) {
                    tooLarge = true;
                    break;
                }

                // Check file type
                if (!allowedTypes.includes(files[i].type)) {
                    invalidType = true;
                    break;
                }
            }

            if (tooLarge) {
                event.target.value = '';
                if (errorBlock) {
                    errorBlock.textContent = 'Each image must be less than 10MB.';
                } else {
                    // Use Snackbar instead of alert
                    Snackbar.show({
                        text: 'Each image must be less than 10MB.',
                        pos: 'bottom-center',
                        backgroundColor: '#dc3545',
                        actionTextColor: 'white'
                    });
                }
                // Clear preview
                const previewContainer = document.getElementById("service_attachment_preview_container");
                if (previewContainer) {
                    const newPreviews = previewContainer.querySelectorAll(".new-upload");
                    newPreviews.forEach(el => el.remove());
                }
            } else if (invalidType) {
                event.target.value = '';
                if (errorBlock) {
                    errorBlock.textContent = 'Please upload a valid image in .jpg , .png , .gif or .jpeg format';
                } else {
                    // Use Snackbar instead of alert
                    Snackbar.show({
                        text: 'Please upload a valid image in .jpg , .png , .gif or .jpeg format',
                        pos: 'bottom-center',
                        backgroundColor: '#dc3545',
                        actionTextColor: 'white'
                    });
                }
                // Clear preview
                const previewContainer = document.getElementById("service_attachment_preview_container");
                if (previewContainer) {
                    const newPreviews = previewContainer.querySelectorAll(".new-upload");
                    newPreviews.forEach(el => el.remove());
                }
            } else {
                if (errorBlock) errorBlock.textContent = '';
            }
        });
    });

    $(document).ready(function () {
        $('.select2js').select2({ width: '100%' });

        function toggleShopDropdown(preselectedIds = []) {
            const visitType = $('#visit_type').val();
            const providerId = $('#provider_id').val();

            if (visitType === 'on_shop' && providerId) {
                $('#shop-select-wrapper').removeClass('d-none');
                $('#shop_ids').html('<option disabled selected>Loading...</option>').trigger('change');

                $.ajax({
                    url: '{{ route('service.shops-list') }}',
                    type: 'GET',
                    data: { provider_id: providerId },
                    dataType: 'json',
                    success: function(response) {
                        $('#shop_ids').empty();
                        $.each(response.data, function(key, shop) {
                            const isSelected = preselectedIds.includes(shop.id);
                            $('#shop_ids').append(
                                $('<option>', {
                                    value: shop.id,
                                    text: shop.shop_name,
                                    selected: isSelected
                                })
                            );
                        });
                        $('#shop_ids').trigger('change');
                    },
                    error: function() {
                        console.error('Failed to load shop data.');
                        $('#shop_ids').html('<option disabled selected>Failed to load shops</option>').trigger('change');
                    }
                });
            } else {
                $('#shop-select-wrapper').addClass('d-none');
                $('#shop_ids').empty().trigger('change');
            }
        }

        // Bind events
        $('#visit_type').on('change', () => toggleShopDropdown([]));
        $('#provider_id').on('change', () => toggleShopDropdown([]));

        // Initial load for edit
        @if(isset($servicedata) && $servicedata->visit_type === 'on_shop')
            const preselectedShops = @json($servicedata->shops->pluck('id')->toArray());
            toggleShopDropdown(preselectedShops);
        @else
            toggleShopDropdown([]);
        @endif
    });
</script>
@endsection
</x-master-layout>