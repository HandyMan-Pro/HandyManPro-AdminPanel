    <x-master-layout>
    <div class="container-fluid">
        @include('partials._provider')
        <div class="row">
            <div class="col-lg-12">
                <ul class="nav nav-tabs pay-tabs nav-fill tabslink" id="tab-text" role="tablist">
                    <li class="nav-item payment-link">
                        <a href="javascript:void(0)"
                            data-href="{{ route('provider_detail_pages') }}?tabpage=all-plan&providerid={{ request()->service }}"
                            data-target=".payment_paste_here" data-toggle="tabajax"
                            class="nav-link d-none  {{ $tabpage == 'all-plan' ? 'active' : '' }}" rel="tooltip">
                            {{ __('messages.all') }}</a>
                    </li>
                    {{-- <li class="nav-item payment-link">
                    <a href="javascript:void(0)" data-href="{{ route('provider_detail_pages') }}?tabpage=subscribe-plan&providerid={{request()->service}}" data-target=".payment_paste_here" data-toggle="tabajax" class="nav-link  {{$tabpage=='subscribe-plan'?'active':''}}" rel="tooltip"> {{__('messages.service_subscribe')}}</a>

                </li>
                <li class="nav-item payment-link">
                    <a href="javascript:void(0)" data-href="{{ route('provider_detail_pages') }}?tabpage=unsubscribe-plan&providerid={{request()->service}}" data-target=".payment_paste_here" data-toggle="tabajax" class="nav-link  {{$tabpage=='unsubscribe-plan'?'active':''}}" rel="tooltip"> {{__('messages.service_unsubscribe')}}</a>
                </li> --}}
                </ul>

                <div class="col-lg-12">
                    <div class="card card-block card-stretch">
                        <div class="card-body p-0">
                            <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                                <h5 class="fw-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row justify-content-end">
                            <div class="col-md-3">
                                <div class="d-flex justify-content-end">
                                    <div class="input-group input-group-search ml-auto">
                                        <span class="input-group-text" id="addon-wrapping"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control dt-search" placeholder="Search..." aria-label="Search" aria-describedby="addon-wrapping" aria-controls="dataTableBuilder">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table data-table mb-0">
                                <!-- DataTable will be populated here -->
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @section('bottom_script')

        <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', (event) => {
        // Set the load URL based on your route
        var loadurl = '{{ route('plan.view', ['id' => $id]) }}';

        // Initialize the DataTable
        var table = $('.data-table').DataTable({
            processing: true,
            serverSide: true,
            autoWidth: false,
            responsive: true,
            dom: '<"row align-items-center"><"table-responsive my-3" rt><"row align-items-center" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">', // Custom DOM layout
            ajax: {
                url: loadurl,
                type: 'GET',
                data: function(d) {
                    // Add custom search parameter
                    d.search_value = $('.dt-search').val();  // Use value from the search input
                    // If you have additional filters, add them here
                    d.filter = {
                        column_status: $('#column_status').val()  // Example of filter by column (optional)
                    };
                },
            },
            columns: [
                {
                    name: 'id',
                    data: 'id',
                    title: "{{ __('messages.id') }}",
                },
                {
                    data: 'provider_name',
                    name: 'provider_name',
                    title: "{{ __('messages.provider_name') }}",
                },
                {
                    data: 'title',
                    name: 'title',
                    title: "{{ __('messages.plan') }}",
                },
                {
                    data: 'type',
                    name: 'type',
                    title: "{{ __('messages.type') }}",
                    render: function(data, type, row) {
                        if (!data) return '';
                        return data.charAt(0).toUpperCase() + data.slice(1);  // Capitalize the first letter
                    }
                },
                {
                    data: 'amount',
                    name: 'amount',
                    title: "{{ __('messages.amount') }}",
                },
                {
                    data: 'start_at',
                    name: 'start_at',
                    title: "{{ __('messages.start_at') }}",
                },
                {
                    data: 'end_at',
                    name: 'end_at',
                    title: "{{ __('messages.end_at') }}",
                },
                {
                    data: 'status',
                    name: 'status',
                    title: "{{ __('messages.status') }}",
                    render: function(data, type, row) {
                        return data.charAt(0).toUpperCase() + data.slice(1);  // Capitalize the first letter
                    }
                },
            ],
            language: {
                processing: "{{ __('messages.processing') }}"  // Set your custom processing text
            }
        });

        // Trigger search when user types in the input field
        $('.dt-search').on('keyup', function() {
            table.ajax.reload();  // Reload the DataTable with the updated search value
        });
    });
    </script>
    @endsection
</x-master-layout>
