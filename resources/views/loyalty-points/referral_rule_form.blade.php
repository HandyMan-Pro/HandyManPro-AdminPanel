<form method="POST" action="/" id="referral_rule" enctype="multipart/form-data" data-toggle="validator">
    @csrf
    <input type="hidden" name="rule_id" id="rule_id" value="">
    <div class="row mb-3">
        <div>
            <h5 class="my-3">{{ __('messages.referral_rule_settings') }}</h5>
        </div>
        <div class="form-group col-md-4">
            <label class="form-label">{{ __('messages.referrer_points') }} <span class="text-danger">*</span></label>
            <input type="number" name="referrer_points" id="referrer_points" class="form-control"
                placeholder="{{ __('messages.enter_referrer_points') }}" min="1" required>
            <small class="help-block with-errors text-danger"></small>
        </div>
        <div class="form-group col-md-4">
            <label class="form-label">{{ __('messages.referred_user_points') }} <span
                    class="text-danger">*</span></label>
            <input type="number" name="referred_user_points" id="referred_user_points" class="form-control"
                placeholder="{{ __('messages.enter_referred_user_points') }}" min="1" required>
            <small class="help-block with-errors text-danger"></small>
        </div>
        <div class="form-group col-md-4">
            <label class="form-label">{{ __('messages.max_referrals_per_user') }} <span
                    class="text-danger">*</span></label>
            <input type="number" name="max_referrals_per_user" id="max_referrals_per_user" class="form-control"
                placeholder="{{ __('messages.enter_max_referrals_per_user') }}" min="1" required>
            <small class="help-block with-errors text-danger"></small>
        </div>
        <div class="form-group col-md-4">
            <label class="form-label">{{ __('messages.expiry_days') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control flatpickr" id="date_picker" name="expiry_days"
                placeholder="{{ __('messages.select_date_range') }}" value="" autocomplete="off" required>
            <small class="help-block with-errors text-danger"></small>
        </div>
        <div class="form-group col-md-4">
            <label class="form-label">{{ __('messages.status') }} <span class="text-danger">*</span></label>
            <select name="status" id="status" class="form-select select2js">
                <option value="1">{{ __('messages.active') }}</option>
                <option value="0">{{ __('messages.inactive') }}</option>
            </select>
            <small class="help-block with-errors text-danger"></small>
        </div>
    </div>
    <button type="submit" id="submit-btn-referral" class="btn btn-md btn-primary float-end"><i
            class="fa fa-plus-circle"></i><span id="submit-text">{{ __('messages.add_referral_rule') }}</span></button>

    <button type="reset" id="reset-btn-referral" class="btn btn-md btn-outline-danger float-end mx-2 d-none">
        <i class="fa fa-times-circle"></i>
        {{ __('messages.cancel') }}</button>
</form>
@push('referral_rule_scripts')
    <script>
        $(document).ready(function() {
            // Flatpickr - Clear input before initialization to prevent 1970 date issue
            document.getElementById('date_picker').value = '';

            var datePicker = flatpickr("#date_picker", {
                mode: "range",
                dateFormat: "Y-m-d",
                minDate: "today",

                onChange: function(selectedDates, dateStr, instance) {
                    // If user double-clicks same date
                    if (selectedDates.length === 2) {
                        const start = selectedDates[0];
                        const end = selectedDates[1];

                        // If both dates are the same → block and clear
                        if (start.getTime() === end.getTime()) {
                            instance.clear();
                            document.getElementById('date_picker')
                                .setCustomValidity("Start and end date cannot be the same.");
                            return;
                        }
                    }

                    document.getElementById('date_picker').setCustomValidity("");
                }
            });

            function updateSubmitButton() {
                var ruleId = $('#rule_id').val();
                if (ruleId) {
                    $('#submit-btn-referral').html('<i class="fa fa-save"></i> ' +
                        '{{ __('messages.update_referral_rule') }}');
                    $('#reset-btn-referral').removeClass('d-none');
                    $('#submit-btn-referral').show(); // Always show when editing
                } else {
                    $('#submit-btn-referral').html('<i class="fa fa-plus-circle"></i> ' +
                        '{{ __('messages.add_referral_rule') }}');
                    $('#reset-btn-referral').addClass('d-none');
                    // Visibility will be controlled by DataTable drawCallback
                }
            }

            updateSubmitButton();

            $('#rule_id').on('change', updateSubmitButton);

            // 🔹 Create / Update via AJAX
            $('#referral_rule').on('submit', function(e) {
                e.preventDefault();

                // Basic front-end validation to prevent zero values
                let isValid = true;
                const fields = [
                    { id: '#referrer_points', label: 'Referrer points' },
                    { id: '#referred_user_points', label: 'Referred user points' },
                    { id: '#max_referrals_per_user', label: 'Max referrals per user' },
                ];

                // Clear previous errors
                fields.forEach(f => {
                    const $input = $(f.id);
                    $input.removeClass('border border-danger');
                    $input.closest('.form-group').find('.help-block').html('');
                });

                fields.forEach(f => {
                    const $input = $(f.id);
                    const value = parseInt($input.val() || '0', 10);
                    if (!value || value <= 0) {
                        isValid = false;
                        $input.addClass('border border-danger');
                        $input.closest('.form-group').find('.help-block').html(f.label + ' must be greater than 0.');
                    }
                });

                if (!isValid) {
                    fields.some(f => {
                        const $input = $(f.id);
                        if ($input.hasClass('border-danger')) {
                            $input.focus();
                            return true;
                        }
                        return false;
                    });
                    return false;
                }

                var form = $(this);
                var ruleId = $('#rule_id').val();
                var url = ruleId ?
                    "{{ route('update.referral_rule', ':id') }}".replace(':id', ruleId) :
                    "{{ route('store.referral_rule') }}";

                var formData = new FormData(this);

                if (ruleId) formData.append('_method', 'POST');

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        Snackbar.show({
                            text: response.message,
                            pos: 'bottom-center'
                        });

                        // reset form
                        form[0].reset();
                        datePicker.clear();
                        $('#rule_id').val('').trigger('change');
                        $('#referral_rule_table').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        Snackbar.show({
                            text: xhr.responseJSON.message ||
                                'Something went wrong. Please try again.',
                            pos: 'bottom-center'
                        });
                    },
                });
            });

            // 🔹 Edit button click
            $(document).on('click', '.edit-referral-rule', function(e) {
                e.preventDefault();
                var id = $(this).data('id');

                $.get("{{ route('edit.referral_rule', '') }}" + '/' + id, function(response) {

                    if (response.status) {
                        var data = response.data;

                        $('#rule_id').val(data.id).trigger('change');
                        $('#referrer_points').val(data.referrer_points);
                        $('#referred_user_points').val(data.referred_user_points);
                        $('#max_referrals_per_user').val(data.max_referrals_per_user);
                        $('#status').val(data.status == 1 ? '1' : '0').trigger('change');

                        if (data.expiry_days) {
                            let cleanDates = data.expiry_days
                                .replace(/ 00:00:00/g, '')
                                .split(' to ')
                                .map(d => d.trim());

                            if (cleanDates.length === 2) {
                                datePicker.setDate([cleanDates[0], cleanDates[1]]);
                            }
                        } else {
                            datePicker.clear();
                        }
                    }
                });
            });

            // Reset form
            $(document).on('click', '#reset-btn-referral', function() {
                $('#rule_id').val('').trigger('change');
                $('#referrer_points').val('');
                $('#referred_user_points').val('');
                $('#max_referrals_per_user').val('');
                $('#status').val('1').trigger('change');
                datePicker.clear();

                // Clear validation errors
                $('.help-block.with-errors').html('');
                $('.form-group').removeClass('has-error');

                // Reset custom validity
                document.getElementById('date_picker').setCustomValidity("");
            });
        });
    </script>
@endpush
