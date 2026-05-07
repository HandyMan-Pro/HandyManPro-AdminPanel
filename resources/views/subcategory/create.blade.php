<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            @if ($auth_user->can('subcategory list'))
                                <a href="{{ route('subcategory.index') }}" class=" float-end btn btn-sm btn-primary"><i
                                        class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ html()->form('POST', route('subcategory.store'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->attribute('data-language-required-mode', 'active')->id('subcategory')->open() }}
                        {{ html()->hidden('id', $subcategory->id ?? null) }}

                        @include('partials._language_toggale')
                        @foreach ($language_array as $language)
                            <div id="form-language-{{ $language['id'] }}" class="language-form"
                                style="display: {{ $language['id'] == app()->getLocale() ? 'block' : 'none' }};">
                                <div class="row">
                                    @foreach (['name' => __('messages.name'), 'description' => __('messages.description')] as $field => $label)
                                        <div class="form-group col-md-{{ $field === 'name' ? '4' : '12' }}">
                                            {{ html()->label($label . ($field === 'name' ? ' <span class="text-danger">*</span>' : ''), $field)->class('form-control-label language-label') }}

                                            @php
                                                $defaultLanguage = getDefaultLanguage();
                                                
                                                // Get translation for this language
                                                if ($subcategory && $subcategory->id) {
                                                    $translation = $subcategory->translations()
                                                        ->where('attribute', $field)
                                                        ->where('locale', $language['id'])
                                                        ->value('value');
                                                } else {
                                                    $translation = null;
                                                }
                                                
                                                // Determine the value to show
                                                if ($language['id'] === 'en') {
                                                    // English tab → main column (always English)
                                                    $value = $subcategory->getAttribute($field) ?? '';
                                                } elseif ($translation !== null && $translation !== '') {
                                                    $value = $translation;
                                                } else {
                                                    $value = '';
                                                }
                                                
                                                // English posts as bare field; all others as translations[locale][field]
                                                $name = $language['id'] === 'en' ? $field : "translations[{$language['id']}][$field]";
                                            @endphp


                                            @if ($field === 'name')
                                                {{ html()->text($name, $value)->placeholder($label)->class('form-control')->attribute('title', 'Please enter alphabetic characters and spaces only')->attribute('data-required', 'true')->attribute('data-lang', $language['id']) }}
                                            @elseif($field === 'description')
                                                {{ html()->textarea($name, $value)->class('form-control textarea description-field')->attribute('maxlength', 250)->rows(3)->placeholder($label)->attribute('data-lang', $language['id']) }}

                                                <small class="text-muted">
                                                    <span class="char-count"
                                                        id="char-count-{{ $language['id'] }}">{{ strlen($value ?? '') }}</span>/250
                                                </small>
                                            @endif


                                            <small class="help-block with-errors text-danger"></small>
                                        </div>
                                    @endforeach

                                    <!-- Category Selection -->
                                    <div class="form-group col-md-4">
                                        {{ html()->label(__('messages.select_name', ['select' => __('messages.category')]) . ' <span class="text-danger">*</span>', 'category_id')->class('form-control-label') }}
                                        <select name="category_id" id="category_id_{{ $language['id'] }}"
                                            class="form-select select2js-category" data-select2-type="category"
                                            data-selected-id="{{ $subcategory->category_id ?? '' }}"
                                            data-language-id="{{ $language['id'] }}"
                                            data-ajax--url="{{ route('ajax-list', ['type' => 'category', 'language_id' => $language['id']]) }}"
                                            data-placeholder="{{ __('messages.select_name', ['select' => __('messages.category')]) }}">
                                        </select>
                                        <small class="help-block with-errors text-danger"></small>
                                    </div>
                                </div>
                            </div>
                        @endforeach


                        <div class="form-group col-md-4">
                            {{ html()->label(trans('messages.status') . ' <span class="text-danger">*</span>', 'status')->class('form-control-label') }}
                            {{ html()->select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], $subcategory->status)->class('form-select select2js')->required() }}
                        </div>

                        <div class="form-group col-md-4">
                            <label class="form-control-label" for="subcategory_image">{{ __('messages.image') }} <span
                                    class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" name="subcategory_image" class="custom-file-input"
                                    onchange="previewImage(event)" accept="image/*" required>
                                @if ($subcategory && getMediaFileExit($subcategory, 'subcategory_image'))
                                    <label
                                        class="custom-file-label upload-label">{{ $subcategory->getFirstMedia('subcategory_image')->file_name }}</label>
                                @else
                                    <label
                                        class="custom-file-label upload-label">{{ __('messages.choose_file', ['file' => __('messages.image')]) }}</label>
                                @endif
                            </div>
                            <small id="subcategory_image_error" class="text-danger"></small> <!-- Error message container -->
                            <small class="text-muted d-block mt-1">{{ __('messages.only_jpg_png_jpeg_allowed') }}</small> <!-- Note for allowed image types -->
                        </div>

                        <div class="col-md-2 mb-2">
                            <div class="image-preview-container">
                                <img id="subcategory_image_preview"
                                    src="{{ getMediaFileExit($subcategory, 'subcategory_image') ? getSingleMedia($subcategory, 'subcategory_image') : '' }}"
                                    alt="Image preview" class="attachment-image mt-1"
                                    style="width: 150px; {{ getMediaFileExit($subcategory, 'subcategory_image') ? '' : 'display: none;' }}">
                                <a class="text-danger remove-file" id="removeButton"
                                    href="{{ route('remove.file', ['id' => $subcategory->id, 'type' => 'subcategory_image']) }}"
                                    data--submit="confirm_form" data--confirmation='true'
                                    data--ajax="true" data-toggle="tooltip"
                                    title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.attachments") ] ) }}'
                                    data-title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.attachments") ] ) }}'
                                    data-message='{{ __("messages.remove_file_msg") }}'
                                    style="{{ getMediaFileExit($subcategory, 'subcategory_image') ? 'display: inline;' : 'display: none;' }}">
                                    <i class="ri-close-circle-line"></i>
                                </a>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6">
                                <div class="custom-control custom-switch">
                                    {{ html()->checkbox('is_featured', $subcategory->is_featured)->class('custom-control-input')->id('is_featured') }}
                                    <label class="custom-control-label" for="is_featured">{{ __('messages.set_as_featured') }}</label>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <div class="custom-control custom-switch">
                                    {{ html()->checkbox('seo_enabled', $subcategory->seo_enabled)->class('custom-control-input')->id('seo_enabled') }}
                                    <label class="custom-control-label" for="seo_enabled">{{ __('messages.set_seo') }}</label>
                                </div>
                            </div>
                        </div>

                        <div id="seo_fields_section" style="{{ isset($subcategory->seo_enable) && $subcategory->seo_enable ? '' : 'display:none;' }}">
                            <div class="row">
                                <div class="form-group col-md-6 mb-3">
                                    {{ html()->label(__('messages.seo_image'), 'seo_image')->class('form-control-label') }}
                                    <div class="custom-file">
                                    @php
                                        $seoImageUrl = (isset($subcategory->id) && getMediaFileExit($subcategory, 'seo_image')) ? $subcategory->getFirstMediaUrl('seo_image') : '';
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
                                                        $metaTitleVal = $language['id'] === $defaultLanguage
                                                            ? ($subcategory->meta_title ?? '')
                                                            : ($subcategory->translate('meta_title', $language['id']) ?? '');
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
                                                $metaKeywordsVal = $language['id'] === $defaultLanguage
                                                    ? (is_array($subcategory->meta_keywords) ? implode(',', $subcategory->meta_keywords) : ($subcategory->meta_keywords ?? ''))
                                                    : ($subcategory->translate('meta_keywords', $language['id']) ?? '');

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
                                                        $metaDescVal = $language['id'] === $defaultLanguage
                                                            ? ($subcategory->meta_description ?? '')
                                                            : ($subcategory->translate('meta_description', $language['id']) ?? '');
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


                        {{ html()->submit(trans('messages.save'))->class('btn btn-md btn-primary float-end')->id('saveButton') }}
                        {{ html()->form()->close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css">
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script>
        function slugify(text) {
            return text.toString().toLowerCase()
                .replace(/\s+/g, '-')           // Replace spaces with -
                .replace(/[^a-z0-9\-]/g, '')   // Remove all non-alphanumeric chars except -
                .replace(/\-+/g, '-')           // Replace multiple - with single -
                .replace(/^-+/, '')             // Trim - from start of text
                .replace(/-+$/, '');            // Trim - from end of text
        }
        document.addEventListener('DOMContentLoaded', function() {
            var nameInput = document.querySelector('input[name="name"]');
            var slugInput = document.querySelector('input[name="slug"]');
            if (nameInput && slugInput) {
                nameInput.addEventListener('input', function() {
                    slugInput.value = slugify(this.value);
                });
            }
            var seoImageInput = document.getElementById('seo_image');
            if (seoImageInput) {
                seoImageInput.addEventListener('change', previewSeoImage);
            }
        });
        function previewSeoImage(event) {
            const preview = document.getElementById('seo_image_preview');
            const fileLabel = document.querySelector('#seo_image').nextElementSibling;
            if (event.target.files && event.target.files[0]) {
                preview.src = URL.createObjectURL(event.target.files[0]);
                preview.style.display = 'block';
                fileLabel.textContent = event.target.files[0].name;
            }
        }
    </script>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                const textareas = document.querySelectorAll('.description-field');

                textareas.forEach(textarea => {
                    const langId = textarea.getAttribute('data-lang');
                    const counter = document.getElementById('char-count-' + langId);

                    const updateCounter = () => {
                        counter.textContent = textarea.value.length;
                    };

                    // Update on load
                    updateCounter();

                    // Update on input
                    textarea.addEventListener('input', updateCounter);
                });
            });

              function previewImage(event) {
                const preview = document.getElementById('subcategory_image_preview');
                const fileLabel = document.querySelector('.custom-file-label');
                const saveButton = document.getElementById('saveButton');
                const removeButton = document.getElementById('removeButton');

                preview.src = URL.createObjectURL(event.target.files[0]);
                preview.style.display = 'block'; // Show the image
                fileLabel.textContent = event.target.files[0].name; // Update label with the file name

                // Show the remove button and enable the save button
                removeButton.style.display = 'inline';
                saveButton.disabled = false;
            }



            document.addEventListener('DOMContentLoaded', function() {
                checkImage();
            });

            function checkImage() {
                var id = @json($subcategory->id);
                var imageInput = document.querySelector('input[name="subcategory_image"]');
                var saveButton = document.getElementById('saveButton');

                // For create flow (no ID), decide only by selected file.
                if (!id) {
                    if (imageInput && imageInput.files && imageInput.files.length > 0) {
                        $('input[name="subcategory_image"]').removeAttr('required');
                        saveButton.disabled = false;
                    } else {
                        $('input[name="subcategory_image"]').attr('required', 'required');
                        saveButton.disabled = false;
                    }
                    return;
                }

                var route = "{{ route('check-image', ':id') }}";
                route = route.replace(':id', id);
                var type = 'subcategory';

                $.ajax({
                    url: route,
                    type: 'GET',
                    data: {
                        type: type,
                    },
                    success: function(result) {
                        var attachments = result.results;
                        var attachmentsCount = Object.keys(attachments).length;
                        var hasSelectedImage = imageInput && imageInput.files && imageInput.files.length > 0;

                        if (attachmentsCount == 0 && !hasSelectedImage) {
                            $('input[name="subcategory_image"]').attr('required', 'required');
                            saveButton.disabled = false; // Keep enabled; required validation handles submission
                        } else {
                            $('input[name="subcategory_image"]').removeAttr('required');
                            saveButton.disabled = false; // Enable if old image exists or user selected new image
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            }


        </script>
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
                // The Blade template will have already populated the fields with $subcategory values
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
                    categoryImageError.textContent = 'Only JPG, JPEG, and PNG files are allowed.';
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
    @endsection
</x-master-layout>


