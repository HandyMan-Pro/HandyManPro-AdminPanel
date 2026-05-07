<x-master-layout>
    <?php
    $auth_user = authSession();
    ?>

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
                            <h5 class="fw-bold">{{ __('messages.loyalty_point_history') }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end gap-4 flex-wrap align-items-center">
                    <div class="datatable-filter">
                        <select name="filter" id="filter" class="select2 form-select" data-filter="select"
                            style="width: 140px">
                            <option value="all">{{ __('messages.all') }}</option>
                            <option value="last_week">{{ __('messages.last_week') }}</option>
                            <option value="last_month">{{ __('messages.last_month') }}</option>
                            <option value="last_year">{{ __('messages.last_year') }}</option>
                        </select>
                    </div>
                    <div class="input-group input-group-search flex-grow-1 flex-md-grow-0" style="max-width: 250px;">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control dt-search" placeholder="Search...">
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
            // Build columns dynamically
            let columns = [];

            // Always visible columns
            columns.push({
                data: 'customer_name',
                name: 'customer_name',
                title: '{{ __('messages.customer_name') }}',
                orderable: false,
                searchable: false
            }, {
                data: 'date',
                name: 'date',
                title: '{{ __('messages.date') }}',
                orderable: false,
                searchable: false
            }, {
                data: 'loyalty_type',
                name: 'loyalty_type',
                title: '{{ __('messages.loyalty_type') }}',
                orderable: false,
            }, {
                data: 'loyalty_points',
                name: 'loyalty_points',
                title: '{{ __('messages.loyalty_points') }}',
                orderable: false,
                searchable: false
            }, )

            window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
                ajax: {
                    url: "{{ route('loyalty_history.index_data') }}",
                    data: function(d) {
                        d.search = {
                            value: $('.dt-search').val()
                        };
                        d.filter = $('#filter').val();
                        d.id = "{{ $id }}";
                        d.type = "{{ $type }}";
                    }
                },
                columns: columns,
                order: [
                    [0, 'desc']
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
    </script>
</x-master-layout>
