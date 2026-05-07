<x-master-layout>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-block card-stretch">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                        <h5 class="fw-bold">{{ __('messages.handyman_earning') }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between gy-3">
                <div class="col-md-6 col-lg-4 col-xl-3"></div>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="d-flex align-items-center gap-3 justify-content-end">
                        <div class="d-flex justify-content-end">
                            <div class="input-group input-group-search ms-2">
                                <span class="input-group-text" id="addon-wrapping"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control dt-search" placeholder="Search..." aria-label="Search" aria-describedby="addon-wrapping" aria-controls="dataTableBuilder">
                            </div>
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
@section('bottom_script')
<script type="text/javascript">
var i18n = window.AppDataTableI18n || {};
document.addEventListener('DOMContentLoaded', (event) => {
window.renderedDataTable = $('#datatable').DataTable({
    processing: true,
    serverSide: true,
    autoWidth: false,
    responsive: true,
    dom: '<"row align-items-center"><"table-responsive my-3 mt-3 mb-2 pb-1" rt><"row align-items-center data_table_widgets" <"col-md-6" <"d-flex align-items-center flex-wrap gap-3" l i>><"col-md-6" p>><"clear">',
    ajax: {
        "type": "GET",
        "url": "{{ route('handymanEarningData') }}",
        "data": function(d) {
            d.search = {
                value: $('.dt-search').val()
            };
        }
    },
    columns: [
        {data: 'handyman_name', name: 'handyman_name', title: "{{ __('messages.handyman') }}"},
        {data: 'total_bookings', name: 'total_bookings', title: "{{ __('messages.booking') }}", orderable: false, searchable: false},
        {data: 'handyman_earning', name: 'handyman_earning', title: "{{ __('messages.handyman_due_earning') }}", orderable: false, searchable: false},
        {data: 'handyman_paid_earning', name: 'handyman_paid_earning', title: "{{ __('messages.handyman_paid_earning') }}", orderable: false, searchable: false},
        {data: 'provider_earning', name: 'provider_earning', title: "{{ __('messages.provider_total_earning') }}", orderable: false, searchable: false},
        {data: 'admin_earning', name: 'admin_earning', title: "{{ __('messages.admin_earning') }}", orderable: false, searchable: false},
        {data: 'total_earning', name: 'total_earning', title: "{{ __('messages.total_earning') }}", orderable: false, searchable: false},
        @if(auth()->user()->hasAnyRole(['provider']))
        {data: 'action', name: 'action', title: "{{ __('messages.action') }}", orderable: false, searchable: false},
        @endif
    ],
    order: [[0, 'desc']],
   
});
});
</script>
@endsection
</x-master-layout>
