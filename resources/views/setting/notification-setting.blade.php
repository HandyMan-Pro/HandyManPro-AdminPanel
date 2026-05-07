{{ html()->form('POST', route('notificationtemplates.settings.update'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->open() }}

{{ html()->hidden('id', null)->class('form-control')->placeholder('id') }}
{{ html()->hidden('page', $page)->class('form-control')->placeholder('id') }}

<div class="col-md-12 mt-10 w-100">
    <div class="table-responsive notification-setting-table">
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th>{{ __('messages.template') }}</th>
                    @foreach($notificationChannels as $key => $value)
                        <!-- Check if WhatsApp or SMS notification is enabled before rendering their columns -->
                        @if(($key == 'IS_WHATSAPP' && isset($othersetting) && $othersetting->whatsapp_notification) ||
                        ($key == 'IS_SMS' && isset($othersetting) && $othersetting->sms_notification) ||
                        !in_array($key, ['IS_WHATSAPP', 'IS_SMS']))
                            <th>{{ $value }}</th>
                        @endif
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach($data as $templateData)
                    <tr>
                        <td>{{ $templateData['template'] }}</td>
                        <td>
                            <input type="hidden" class="form-check-input" name="template_channels[{{ $templateData['id'] }}][IS_MAIL]" value="0">
                            <input type="checkbox" class="form-check-input" name="template_channels[{{ $templateData['id'] }}][IS_MAIL]" value="1" {{ $templateData['channels']['IS_MAIL'] ? 'checked' : '' }}>
                        </td>
                        <td>
                            <input type="hidden" class="form-check-input" name="template_channels[{{ $templateData['id'] }}][PUSH_NOTIFICATION]" value="0">
                            @if($templateData['type'] === 'email_verification' && $templateData['template'] === 'Email verification')
                                &mdash;
                            @else
                                <input type="checkbox" class="form-check-input" name="template_channels[{{ $templateData['id'] }}][PUSH_NOTIFICATION]" value="1" {{ $templateData['channels']['PUSH_NOTIFICATION'] ? 'checked' : '' }}>
                            @endif
                        </td>

                        <!-- WhatsApp Notification Section - Conditional Display -->
                        @if(isset($othersetting) && $othersetting->whatsapp_notification)
                            <td>
                                <input type="hidden" class="form-check-input" name="template_channels[{{ $templateData['id'] }}][IS_WHATSAPP]" value="0">
                                <input type="checkbox" class="form-check-input" name="template_channels[{{ $templateData['id'] }}][IS_WHATSAPP]" value="1" {{ $templateData['channels']['IS_WHATSAPP'] ? 'checked' : '' }}>
                            </td>
                        @endif

                        <!-- SMS Notification Section - Conditional Display -->
                        @if(isset($othersetting) && $othersetting->sms_notification)
                            <td>
                                <input type="hidden" class="form-check-input" name="template_channels[{{ $templateData['id'] }}][IS_SMS]" value="0">
                                <input type="checkbox" class="form-check-input" name="template_channels[{{ $templateData['id'] }}][IS_SMS]" value="1" {{ $templateData['channels']['IS_SMS'] ? 'checked' : '' }}>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{ html()->submit(__('messages.save'))->class('btn btn-md btn-primary float-md-end submit_section1') }}
{{ html()->form()->close() }}
