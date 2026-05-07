<div class="row justify-content-between gy-3">
    @if ($auth_user->can('loyalty delete'))
        <div class="col-md-6 col-lg-4 col-xl-4">
            <div class="col-md-8">
                <form action="{{ route('bulk_action.referral_rule') }}" id="referral_quick-action-form"
                    class="form-disabled d-flex gap-3 align-items-center">
                    @csrf
                    <select name="action_type" class="form-select select2" id="referral_quick-action-type"
                        style="width:100%" disabled>
                        <option value="">{{ __('messages.no_action') }}</option>
                        <option value="change-status">{{ __('messages.status') }}</option>
                        @if ($auth_user->can('loyalty delete'))
                            <option value="delete">{{ __('messages.delete') }}</option>
                            <option value="restore">{{ __('messages.restore') }}</option>
                            <option value="permanently-delete">{{ __('messages.permanent_dlt') }}
                            </option>
                        @endif
                    </select>
                    <div class="select-status d-none quick-action-field" id="referral_change-status-action"
                        style="width:100%">
                        <select name="status" class="form-select select2" id="referral_status">
                            <option value="1">{{ __('messages.active') }}</option>
                            <option value="0">{{ __('messages.inactive') }}</option>
                        </select>
                    </div>
                    <button id="referral_quick-action-apply" class="btn btn-primary" data-ajax="true"
                        data--submit="{{ route('bulk_action.referral_rule') }}" data-datatable="reload"
                        data-confirmation='true' data-title="{{ __('messages.referral_rule') }}"
                        title="{{ __('messages.referral_rule') }}"
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
                <select name="referral_rule_status" id="referral_rule_status" class="select2 form-select"
                    data-filter="referral_rule_status" style="width: 100%">
                    <option value="">{{ __('messages.all') }}</option>
                    <option value="0">{{ __('messages.inactive') }}</option>
                    <option value="1">{{ __('messages.active') }}</option>
                </select>
            </div>
        </div>
    </div>
    {{-- Table --}}
    <div class="table-responsive">
        <table id="referral_rule_table" class="table table-striped border"></table>
    </div>
</div>

@push('referral_rule_scripts')
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
                    title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="referral_select-all-table" data-type="loyaltyreferrerule" onclick="selectAllTable(this)">',
                    exportable: false,
                    orderable: false,
                    searchable: false
                });
            }

            // Always visible columns
            columns.push({
                data: 'referrer_points',
                name: 'referrer_points',
                title: '{{ __('messages.referrer_points') }}',
                orderable: false,
                searchable: false
            }, {
                data: 'referred_user_points',
                name: 'referred_user_points',
                title: '{{ __('messages.referred_user_points') }}',
                orderable: false,
                searchable: false
            }, {
                data: 'max_referrals_per_user',
                name: 'max_referrals_per_user',
                title: '{{ __('messages.max_referrals_per_user') }}',
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

            window.renderedDataTable = $('#referral_rule_table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
                ajax: {
                    url: "{{ route('index.referral_rule') }}",
                    data: function(d) {
                        d.filter = {
                            column_status: $('#referral_rule_status').val()
                        };
                    }
                },
                columns: columns,
                order: [
                    [defaultOrderIndex, 'desc']
                ],
                language: {
                    processing: "{{ __('messages.processing') }}"
                },
                drawCallback: function(settings) {
                    // Hide submit button if any rule exists (only show when table is empty)
                    var api = this.api();
                    var totalRecords = api.page.info().recordsTotal;

                    if (totalRecords > 0 && !$('#rule_id').val()) {
                        $('#submit-btn-referral').hide();
                    } else if (totalRecords === 0) {
                        $('#submit-btn-referral').show();
                    }
                }
            });

            $(document).on('change', '.datatable-filter [data-filter="referral_rule_status"]', function() {
                window.renderedDataTable.ajax.reload(null, false);
            });

            function resetReferralQuickAction() {
                const actionValue = $('#referral_quick-action-type').val();

                if (actionValue != '') {
                    $('#referral_quick-action-apply').removeAttr('disabled');
                    if (actionValue == 'change-status') {
                        $('#referral_quick-action-form .quick-action-field').addClass('d-none');
                        $('#referral_change-status-action').removeClass('d-none');
                    } else {
                        $('#referral_quick-action-form .quick-action-field').addClass('d-none');
                    }
                } else {
                    $('#referral_quick-action-apply').attr('disabled', true);
                    $('#referral_quick-action-form .quick-action-field').addClass('d-none');
                }
            }

            $('#referral_quick-action-type').change(function() {
                resetReferralQuickAction();
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
