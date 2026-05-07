<div class="row justify-content-between gy-3">
    @if ($auth_user->can('loyalty delete'))
        <div class="col-md-6 col-lg-4 col-xl-4">
            <div class="col-md-8">
                <form action="{{ route('bulk_action.earn_rule') }}" id="earn_quick-action-form"
                    class="form-disabled d-flex gap-3 align-items-center">
                    @csrf
                    <select name="action_type" class="form-select select2" id="earn_quick-action-type" style="width:100%"
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

                    <div class="select-status d-none quick-action-field" id="earn_change-status-action"
                        style="width:100%">
                        <select name="status" class="form-select select2" id="earn_status">
                            <option value="1">{{ __('messages.active') }}</option>
                            <option value="0">{{ __('messages.inactive') }}</option>
                        </select>
                    </div>
                    <button id="earn_quick-action-apply" class="btn btn-primary" data-ajax="true"
                        data--submit="{{ route('bulk_action.earn_rule') }}" data-datatable="reload"
                        data-confirmation='true' data-title="{{ __('shop', ['form' => __('shop')]) }}"
                        title="{{ __('shop', ['form' => __('shop')]) }}"
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
                <select name="earn_rule_status" id="earn_rule_status" class="select2 form-select"
                    data-filter="earn_rule_status" style="width: 100%">
                    <option value="">{{ __('messages.all') }}</option>
                    <option value="0">{{ __('messages.inactive') }}</option>
                    <option value="1">{{ __('messages.active') }}</option>
                </select>
            </div>
            <div class="d-flex justify-content-end">
                <div class="input-group input-group-search ms-2">
                    <span class="input-group-text" id="addon-wrapping"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control dt-search" id="earn_rule_search" placeholder="Search..."
                        aria-label="Search" aria-describedby="addon-wrapping" aria-controls="dataTableBuilder">
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table id="earn_rule_table" class="table table-striped border"></table>
    </div>
</div>

@push('earn_rule_scripts')
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
                    title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="earn_select-all-table" data-type="loyaltyearnrule" onclick="selectAllTable(this)">',
                    exportable: false,
                    orderable: false,
                    searchable: false,
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
                data: 'minimum_amount',
                name: 'minimum_amount',
                title: '{{ __('messages.minimum_amount') }}',
                orderable: true,
                searchable: true
            }, {
                data: 'maximum_amount',
                name: 'maximum_amount',
                title: '{{ __('messages.maximum_amount') }}',
                orderable: true,
                searchable: true
            }, {
                data: 'points',
                name: 'points',
                title: '{{ __('messages.points') }}',
                orderable: true,
                searchable: true
            }, {
                data: 'stackable',
                name: 'stackable',
                title: '{{ __('messages.stackable') }}',
                orderable: false,
                searchable: false
            }, {
                data: 'expiry_days',
                name: 'expiry_days',
                title: '{{ __('messages.expiry_days') }}',
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

            window.renderedDataTable = $('#earn_rule_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
                ajax: {
                    url: "{{ route('index.earn_rule') }}",
                    data: function(d) {
                        d.filter = {
                            column_status: $('#earn_rule_status').val(),
                        };
                        d.search = {
                            value: $('#earn_rule_search').val()
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

            $(document).on('change', '.datatable-filter [data-filter="earn_rule_status"]', function() {
                $('#earn_rule_table').DataTable().ajax.reload(null, false);
            });

            $(document).on('input', '#earn_rule_search', function() {
                $('#earn_rule_table').DataTable().ajax.reload(null, false);
            });

            function resetEarnQuickAction() {
                const actionValue = $('#earn_quick-action-type').val();

                if (actionValue != '') {
                    $('#earn_quick-action-apply').removeAttr('disabled');
                    if (actionValue == 'change-status') {
                        $('#earn_quick-action-form .quick-action-field').addClass('d-none');
                        $('#earn_change-status-action').removeClass('d-none');
                    } else {
                        $('#earn_quick-action-form .quick-action-field').addClass('d-none');
                    }
                } else {
                    $('#earn_quick-action-apply').attr('disabled', true);
                    $('#earn_quick-action-form .quick-action-field').addClass('d-none');
                }
            }

            $('#earn_quick-action-type').change(function() {
                resetEarnQuickAction();
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
