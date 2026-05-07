{{ html()->form('POST', route('seosetting'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->open() }}

{{ html()->hidden('id',$seosetting->id ?? null)->class('form-control')->placeholder('id') }}
{{ html()->hidden('page')->value($page)->class('form-control')->placeholder('id') }}

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{__('messages.seo_settings')}}</h4>
            </div>
            <div class="card-body">
                @include('partials._language_toggale')
                <!-- First Row: SEO Image, Meta Title, Meta Keywords -->
                <div class="row">
                    <div class="form-group col-md-4 mb-3">
                        <label class="form-control-label language-label">
                            {{ __('messages.seo_image') }} <span class="text-danger">*</span>
                        </label>
                        <div class="custom-file">
                            <input type="file" name="seo_image" class="custom-file-input" accept="image/*" id="seo_image" onchange="previewSeoImage(event)">
                            <label class="custom-file-label upload-label" for="seo_image">{{ __('messages.choose_file', ['file' => __('messages.seo_image')]) }}</label>
                        </div>
                        <small class="help-block with-errors text-danger"></small>
                        @if ($errors->has('seo_image'))
                            <span class="text-danger">{{ $errors->first('seo_image') }}</span>
                        @endif
                        @php
                            $seoImageUrl = isset($seosetting) && $seosetting->getFirstMediaUrl('seo_image') ? $seosetting->getFirstMediaUrl('seo_image') : null;
                        @endphp
                        <img id="seo_image_preview" src="{{ $seoImageUrl }}" alt="{{ __('messages.seo_image') }}" style="max-width: 100px; margin-top: 10px; @if(empty($seoImageUrl)) display: none; @endif" />
                    </div>
                    @foreach ($language_array as $language)
                    <div id="form-language-{{ $language['id'] }}" class="language-form" style="display: {{ $language['id'] == app()->getLocale() ? 'block' : 'none' }};">
                                        
                    <div class="form-group col-md-4 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-control-label language-label">
                                {{ __('messages.meta_title') }} <span class="text-danger">*</span>
                            </label>
                            <span class="text-muted" style="font-size: 12px;">
                                @php
                                    $metaTitleVal = $language['id'] === 'en'
                                        ? ($seosetting->meta_title ?? '')
                                        : ($seosetting ? $seosetting->translate('meta_title', $language['id']) : '');
                                @endphp
                                <span id="meta-title-count-{{ $language['id'] }}">{{ strlen((string) $metaTitleVal) }}</span>/100
                            </span>
                        </div>
                        @php
                            $metaTitleName = $language['id'] === 'en'
                                ? 'meta_title'
                                : "translations[{$language['id']}][meta_title]";
                            $metaTitleVal = $language['id'] === 'en'
                                ? ($seosetting->meta_title ?? '')
                                : ($seosetting ? $seosetting->translate('meta_title', $language['id']) : '');
                        @endphp
                        <input type="text" name="{{ $metaTitleName }}"
                            id="meta_title_{{ $language['id'] }}"
                            class="form-control"
                            maxlength="100"
                            placeholder="{{ __('messages.enter_meta_title') }}"
                            value="{{ $metaTitleVal }}"
                            data-lang="{{ $language['id'] }}"
                            data-required="true"
                        >
                        <small class="help-block with-errors text-danger"></small>
                        @if ($errors->has('meta_title'))
                            <span class="text-danger">{{ $errors->first('meta_title') }}</span>
                        @endif
                    </div>
                    <div class="form-group col-md-4 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-control-label language-label">
                                {{ __('messages.meta_keywords') }} <span class="text-danger">*</span>
                            </label>
                        </div>
                        @php
                            if ($language['id'] === 'en') {
                                $rawKeywords = $seosetting->meta_keywords ?? [];
                            } else {
                                $rawKeywords = $seosetting?->translate('meta_keywords', $language['id']) ?? [];
                            }

                            // Normalize for both string and array formats
                            if (is_string($rawKeywords)) {
                                // If accidentally stored as JSON string (like '["x","y"]')
                                $keywordsArray = json_decode($rawKeywords, true) ?? [];
                            } elseif (is_array($rawKeywords)) {
                                $keywordsArray = $rawKeywords;
                            } else {
                                $keywordsArray = [];
                            }

                            $metaKeywordsVal = json_encode($keywordsArray); // Always returns JSON string like ["x","y"]

                            $metaKeywordsName = $language['id'] === 'en'
                                ? 'meta_keywords'
                                : "translations[{$language['id']}][meta_keywords]";
                        @endphp

                        <input
                            type="text"
                            class="form-control meta-keywords-input"
                            name="{{ $metaKeywordsName }}"
                            id="meta_keywords_{{ $language['id'] }}"
                            value='{{ $metaKeywordsVal }}'
                            placeholder="{{ __('messages.type_and_press_enter') }}"
                            data-lang="{{ $language['id'] }}"
                            data-required="true"
                        />
                        <small class="text-muted">{{ __('messages.type_and_press_enter') }}</small>
                        @if ($errors->has('meta_keywords'))
                            <span class="text-danger">{{ $errors->first('meta_keywords') }}</span>
                        @endif
                    </div>
               
                    <div class="form-group col-12 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-control-label language-label">
                                {{ __('messages.meta_description') }}
                            </label>
                        </div>
                        @php
                            $metaDescriptionName = $language['id'] === 'en'
                                ? 'meta_description'
                                : "translations[{$language['id']}][meta_description]";
                            $metaDescriptionVal = $language['id'] === 'en'
                                ? ($seosetting->meta_description ?? '')
                                : ($seosetting ? $seosetting->translate('meta_description', $language['id']) : '');
                        @endphp
                        <textarea name="{{ $metaDescriptionName }}" id="meta_description_{{ $language['id'] }}" class="form-control" rows="4" maxlength="200" placeholder="{{ __('messages.enter_meta_description') }}" data-lang="{{ $language['id'] }}" data-required="true">{{ $metaDescriptionVal }}</textarea>
                        <small class="text-muted d-block">
                            <span class="text-muted" style="font-size: 12px;">
                                @php
                                    $metaDescVal = $language['id'] === 'en'
                                        ? ($seosetting->meta_description ?? '')
                                        : ($seosetting ? $seosetting->translate('meta_description', $language['id']) : '');
                                @endphp
                                <span id="meta-desc-count-{{ $language['id'] }}">{{ strlen((string) $metaDescVal) }}</span>/200
                            </span>
                        </small>
                        @if ($errors->has('meta_description'))
                            <span class="text-danger">{{ $errors->first('meta_description') }}</span>
                        @endif
                    </div>
                    </div>
                    @endforeach
                </div>
                <!-- Second Row: Global Canonical URL, Google Site Verification -->
                <div class="row">
                    <div class="form-group col-md-6 mb-3">
                        <label class="form-control-label">
                            {{ __('messages.global_canonical_url') }} <span class="text-danger">*</span>
                        </label>
                        {{ html()->text('global_canonical_url', $seosetting->global_canonical_url ?? '')
                            ->class('form-control')
                            ->placeholder(__('messages.global_canonical_url'))
                            ->id('global_canonical_url') }}
                        @if ($errors->has('global_canonical_url'))
                            <span class="text-danger">{{ $errors->first('global_canonical_url') }}</span>
                        @endif
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label class="form-control-label">
                            {{ __('messages.google_site_verification') }} <span class="text-danger">*</span>
                        </label>
                        {{ html()->text('google_site_verification', $seosetting->google_site_verification ?? '')
                            ->class('form-control')
                            ->placeholder(__('messages.google_site_verification'))
                            ->id('google_site_verification') }}
                        @if ($errors->has('google_site_verification'))
                            <span class="text-danger">{{ $errors->first('google_site_verification') }}</span>
                        @endif
                    </div>
                </div>
                <!-- Third Row: Meta Description (full width) -->
                
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <div class="col-md-offset-3 col-sm-12 ">
                                  {{ html()->submit(trans('messages.save'))
                                    ->class('btn btn-md btn-primary float-end')
                                    ->attribute('onclick', 'return checkData()')
                                    ->id('saveButton') }}
            
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{ html()->form()->close() }}
<script>
function previewSeoImage(event) {
    const preview = document.getElementById('seo_image_preview');
    const file = event.target.files[0];
    const fileLabel = event.target.nextElementSibling;

    if (preview && file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
        fileLabel.textContent = file.name;
    } else if (preview && !file) {
        preview.style.display = 'none';
        fileLabel.textContent = '{{ __('messages.choose_file', ['file' => __('messages.seo_image')]) }}';
    }
}

$(document).ready(function() {
    // ✅ Handle meta_title counters for all languages
    $('[id^="meta_title_"]').each(function() {
        const $input = $(this);
        const lang = $input.data('lang');
        const $counter = $('#meta-title-count-' + lang);

        function updateCount() {
            $counter.text($input.val().length);
        }

        $input.on('input', updateCount);
        updateCount();
    });

    // ✅ Handle meta_description counters for all languages
    $('[id^="meta_description_"]').each(function() {
        const $textarea = $(this);
        const lang = $textarea.data('lang');
        const $counter = $('#meta-desc-count-' + lang);

        function updateDescCount() {
            $counter.text($textarea.val().length);
        }

        $textarea.on('input', updateDescCount);
        updateDescCount();
    });
});
</script>



