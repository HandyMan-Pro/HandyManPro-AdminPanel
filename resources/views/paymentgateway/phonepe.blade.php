{{ html()->form('POST', route('paymentsettingsUpdates'))->attribute('enctype', 'multipart/form-data')->attribute('data-toggle', 'validator')->open() }}
{{ html()->hidden('id',$payment_data->id ?? null)->attribute('placeholder', 'id')->class('form-control') }}
{{ html()->hidden('type', $tabpage)->attribute('placeholder', 'id')->class('form-control') }}
 <div class="row">
    <div class="form-group col-md-12" >
        <div class="form-control d-flex align-items-center justify-content-between">
            <label for="enable_phonepe" class="mb-0">{{__('messages.payment_on',['gateway'=>__('messages.phonepe')])}}</label>
            <div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                <input type="checkbox" class="custom-control-input" name="status" id="enable_phonepe" {{!empty($payment_data) && $payment_data->status == 1 ? 'checked' : ''}}>
                <label class="custom-control-label" for="enable_phonepe"></label>
            </div>
        </div>
    </div>
 </div>
 <div class="row" id='enable_phonepe_payment'>
    <div class="form-group col-md-12">
        <label class="form-control-label">{{__('messages.payment_option',['gateway'=>__('messages.phonepe')])}}</label><br/>
        <div class="form-check-inline">
            <label class="form-check-label">
                <input type="radio" class="form-check-input is_test" value="on" name="is_test" data-type="is_test_mode" {{!empty($payment_data) && $payment_data->is_test == 1 ? 'checked' :''}}>{{__('messages.is_test_mode')}}
            </label>
        </div>
        <div class="form-check-inline">
            <label class="form-check-label">
                <input type="radio" class="form-check-input is_test" value="off" name="is_test" data-type="is_live_mode" {{!empty($payment_data) && $payment_data->is_test == 0 ? 'checked' :''}}>{{__('messages.is_live_mode')}}
            </label>
        </div>
        <small class="help-block with-errors text-danger"></small>
    </div>
    <div class="form-group col-md-12">
        {{ html()->label(trans('messages.gateway_name').' <span class="text-danger">*</span>', 'title', ['class' => 'form-control-label']) }}
        {{ html()->text('title',old('title'))
            ->id('title')
            ->placeholder(trans('messages.title'))
            ->class('form-control')
        }}
        <small class="help-block with-errors text-danger"></small>
    </div>
    <div class="form-group col-md-12">
        <label class="form-control-label">{{__('messages.phonepe_version')}} <span class="text-danger">*</span></label><br/>
        <div class="form-check-inline">
            <label class="form-check-label">
                <input type="radio" class="form-check-input phonepe_version" value="v1" name="phonepe_version" data-version="v1" {{!empty($payment_data) && isset($payment_data['phonepe_version']) && $payment_data['phonepe_version'] == 'v1' ? 'checked' : (!empty($payment_data) ? '' : 'checked')}}>{{__('messages.phonepe_v1')}}
            </label>
        </div>
        <div class="form-check-inline">
            <label class="form-check-label">
                <input type="radio" class="form-check-input phonepe_version" value="v2" name="phonepe_version" data-version="v2" {{!empty($payment_data) && isset($payment_data['phonepe_version']) && $payment_data['phonepe_version'] == 'v2' ? 'checked' :''}}>{{__('messages.phonepe_v2')}}
            </label>
        </div>
        <small class="help-block with-errors text-danger"></small>
    </div>
    <!-- PhonePe V1 Fields -->
    <div id="phonepe_v1_fields" class="version-fields">
        <div class="form-group col-md-12">
            <h5>{{__('messages.phonepe_v1_configuration')}}</h5>
        </div>
        <div class="form-group col-md-12">
            {{ html()->label(__('messages.phonepe_merchant_id').' <span class="text-danger">*</span>', 'merchant_id_v1', ['class' => 'form-control-label']) }}
            {{ html()->text('merchant_id_v1',old('merchant_id_v1') )
                ->id('merchant_id_v1')
                ->placeholder(__('messages.phonepe_merchant_id'))
                ->class('form-control')
            }}
            <small class="help-block with-errors text-danger"></small>
        </div>
        <div class="form-group col-md-12">
            {{ html()->label(__('messages.phonepe_salt_key').' <span class="text-danger">*</span>', 'salt_key_v1', ['class' => 'form-control-label']) }}
            {{ html()->text('salt_key_v1', old('salt_key_v1'))
                ->id('salt_key_v1')
                ->placeholder(__('messages.phonepe_salt_key'))
                ->class('form-control')
            }}
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-12">
            {{ html()->label(__('messages.salt_index').' <span class="text-danger">*</span>', 'salt_index_v1', ['class' => 'form-control-label']) }}
            {{ html()->number('salt_index_v1', old('salt_index_v1'))
                ->id('salt_index_v1')
                ->placeholder(__('messages.salt_index'))
                ->class('form-control')
            }}
            <small class="help-block with-errors text-danger"></small>
        </div>
    </div>

    <!-- PhonePe V2 Fields -->
    <div id="phonepe_v2_fields" class="version-fields" style="display: none;">
        <div class="form-group col-md-12">
            <h5>{{__('messages.phonepe_v2_configuration')}}</h5>
        </div>
        <div class="form-group col-md-12">
            {{ html()->label(__('messages.phonepe_merchant_id').' <span class="text-danger">*</span>', 'merchant_id_v2', ['class' => 'form-control-label']) }}
            {{ html()->text('merchant_id_v2',old('merchant_id_v2') )
                ->id('merchant_id_v2')
                ->placeholder(__('messages.phonepe_merchant_id'))
                ->class('form-control')
            }}
            <small class="help-block with-errors text-danger"></small>
        </div>
        <div class="form-group col-md-12">
            {{ html()->label(__('messages.client_id').' <span class="text-danger">*</span>', 'client_id_v2', ['class' => 'form-control-label']) }}
            {{ html()->text('client_id_v2', old('client_id_v2') )
                ->id('client_id_v2')
                ->placeholder(__('messages.client_id'))
                ->class('form-control')
            }}
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-12">
            {{ html()->label(__('messages.client_secret').' <span class="text-danger">*</span>', 'client_secret_v2', ['class' => 'form-control-label']) }}
            {{ html()->text('client_secret_v2', old('client_secret_v2'))
                ->id('client_secret_v2')
                ->placeholder(__('messages.client_secret'))
                ->class('form-control')
            }}
            <small class="help-block with-errors text-danger"></small>
        </div>
    </div>
    
   
 </div>
 {{ html()->submit(__('messages.save'))->class("btn btn-md btn-primary float-md-end") }}
 {{ html()->form()->close() }}
