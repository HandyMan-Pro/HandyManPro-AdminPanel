<x-master-layout>

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
                            <h5 class="fw-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            @if ($list_status != 'pending' &&  $list_status != 'subscribe')
                                @if ($auth_user->can('provider add'))
                                    <a href="{{ route('provider.create') }}"
                                        class=" float-end me-1 btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i>
                                        {{ __('messages.new') }}
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row justify-content-between gy-3">
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="col-md-12">
                                    <form action="{{ route('provider.bulk-action') }}" id="quick-action-form"
                                        class="form-disabled d-flex gap-3 align-items-center">
                                        @csrf
                                        <input type="hidden" name="list_status" value="{{ $list_status }}">
                                        <select name="action_type" class="form-select select2" id="quick-action-type"
                                            style="width:100%" disabled>
                                            <option value="">{{ __('messages.no_action') }}</option>
                                            @if ($list_status != 'subscribe')
                                                <option value="change-status">{{ __('messages.status') }}</option>
                                            @endif
                                            <option value="delete">{{ __('messages.delete') }}</option>
                                            <option value="restore">{{ __('messages.restore') }}</option>
                                            <option value="permanently-delete">{{ __('messages.permanent_dlt') }}
                                            </option>
                                        </select>

                                        <div class="select-status d-none quick-action-field" id="change-status-action"
                                            style="width:100%">
                                            <select name="status" class="form-select select2" id="status"
                                                style="width:100%">
                                                @if ($list_status == 'pending')
                                                    <option value="1">{{ __('messages.approve') }}</option>
                                                @elseif ($list_status == 'subscribe')
                                                    <option value="active">{{ __('messages.active') }}</option>
                                                    <option value="inactive">{{ __('messages.inactive') }}</option>
                                                @else
                                                    <option value="1">{{ __('messages.active') }}</option>
                                                    <option value="0">{{ __('messages.inactive') }}</option>
                                                @endif
                                            </select>
                                        </div>
                                        <button id="quick-action-apply" class="btn btn-primary" data-confirmation='true'
                                            data-ajax="true" data-submit="{{ route('provider.bulk-action') }}"
                                            data-datatable="reload"
                                            data-title="{{ __('provider', ['form' => __('provider')]) }}"
                                            title="{{ __('provider', ['form' => __('provider')]) }}"
                                            data-message='{{ __('Do you want to perform this action?') }}'
                                            >{{ __('messages.apply') }}</button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4 col-xl-3">
                                <div class="d-flex align-items-center gap-3 justify-content-end">
                                    <div class="input-group input-group-search ms-2">
                                        <span class="input-group-text" id="addon-wrapping"><i
                                                class="fas fa-search"></i></span>
                                        <input type="text" class="form-control dt-search" placeholder="Search..."
                                            aria-label="Search" aria-describedby="addon-wrapping"
                                            aria-controls="dataTableBuilder">
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="datatable" class="table table-striped border"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const canEdit = {{ json_encode($auth_user->can('provider edit')) }};
            const canDelete = {{ json_encode($auth_user->can('provider delete')) }};
            const canChangePassword = {{ json_encode($auth_user->can('provider changepassword')) }};
            const zone_id = @json($zone_id);
            const listStatus = '{{ $list_status }}';
            
            let columns = [];

            if (listStatus === 'subscribe') {
                // Columns for subscription list
                columns = [
                    {
                        name: 'check',
                        data: 'check',
                        title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="user" onclick="selectAllTable(this)">',
                        searchable: false,
                        exportable: false,
                        orderable: false,
                    },
                    {
                        data: 'updated_at',
                        name: 'updated_at',
                        title: "{{ __('product.lbl_update_at') }}",
                        orderable: true,
                        visible: false,
                        searchable: false,
                    },
                    {
                        data: 'display_name',
                        name: 'display_name',
                        title: "{{ __('messages.name') }}",
                        searchable: true,
                    },
                    {
                        data: 'plan',
                        name: 'plan',
                        title: "{{ __('messages.plan') }}",
                        orderable: false,
                        searchable: true,
                    },
                    {
                        data: 'type',
                        name: 'type',
                        title: "{{ __('messages.type') }}",
                        orderable: false,
                        searchable: true,
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                        title: "{{ __('messages.amount') }}",
                        orderable: false,
                        searchable: true,
                    },
                    {
                        data: 'start_at',
                        name: 'start_at',
                        title: "{{ __('messages.start_at') }}",
                        orderable: false,
                        searchable: true,
                    },
                    {
                        data: 'end_at',
                        name: 'end_at',
                        title: "{{ __('messages.end_at') }}",
                        orderable: false,
                        searchable: true,
                    },
                    {
                        data: 'status',
                        name: 'status',
                        title: "{{ __('messages.status') }}",
                        orderable: false,
                        searchable: true,
                    },
                ];

                // Add action column for subscription list
                if (canEdit) {
                    columns.push({
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        title: "{{ __('messages.action') }}",
                        className: 'text-end'
                    });
                }
            } else {
                // Default columns for regular provider list
                columns = [
                    {
                        name: 'check',
                        data: 'check',
                        title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="user" onclick="selectAllTable(this)">',
                        searchable: false,
                        exportable: false,
                        orderable: false,
                    },
                    {
                        data: 'updated_at',
                        name: 'updated_at',
                        title: "{{ __('product.lbl_update_at') }}",
                        orderable: true,
                        visible: false,
                    },
                    {
                        data: 'display_name',
                        name: 'display_name',
                        title: "{{ __('messages.name') }}",
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        title: "{{ __('messages.joining_date') }}"
                    },
                    {
                        data: 'providertype_id',
                        name: 'providertype_id',
                        title: "{{ __('messages.providertype') }}"
                    },
                    {
                        data: 'contact_number',
                        name: 'contact_number',
                        title: "{{ __('messages.contact_number') }}"
                    },
                    {
                        data: 'shop',
                        name: 'shop',
                        title: "{{ __('messages.shop') }}",
                        searchable: false,
                        orderable: false,
                    },
                    {
                        data: 'wallet',
                        name: 'wallet',
                        title: "{{ __('messages.wallet_amt') }}",
                        searchable: false,
                        orderable: false,
                    },
                    {
                        data: 'status',
                        name: 'status',
                        title: "{{ __('messages.status') }}"
                    },
                ];

                // Add the action column if the user has edit or delete permissions
                if (canEdit || canDelete || canChangePassword) {
                    columns.push({
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        title: "{{ __('messages.action') }}",
                        className: 'text-end'
                    });
                }
            }

            window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" i l>><"col-md-6" p>><"clear">',
                ajax: {
                    "type": "GET",
                    "url": '{{ route('provider.index_data', ['list_status' => $list_status]) }}',
                    "data": function(d) {
                        d.search = {
                            value: $('.dt-search').val()
                        };
                        d.filter = {
                            column_status: $('#column_status').val()
                        };
                        d.zone_id = zone_id;
                    },
                },
                columns: columns,
                order: [
                    [1, 'desc']
                ],
                language: {
                    processing: "{{ __('messages.processing') }}"
                }
            });

            // Handle search input
            $('.dt-search').on('keyup', function() {
                window.renderedDataTable.draw();
            });

            // Handle Approve button clicks with loader
            $(document).on('click', '.approve-btn', function(e) {
                e.preventDefault();
                const button = $(this);
                const url = button.attr('href');

                // Show loader and disable button
                const originalText = button.html();
                button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __('messages.processing') }}...');
                button.prop('disabled', true);

                // Make AJAX request
                $.ajax({
                    url: url,
                    type: 'GET',
                    timeout: 30000,
                    success: function(response) {
                        console.log('Approval response:', response);

                        if (response.status === true) {
                            Swal.fire({
                                title: 'Success',
                                text: response.message || 'Provider approved successfully!',
                                icon: 'success',
                                iconColor: '#5F60B9',
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.renderedDataTable.ajax.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.message || 'Approval failed',
                                icon: 'error',
                                iconColor: '#5F60B9',
                                confirmButtonText: 'OK'
                            });
                            button.html(originalText);
                            button.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Approval AJAX error:', {
                            status: xhr.status,
                            responseText: xhr.responseText,
                            error: error
                        });

                        let errorMessage = 'Something went wrong!';

                        if (xhr.status === 0) {
                            errorMessage = 'Network error: Please check your internet connection.';
                        } else if (xhr.status === 404) {
                            errorMessage = 'Error: Provider not found.';
                        } else if (xhr.status === 500) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.message || 'Server error occurred.';
                            } catch (e) {
                                errorMessage = 'Server error: ' + xhr.status;
                            }
                        } else if (status === 'timeout') {
                            errorMessage = 'Request timed out. Please try again.';
                        } else {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.message || ('HTTP Error: ' + xhr.status);
                            } catch (e) {
                                errorMessage = 'HTTP Error: ' + xhr.status;
                            }
                        }

                        Swal.fire({
                            title: 'Error',
                            text: errorMessage,
                            icon: 'error',
                            iconColor: '#5F60B9',
                            confirmButtonText: 'OK'
                        });

                        button.html(originalText);
                        button.prop('disabled', false);
                    }
                });
            });
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

        $(document).on('click', '[data-ajax="true"]', function(e) {
            e.preventDefault();
            const button = $(this);
            const confirmation = button.data('confirmation');

            if (confirmation === 'true') {
                const message = button.data('message');
                Swal.fire({
                    title: 'Are you sure?',
                    text: message,
                    icon: 'warning',
                    iconColor: '#5F60B9',
                    showCancelButton: true,
                    confirmButtonColor: '#5F60B9',
                    cancelButtonColor: '#858482',
                    confirmButtonText: 'Yes, proceed!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const submitUrl = button.data('submit');
                        const form = button.closest('form');
                        
                        // Get all checked checkboxes from the table - try multiple selectors
                        const checkedIds = [];
                        $('input.select-table-row:checked').each(function() {
                            checkedIds.push($(this).val());
                        });
                        
                        console.log('Checked IDs:', checkedIds);
                        
                        if (checkedIds.length === 0) {
                            Swal.fire({
                                title: 'Error',
                                text: 'Please select at least one item',
                                icon: 'error',
                                iconColor: '#5F60B9',
                                confirmButtonText: 'OK'
                            });
                            return;
                        }
                        
                        // Clear existing datatable_ids and add checked ones
                        form.find('input[name="datatable_ids[]"]').remove();
                        checkedIds.forEach(function(id) {
                            form.append('<input type="hidden" name="datatable_ids[]" value="' + id + '">');
                        });
                        
                        form.attr('action', submitUrl);
                        form.submit();
                    }
                });
            } else {
                const submitUrl = button.data('submit');
                const form = button.closest('form');
                form.attr('action', submitUrl);
                form.submit();
            }
        });
    </script>

</x-master-layout>
