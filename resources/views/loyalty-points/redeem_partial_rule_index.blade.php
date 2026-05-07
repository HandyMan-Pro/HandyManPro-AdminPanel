<x-master-layout>
    <?php $auth_user = authSession(); ?>

    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5>{{ __('messages.redeem_partial_points') }}</h5>
                            <a href="{{ route('loyalty.index') }}" class="float-end btn btn-sm btn-primary">
                                <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="partial_redeem_section" class="card d-none">
        <div class="card-body">
            <div class="row justify-content-between gy-3">
                <div class="m-3">
                    <div class="mb-3">
                        <h5 class="my-3">{{ __('messages.update_partial_rules') }}</h5>
                    </div>

                    <div id="partial_rules_container">
                        <form method="POST" action="/" id="redeem_rule" enctype="multipart/form-data" data-toggle="validator">
                            @csrf
                            <div class="partial-rule-row row mb-3">
                                <div class="form-group col-md-4">
                                    <label class="form-label">{{ __('messages.rule_name') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="partial_rule_name"
                                        class="form-control partial_rule_name" placeholder="Enter Name" required>
                                    <small class="help-block with-errors text-danger"></small>
                                </div>

                                <div class="form-group col-md-4">
                                    <label class="form-label">{{ __('messages.point_from') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="point_from" class="form-control point_from"
                                        placeholder="Enter Point From" min="1" required>
                                    <small class="help-block with-errors text-danger"></small>
                                </div>

                                <div class="form-group col-md-4">
                                    <label class="form-label">{{ __('messages.point_to') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="point_to" class="form-control point_to"
                                        placeholder="Enter Point To" min="1" required>
                                    <small class="help-block with-errors text-danger"></small>
                                </div>

                                <div class="form-group col-md-4">
                                    <label class="form-label">{{ __('messages.amount') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="number" name="partial_amount" class="form-control partial_amount"
                                        placeholder="Enter Amount" min="1" required>
                                    <small class="help-block with-errors text-danger"></small>
                                </div>

                                <div class="form-group col-md-4">
                                    <label class="form-label">{{ __('messages.status') }} <span
                                            class="text-danger">*</span></label>
                                    <select name="partial_status" class="form-select select2js partial_status">
                                        <option value="1">{{ __('messages.active') }}</option>
                                        <option value="0">{{ __('messages.inactive') }}</option>
                                    </select>
                                    <small class="help-block with-errors text-danger"></small>
                                </div>


                                <input type="hidden" name="redeem_rule_id" id="rule_id" value="{{ $id }}">
                                <input type="hidden" name="partial_rule_id" id="redeem_rule_id" value="">

                            </div>
                            <button type="submit" id="submit-btn-redeem" class="btn btn-md btn-primary float-end"><i
                                    class="fa fa-save"></i>
                                {{ __('messages.update_redeem_rule') }}</button>

                            <button type="reset" id="reset-btn-redeem"
                                class="btn btn-md btn-outline-danger float-end mx-2 d-none">
                                <i class="fa fa-times-circle"></i>
                                {{ __('messages.cancel') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between gy-3">
                @if ($auth_user->can('loyalty delete'))
                    <div class="col-md-6 col-lg-4 col-xl-4">
                        <div class="col-md-12">
                            <form action="{{ route('bulk_action.partial_rule') }}" id="quick-action-form"
                                class="form-disabled d-flex gap-3 align-items-center">
                                @csrf
                                <select name="action_type" class="form-select select2" id="quick-action-type"
                                    style="width:100%" disabled>
                                    <option value="">{{ __('messages.no_action') }}</option>
                                    <option value="change-status">{{ __('messages.status') }}</option>
                                    @if ($auth_user->can('loyalty delete'))
                                        <option value="delete">{{ __('messages.delete') }}</option>
                                        <option value="restore">{{ __('messages.restore') }}</option>
                                        <option value="permanently-delete">{{ __('messages.permanent_dlt') }}</option>
                                    @endif
                                </select>
                                <div class="select-status d-none quick-action-field" id="change-status-action"
                                    style="width:100%">
                                    <select name="status" class="form-select select2" id="status">
                                        <option value="1">{{ __('messages.active') }}</option>
                                        <option value="0">{{ __('messages.inactive') }}</option>
                                    </select>
                                </div>
                                <button id="quick-action-apply" class="btn btn-primary" data-ajax="true"
                                    data--submit="{{ route('bulk_action.partial_rule') }}" data-datatable="reload"
                                    data-confirmation='true' data-title="{{ __('messages.redeem_rule') }}"
                                    title="{{ __('messages.redeem_rule') }}"
                                    data-message='{{ __('Do you want to perform this action?') }}'
                                    disabled>{{ __('messages.apply') }}</button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="col-md-6 col-lg-4 col-xl-4">
                        <div class="col-md-12"></div>
                    </div>
                @endif
                {{-- Filter --}}
                <div class="col-md-6 col-lg-4 col-xl-4">
                    <div class="d-flex align-items-center gap-3 justify-content-end">
                        <div class="datatable-filter ml-auto">
                            <select name="partial_rule_status" id="partial_rule_status" class="select2 form-select"
                                data-filter="partial_rule_status" style="width: 100%">
                                <option value="">{{ __('messages.all') }}</option>
                                <option value="0">{{ __('messages.inactive') }}</option>
                                <option value="1">{{ __('messages.active') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                {{-- Table --}}
                <div class="table-responsive">
                    <table id="datatable" class="table table-striped border"></table>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let canEdit = {{ json_encode($auth_user->can('loyalty edit')) }};
            let canDelete = {{ json_encode($auth_user->can('loyalty delete')) }};

            // Build columns dynamically
            let columns = [];

            // Show checkbox column only if delete is allowed
            if (canDelete) {
                columns.push({
                    name: 'check',
                    data: 'check',
                    title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="loyaltyredeempartialrule" onclick="selectAllTable(this)">',
                    exportable: false,
                    orderable: false,
                    searchable: false,
                });
            }

            // Always visible columns
            columns.push({
                data: 'redeem_name',
                name: 'redeem_name',
                title: '{{ __('messages.rule_name') }}',
                orderable: false,
                searchable: false
            }, {
                data: 'point_from',
                name: 'point_from',
                title: '{{ __('messages.point_from') }}',
                orderable: false,
                searchable: false
            }, {
                data: 'point_to',
                name: 'point_to',
                title: '{{ __('messages.point_to') }}',
                orderable: false,
                searchable: false
            }, {
                data: 'discount_amount',
                name: 'discount_amount',
                title: '{{ __('messages.amount') }}',
                orderable: false,
                searchable: false
            }, {
                data: 'status',
                name: 'status',
                title: '{{ __('messages.status') }}',
                orderable: false,
                searchable: false
            }, );

            // Action column only if edit OR delete allowed
            if (canEdit || canDelete) {
                columns.push({
                    data: 'action',
                    name: 'action',
                    title: 'Action',
                    orderable: false,
                    searchable: false
                });
            }

            const defaultOrderIndex = canDelete ? 1 : 0;

            window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
                ajax: {
                    url: "{{ route('index.partial_rule') }}",
                    data: function(d) {
                        d.filter = {
                            column_status: $('#partial_rule_status').val()
                        };
                        d.id = "{{ $id }}";
                        d.type = "{{ $type }}";
                    }
                },
                columns: columns,
                order: [
                    [defaultOrderIndex, 'desc']
                ],
                language: {
                    processing: "{{ __('messages.processing') }}"
                }
            });

            function resetQuickAction() {
                const actionValue = $('#quick-action-type').val();

                if (actionValue != '') {
                    $('#quick-action-apply').removeAttr('disabled');
                    if (actionValue == 'change-status') {
                        $('.quick-action-field').addClass('d-none');
                        $('#change-status-action').removeClass('d-none');
                    } else {
                        $('.quick-action-field').addClass('d-none');
                    }
                } else {
                    $('#quick-action-apply').attr('disabled', true);
                    $('.quick-action-field').addClass('d-none');
                }
            }

            $('#quick-action-type').change(function() {
                resetQuickAction();
            });

            $(document).on('update_quick_action', function() {

            });

            $(document).on('click', '[data-ajax="true"]', function(e) {
                e.preventDefault();
                const button = $(this);
                const confirmation = button.data('confirmation');

                if (confirmation === 'true') {
                    const message = button.data('message');
                    if (confirm(message)) {
                        const submitUrl = button.data('submit');
                        const form = button.closest('form');
                        form.attr('action', submitUrl);
                        form.submit();
                    }
                } else {
                    const submitUrl = button.data('submit');
                    const form = button.closest('form');
                    form.attr('action', submitUrl);
                    form.submit();
                }
            });
        });

        $(document).ready(function() {
            $(document).on('click', '.edit-partial-rule', function() {
                let id = $(this).data('id');

                $.ajax({
                    url: "{{ route('edit.partial_rule', '') }}/" + id,
                    type: "GET",
                    success: function(response) {
                        $('#partial_redeem_section').removeClass('d-none');
                        $('input[name="partial_rule_name"]').val(response.rule_name);
                        $('input[name="point_from"]').val(response.point_from);
                        $('input[name="point_to"]').val(response.point_to);
                        $('input[name="partial_amount"]').val(response.amount);
                        $('select[name="partial_status"]').val(response.status == 1 ? '1' : '0')
                            .trigger('change');
                        $('input[name="partial_rule_id"]').val(response.id);
                        // Show cancel button
                        $('#reset-btn-redeem').removeClass('d-none');
                    },
                    error: function(xhr) {
                        console.error(xhr);
                        alert('Failed to fetch rule data!');
                    }
                });
            });

            // Cancel button functionality
            $('#reset-btn-redeem').on('click', function(e) {
                e.preventDefault();
                $('#redeem_rule')[0].reset();
                $('input[name="partial_rule_id"]').val('');
                $('#partial_redeem_section').addClass('d-none');
                // Hide cancel button again
                $('#reset-btn-redeem').addClass('d-none');
            });

            $('#redeem_rule').on('submit', function(e) {
                e.preventDefault();

                let id = $('input[name="partial_rule_id"]').val();
                let formData = new FormData(this);

                $.ajax({
                    url: "{{ route('update.partial_rule', '') }}/" + id,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Snackbar.show({
                                text: response.message,
                                pos: 'bottom-center'
                            });

                            $('#redeem_rule')[0].reset();
                            $('input[name="partial_rule_id"]').val('');

                            // Refresh table
                            $('#datatable').DataTable().ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        Snackbar.show({
                            text: xhr.responseJSON.message ||
                                'Something went wrong. Please try again.',
                            pos: 'bottom-center'
                        });
                    }
                });
            });
        });
    </script>
</x-master-layout>