<script>
var enable_phonepe = $("input[name='status']").prop('checked');
checkPaymentTabOption(enable_phonepe);

$('#enable_phonepe').change(function(){
    value = $(this).prop('checked') == true ? true : false;
    checkPaymentTabOption(value);
});

$('.phonepe_version').change(function(){
    var version = $(this).data("version");
    toggleVersionFields(version);
});

function checkPaymentTabOption(value){
    if(value == true){
        $('#enable_phonepe_payment').removeClass('d-none');
        $('#title').prop('required', true);
        updateFieldRequirements();
    }else{
        $('#enable_phonepe_payment').addClass('d-none');
        $('#title').prop('required', false);
        $('.version-fields input').prop('required', false);
    }
}

function toggleVersionFields(version){
    $('.version-fields').hide();
    $('#phonepe_' + version + '_fields').show();
    updateFieldRequirements();
}

function updateFieldRequirements(){
    var selectedVersion = $('input[name="phonepe_version"]:checked').val();

    // Reset all requirements
    $('.version-fields input').prop('required', false);

    if(selectedVersion === 'v1'){
        $('#merchant_id_v1').prop('required', true);
        $('#salt_key_v1').prop('required', true);
        $('#salt_index_v1').prop('required', true);
    } else if(selectedVersion === 'v2'){
        $('#merchant_id_v2').prop('required', true);
        $('#client_id_v2').prop('required', true);
        $('#client_secret_v2').prop('required', true);
    }
}

var get_value = $('input[name="is_test"]:checked').data("type");
getConfig(get_value);

$('.is_test').change(function(){
    value = $(this).prop('checked') == true ? true : false;
    type = $(this).data("type");
    getConfig(type);
});

function getConfig(type){
    var _token   = $('meta[name="csrf-token"]').attr('content');
    var baseUrl = $('meta[name="baseUrl"]').attr('content');
    var page =  "{{$tabpage}}";
    $.ajax({
        url: baseUrl+"/get_payment_config",
        type:"POST",
        data:{
          type:type,
          page:page,
          _token: _token
        },
        success:function(response){
            var obj = '';
            var title = '';
            var phonepe_version = 'v1';

            if(response && response.data){
                if(response.data.type == 'is_test_mode'){
                    obj = response.data.value ? JSON.parse(response.data.value) : {};
                }else{
                    obj = response.data.live_value ? JSON.parse(response.data.live_value) : {};
                }

                if(response.data.title != ''){
                    title = response.data.title;
                }

                // Get version from the object or default to v1
                phonepe_version = obj.phonepe_version || 'v1';

                // Set the version radio button
                $('input[name="phonepe_version"][value="' + phonepe_version + '"]').prop('checked', true);
                toggleVersionFields(phonepe_version);
                
                // Populate fields based on version
                if(phonepe_version === 'v1'){
                    $('#merchant_id_v1').val(obj.merchant_id_v1 || obj.merchant_id || '');
                    $('#salt_key_v1').val(obj.salt_key_v1 || obj.salt_key || '');
                    $('#salt_index_v1').val(obj.salt_index_v1 || obj.salt_index || '');
                } else if(phonepe_version === 'v2'){
                    $('#merchant_id_v2').val(obj.merchant_id_v2 || '');
                    $('#client_id_v2').val(obj.client_id_v2 || '');
                    $('#client_secret_v2').val(obj.client_secret_v2 || '');
                }

                $('#title').val(title);
            }
        },
        error: function(error) {
         console.log(error);
        }
    });
}

// Initialize on page load
$(document).ready(function(){
    var selectedVersion = $('input[name="phonepe_version"]:checked').val() || 'v1';
    toggleVersionFields(selectedVersion);
});

</script>