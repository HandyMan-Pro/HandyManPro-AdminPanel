<form method="POST" action="/" id="earn_rule" enctype="multipart/form-data" data-toggle="validator">
    @csrf
    <div class="row mb-3">
        <div>
            <h5 class="my-3">{{ __('messages.earn_rule_settings') }}</h5>
        </div>

        <div class="form-group col-md-4">
            <label for="earn_loyalty_type" class="form-label">{{ __('messages.loyalty_type') }} <span
                    class="text-danger">*</span></label>
            <select name="earn_loyalty_type" id="earn_loyalty_type" class="form-select select2js" required>
                <option value="" disabled selected>{{ __('messages.select_loyalty_type') }}</option>
                <option value="service">{{ __('messages.service') }}</option>
                <option value="package_service">{{ __('messages.package') }}</option>
                <option value="category">{{ __('messages.category') }}</option>
            </select>
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-8">
            <div class="d-flex justify-content-between">
                <label for="earn_service_id" id="earn_service_label"
                    class="form-label">{{ __('messages.service_type') }}
                    <i class="fa fa-info-circle" style="cursor: pointer;" data-bs-toggle="tooltip"
                        data-bs-placement="top" title="Leave empty to apply globally to all items of selected type"></i>
                </label>
                <div class="form-check d-inline-block ms-2 select-all-wrapper d-none">
                    <input class="form-check-input" type="checkbox" id="select_all_earn_services">
                    <label class="form-check-label" for="select_all_earn_services">
                        {{ __('messages.select_all') }}
                    </label>
                </div>
            </div>
            <select name="earn_service_id[]" id="earn_service_id" class="form-select select2js"
                multiple="multiple"></select>
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-4">
            <label for="minimum_amount" class="form-label">{{ __('messages.minimum_amount') }} <span
                    class="text-danger">*</span></label>
            <input type="number" name="minimum_amount" id="minimum_amount" class="form-control"
                placeholder="{{ __('messages.enter_minimum_amount') }}" min="1" required>
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-4">
            <label for="maximum_amount" class="form-label">{{ __('messages.maximum_amount') }} <span
                    class="text-danger">*</span></label>
            <input type="number" name="maximum_amount" id="maximum_amount" class="form-control"
                placeholder="{{ __('messages.enter_maximum_amount') }}" min="1" required>
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-4">
            <label for="points" class="form-label">{{ __('messages.points_earned') }} <span
                    class="text-danger">*</span></label>
            <input type="number" name="points" id="points" class="form-control"
                placeholder="{{ __('messages.enter_points_earned') }}" min="1" required>
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-4">
            <label for="expiry_days" class="form-label">{{ __('messages.expiry_date_range') }}<span
                    class="text-danger">*</span></label>
            <input type="text" class="form-control flatpickr" id="expiry_days" name="expiry_days"
                placeholder="{{ __('messages.select_date_range') }}" autocomplete="off" required>
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-4">
            <label for="status" class="form-label">{{ __('messages.status') }} <span
                    class="text-danger">*</span></label>
            <select name="status" id="earn_status" class="form-select select2js" required>
                <option value="1">{{ __('messages.active') }}</option>
                <option value="0">{{ __('messages.inactive') }}</option>
            </select>
            <small class="help-block with-errors text-danger"></small>
        </div>
    </div>
    <div class="form-group float-start">
        <div class="custom-control custom-switch">
            <input class="custom-control-input" type="checkbox" id="earn_is_stackable" name="is_stackable" value="1">
            <label class="custom-control-label" for="earn_is_stackable">
                {{ __('messages.stackable') }}
            </label>
            <small class="text-muted ms-1">
                ({{ __('messages.more_than_one_rule_work_together') }})
            </small>
        </div>
    </div>

    <input type="hidden" name="rule_id" id="earn_rule_id" value="">

    <button type="submit" id="submit-btn-earn" class="btn btn-md btn-primary float-end">
        <i class="fa fa-plus-circle"></i>
        {{ __('messages.add_earn_rule') }}</button>

    <button type="reset" id="reset-btn-earn" class="btn btn-md btn-outline-danger float-end mx-2 d-none">
        <i class="fa fa-times-circle"></i>
        {{ __('messages.cancel') }}</button>
