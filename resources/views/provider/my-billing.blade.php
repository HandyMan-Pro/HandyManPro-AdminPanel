<x-master-layout>
    <main class="main-area">
        <div class="main-content">
            <div class="container-fluid">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? __('messages.my_billing') }}</h5>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body p-0">
                        <div class="row align-items-center p-3 mb-3">
                            <div class="col-md-6">
                                <!-- Empty space - entries dropdown will be at bottom -->
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-end">
                                    <div class="input-group input-group-search" style="max-width: 300px;">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control dt-search" id="search-input" placeholder="Search...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="datatable" class="table table-striped border">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.srno') }}</th>
                                        <th>{{ __('messages.plan') }}</th>
                                        <th>{{ __('messages.type') }}</th>
                                        <th>{{ __('messages.amount') }}</th>
                                        <th>{{ __('messages.start_date') }}</th>
                                        <th>{{ __('messages.end_date') }}</th>
                                        <th>{{ __('messages.status') }}</th>
                                        <th>{{ __('messages.payment_status') }}</th>
                                        <th>{{ __('messages.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        <!-- Bottom info and pagination -->
                        <div class="row align-items-center data_table_widgets p-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center flex-wrap gap-3">
                                    <div class="dataTables_length">
                                        <label class="d-flex align-items-center gap-2 mb-0">
                                            <span>Display</span>
                                            <select class="form-select" id="entries-select" style="width: auto;">
                                                <option value="10">10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                                <option value="100">100</option>
                                            </select>
                                            <span>entries</span>
                                        </label>
                                    </div>
                                    <div class="dataTables_info" id="datatable-info">0 to 0 of 0 entries</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="dataTables_paginate" id="datatable-pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    @section('bottom_script')
        <script>
            (function($) {
                "use strict";
                $(document).ready(function() {
                    var table = $('#datatable').DataTable({
                        processing: true,
                        serverSide: true,
                        searching: false,
                        lengthChange: false,
                        paging: true,
                        pageLength: 10,
                        dom: 'rt',
                        ajax: {
                            url: "{{ route('provider.my-billing-data') }}",
                            type: 'GET',
                            data: function(d) {
                                d.search = {
                                    value: $('#search-input').val()
                                };
                            }
                        },
                        columns: [
                            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                            { data: 'title', name: 'title', searchable: true },
                            { data: 'type', name: 'type', searchable: true },
                            { data: 'amount', name: 'amount', searchable: true },
                            { data: 'start_at', name: 'start_at', searchable: true },
                            { data: 'end_at', name: 'end_at', searchable: true },
                            { data: 'status', name: 'status', searchable: true },
                            { data: 'payment_status', name: 'payment_status', searchable: true },
                            { data: 'action', name: 'action', orderable: false, searchable: false }
                        ],
                        order: [[0, 'desc']],
                        drawCallback: function(settings) {
                            var api = this.api();
                            var pageInfo = api.page.info();
                            
                            // Update info text - "X to Y of Z entries"
                            var infoText = (pageInfo.start + 1) + ' to ' + pageInfo.end + ' of ' + 
                                          pageInfo.recordsTotal + ' entries';
                            $('#datatable-info').html(infoText);
                            
                            // Move pagination
                            $('#datatable-pagination').html($('#datatable_paginate').html());
                            $('#datatable_paginate').remove();
                        }
                    });

                    // Handle entries dropdown change
                    $('#entries-select').on('change', function() {
                        var value = $(this).val();
                        table.page.len(value).draw();
                    });

                    // Handle search input
                    $('#search-input').on('keyup', function() {
                        table.draw();
                    });
                });
            })(jQuery);
        </script>
    @endsection
</x-master-layout>
