{{ html()->form('POST', route('landing_page_settings_updates'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->open() }}
{{ html()->hidden('id',$landing_page->id)->placeholder('id')->class('form-control') }}
{{ html()->hidden('type', $tabpage)->placeholder('id')->class('form-control') }}

<div class="form-group">
    <div class="form-control d-flex align-items-center justify-content-between">
                    <label for="enable_section_4" class="mb-0">{{__('messages.fetured_services')}}</label>
        <div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                        <input type="checkbox" class="custom-control-input section_4" name="status" id="section_4" data-type="section_4"  {{!empty($landing_page) && $landing_page->status == 1 ? 'checked' : ''}}>
            <label class="custom-control-label" for="section_4"></label>
        </div>
    </div>
</div>
<div class="form-section" id='enable_section_4'>
    @include('partials._language_toggale')
    @foreach ($language_array as $language)
        <div id="form-language-{{ $language['id'] }}" class="language-form"
            style="display: {{ $language['id'] == app()->getLocale() ? 'block' : 'none' }};">
            @php
                $title_key = 'title';

                $title_value =
                    $language['id'] == 'en'
                        ? $landing_page->$title_key ?? ''
                        : $landing_page->translate($title_key, $language['id']) ?? '';

                $title_name = $language['id'] == 'en' ? $title_key : "translations[{$language['id']}][$title_key]";

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
        </div>
    @endforeach

    <div class="form-group" id='enable_select_service'>
        {{ html()->label(__('messages.select_name', ['select' => __('messages.service')]) . ' <span class="text-danger">*</span>', 'name')->class('form-control-label language-label') }}
        <br />
        {{ html()->select('service_id[]', [])
            ->value(old('service_id'))
            ->class('select2js form-control service_id')
            ->id('service_id')
            ->attribute('data-required', 'true')
            ->attribute('data-placeholder', __('messages.select_name', ['select' => __('messages.service')]))
            ->attribute('data-ajax--url', route('ajax-list', ['type' => 'service', 'is_featured' => 1]))
            ->multiple()
        }}
    </div>
</div>

{{ html()->submit(__('messages.save'))->class('btn btn-md btn-primary float-md-end submit_section1')->id('section4_save_btn') }}
{{ html()->form()->close() }}

<script>
    var primaryLangId = typeof primaryLanguageId !== 'undefined' ? primaryLanguageId : 'en';

    function toggleSection4SaveBtn() {
        var enabled = $("input[name='status']").prop('checked');
        if (!enabled) {
            $('#section4_save_btn').prop('disabled', false);
            return;
        }
        var titleVal = $('#title_' + primaryLangId).val();
        var serviceVal = $('#service_id').val();
        var isValid = titleVal && titleVal.trim() !== '' && serviceVal && serviceVal.length > 0;
        $('#section4_save_btn').prop('disabled', !isValid);
    }

    function checkSection4(value) {
        if (value == true) {
            $('#enable_section_4').removeClass('d-none');
            $('#title_' + primaryLangId).prop('required', true);
            $('#service_id').prop('required', true);
            if (typeof setRequiredFields === 'function') setRequiredFields(primaryLangId);
        } else {
            $('#enable_section_4').addClass('d-none');
            $('#title_' + primaryLangId).prop('required', false);
            $('#service_id').prop('required', false);
            $('#section4_save_btn').prop('disabled', false);
        }
        toggleSection4SaveBtn();
    }

    var enable_section_4 = $("input[name='status']").prop('checked');
    checkSection4(enable_section_4);

    $('#section_4').change(function() {
        checkSection4($(this).prop('checked'));
    });

    ///// open select popular category ///////////
    $(document).ready(function() {
        $('.select2js').select2();
        if (typeof setRequiredFields === 'function') setRequiredFields(primaryLangId);
        toggleSection4SaveBtn();
        $(document).on('input', '#title_' + primaryLangId, toggleSection4SaveBtn);
        $(document).on('change', '#service_id', toggleSection4SaveBtn);

        $('#service_id').on('change', function() {
            var selectedOptions = $(this).val();
            if (selectedOptions && selectedOptions.length > 16) {
                selectedOptions.pop();
                $(this).val(selectedOptions).trigger('change.select2');
            }
        });


    });

    var get_value = $('input[name="status"]:checked').data("type");
    getConfig(get_value)
    $('.section_4').change(function(){
        value = $(this).prop('checked') == true ? true : false;
        type = $(this).data("type");
        getConfig(type)

    });

    function getConfig(type) {
        var _token = $('meta[name="csrf-token"]').attr('content');
        var page = "{{$tabpage}}";
        var getDataRoute = "{{ route('getLandingLayoutPageConfig') }}";
        $.ajax({
            url: getDataRoute,
            type: "POST",
            data: {
                type: type,
                page: page,
                _token: _token
            },
            success: function (response) {
                var obj = '';
                var section_4 = title = service_ids = '';

                if (response) {
                    if (response.data.key == 'section_4') {
                        obj = JSON.parse(response.data.value);
                    }
                    if (obj !== null) {
                        var title = obj.title;
                        var service_ids = obj.service_id;
                    }
                    $('#title_' + primaryLangId).val(title);
                    loadService(service_ids);

                }
            },
            error: function (error) {
                console.log(error);
            }
        });
    }


    function loadService(service_ids) {
    var service_route = "{{ route('ajax-list', ['type' => 'service']) }}";
    service_route = service_route.replace('amp;', '');
    var is_featured = 1;
    $.ajax({
        url: service_route,
        data: {
            is_featured: is_featured,
            ids: service_ids,
        },
        success: function(result) {
            $('#service_id').select2({
                width: '100%',
                placeholder: "{{ trans('messages.select_name',['select' => trans('messages.service')]) }}",
                data: result.results
            });
            $('#service_id').val(service_ids).trigger('change');
        }
    });
}

</script>
