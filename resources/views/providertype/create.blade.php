<x-master-layout>
    <div class="container-fluid">
        <div class="row">
        <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? __('messages.list') }}</h5>
                                <a href="{{ route('providertype.index') }}" class=" float-end btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            @if($auth_user->can('providertype list'))
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ html()->form('POST', route('providertype.store'))->attribute('data-toggle', 'validator')->attribute('data-language-required-mode', 'active')->id('providertype')->open()}}
                        {{ html()->hidden('id',$providertypedata->id ?? null) }}
                        @include('partials._language_toggale')

                        <!-- Loop through all languages -->
                        @foreach($language_array as $language)
                        <div id="form-language-{{ $language['id'] }}" class="language-form" style="display: {{ $language['id'] == app()->getLocale() ? 'block' : 'none' }};">
                            <div class="row">    
                            @foreach(['name' => __('messages.name')] as $field => $label)
                                <div class="form-group col-md-{{ $field === 'name' ? '4' : '12' }}">
                                    {{ html()->label($label . ($field === 'name' ? ' <span class="text-danger">*</span>' : ''), $field)->class('form-control-label language-label') }}
                                    
                                    @php
                                        $defaultLanguage = getDefaultLanguage();
                                        
                                        // Get translation row for this language
                                        if ($providertypedata && $providertypedata->id) {
                                            $translation = $providertypedata->translations()
                                                ->where('attribute', $field)
                                                ->where('locale', $language['id'])
                                                ->value('value');
                                        } else {
                                            $translation = null;
                                        }
                                        
                                        // Main column always holds the English value
                                        $mainValue = ($providertypedata && $providertypedata->id)
                                            ? ($providertypedata->getAttribute($field) ?? '')
                                            : '';

                                        // English tab → main column value (always English)
                                        // Other tabs  → translation row only, empty if none exists
                                        if ($language['id'] === 'en') {
                                            $value = $mainValue;
                                        } elseif ($translation !== null && $translation !== '') {
                                            $value = $translation;
                                        } else {
                                            $value = '';
                                        }

                                        // English always posts as bare field → stored in main column
                                        // Every other language posts as translations[locale][field]
                                        $name = $language['id'] === 'en'
                                            ? $field
                                            : "translations[{$language['id']}][$field]";
                                    @endphp

                                    @if($field === 'name')
                                        {{ html()->text($name, $value)
                                            ->placeholder($label)
                                            ->class('form-control')
                                            ->attribute('title', 'Please enter alphabetic characters and spaces only')
                                            ->attribute('data-required', 'true')
                                            ->attribute('data-lang', $language['id']) }}
                                    @endif

                                    <small class="help-block with-errors text-danger"></small>
                                </div>
                            @endforeach
                            </div>
                        </div>
                        @endforeach
                        <div class="row">
                            <!-- <div class="form-group col-md-4">
                                {{ html()->label(__('messages.name') . ' <span class="text-danger">*</span>', 'name')->class('form-control-label') }}
                                {{ html()->text('name',$providertypedata->name)->placeholder(__('messages.name'))->class('form-control')}}
                                <small class="help-block with-errors text-danger"></small>
                            </div> -->
                            
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.commission') . ' <span class="text-danger">*</span>', 'commission')->class('form-control-label') }}
                                {{ html()->number('commission',$providertypedata->commission)->attributes(['min' => 0,'step' => 'any'])->placeholder(__('messages.commission'))->class('form-control')}}
                            </div>
                    
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.select_name', ['select' => __('messages.type')]) . ' <span class="text-danger">*</span>', 'type')->class('form-control-label') }}
                                <br />
                                {{ html()->select('type', ['percent' => __('messages.percent'), 'fixed' => __('messages.fixed')], $providertypedata->type)->id('type')->class('form-select select2js')->required()}}
                                <span class="text-danger">{{ __('messages.hint') }}</span>
                            </div>
                            
                            <div class="form-group col-md-4">
                                {{ html()->label(__('messages.status') . ' <span class="text-danger">*</span>', 'status')->class('form-control-label') }}
                                {{ html()->select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], $providertypedata->status)->id('role')->class('form-select select2js')->required()}}
                        </div>
                        </div>
                        {{ html()->submit(__('messages.save'))->class('btn btn-md btn-primary float-end')->attribute('onclick', 'return checkData()')->id('saveButton') }}
                        {{ html()->form()->close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('type');
        const valueInput = document.getElementById('commission');

        function setMinMax() {
            const type = typeSelect.value;
            if (type === 'percent') {
                valueInput.min = 1;
                valueInput.max = 100;
            } else if (type === 'fixed') {
                valueInput.removeAttribute('min');
                valueInput.removeAttribute('max');
            }
        }

        $(document).on('change', '#type', function() { setMinMax(); });
        typeSelect.addEventListener('change', setMinMax);
        valueInput.addEventListener('input', setMinMax);
    });
    </script>
</x-master-layout>