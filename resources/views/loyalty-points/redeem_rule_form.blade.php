<form method="POST" action="{{ route('store.redeem_rule') }}" id="redeem_rule" enctype="multipart/form-data"
    data-toggle="validator">
    @csrf
    <div class="row mb-3">
        <div>
            <h5 class="my-3">{{ __('messages.redeem_rule_settings') }}</h5>
        </div>

        <div class="form-group col-md-4">
            <label for="redeem_loyalty_type" class="form-label">{{ __('messages.loyalty_type') }} <span
                    class="text-danger">*</span></label>
            <select name="redeem_loyalty_type" id="redeem_loyalty_type" class="form-select select2js" required>
                <option value="" disabled selected>{{ __('messages.select_loyalty_type') }}</option>
                <option value="service">{{ __('messages.service') }}</option>
                <option value="package_service">{{ __('messages.package') }}</option>
                <option value="category">{{ __('messages.category') }}</option>
            </select>
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-8">
            <div class="d-flex justify-content-between">
                <label for="redeem_service_id" id="redeem_service_label"
                    class="form-label">{{ __('messages.service_type') }}
                    <i class="fa fa-info-circle" style="cursor: pointer;" data-bs-toggle="tooltip"
                        data-bs-placement="top" title="Leave empty to apply globally to all items of selected type"></i>
                </label>
                <div class="form-check d-inline-block ms-2 select-all-wrapper d-none">
                    <input class="form-check-input" type="checkbox" id="select_all_redeem_services">
                    <label class="form-check-label" for="select_all_redeem_services">
                        {{ __('messages.select_all') }}
                    </label>
                </div>
            </div>
            <select name="redeem_service_id[]" id="redeem_service_id" class="form-select select2js"
                multiple="multiple"></select>
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-4">
            <label for="redeem_type" class="form-label">{{ __('messages.redeem_type') }}
                <span class="text-danger">*</span></label>
            <select name="redeem_type" id="redeem_type" class="form-select select2js" required>
                <option value="" disabled selected>{{ __('messages.select_redeem_type') }}</option>
                <option value="full">{{ __('messages.full') }}</option>
                <option value="partial">{{ __('messages.partial') }}</option>
            </select>
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-4 full-only">
            <label for="threshold_points" class="form-label">{{ __('messages.threshold_points') }} <span
                    class="text-danger">*</span></label>
            <input type="number" name="threshold_points" id="threshold_points" class="form-control"
                placeholder="{{ __('messages.enter_threshold_points') }}" min="1">
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-4 full-only">
            <label for="max_discount" class="form-label">{{ __('messages.max_discount_points') }}<span
                    class="text-danger">*</span></label>
            <input type="number" name="max_discount" id="max_discount" class="form-control"
                placeholder="{{ __('messages.enter_max_discount_points') }}" min="1">
            <small class="help-block with-errors text-danger"></small>
        </div>

        <div class="form-group col-md-4">
            <label for="status" class="form-label">{{ __('messages.status') }} <span
                    class="text-danger">*</span></label>
            <select name="status" id="redeem_status" class="form-select select2js" required>
                <option value="1">{{ __('messages.active') }}</option>
                <option value="0">{{ __('messages.inactive') }}</option>
            </select>
            <small class="help-block with-errors text-danger"></small>
        </div>

        {{-- Form section for partial redeem type --}}
        <div id="partial_redeem_section" class="d-none m-3 d-flex justify-content-center flex-column">
            <div class="mb-3">
                <h5 class="my-3">{{ __('messages.partial_redeem_rules_set') }}</h5>
            </div>

            <div id="partial_rules_container">
                {{-- First partial rule row --}}
                <div class="partial-rule-row row mb-3">
                    <div class="form-group col-md-2">
                        <label class="form-label">{{ __('messages.rule_name') }} <span
                                class="text-danger">*</span></label>
                        <input type="text" name="partial_rule_name[]" class="form-control partial_rule_name"
                            placeholder="{{ __('messages.enter_rule_name') }}">
                        <small class="help-block with-errors text-danger"></small>
                    </div>

                    <div class="form-group col-md-2">
                        <label class="form-label">{{ __('messages.point_from') }} <span
                                class="text-danger">*</span></label>
                        <input type="number" name="point_from[]" class="form-control point_from"
                            placeholder="{{ __('messages.enter_point_from') }}" min="1">
                        <small class="help-block with-errors text-danger"></small>
                    </div>

                    <div class="form-group col-md-2">
                        <label class="form-label">{{ __('messages.point_to') }} <span
                                class="text-danger">*</span></label>
                        <input type="number" name="point_to[]" class="form-control point_to"
                            placeholder="{{ __('messages.enter_point_to') }}" min="1">
                        <small class="help-block with-errors text-danger"></small>
                    </div>

                    <div class="form-group col-md-2">
                        <label class="form-label">{{ __('messages.amount') }} <span
                                class="text-danger">*</span></label>
                        <input type="number" name="partial_amount[]" class="form-control partial_amount"
                            placeholder="Enter Amount" min="1">
                        <small class="help-block with-errors text-danger"></small>
                    </div>

                    <div class="form-group col-md-2">
                        <label class="form-label">{{ __('messages.status') }} <span
                                class="text-danger">*</span></label>
                        <select name="partial_status[]" class="form-select select2js partial_status">
                            <option value="1">{{ __('messages.active') }}</option>
                            <option value="0">{{ __('messages.inactive') }}</option>
                        </select>
                        <small class="help-block with-errors text-danger"></small>
                    </div>

                    <div class="form-group col-md-2 d-flex align-items-end gap-2">
                        <button type="button" class="btn btn-success add_partial_rule_row"
                            style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-plus"></i>
                        </button>
                        <button type="button" class="btn btn-danger remove_partial_rule"
                            style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="form-group float-start">
        <div class="custom-control custom-switch">
            <input class="custom-control-input" type="checkbox" id="redeem_is_stackable" name="is_stackable"
                value="1">
            <label class="custom-control-label" for="redeem_is_stackable">
                {{ __('messages.stackable') }}
            </label>
            <small class="text-muted ms-1">
                ({{ __('messages.more_than_one_rule_work_together') }})
            </small>
        </div>
    </div>

    <input type="hidden" name="rule_id" id="redeem_rule_id" value="">

    <button type="submit" id="submit-btn-redeem" class="btn btn-md btn-primary float-end"><i
            class="fa fa-plus-circle"></i>
        {{ __('messages.add_redeem_rule') }}</button>

    <button type="reset" id="reset-btn-redeem" class="btn btn-md btn-outline-danger float-end mx-2 d-none">
        <i class="fa fa-times-circle"></i>
        {{ __('messages.cancel') }}</button>
