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
                            <h5 class="fw-bold">{{ __('messages.customer_loyalty_points_overview') }}</h5>
                            @php
                                // Preserve filters/pagination by using referrer; fallback to list route
                                $backUrl = url()->previous();
                                $path = parse_url($backUrl, PHP_URL_PATH) ?: '';
                                if (empty($backUrl) || strpos($path, 'loyalty-history') === false) {
                                    $backUrl = route('loyalty_history.view');
                                }
                            @endphp
                            <a href="{{ $backUrl }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="m-3">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <img src="{{ getSingleMedia($user, 'profile_image', null) }}" alt="avatar"
                            class="avatar avatar-60 rounded-pill">
                        <div>
                            <h2 class="m-0">{{ e($user->first_name) }} {{ e($user->last_name) }}</h2>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-4 mb-4">
                            <div
                                class="bg-primary-subtle rounded d-flex align-items-center justify-content-between p-4 h-100">
                                <div>
                                    <p class="text-muted">{{ __('messages.total_referral') }}</p>
                                    <h2 class="fw-bold mt-2">{{ $total_referral ?? 0 }}</h2>
                                </div>
                                <div class="p-3 bg-white rounded-circle">
                                    <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13.0014 5.90906C14.6331 5.90906 15.9559 4.58627 15.9559 2.95453C15.9559 1.32279 14.6331 0 13.0014 0C11.3697 0 10.0469 1.32279 10.0469 2.95453C10.0469 4.58627 11.3697 5.90906 13.0014 5.90906Z" fill="#5F60B9"/>
                                        <path d="M8.94965 11.8182H17.0539C17.7521 11.8182 18.32 11.2053 18.32 10.4517V10.0732C18.32 9.0858 17.8312 8.18387 17.0435 7.7193C16.1 7.163 14.6331 6.5 13.0018 6.5C11.3704 6.5 9.90355 7.16306 8.96007 7.7193C8.17237 8.18382 7.68359 9.0858 7.68359 10.0732V10.4517C7.68359 11.2054 8.25142 11.8182 8.94965 11.8182Z" fill="#5F60B9"/>
                                        <path d="M5.31781 20.0907C6.94955 20.0907 8.27234 18.7679 8.27234 17.1362C8.27234 15.5044 6.94955 14.1816 5.31781 14.1816C3.68607 14.1816 2.36328 15.5044 2.36328 17.1362C2.36328 18.7679 3.68607 20.0907 5.31781 20.0907Z" fill="#5F60B9"/>
                                        <path d="M9.35989 21.9009C8.41642 21.3446 6.94954 20.6816 5.31819 20.6816C3.68683 20.6816 2.21995 21.3447 1.27648 21.9009C0.488774 22.3655 0 23.2674 0 24.2547V24.6333C0 25.3869 0.567827 25.9998 1.26606 25.9998H9.37031C10.0685 25.9998 10.6364 25.3869 10.6364 24.6333V24.2547C10.6364 23.2674 10.1476 22.3655 9.35989 21.9009Z" fill="#5F60B9"/>
                                        <path d="M20.6811 20.0907C22.3128 20.0907 23.6356 18.7679 23.6356 17.1362C23.6356 15.5044 22.3128 14.1816 20.6811 14.1816C19.0494 14.1816 17.7266 15.5044 17.7266 17.1362C17.7266 18.7679 19.0494 20.0907 20.6811 20.0907Z" fill="#5F60B9"/>
                                        <path d="M24.7232 21.9009C23.7797 21.3446 22.3128 20.6816 20.6815 20.6816C19.0501 20.6816 17.5832 21.3447 16.6398 21.9009C15.8521 22.3655 15.3633 23.2674 15.3633 24.2548V24.6333C15.3633 25.387 15.9311 25.9998 16.6293 25.9998H24.7336C25.4318 25.9998 25.9997 25.387 25.9997 24.6333V24.2548C25.9996 23.2674 25.5108 22.3655 24.7232 21.9009Z" fill="#5F60B9"/>
                                        <path d="M15.9526 20.0909C16.1263 20.0909 16.2977 20.0147 16.4149 19.8693C16.6186 19.6143 16.577 19.2426 16.3225 19.0384L13.5896 16.852V13.5909C13.5896 13.2643 13.3253 13 12.9986 13C12.672 13 12.4077 13.2642 12.4077 13.5909V16.8524L9.67478 19.0383C9.42028 19.2426 9.37873 19.6142 9.58243 19.8693C9.6996 20.0147 9.87094 20.0909 10.0447 20.0909C10.1739 20.0909 10.3044 20.0488 10.4134 19.9616L12.9986 17.8935L15.5838 19.9616C15.6929 20.0488 15.8233 20.0909 15.9526 20.0909Z" fill="#5F60B9"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div
                                class="bg-primary-subtle rounded d-flex align-items-center justify-content-between p-4 h-100">
                                <div>
                                    <p class="text-muted">{{ __('messages.total_loyalty_points') }}</p>
                                    <h2 class="fw-bold mt-2">{{ $total_loyalty_points ?? 0 }}</h2>
                                </div>
                                <div class="p-3 bg-white rounded-circle">
                                    <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M11.8805 3.22939C11.9825 3.02384 12.1399 2.85086 12.3349 2.72993C12.5299 2.609 12.7548 2.54492 12.9843 2.54492C13.2138 2.54492 13.4387 2.609 13.6337 2.72993C13.8288 2.85086 13.9861 3.02384 14.0882 3.22939L16.2378 7.56088L21.0216 8.26682C21.2486 8.30033 21.4618 8.39656 21.637 8.54467C21.8123 8.69277 21.9428 8.88687 22.0137 9.1051C22.0846 9.32335 22.0931 9.55706 22.0384 9.77992C21.9836 10.0028 21.8678 10.2059 21.7038 10.3665L18.2486 13.7494L19.0555 18.5172C19.0937 18.7435 19.0681 18.976 18.9814 19.1884C18.8947 19.4009 18.7504 19.5849 18.5647 19.7198C18.3791 19.8547 18.1595 19.935 17.9306 19.9518C17.7018 19.9686 17.4728 19.9212 17.2694 19.8148L12.9843 17.5742L8.69921 19.8148C8.49586 19.9211 8.26687 19.9686 8.03803 19.9517C7.80919 19.9349 7.58958 19.8546 7.40395 19.7197C7.21831 19.5848 7.07401 19.4008 6.9873 19.1884C6.9006 18.9759 6.87492 18.7435 6.91317 18.5172L7.72005 13.7494L4.26483 10.3665C4.10082 10.2059 3.9849 10.0028 3.93014 9.77994C3.87537 9.55708 3.88392 9.32335 3.95484 9.10509C4.02576 8.88683 4.15621 8.69272 4.33152 8.54461C4.50682 8.3965 4.72 8.30029 4.94703 8.26682L9.73081 7.56088L11.8805 3.22939Z" fill="#5F60B9"/>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.9844 2.61328C13.2138 2.61328 13.4388 2.67736 13.6338 2.79829C13.8288 2.91922 13.9862 3.0922 14.0882 3.29775L16.2379 7.62924L21.0217 8.33518C21.2487 8.3687 21.4618 8.46492 21.6371 8.61303C21.8124 8.76113 21.9428 8.95523 22.0138 9.17346L22.0205 9.19486L12.9844 12.1073V2.61328Z" fill="#5F60B9"/>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.9853 12.1053L3.94922 9.19291C4.01726 8.96976 4.14724 8.77052 4.32405 8.61834C4.50086 8.46615 4.71724 8.36729 4.94803 8.33323L9.73181 7.62728L11.8815 3.2958C11.9835 3.09025 12.1409 2.91726 12.3359 2.79633C12.5309 2.6754 12.7558 2.61133 12.9853 2.61133V12.1053Z" fill="#5F60B9"/>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M18.5743 19.6948C18.3872 19.8395 18.1625 19.9277 17.9269 19.949C17.6912 19.9703 17.4544 19.9238 17.2443 19.815L12.9592 17.5934L8.67412 19.815C8.47418 19.9185 8.24985 19.9657 8.02513 19.9516C7.80042 19.9374 7.58379 19.8624 7.39844 19.7346L12.9592 12.1055L18.5743 19.6948Z" fill="#5F60B9"/>
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.6811 19.7557V23.9065C12.6811 24.0745 12.8174 24.2109 12.9854 24.2109C13.0661 24.2108 13.1435 24.1787 13.2005 24.1217C13.2576 24.0646 13.2896 23.9872 13.2897 23.9065V19.7557C13.2896 19.675 13.2576 19.5976 13.2005 19.5406C13.1435 19.4835 13.0661 19.4515 12.9854 19.4514C12.9047 19.4515 12.8273 19.4835 12.7703 19.5406C12.7132 19.5976 12.6811 19.675 12.6811 19.7557ZM20.3238 14.5907L24.2468 15.947C24.323 15.9734 24.4066 15.9684 24.4792 15.9331C24.5518 15.8978 24.6074 15.8352 24.6338 15.7589C24.6601 15.6826 24.6551 15.599 24.6198 15.5265C24.5845 15.4539 24.5219 15.3983 24.4456 15.3719L20.5226 14.0154C20.447 13.9914 20.3648 13.9978 20.2939 14.0334C20.2229 14.069 20.1686 14.131 20.1426 14.2061C20.1167 14.2811 20.1211 14.3634 20.1549 14.4352C20.1888 14.5071 20.2494 14.5629 20.3238 14.5907ZM5.44812 14.0154L1.52518 15.3719C1.44892 15.3983 1.38626 15.4539 1.35097 15.5264C1.31568 15.599 1.31064 15.6826 1.33698 15.7589C1.36339 15.8352 1.419 15.8978 1.49158 15.9331C1.56416 15.9684 1.64778 15.9734 1.72405 15.947L5.64706 14.5907C5.7233 14.5642 5.78593 14.5086 5.8212 14.4361C5.85648 14.3635 5.86151 14.2799 5.83519 14.2037C5.80878 14.1274 5.75317 14.0647 5.6806 14.0295C5.60802 13.9942 5.52441 13.9891 5.44812 14.0154ZM18.1741 5.65024L20.5269 2.23065C20.5727 2.16416 20.5901 2.08224 20.5755 2.0029C20.5608 1.92355 20.5152 1.85327 20.4488 1.80749C20.3823 1.76176 20.3004 1.74431 20.221 1.75898C20.1416 1.77364 20.0713 1.81923 20.0256 1.88571L17.6727 5.3053C17.6286 5.37182 17.6124 5.45299 17.6276 5.53134C17.6428 5.60969 17.6881 5.67894 17.7539 5.72417C17.8196 5.76941 17.9005 5.78701 17.9791 5.77318C18.0577 5.75935 18.1277 5.7152 18.1741 5.65024ZM8.59645 5.28215L5.92758 2.10299C5.87526 2.04263 5.8013 2.00527 5.72167 1.99896C5.64205 1.99265 5.56313 2.0179 5.50195 2.06926C5.44078 2.12062 5.40225 2.19398 5.39467 2.27349C5.3871 2.35301 5.4111 2.43232 5.46148 2.4943L8.13028 5.67346C8.18221 5.73523 8.25654 5.77386 8.33693 5.78088C8.41733 5.78789 8.49722 5.7627 8.55906 5.71085C8.62081 5.65892 8.65943 5.58461 8.66644 5.50424C8.67345 5.42386 8.64828 5.34398 8.59645 5.28215Z" fill="#5F60B9"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div
                                class="bg-primary-subtle rounded d-flex align-items-center justify-content-between p-4 h-100">
                                <div>
                                    <p class="text-muted">{{ __('messages.total_referral') }}</p>
                                    <h2 class="fw-bold mt-2">{{ $user->loyalty_points ?? 0 }}</h2>
                                </div>
                                <div class="p-3 bg-white rounded-circle">
                                    <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clip-path="url(#clip0_10352_65608)">
                                            <path d="M7.72266 19.1669V20.0281C7.72274 20.2127 7.65999 20.3918 7.54472 20.5359L6.09766 22.3438H2.44141V21.125L1.62891 13L3.16453 1.08875C3.18915 0.900258 3.28142 0.72713 3.42415 0.601583C3.56689 0.476035 3.75038 0.406619 3.94047 0.40625H4.06641C4.17163 0.406159 4.27579 0.427359 4.3726 0.468574C4.46942 0.509789 4.55689 0.570168 4.62977 0.646075C4.70264 0.721983 4.7594 0.811849 4.79663 0.910267C4.83386 1.00868 4.85079 1.11362 4.84641 1.21875L4.47672 10.8875C4.49559 10.7012 4.57893 10.5273 4.71234 10.3959C4.78683 10.3196 4.87594 10.259 4.97436 10.2178C5.07277 10.1766 5.17847 10.1557 5.28516 10.1562H5.41922C5.60952 10.156 5.79388 10.2226 5.94014 10.3443C6.0864 10.4661 6.18528 10.6353 6.21953 10.8225L7.70966 19.0218C7.71825 19.0697 7.7226 19.1182 7.72266 19.1669Z" fill="#5F60B9"/>
                                            <path d="M1.21875 25.5938V22.3438H7.71875V25.5938H1.21875Z" fill="#5F60B9"/>
                                            <path d="M18.2852 19.1669V20.0281C18.2851 20.2127 18.3478 20.3918 18.4631 20.5359L19.9102 22.3438H23.5664V21.125L24.3789 13L22.8433 1.08875C22.8187 0.900258 22.7264 0.72713 22.5837 0.601583C22.4409 0.476035 22.2574 0.406619 22.0673 0.40625H21.9414C21.8362 0.406159 21.732 0.427359 21.6352 0.468574C21.5384 0.509789 21.4509 0.570168 21.378 0.646075C21.3052 0.721983 21.2484 0.811849 21.2112 0.910267C21.174 1.00868 21.157 1.11362 21.1614 1.21875L21.5311 10.8875C21.5122 10.7012 21.4289 10.5273 21.2955 10.3959C21.221 10.3196 21.1319 10.259 21.0335 10.2178C20.935 10.1766 20.8293 10.1557 20.7227 10.1562H20.5886C20.3983 10.156 20.2139 10.2226 20.0677 10.3443C19.9214 10.4661 19.8225 10.6353 19.7883 10.8225L18.2982 19.0218C18.2896 19.0697 18.2852 19.1182 18.2852 19.1669Z" fill="#5F60B9"/>
                                            <path d="M18.2852 25.5938V22.3438H24.7852V25.5938H18.2852Z" fill="#5F60B9"/>
                                            <path d="M13.0039 11.7812L10.2735 13.3417C10.1992 13.3839 10.1152 13.4062 10.0298 13.4062C9.95184 13.4063 9.87504 13.3878 9.80569 13.3523C9.73634 13.3168 9.67642 13.2653 9.63087 13.2021C9.58532 13.1389 9.55544 13.0657 9.54371 12.9887C9.53197 12.9117 9.53871 12.833 9.56337 12.7591L10.5664 9.75L7.97047 8.26637C7.89533 8.22352 7.83284 8.16158 7.78933 8.08681C7.74581 8.01205 7.72281 7.92713 7.72266 7.84062V7.75328C7.72291 7.63252 7.76761 7.51607 7.84824 7.42616C7.92886 7.33624 8.03976 7.27916 8.15978 7.26578L11.3789 6.90625L12.4782 3.97516C12.5133 3.8815 12.5761 3.80079 12.6583 3.7438C12.7405 3.68681 12.8381 3.65627 12.9381 3.65625H13.0697C13.1697 3.65627 13.2673 3.68681 13.3495 3.7438C13.4317 3.80079 13.4945 3.8815 13.5296 3.97516L14.6289 6.90625L17.848 7.26375C17.9683 7.27715 18.0793 7.33441 18.16 7.42458C18.2406 7.51475 18.2852 7.63149 18.2852 7.75247V7.84062C18.2851 7.92727 18.2622 8.01236 18.2187 8.08728C18.1752 8.16219 18.1126 8.22426 18.0373 8.26719L15.4414 9.75L16.4444 12.7591C16.4691 12.833 16.4758 12.9117 16.4641 12.9887C16.4524 13.0657 16.4225 13.1389 16.3769 13.2021C16.3314 13.2653 16.2715 13.3168 16.2021 13.3523C16.1328 13.3878 16.056 13.4063 15.9781 13.4062C15.8926 13.4062 15.8086 13.3839 15.7343 13.3417L13.0039 11.7812Z" fill="#5F60B9"/>
                                            <path d="M13.0039 0.40625V2.03125" stroke="#5F60B9" stroke-width="0.817932" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M7.32031 2.4375L8.53906 3.65625" stroke="#5F60B9" stroke-width="0.817932" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M18.6914 2.4375L17.4727 3.65625" stroke="#5F60B9" stroke-width="0.817932" stroke-linecap="round" stroke-linejoin="round"/>
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_10352_65608">
                                            <rect width="26" height="26" fill="white"/>
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="m-3">
                    <div class="d-flex justify-content-end gap-4 flex-wrap align-items-center">
                        <div class="datatable-filter">
                            <select name="filter" id="filter" class="select2 form-select"
                                data-filter="select" style="width: 140px">
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
    </div>
    <script>
        $(document).ready(function() {
            // Build columns dynamically
            let columns = [];

            // Always visible columns
            columns.push({
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
