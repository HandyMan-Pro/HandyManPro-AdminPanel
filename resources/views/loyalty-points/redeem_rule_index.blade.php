<div class="row justify-content-between gy-3">
    @if ($auth_user->can('loyalty delete'))
        <div class="col-md-6 col-lg-4 col-xl-4">
            <div class="col-md-8">
                <form action="{{ route('bulk_action.redeem_rule') }}" id="redeem_quick-action-form"
                    class="form-disabled d-flex gap-3 align-items-center">
                    @csrf
                    <select name="action_type" class="form-select select2" id="redeem_quick-action-type" style="width:100%"
                        disabled>
                        <option value="">{{ __('messages.no_action') }}</option>
                        <option value="change-status">{{ __('messages.status') }}</option>
                        @if ($auth_user->can('loyalty delete'))
                            <option value="delete">{{ __('messages.delete') }}</option>
                            <option value="restore">{{ __('messages.restore') }}</option>
                            <option value="permanently-delete">{{ __('messages.permanent_dlt') }}
                            </option>
                        @endif
                    </select>
                    <div class="select-status d-none quick-action-field" id="redeem_change-status-action"
                        style="width:100%">
                        <select name="status" class="form-select select2" id="redeem_status">
                            <option value="1">{{ __('messages.active') }}</option>
                            <option value="0">{{ __('messages.inactive') }}</option>
                        </select>
                    </div>
                    <button id="redeem_quick-action-apply" class="btn btn-primary" data-ajax="true"
                        data--submit="{{ route('bulk_action.redeem_rule') }}" data-datatable="reload"
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
                <select name="redeem_rule_status" id="redeem_rule_status" class="select2 form-select"
                    data-filter="redeem_rule_status" style="width: 100%">
                    <option value="">{{ __('messages.all') }}</option>
                    <option value="0">{{ __('messages.inactive') }}</option>
                    <option value="1">{{ __('messages.active') }}</option>
                </select>
            </div>
            <div class="d-flex justify-content-end">
                <div class="input-group input-group-search ms-2">
                    <span class="input-group-text" id="addon-wrapping"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control dt-search" id="redeem_rule_search" placeholder="Search..."
                        aria-label="Search" aria-describedby="addon-wrapping" aria-controls="dataTableBuilder">
                </div>
            </div>
        </div>
    </div>
    {{-- Table --}}
    <div class="table-responsive">
        <table id="redeem_rule_table" class="table table-striped border"></table>
    </div>
</div>

@push('redeem_rule_scripts')
    <script>
        $(document).ready(function() {
            let canEdit = {{ json_encode($auth_user->can('loyalty edit')) }};
            let canDelete = {{ json_encode($auth_user->can('loyalty delete')) }};

            // Build columns dynamically
            let columns = [];


            if (canDelete) {
                columns.push({
                    name: 'check',
                    data: 'check',
                    title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="redeem_select-all-table" data-type="loyaltyredeemrule" onclick="selectAllTable(this)">',
                    exportable: false,
                    orderable: false,
                    searchable: false
                });
            }

            // Always visible columns
            columns.push({
                data: 'loyalty_type',
                name: 'loyalty_type',
                title: '{{ __('messages.loyalty_type') }}',
                orderable: true,
                searchable: true
            }, {
                data: 'service_count',
                name: 'service_count',
                title: '{{ __('messages.service_count') }}',
                orderable: false,
                searchable: false
            }, {
                data: 'redeem_type',
                name: 'redeem_type',
                title: '{{ __('messages.redeem_type') }}',
                orderable: false,
                searchable: true
            }, {
                data: 'partial_redeem_rules',
                name: 'partial_redeem_rules',
                title: '{{ __('messages.partial_redeem_rules') }}',
                orderable: false,
                searchable: false
            }, {
                data: 'threshold_points',
                name: 'threshold_points',
                title: '{{ __('messages.threshold_points') }}',
                orderable: true,
                searchable: true
            }, {
                data: 'max_discount',
                name: 'max_discount',
                title: '{{ __('messages.max_discount') }}',
                orderable: true,
                searchable: true
            }, {
                data: 'stackable',
                name: 'stackable',
                title: '{{ __('messages.stackable') }}',
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
                    title: '{{ __('messages.action') }}',
                    orderable: false,
                    searchable: false
                });
            }

            const defaultOrderIndex = canDelete ? 1 : 0;

            window.renderedDataTable = $('#redeem_rule_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
                ajax: {
                    url: "{{ route('index.redeem_rule') }}",
                    data: function(d) {
                        d.filter = {
                            column_status: $('#redeem_rule_status').val()
                        };
                        d.search = {
                            value: $('#redeem_rule_search').val()
                        };
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

            $(document).on('change', '.datatable-filter [data-filter="redeem_rule_status"]', function() {
                $('#redeem_rule_table').DataTable().ajax.reload(null, false);
            });

            $(document).on('input', '#redeem_rule_search', function() {
                $('#redeem_rule_table').DataTable().ajax.reload(null, false);
            });

            function resetRedeemQuickAction() {
                const actionValue = $('#redeem_quick-action-type').val();

                if (actionValue != '') {
                    $('#redeem_quick-action-apply').removeAttr('disabled');
                    if (actionValue == 'change-status') {
                        $('#redeem_quick-action-form .quick-action-field').addClass('d-none');
                        $('#redeem_change-status-action').removeClass('d-none');
                    } else {
                        $('#redeem_quick-action-form .quick-action-field').addClass('d-none');
                    }
                } else {
                    $('#redeem_quick-action-apply').attr('disabled', true);
                    $('#redeem_quick-action-form .quick-action-field').addClass('d-none');
                }
            }

            $('#redeem_quick-action-type').change(function() {
                resetRedeemQuickAction();
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
    </script>
@endpush
