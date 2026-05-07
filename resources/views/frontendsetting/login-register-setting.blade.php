{{ html()->form('POST', route('login_register_page_settings'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->id('frontend_setting')->open() }}
{{ html()->hidden('id', $landing_page_data ?? null)->placeholder('id')->class('form-control') }}
{{ html()->hidden('type', $tabpage)->placeholder('id')->class('form-control') }}

<div class="form-group">
    <div class="d-flex align-items-center justify-content-between form-control">
        <label class="mb-0" for="enable_login_register">{{ __('messages.enable_login_register') }}</label>
        {{-- <div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
             <input type="checkbox" class="custom-control-input login_register" name="status" id="login_register"
                data-type="login_register"
                {{ !empty($landing_page_data) && $landing_page_data->status == 1 ? 'checked' : '' }}> 
            <label class="custom-control-label" for="login_register"></label>
        </div>--}}
    </div>
</div>

{{ html()->hidden('status', !empty($landing_page_data) && $landing_page_data->status == 1 ? 'on' : '') }}

<div class="row" id='enable_login_register'>
    @include('partials._language_toggale')
    @foreach ($language_array as $language)
        <div id="form-language-{{ $language['id'] }}" class="language-form" style="display: {{ $language['id'] == app()->getLocale() ? 'block' : 'none' }};">
            @php
                $title_key = 'title';
                $description_key = 'description';

                $title_value =
                    $language['id'] == 'en'
                        ? $landing_page_data->$title_key ?? ''
                        : (method_exists($landing_page_data, 'translate')
                            ? $landing_page_data->translate($title_key, $language['id']) ?? ''
                            : '');

                $description_value =
                    $language['id'] == 'en'
                        ? $landing_page_data->$description_key ?? ''
                        : (method_exists($landing_page_data, 'translate')
                            ? $landing_page_data->translate($description_key, $language['id']) ?? ''
                            : '');

                $title_name = $language['id'] == 'en' ? $title_key : "translations[{$language['id']}][$title_key]";

                $description_name = $language['id'] == 'en' ? $description_key : "translations[{$language['id']}][$description_key]";
            @endphp

            <div class="form-group">
                {{ html()->label(trans('messages.title') . ' <span class="text-danger">*</span>', 'title')->class('form-control-label language-label') }}
                @if($language['id'] === 'en')
                {{ html()->text($title_name, old($title_name, $title_value))->id('title_' . $language['id'])->placeholder(trans('messages.title'))->class('form-control')->attribute('data-required', 'true')->required() }}
                @else
                {{ html()->text($title_name, old($title_name, $title_value))->id('title_' . $language['id'])->placeholder(trans('messages.title'))->class('form-control')->attribute('data-required', 'false') }}
                @endif
                <small class="help-block with-errors text-danger"></small>
            </div>

            <div class="form-group">
                {{ html()->label(__('messages.description'), 'description')->class('form-control-label') }}
                {{ html()->textarea($description_name, old($description_name, $description_value))->id('description_' . $language['id'])->placeholder(trans('messages.description'))->class('form-control textarea')->rows(2) }}
            </div>
        </div>
    @endforeach

    <div class="form-group col-md-6">
        <label for="avatar" class="col-sm-6 form-control-label">{{ __('messages.image') }}</label>
        <div class="col-sm-12">
            <div class="row">
                <div class="col-sm-4">
                    <div class="image-preview-wrapper">
                        <img src="{{ getSingleMedia($landing_page_data, 'login_register_image') }}" width="100"
                            id="login_register_image_preview" alt="login_register_image"
                            class="image login_register_image login_register_image_preview">
                        @if ($landing_page_data && getMediaFileExit($landing_page_data, 'login_register_image'))
                            <a class="text-danger remove-file remove-btn"
                                href="{{ route('remove.file', ['id' => $landing_page_data->id, 'type' => 'login_register_image']) }}"
                                data--submit="confirm_form" data--confirmation="true" data--ajax="true"
                                title='{{ __('messages.remove_file_title', ['name' => __('messages.image')]) }}'
                                data-title='{{ __('messages.remove_file_title', ['name' => __('messages.image')]) }}'
                                data-message='{{ __('messages.remove_file_msg') }}'>
                                <i class="ri-close-circle-line"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <style>
                    .image-preview-wrapper {
                        position: relative;
                        display: inline-block;
                        padding-top: 10px;
                        /* Add padding to prevent icon overlap if needed */
                    }

                    .image-preview-wrapper .remove-btn {
                        position: absolute;
                        top: -10px;
                        /* Move it up outside the image */
                        right: -10px;
                        /* Move it to the right outside the image */
                        background: #ccd3da;
                        border-radius: 50%;
                        padding: 4px;
                        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
                        z-index: 10;
                    }

                    .image-preview-wrapper img {
                        display: block;
                        max-width: 100px;
                    }
                </style>

                <div class="col-sm-8 mt-sm-0 mt-2">
                    <div class="custom-file col-md-12">
                        {{ html()->file('login_register_image')->class('custom-file-input custom-file-input-sm detail')->id('login_register_image')->attribute('lang', 'en')->attribute('accept', 'image/*')->attribute('onchange', 'preview()') }}
                        @if ($landing_page_data && getMediaFileExit($landing_page_data, 'login_register_image'))
                            <label
                                class="custom-file-label upload-label">{{ $landing_page_data->getFirstMedia('login_register_image')->file_name }}</label>
                        @else
                            <label
                                class="custom-file-label upload-label">{{ __('messages.choose_file', ['file' => __('messages.attachments')]) }}</label>
                        @endif
                    </div>
                    <img id="login_register_image" src="" width="150px" />
                </div>
            </div>
        </div>
    </div>
</div>
{{ html()->submit(__('messages.save'))->class('btn btn-md btn-primary float-md-end submit_section1')->id('login_register_save_btn') }}
{{ html()->form()->close() }}


<script>
    (function() {
        {{-- Switch hidden: always treat as enabled --}}
        var enable_login_register = true;
        var saveBtn = $('#login_register_save_btn');
        var primaryLangId = typeof primaryLanguageId !== 'undefined' ? primaryLanguageId : 'en';

        function toggleLoginRegisterSaveBtn() {
            if (!enable_login_register) {
                saveBtn.prop('disabled', false);
                return;
            }
            var titleVal = $('#title_' + primaryLangId).val();
            var isValid = titleVal && titleVal.trim() !== '';
            saveBtn.prop('disabled', !isValid);
        }

        function checkSection3(value) {
            enable_login_register = value;
            if (value == true) {
                $('#enable_login_register').removeClass('d-none');
                $('#title_' + primaryLangId).prop('required', true);
                if (typeof setRequiredFields === 'function') setRequiredFields(primaryLangId);
            } else {
                $('#enable_login_register').addClass('d-none');
                $('#title_' + primaryLangId).prop('required', false);
                saveBtn.prop('disabled', false);
            }
            toggleLoginRegisterSaveBtn();
        }

        checkSection3(enable_login_register);

        {{-- $('#login_register').change(...) - switch hidden, no change handler needed --}}

        $(document).on('input', '#title_en', toggleLoginRegisterSaveBtn);

        if (typeof setRequiredFields === 'function') setRequiredFields(primaryLangId);
        if ($.fn.validator) $('#frontend_setting').validator();
    })();





    function getExtension(filename) {
        var parts = filename.split('.');
        return parts[parts.length - 1];
    }

    function isImage(filename) {
        var ext = getExtension(filename);
        switch (ext.toLowerCase()) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'ico':
                return true;
        }
        return false;
    }

    function readURL(input, className) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            var res = isImage(input.files[0].name);
            if (res == false) {
                var msg = 'Please upload a valid image in .jpg , .png , .gif or .jpeg format.';
                Snackbar.show({
                    text: msg,
                    pos: 'bottom-right',
                    backgroundColor: '#d32f2f',
                    actionTextColor: '#fff'
                });
                $(input).val("");
                return false;
            }
            reader.onload = function(e) {
                $(document).find('img.' + className).attr('src', e.target.result);
                $(document).find("label." + className).text((input.files[0].name));
            }

            reader.readAsDataURL(input.files[0]);
        }
    }
    $(document).ready(function() {
        $('.select2js').select2();
        $(document).on('change', '#login_register_image', function() {
            readURL(this, 'login_register_image');
        });
    })

    function preview() {
        var input = event.target;
        login_register_image.src = URL.createObjectURL(input.files[0]);
        var fileName = input.files[0].name;
        var label = $(input).closest('.custom-file').find('.custom-file-label');
        label.text(fileName);
    }
</script>
<script>
    $(document).on('click', '.remove-file', function(e) {
        e.preventDefault();

        var url = $(this).attr('href');
        var id = getUrlParameter(url, 'id');
        var type = getUrlParameter(url, 'type');

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id: id,
                type: type
            },
            success: function(response) {
                Snackbar.show({
                    text: 'File removed successfully.',
                    pos: 'bottom-right',
                    backgroundColor: '#28a745',
                    actionTextColor: '#fff'
                });

                // Clear image preview and remove the icon
                $('.login_register_image_preview').attr('src', '');
                $('.remove-file').remove();
            },
            error: function(xhr) {
                Snackbar.show({
                    text: 'Failed to remove file.',
                    pos: 'bottom-right',
                    backgroundColor: '#d32f2f',
                    actionTextColor: '#fff'
                });
            }
        });
    });

    // Helper to get query string values
    function getUrlParameter(url, name) {
        let results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
        return results ? decodeURIComponent(results[1]) : null;
    }
</script>