</form>

@push('redeem_rule_scripts')
    <script>
        $(document).ready(function() {
            let selectedServices = [];

            // Prevent global Select2 initialization for redeem_service_id - we'll handle it manually
            var $serviceSelect = $('#redeem_service_id');
            // Remove select2js class to prevent global initialization
            $serviceSelect.removeClass('select2js');

            // Initialize Select2 manually with default placeholder
            $serviceSelect.select2({
                placeholder: '{{ __('messages.select_type') }}',
                width: '100%',
                allowClear: true
            });

            // 1. Function to update submit button text
            function updateSubmitButton() {
                var ruleId = $('#redeem_rule_id').val();
                if (ruleId) {
                    $('#submit-btn-redeem').html(
                        '<i class="fa fa-save"></i> {{ __('messages.update_redeem_rule') }}').prop('disabled',
                        false);
                    $('#reset-btn-redeem').removeClass('d-none');
                } else {
                    $('#submit-btn-redeem').html(
                        '<i class="fa fa-plus-circle"></i> {{ __('messages.add_redeem_rule') }}').prop(
                        'disabled', false);
                    $('#reset-btn-redeem').addClass('d-none');
                }
            }

            // Function to reinitialize tooltip
            function reinitTooltip() {
                // Destroy existing tooltips first
                $('#redeem_service_label [data-bs-toggle="tooltip"]').each(function() {
                    var existingTooltip = bootstrap.Tooltip.getInstance(this);
                    if (existingTooltip) {
                        existingTooltip.dispose();
                    }
                });
                // Initialize new tooltip
                var tooltipElement = $('#redeem_service_label [data-bs-toggle="tooltip"]')[0];
                if (tooltipElement && typeof bootstrap !== 'undefined') {
                    new bootstrap.Tooltip(tooltipElement);
                }
            }

            // 2. Loyalty Type Change → Load Service Options
            $('#redeem_loyalty_type').on('change', function() {
                var type = $(this).val();
                var $serviceSelect = $('#redeem_service_id');

                // Destroy Select2 first to ensure clean state
                if ($serviceSelect.hasClass('select2-hidden-accessible')) {
                    $serviceSelect.select2('destroy');
                }

                $serviceSelect.empty();
                $('#select_all_redeem_services').prop('checked', false);

                if (!type) {
                    // Reset label to default
                    $('#redeem_service_label').html(
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
                $('#redeem_service_label').html(labelText +
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
                    url: "{{ route('loyalty.redeem_service_type_data') }}",
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

                        // If we are editing, preselect services here
                        if (selectedServices.length > 0) {
                            $serviceSelect.val(selectedServices).trigger('change');
                            selectedServices = []; // reset
                        }

                        // Enable submit button when services are loaded
                        updateSubmitButton();

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
            $('#select_all_redeem_services').on('change', function() {
                if ($(this).is(':checked')) {
                    let allValues = [];
                    $('#redeem_service_id option').each(function() {
                        let val = $(this).val();
                        if (val !== '' && val !== '__all__') { // Filter empty and special values
                            allValues.push(String(val)); // Ensure value is string
                        }
                    });
                    $('#redeem_service_id').val(allValues).trigger('change.select2');
                } else {
                    $('#redeem_service_id').val(null).trigger('change.select2');
                }
            });

            // Uncheck "Select All" if any individual item is deselected
            $('#redeem_service_id').on('change', function() {
                var totalOptions = 0;
                $('#redeem_service_id option').each(function() {
                    if ($(this).val() !== '' && $(this).val() !== '__all__') {
                        totalOptions++;
                    }
                });

                var selectedOptions = $('#redeem_service_id').val() ? $('#redeem_service_id').val().length :
                    0;

                if (totalOptions > 0 && totalOptions === selectedOptions) {
                    $('#select_all_redeem_services').prop('checked', true);
                } else {
                    $('#select_all_redeem_services').prop('checked', false);
                }
            });

            function updateButtonVisibility() {
                var ruleCount = $('.partial-rule-row').length;
                if (ruleCount > 1) {
                    $('.remove_partial_rule').removeClass('d-none');
                } else {
                    $('.remove_partial_rule').addClass('d-none');
                }
                $('.add_partial_rule_row').addClass('d-none');
                $('.partial-rule-row:last .add_partial_rule_row').removeClass('d-none');
            }

            function togglePartialSection() {
                var redeemType = $('#redeem_type').val();
                if (redeemType === 'full') {
                    $('.full-only').show();
                    $('#threshold_points, #max_discount').attr('required', 'required');
                    $('#partial_redeem_section').addClass('d-none');
                    $('.partial_rule_name, .point_from, .point_to, .partial_amount, .partial_status').removeAttr(
                        'required');
                    // Reset partial rules when switching to full
                    resetPartialRules();
                } else if (redeemType === 'partial') {
                    $('.full-only').hide();
                    $('#threshold_points, #max_discount').removeAttr('required');
                    $('#partial_redeem_section').removeClass('d-none');
                    $('.partial_rule_name, .point_from, .point_to, .partial_amount, .partial_status').attr(
                        'required', 'required');
                    updateButtonVisibility();
                } else {
                    $('.full-only').hide();
                    $('#partial_redeem_section').addClass('d-none');
                    $('#threshold_points, #max_discount').removeAttr('required');
                    $('.partial_rule_name, .point_from, .point_to, .partial_amount, .partial_status').removeAttr(
                        'required');
                }
            }

            $('#redeem_type').on('change', function() {
                togglePartialSection();
            });

            function resetPartialRules() {
                $('#partial_rules_container').html(`
                    <div class="partial-rule-row row mb-3">
                        <div class="form-group col-md-2">
                            <label class="form-label">{{ __('messages.rule_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="partial_rule_name[]" class="form-control partial_rule_name" placeholder="{{ __('messages.enter_rule_name') }}" required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label">{{ __('messages.point_from') }} <span class="text-danger">*</span></label>
                            <input type="number" name="point_from[]" class="form-control point_from" placeholder="{{ __('messages.enter_point_from') }}" min="1" required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label">{{ __('messages.point_to') }} <span class="text-danger">*</span></label>
                            <input type="number" name="point_to[]" class="form-control point_to" placeholder="{{ __('messages.enter_point_to') }}" step="1" required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label">{{ __('messages.amount') }} <span class="text-danger">*</span></label>
                            <input type="number" name="partial_amount[]" class="form-control partial_amount" placeholder="{{ __('messages.enter_amount') }}" min="1"  required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label">{{ __('messages.status') }} <span class="text-danger">*</span></label>
                            <select name="partial_status[]" class="form-select select2js partial_status" required>
                                <option value="1">{{ __('messages.active') }}</option>
                                <option value="0">{{ __('messages.inactive') }}</option>
                            </select>
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end gap-2">
                            <button type="button" class="btn btn-success add_partial_rule_row" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-danger remove_partial_rule" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `);

                $('#partial_rules_container .partial_status.select2js').select2({
                    width: '100%'
                });
                updateButtonVisibility();
            }

            // Add partial rule (clicked from add button on row)
            $(document).on('click', '.add_partial_rule_row', function() {
                var newRule = `
                    <div class="partial-rule-row row mb-3">
                        <div class="form-group col-md-2">
                            <label class="form-label">{{ __('messages.rule_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="partial_rule_name[]" class="form-control partial_rule_name" placeholder="{{ __('messages.enter_rule_name') }}" required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label">{{ __('messages.point_from') }} <span class="text-danger">*</span></label>
                            <input type="number" name="point_from[]" class="form-control point_from" placeholder="{{ __('messages.enter_point_from') }}" min="1" required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label">{{ __('messages.point_to') }} <span class="text-danger">*</span></label>
                            <input type="number" name="point_to[]" class="form-control point_to" placeholder="{{ __('messages.enter_point_to') }}" step="1" required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label">{{ __('messages.amount') }} <span class="text-danger">*</span></label>
                            <input type="number" name="partial_amount[]" class="form-control partial_amount" placeholder="{{ __('messages.enter_amount') }}" min="1" required>
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="form-label">{{ __('messages.status') }} <span class="text-danger">*</span></label>
                            <select name="partial_status[]" class="form-select select2js partial_status" required>
                                <option value="1">{{ __('messages.active') }}</option>
                                <option value="0">{{ __('messages.inactive') }}</option>
                            </select>
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end gap-2">
                            <button type="button" class="btn btn-success add_partial_rule_row" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-plus"></i>
                            </button>
                            <button type="button" class="btn btn-danger remove_partial_rule" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;

                $('#partial_rules_container').append(newRule);

                $('#partial_rules_container .partial-rule-row:last .partial_status.select2js').select2({
                    width: '100%'
                });
                updateButtonVisibility();
            });

            // Remove partial rule
            $(document).on('click', '.remove_partial_rule', function() {
                if ($('.partial-rule-row').length > 1) {
                    $(this).closest('.partial-rule-row').remove();
                    updateButtonVisibility();
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'At least one partial rule is required.'
                    });
                }
            });
            // Clear validation errors on input
            $(document).on('input', '.point_from, .point_to, .partial_amount, .partial_rule_name', function() {
                $(this).removeClass('border border-danger');
                $(this).closest('.form-group').find('.help-block').html('');
            });

            // Validate partial rules ranges
            function validatePartialRules() {
                var redeemType = $('#redeem_type').val();

                // Only validate if redeem type is partial
                if (redeemType !== 'partial') {
                    // Clear all validation states
                    $('.partial-rule-row .form-control').removeClass('border border-danger');
                    $('.partial-rule-row .help-block').html('');
                    return true;
                }

                var isValid = true;
                var ranges = [];
                var firstInvalidField = null;

                // Clear all validation states first
                $('.partial-rule-row .form-control').removeClass('border border-danger');
                $('.partial-rule-row .help-block').html('');

                // Helper to set error
                function setError(field, message) {
                    // Ensure we are working with a jQuery object
                    var $field = $(field);
                    $field.addClass('border border-danger');
                    $field.closest('.form-group').find('.help-block').html(message);

                    if (!firstInvalidField) {
                        firstInvalidField = $field;
                    }
                    isValid = false;
                }

                // Validate each partial rule row
                $('.partial-rule-row').each(function(index) {
                    var $row = $(this);
                    var ruleNameField = $row.find('.partial_rule_name');
                    var pointFromField = $row.find('.point_from');
                    var pointToField = $row.find('.point_to');
                    var amountField = $row.find('.partial_amount');

                    var ruleName = ruleNameField.val().trim();
                    var pointFrom = pointFromField.val().trim();
                    var pointTo = pointToField.val().trim();
                    var amount = amountField.val().trim();
                    var rowNumber = index + 1;

                    // Check if all fields are filled
                    if (!ruleName) setError(ruleNameField, 'Rule name is required');
                    if (!pointFrom) setError(pointFromField, 'Point from is required');
                    if (!pointTo) setError(pointToField, 'Point to is required');
                    if (!amount) setError(amountField, 'Amount is required');

                    // If all fields are filled, validate the range
                    if (pointFrom && pointTo) {
                        var from = parseFloat(pointFrom);
                        var to = parseFloat(pointTo);

                        // Validate numeric values
                        if (isNaN(from)) setError(pointFromField, 'Must be a number');
                        if (isNaN(to)) setError(pointToField, 'Must be a number');

                        // Check if point_from is 0
                        if (from === 0) {
                            setError(pointFromField, 'Must be greater than 0');
                        }

                        // Check if point_to is 0
                        if (to === 0) {
                            setError(pointToField, 'Must be greater than 0');
                        }

                        // Check if point_from <= point_to
                        if (!isNaN(from) && !isNaN(to) && from > to) {
                            setError(pointToField, 'Must be >= Point From (' + from + ')');
                        } else if (!isNaN(from) && !isNaN(to)) {
                            // Store valid range for overlap checking
                            ranges.push({
                                index: index,
                                rowNumber: rowNumber,
                                from: from,
                                to: to,
                                pointFromField: pointFromField,
                                pointToField: pointToField
                            });
                        }
                    }

                    // Validate amount is a valid number
                    if (amount) {
                        var amt = parseFloat(amount);
                        if (isNaN(amt)) {
                            setError(amountField, 'Must be a number');
                        } else if (amt <= 0) {
                            setError(amountField, 'Must be greater than 0');
                        }
                    }
                });

                // Check for overlapping ranges
                for (var i = 0; i < ranges.length; i++) {
                    for (var j = i + 1; j < ranges.length; j++) {
                        var range1 = ranges[i];
                        var range2 = ranges[j];

                        // Check if ranges overlap
                        if ((range1.from <= range2.to && range1.to >= range2.from)) {
                            var overlapMsg = 'Overlaps with Rule ' + range2.rowNumber + ' (' + range2.from + '-' +
                                range2.to + ')';
                            setError(range1.pointToField, overlapMsg);

                            var overlapMsg2 = 'Overlaps with Rule ' + range1.rowNumber + ' (' + range1.from + '-' +
                                range1.to + ')';
                            setError(range2.pointToField, overlapMsg2);
                        }
                    }
                }

                // Focus on first invalid field
                if (!isValid && firstInvalidField) {
                    firstInvalidField.focus();
                }

                return isValid;
            }
            // partial rule validation -----------------

            // AJAX Form Submission
            $('#redeem_rule').on('submit', function(e) {
                e.preventDefault();

                // Validate partial rules before submission
                if (!validatePartialRules()) {
                    return false;
                }

                // Additional front-end validation for full redeem type
                let isValid = true;
                const redeemType = $('#redeem_type').val();

                // Clear previous errors for full fields
                ['#threshold_points', '#max_discount'].forEach(function(id) {
                    const $input = $(id);
                    $input.removeClass('border border-danger');
                    $input.closest('.form-group').find('.help-block').html('');
                });

                if (redeemType === 'full') {
                    const fullFields = [
                        { id: '#threshold_points', label: 'Threshold points' },
                        { id: '#max_discount', label: 'Max discount points' },
                    ];

                    fullFields.forEach(function(f) {
                        const $input = $(f.id);
                        const value = parseFloat($input.val() || '0');
                        if (!value || value <= 0) {
                            isValid = false;
                            $input.addClass('border border-danger');
                            $input.closest('.form-group').find('.help-block').html(f.label + ' must be greater than 0.');
                        }
                    });
                }

                if (!isValid) {
                    // Focus first invalid full field
                    ['#threshold_points', '#max_discount'].some(function(id) {
                        const $input = $(id);
                        if ($input.hasClass('border-danger')) {
                            $input.focus();
                            return true;
                        }
                        return false;
                    });
                    return false;
                }

                var form = $(this);
                var ruleId = $('#redeem_rule_id').val();
                var url = ruleId ? "{{ route('update.redeem_rule', '') }}" + '/' + ruleId : form.attr(
                    'action');
                var formData = new FormData(this);

                // Show loading indicator
                var submitBtn = $('#submit-btn-redeem');
                var originalBtnText = submitBtn.html();
                submitBtn.html(
                    '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...'
                ).prop('disabled', true);

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status) {

                            Snackbar.show({
                                text: response.message,
                                pos: 'bottom-center'
                            });

                            $('#redeem_rule_id').val('');
                            $('#threshold_points').val('');
                            $('#max_discount').val('');
                            $('#redeem_service_id').empty().trigger('change.select2');
                            $('#redeem_loyalty_type').val('').trigger('change.select2');
                            $('#redeem_type').val('').trigger('change');
                            $('#redeem_status').val('1').trigger('change.select2');
                            $('#redeem_is_stackable').prop('checked', false);
                            resetPartialRules();
                            $('#partial_redeem_section').addClass('d-none');

                            // Reload table if it exists
                            if ($.fn.DataTable.isDataTable('#redeem_rule_table')) {
                                $('#redeem_rule_table').DataTable().ajax.reload(null, false);
                            }

                        } else {
                            Snackbar.show({
                                text: response.message ||
                                    'Something went wrong. Please try again.'
                            });
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'Something went wrong. Please try again.';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            var errorMessages = [];
                            $.each(errors, function(key, value) {
                                errorMessages.push(value[0]);
                            });
                            errorMessage = errorMessages.join('\n');
                        }

                        Snackbar.show({
                            text: errorMessage,
                            pos: 'bottom-center'
                        });
                    },
                    complete: function() {
                        // Update button state based on current form mode
                        updateSubmitButton();
                    }
                });
            });

            // Edit button logic
            $(document).on('click', '.edit-redeem-rule', function(e) {
                e.preventDefault();
                var id = $(this).data('id');

                $.get("{{ route('edit.redeem_rule', '') }}" + '/' + id, function(response) {
                    if (response.status) {
                        var data = response.data;

                        $('#redeem_rule_id').val(data.id);
                        $('#threshold_points').val(data.threshold_points);
                        $('#max_discount').val(data.max_discount);
                        $('#redeem_status').val(data.status == 1 ? '1' : '0').trigger(
                            'change.select2');
                        $('#redeem_is_stackable').prop('checked', data.is_stackable == 1);

                        // Set loyalty type and redeem type
                        $('#redeem_loyalty_type').val(data.loyalty_type).trigger('change');
                        $('#redeem_type').val(data.redeem_type).trigger('change');

                        // Handle service selection after a short delay to ensure select2 is ready
                        setTimeout(function() {
                            if (data.service_id && data.service_id.length > 0) {
                                $('#redeem_service_id').val(data.service_id).trigger(
                                    'change');
                            }
                            // Update button after service selection
                            updateSubmitButton();
                        }, 800);

                        // Handle partial rules
                        if (data.redeem_type === 'partial') {
                            $('#partial_redeem_section').removeClass('d-none');
                            if (data.partial_rules && data.partial_rules.length > 0) {
                                $('#partial_rules_container').empty();
                                data.partial_rules.forEach(function(partialRule, index) {
                                    var isLast = index === data.partial_rules.length - 1;
                                    var ruleName = (partialRule.rule_name || '').replace(
                                        /"/g, '&quot;');
                                    var pointFrom = (partialRule.point_from !== null &&
                                            partialRule.point_from !== undefined) ?
                                        partialRule.point_from : '';
                                    var pointTo = (partialRule.point_to !== null &&
                                            partialRule.point_to !== undefined) ?
                                        partialRule.point_to : '';
                                    var amount = (partialRule.partial_amount !== null &&
                                            partialRule.partial_amount !== undefined) ?
                                        partialRule.partial_amount : '';
                                    var statusVal = partialRule.partial_status == 1 ? '1' :
                                        '0';
                                    var addBtnClass = isLast ? '' : 'd-none';
                                    var removeBtnClass = data.partial_rules.length > 1 ?
                                        '' : 'd-none';

                                    var newRuleHtml =
                                        '<div class="partial-rule-row row mb-3">' +
                                        '<div class="form-group col-md-2">' +
                                        '<label class="form-label">{{ __('messages.rule_name') }} <span class="text-danger">*</span></label>' +
                                        '<input type="text" name="partial_rule_name[]" class="form-control partial_rule_name" placeholder="{{ __('messages.enter_rule_name') }}" value="' +
                                        ruleName + '" required>' +
                                        '<small class="help-block with-errors text-danger"></small>' +
                                        '</div>' +
                                        '<div class="form-group col-md-2">' +
                                        '<label class="form-label">{{ __('messages.point_from') }} <span class="text-danger">*</span></label>' +
                                        '<input type="number" name="point_from[]" class="form-control point_from" placeholder="{{ __('messages.enter_point_from') }}" min="1" value="' +
                                        pointFrom + '" required>' +
                                        '<small class="help-block with-errors text-danger"></small>' +
                                        '</div>' +
                                        '<div class="form-group col-md-2">' +
                                        '<label class="form-label">{{ __('messages.point_to') }} <span class="text-danger">*</span></label>' +
                                        '<input type="number" name="point_to[]" class="form-control point_to" placeholder="{{ __('messages.enter_point_to') }}" min="1" value="' +
                                        pointTo + '" required>' +
                                        '<small class="help-block with-errors text-danger"></small>' +
                                        '</div>' +
                                        '<div class="form-group col-md-2">' +
                                        '<label class="form-label">{{ __('messages.amount') }} <span class="text-danger">*</span></label>' +
                                        '<input type="number" name="partial_amount[]" class="form-control partial_amount" placeholder="{{ __('messages.enter_amount') }}" min="1" value="' +
                                        amount + '" required>' +
                                        '<small class="help-block with-errors text-danger"></small>' +
                                        '</div>' +
                                        '<div class="form-group col-md-2">' +
                                        '<label class="form-label">{{ __('messages.status') }} <span class="text-danger">*</span></label>' +
                                        '<select name="partial_status[]" class="form-select select2js partial_status" required>' +
                                        '<option value="1" ' + (statusVal == '1' ?
                                            'selected' : '') +
                                        '>{{ __('messages.active') }}</option>' +
                                        '<option value="0" ' + (statusVal == '0' ?
                                            'selected' : '') +
                                        '>{{ __('messages.inactive') }}</option>' +
                                        '</select>' +
                                        '<small class="help-block with-errors text-danger"></small>' +
                                        '</div>' +
                                        '<div class="form-group col-md-2 d-flex align-items-end gap-2">' +
                                        '<button type="button" class="btn btn-success add_partial_rule_row ' +
                                        addBtnClass +
                                        '" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">' +
                                        '<i class="fa fa-plus"></i>' +
                                        '</button>' +
                                        '<button type="button" class="btn btn-danger remove_partial_rule ' +
                                        removeBtnClass +
                                        '" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;">' +
                                        '<i class="fa fa-trash"></i>' +
                                        '</button>' +
                                        '</div>' +
                                        '</div>';

                                    $('#partial_rules_container').append(newRuleHtml);
                                });
                                // Reinitialize select2 for all partial status selects
                                $('#partial_rules_container .partial_status.select2js').select2({
                                    width: '100%'
                                });
                            } else {
                                // No partial rules, show single empty row
                                resetPartialRules();
                            }
                            updateButtonVisibility();
                        } else {
                            $('#partial_redeem_section').addClass('d-none');
                            resetPartialRules();
                        }

                        updateSubmitButton();
                    } else {
                        Snackbar.show({
                            text: response.message || 'Failed to load rule data.',
                            pos: 'bottom-center'
                        });
                    }
                }).fail(function(xhr) {
                    Snackbar.show({
                        text: 'Failed to load rule data. Please try again.',
                        pos: 'bottom-center'
                    });
                }).always(function() {
                    // Update button after loading edit data
                    updateSubmitButton();
                });
            });

            // Reset form
            $(document).on('click', '#reset-btn-redeem', function() {
                $('#redeem_rule_id').val('');
                $('#threshold_points').val('');
                $('#max_discount').val('');
                $('#redeem_service_id').empty().trigger('change.select2');
                $('#redeem_loyalty_type').val('').trigger('change.select2');
                $('#redeem_type').val('').trigger('change');
                $('#redeem_status').val('1').trigger('change.select2');
                $('#redeem_is_stackable').prop('checked', false);

                // Reset partial rules
                resetPartialRules();
                $('#partial_redeem_section').addClass('d-none');

                // Clear validation errors
                $('.help-block.with-errors').html('');
                $('.form-group').removeClass('has-error');

                updateSubmitButton();
            });

            togglePartialSection();
            updateSubmitButton();
            $('#submit-btn-redeem').prop('disabled', false);
        });
    </script>
@endpush