</form>
@push('earn_rule_scripts')
    <script>
        $(document).ready(function() {
            let selectedServices = [];

            // Prevent global Select2 initialization for earn_service_id - we'll handle it manually
            var $serviceSelect = $('#earn_service_id');
            // Remove select2js class to prevent global initialization
            $serviceSelect.removeClass('select2js');

            // Initialize Select2 manually with default placeholder
            $serviceSelect.select2({
                placeholder: '{{ __('messages.select_type') }}',
                width: '100%',
                allowClear: true
            });


            // Flatpickr - Clear input before initialization to prevent 1970 date issue
            document.getElementById('expiry_days').value = '';

            let expiryPicker = flatpickr("#expiry_days", {
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
                            document.getElementById('expiry_days')
                                .setCustomValidity("Start and end date cannot be the same.");
                            return;
                        }
                    }

                    document.getElementById('expiry_days').setCustomValidity("");
                }
            });

            function updateSubmitButton() {
                var ruleId = $('#earn_rule_id').val();
                if (ruleId) {
                    $('#submit-btn-earn').html('<i class="fa fa-save"></i> {{ __('messages.update_earn_rule') }}');
                    $('#reset-btn-earn').removeClass('d-none');
                } else {
                    $('#submit-btn-earn').html(
                        '<i class="fa fa-plus-circle"></i> {{ __('messages.add_earn_rule') }}');
                    $('#reset-btn-earn').addClass('d-none');
                }
            }

            updateSubmitButton();

            $('#earn_rule_id').on('change', function() {
                updateSubmitButton();
            });

            // Load services on type change
            $('#earn_loyalty_type').on('change', function() {
                var type = $(this).val();
                var $serviceSelect = $('#earn_service_id');

                // Destroy Select2 first to ensure clean state
                if ($serviceSelect.hasClass('select2-hidden-accessible')) {
                    $serviceSelect.select2('destroy');
                }

                $serviceSelect.empty();
                $('#select_all_earn_services').prop('checked', false);
                $('#earn_service_label').html('');

                // Function to reinitialize tooltip
                function reinitTooltip() {
                    // Destroy existing tooltips first
                    $('#earn_service_label [data-bs-toggle="tooltip"]').each(function() {
                        var existingTooltip = bootstrap.Tooltip.getInstance(this);
                        if (existingTooltip) {
                            existingTooltip.dispose();
                        }
                    });
                    // Initialize new tooltip
                    var tooltipElement = $('#earn_service_label [data-bs-toggle="tooltip"]')[0];
                    if (tooltipElement && typeof bootstrap !== 'undefined') {
                        new bootstrap.Tooltip(tooltipElement);
                    }
                }

                if (!type) {
                    // Reset label to default
                    $('#earn_service_label').html(
                        '{{ __('messages.service_type') }} <i class="fa fa-info-circle" style="cursor: pointer;" data-bs-toggle="tooltip" data-bs-placement="top" title="Leave empty to apply globally to all items of selected type"></i>'
                    );
                    // Reinitialize Bootstrap tooltip
                    setTimeout(reinitTooltip, 100);
                    $serviceSelect.prop('disabled', true);
                    $serviceSelect.select2({
                        placeholder: '{{ __('messages.select_loyalty_type') }}',
                        width: '100%',
                        allowClear: true
                    });
                    $serviceSelect.val(null).trigger('change');
                    return;
                }

                // Update label text based on selected type
                var labelMap = {
                    'service': '{{ __('messages.service') }} {{ __('messages.type') }}',
                    'package_service': '{{ __('messages.package') }} {{ __('messages.type') }}',
                    'category': '{{ __('messages.category') }} {{ __('messages.type') }}'
                };
                var labelText = labelMap[type] || '{{ __('messages.service_type') }}';
                $('#earn_service_label').html(labelText +
                    ' <i class="fa fa-info-circle" style="cursor: pointer;" data-bs-toggle="tooltip" data-bs-placement="top" title="Leave empty to apply globally to all items of selected type"></i>'
                );
                // Reinitialize Bootstrap tooltip
                setTimeout(reinitTooltip, 100);

                // Create placeholder text based on selected type
                var placeholderMap = {
                    'service': '{{ __('messages.select_service') }}',
                    'package_service': 'Select {{ __('messages.package') }}',
                    'category': 'Select {{ __('messages.category') }}'
                };
                var placeholderText = placeholderMap[type] || '{{ __('messages.select_type') }}';

                $serviceSelect.prop('disabled', true);

                // Initialize Select2 with placeholder
                $serviceSelect.select2({
                    placeholder: placeholderText,
                    width: '100%',
                    allowClear: true
                });

                // Clear selection to show placeholder
                $serviceSelect.val(null).trigger('change');

                $.ajax({
                    url: "{{ route('loyalty.earn_service_type_data') }}",
                    type: 'GET',
                    data: {
                        type: type
                    },
                    success: function(response) {
                        // Destroy and rebuild Select2
                        if ($serviceSelect.hasClass('select2-hidden-accessible')) {
                            $serviceSelect.select2('destroy');
                        }

                        $serviceSelect.empty();

                        // Get placeholder text based on type
                        var placeholderMap = {
                            'service': '{{ __('messages.select_service') }}',
                            'package_service': 'Select {{ __('messages.package') }}',
                            'category': 'Select {{ __('messages.category') }}'
                        };
                        var placeholderText = placeholderMap[type] ||
                            '{{ __('messages.select_type') }}';

                        if (response.data && response.data.length > 0) {
                            $.each(response.data, function(index, item) {
                                $serviceSelect.append(
                                    $('<option>', {
                                        value: item.id,
                                        text: item.name
                                    })
                                );
                            });
                        } else {
                            $serviceSelect.append(
                                '<option value="">{{ __('messages.no_data_found') }}</option>'
                            );
                        }

                        $serviceSelect.prop('disabled', false);

                        // Reinitialize Select2 with placeholder
                        $serviceSelect.select2({
                            placeholder: placeholderText,
                            width: '100%',
                            allowClear: true
                        });

                        // Clear selection to show placeholder
                        $serviceSelect.val(null).trigger('change');

                        // Show Select All checkbox when services are loaded
                        if (response.data && response.data.length > 0) {
                            $('.select-all-wrapper').removeClass('d-none');
                        }

                        // Preselect when editing
                        if (selectedServices.length > 0) {
                            $serviceSelect.val(selectedServices).trigger('change');
                            selectedServices = [];
                        }

                        $serviceSelect.trigger('change.select2');
                    },
                    error: function() {
                        // Destroy and rebuild Select2
                        if ($serviceSelect.hasClass('select2-hidden-accessible')) {
                            $serviceSelect.select2('destroy');
                        }

                        $serviceSelect.empty();

                        // Get placeholder text based on type
                        var placeholderMap = {
                            'service': '{{ __('messages.select_service') }}',
                            'package_service': 'Select {{ __('messages.package') }}',
                            'category': 'Select {{ __('messages.category') }}'
                        };
                        var placeholderText = placeholderMap[type] ||
                            '{{ __('messages.select_type') }}';

                        $serviceSelect.append(
                            '<option value="">{{ __('messages.error_loading_data') }}</option>'
                        );
                        $serviceSelect.prop('disabled', false);

                        // Reinitialize Select2 with placeholder
                        $serviceSelect.select2({
                            placeholder: placeholderText,
                            width: '100%',
                            allowClear: true
                        });

                        // Clear selection to show placeholder
                        $serviceSelect.val(null).trigger('change');
                    }
                });
            });

            // Select All Checkbox Handler
            $('#select_all_earn_services').on('change', function() {
                if ($(this).is(':checked')) {
                    let allValues = [];
                    $('#earn_service_id option').each(function() {
                        let val = $(this).val();
                        if (val !== '' && val !== '__all__') { // Filter empty and special values
                            allValues.push(String(val)); // Ensure value is string
                        }
                    });
                    $('#earn_service_id').val(allValues).trigger('change.select2');
                } else {
                    $('#earn_service_id').val(null).trigger('change.select2');
                }
            });

            // Uncheck "Select All" if any individual item is deselected
            $('#earn_service_id').on('change', function() {
                var totalOptions = 0;
                $('#earn_service_id option').each(function() {
                    if ($(this).val() !== '' && $(this).val() !== '__all__') {
                        totalOptions++;
                    }
                });

                var selectedOptions = $('#earn_service_id').val() ? $('#earn_service_id').val().length : 0;

                if (totalOptions > 0 && totalOptions === selectedOptions) {
                    $('#select_all_earn_services').prop('checked', true);
                } else {
                    $('#select_all_earn_services').prop('checked', false);
                }
            });

            // Validate maximum amount
            $(document).ready(function() {
                function validateAmounts() {
                    const min = parseInt($('#minimum_amount').val() || 0);
                    const max = parseInt($('#maximum_amount').val() || 0);

                    if (max < min) {
                        document.getElementById('maximum_amount')
                            .setCustomValidity(
                                "Maximum amount must be greater than or equal to minimum amount.");
                    } else {
                        document.getElementById('maximum_amount').setCustomValidity("");
                    }
                }

                $('#minimum_amount, #maximum_amount').on('input', validateAmounts);
            });

            // Submit form
            $('#earn_rule').on('submit', function(e) {
                e.preventDefault();

                // Basic front-end validation to prevent zero values
                let isValid = true;
                const fields = [
                    { id: '#minimum_amount', label: 'Minimum amount' },
                    { id: '#maximum_amount', label: 'Maximum amount' },
                    { id: '#points', label: 'Points earned' },
                ];

                // Clear previous errors
                fields.forEach(f => {
                    const $input = $(f.id);
                    $input.removeClass('border border-danger');
                    $input.closest('.form-group').find('.help-block').html('');
                });

                const minVal = parseFloat($('#minimum_amount').val() || '0');
                const maxVal = parseFloat($('#maximum_amount').val() || '0');

                // > 0 validation
                fields.forEach(f => {
                    const $input = $(f.id);
                    const value = parseFloat($input.val() || '0');
                    if (!value || value <= 0) {
                        isValid = false;
                        $input.addClass('border border-danger');
                        $input.closest('.form-group').find('.help-block').html(f.label + ' must be greater than 0.');
                    }
                });

                // maximum >= minimum validation
                if (!isNaN(minVal) && !isNaN(maxVal) && maxVal < minVal) {
                    isValid = false;
                    const $maxInput = $('#maximum_amount');
                    $maxInput.addClass('border border-danger');
                    $maxInput.closest('.form-group').find('.help-block').html(
                        'Maximum amount must be greater than or equal to minimum amount.'
                    );
                }

                if (!isValid) {
                    // Focus first invalid field
                    for (let i = 0; i < fields.length; i++) {
                        const $input = $(fields[i].id);
                        if ($input.hasClass('border-danger')) {
                            $input.focus();
                            break;
                        }
                    }
                    return false;
                }

                var form = $(this);
                var ruleId = $('#earn_rule_id').val();
                var url = ruleId ?
                    "{{ route('update.earn_rule', ':id') }}".replace(':id', ruleId) :
                    "{{ route('store.earn_rule') }}";

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

                        form[0].reset();

                        $('#earn_rule_id').val('').trigger('change');
                        $('#earn_loyalty_type').val('').trigger('change.select2');
                        $('#earn_service_id').empty().trigger('change.select2');
                        $('#earn_rule_table').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let errorMessage = 'Something went wrong. Please try again.';
                        
                        // Check if we have validation errors from the server
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            // Get the first error message
                            for (let field in errors) {
                                if (errors[field] && errors[field].length > 0) {
                                    errorMessage = errors[field][0];
                                    break;
                                }
                            }
                        }
                        
                        Snackbar.show({
                            text: errorMessage,
                            pos: 'bottom-center'
                        });
                    }
                });
            });

            // Edit rule
            $(document).on('click', '.edit-earn-rule', function(e) {
                e.preventDefault();
                var id = $(this).data('id');

                $.get("{{ route('edit.earn_rule', '') }}" + '/' + id, function(response) {
                    if (response.status) {
                        var data = response.data;

                        $('#earn_rule_id').val(data.id).trigger('change');
                        $('#minimum_amount').val(data.minimum_amount);
                        $('#maximum_amount').val(data.maximum_amount);
                        $('#points').val(data.points);
                        $('#earn_status').val(data.status == 1 ? '1' : '0').trigger(
                            'change.select2');
                        $('#earn_is_stackable').prop('checked', data.is_stackable == 1);

                        if (data.expiry_days) {
                            let cleanDates = data.expiry_days
                                .replace(/ 00:00:00/g, '')
                                .split(' to ')
                                .map(d => d.trim());
                            expiryPicker.setDate(cleanDates);
                        } else {
                            expiryPicker.clear();
                        }

                        selectedServices = data.service_id;
                        $('#earn_loyalty_type').val(data.loyalty_type).trigger('change');
                    }
                });
            });

            // Reset form
            $(document).on('click', '#reset-btn-earn', function() {
                $('#earn_rule_id').val('').trigger('change');
                $('#earn_loyalty_type').val('').trigger('change.select2');
                $('#earn_service_id').empty().trigger('change.select2');
                $('#earn_status').val('1').trigger('change.select2'); // Default to Active
                expiryPicker.clear();

                // Clear validation errors
                $('.help-block.with-errors').html('');
                $('.form-group').removeClass('has-error');

                // Reset custom validity
                document.getElementById('maximum_amount').setCustomValidity("");
                document.getElementById('expiry_days').setCustomValidity("");
            });
        });
    </script>
@endpush
