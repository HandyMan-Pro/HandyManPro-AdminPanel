<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <!-- test-->
            @if($rezorpayX_details ==null)
            <div class="col-md-12">
                <div class="alert alert-warning border border-warning py-3">
                    <p class="h5 text-warning">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <i class="fas fa-info-circle"></i>
                            {{__('messages.info_message')}}
                            <a href="{{ route('setting.index') }}" target="_blank" class="text-primary"> {{__('messages.here_is_the_link')}}<i class="fas fa-external-link-alt mx-2"></i></a>
                        </div>
                    </p>
                </div>
            </div>
            @endif

            <div class="col-md-12">
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <a href="{{ route('service.index') }}">
                            <div class="card total-booking-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <div class="d-flex flex-wrap justify-content-start align-items-center">
                                                <h4 class="mb-2 booking-text  fw-bold">{{ !empty($data['dashboard']['count_total_service']) ? $data['dashboard']['count_total_service']: 0 }} </h4>
                                                <!-- <h4 class="mb-2 booking-text  font-weight-bold text-break"> 000000000000 </h4> -->
                                            </div>
                                            <p class="mb-0 booking-text">{{ __('messages.total_name', ['name' => __('messages.service')]) }}</p>
                                        </div>
                                        <div class="col-auto d-flex align-items-center flex-column">
                                            <div class="iq-card-icon iq-card-icon-booking icon-shape  rounded-circle shadow">
                                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M8.52083 14.7917L6.5625 12.8542C6.4375 12.7292 6.375 12.5799 6.375 12.4062C6.375 12.2326 6.4375 12.0764 6.5625 11.9375C6.67361 11.8264 6.82292 11.7708 7.01042 11.7708C7.19792 11.7708 7.35417 11.8264 7.47917 11.9375L9.02083 13.4583L12.3958 10.0625C12.5347 9.93749 12.691 9.87846 12.8646 9.88541C13.0382 9.89235 13.1875 9.95832 13.3125 10.0833C13.4236 10.2222 13.4826 10.3785 13.4896 10.5521C13.4965 10.7257 13.4375 10.875 13.3125 11L9.52083 14.7917C9.38195 14.9305 9.21528 15 9.02083 15C8.82639 15 8.65972 14.9305 8.52083 14.7917ZM3.79167 18.4583C3.41667 18.4583 3.08681 18.316 2.80208 18.0312C2.51736 17.7465 2.375 17.4167 2.375 17.0417V4.20832C2.375 3.81943 2.51736 3.4861 2.80208 3.20832C3.08681 2.93055 3.41667 2.79166 3.79167 2.79166H5.10417V2.24999C5.10417 2.05555 5.17361 1.88888 5.3125 1.74999C5.45139 1.6111 5.61806 1.54166 5.8125 1.54166C6.02083 1.54166 6.19444 1.6111 6.33333 1.74999C6.47222 1.88888 6.54167 2.05555 6.54167 2.24999V2.79166H13.4583V2.24999C13.4583 2.05555 13.5278 1.88888 13.6667 1.74999C13.8056 1.6111 13.9722 1.54166 14.1667 1.54166C14.375 1.54166 14.5486 1.6111 14.6875 1.74999C14.8264 1.88888 14.8958 2.05555 14.8958 2.24999V2.79166H16.2083C16.5972 2.79166 16.9306 2.93055 17.2083 3.20832C17.4861 3.4861 17.625 3.81943 17.625 4.20832V17.0417C17.625 17.4167 17.4861 17.7465 17.2083 18.0312C16.9306 18.316 16.5972 18.4583 16.2083 18.4583H3.79167ZM3.79167 17.0417H16.2083V8.12499H3.79167V17.0417ZM3.79167 6.87499H16.2083V4.20832H3.79167V6.87499ZM3.79167 6.87499V4.20832V6.87499Z" fill="white"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">

                            <div class="card total-service-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <div class="d-flex flex-wrap justify-content-start align-items-center">
                                                <h4 class="mb-2 booking-text fw-bold">{{ !empty($data['total_tax']) ? getPriceFormat($data['total_tax']) : getPriceFormat(0) }}</h4>
                                            </div>
                                            <p class="mb-0 booking-text">{{ __('messages.total_name', ['name' => __('messages.Tax')]) }}</p>
                                        </div>
                                        <div class="col-auto d-flex flex-column">
                                            <div class="iq-card-icon iq-card-icon-revenue icon-shape text-white rounded-circle shadow">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 256 256">
                                                    <path fill="white" stroke="white" stroke-width="5" d="M39.7,10.6C39.6,11,39.5,64,39.6,128.5l0.2,117.2l62.7,0.2l62.8,0.1l25.6-25.1l25.6-25.1v-92.9V10h-88.2C58.1,10,39.9,10.2,39.7,10.6z M201.7,100.3v75.6l-26.2,0.1l-26.2,0.2l-0.2,27.2l-0.1,27.3h-47.1H54.7v-103v-103h73.5h73.5V100.3z M199.6,191.4c0,0.1-8,8-17.6,17.4l-17.6,17.3v-17.4v-17.4H182C191.6,191.2,199.6,191.3,199.6,191.4z"/>
                                                    <path fill="white" stroke="white" stroke-width="5" d="M94.1,45.2c-10.9,3.5-18.8,15.4-16.8,25.4c2,10.3,11.5,19.1,21.2,19.8c13.9,1,27.1-13,24.7-26.1C120.7,50.7,106.5,41.3,94.1,45.2z M104.5,59.7c2.7,1.2,4,3.6,4,7.7s-1.3,6.5-4,7.7c-3.5,1.5-8.8,0.6-10.9-1.7c-2.2-2.5-2.2-9.5,0-12C95.7,59.1,101,58.3,104.5,59.7z"/>
                                                    <path fill="white" stroke="white" stroke-width="5" d="M152.2,63.6c-0.4,0.2-13.1,12.5-28.1,27.5c-21.7,21.6-27.3,27.5-27.8,29.1c-1.4,5.2,3.8,10.4,9,8.9c1.4-0.4,8.1-6.8,28.6-27.3c14.7-14.7,27.1-27.3,27.6-28.1C164.4,68.2,158.1,61.5,152.2,63.6z"/>
                                                    <path fill="white" stroke="white" stroke-width="5" d="M152.9,102.5c-4.2,1.2-6.9,2.7-10.3,6c-5.8,5.6-8.1,10.6-7.7,17.7c0.3,5.9,2.3,10.2,7,14.9c7.3,7.3,16.7,9.2,25.3,4.9c8.6-4.2,14.6-14,13.8-22.5C179.7,110,165,99,152.9,102.5z M160.9,117c1.2,0.3,2.6,1.1,3.2,1.6c1.5,1.4,2.5,5.2,2.1,7.8c-0.8,4.9-3.2,6.8-8.6,6.8c-3.6,0-6-1.2-7.3-3.6c-0.9-1.7-1.1-6.6-0.3-8.6C151.5,117.3,156,115.7,160.9,117z"/>
                                                </svg>
                                           </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a  href="{{ route('earning') }}">
                            <div class="card total-revenue">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <div class="d-flex flex-wrap justify-content-start align-items-center">
                                                <h4 class="mb-2 booking-text fw-bold">{{ getPriceFormat($data['total_earning']) }}</h4>
                                                <p class="mb-0 ml-3 text-danger fw-bold"></p>
                                            </div>
                                            <p class="mb-0 booking-text">{{ __('messages.my_earning') }}</p>
                                        </div>
                                        <div class="col-auto d-flex flex-column">
                                            <div class="iq-card-icon iq-card-icon-revenue icon-shape text-white rounded-circle shadow">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 256 256" class="text-bold">
                                                    <path fill="white" stroke="white" stroke-width="7" d="M52.7,12c-7.4,1.4-12.6,6.2-14.2,13.1c-2.5,10.9,3,30.6,12.8,45.1c4.5,6.6,8.9,11,14.7,14.6c1.9,1.2,3.4,2.3,3.4,2.4c0,0.1-2.2,1.7-4.9,3.5c-10.5,6.9-23.3,18.5-30.7,27.7c-7.5,9.3-13.5,19.6-16.6,28.6c-7.6,21.5-9.4,50.6-4.3,69.3c4.9,18.2,14,25.8,33.1,27.8c5.9,0.6,184.4,0.2,188-0.4c4.8-0.9,8.4-3.6,10.9-8.2c0.8-1.5,0.9-2.8,0.9-11.3v-9.6l-1.6-2.8l-1.5-2.7l1.5-2.8l1.6-2.7l0.1-8.6c0.2-10.2-0.4-12.5-3.8-16c-3.2-3.2-6-4.4-11-4.4h-4.2l0.3-1.6c0.4-2,0.4-16,0-17.7l-0.4-1.3l4.5-0.2c3.6-0.1,4.9-0.4,6.8-1.4c2.8-1.5,5-3.6,6.5-6.6c1.1-2,1.2-2.8,1.3-10.8c0.1-7.8,0-8.9-0.8-11.3c-1.2-3.3-4.2-6.4-7.4-7.9l-2.4-1.2h-38h-38.1l-5.9-5.9c-6.6-6.6-15.7-14-22.3-18.3c-2.4-1.6-4.4-3-4.4-3.1s1.5-1.2,3.4-2.4c14.1-9,29.2-39.2,28.1-55.8c-0.3-4.9-0.9-6.9-2.9-9.8c-3.2-4.8-9.7-7.8-16.8-7.8c-5.2,0-10.2,1.3-19.1,4.8c-8.5,3.4-14.9,5.1-20.6,5.6c-4.7,0.3-9.7-0.4-12.6-1.8c-1-0.5-2.5-1.1-3.4-1.2c-0.9-0.1-5.1-1.6-9.5-3.2C62.7,11.9,57.9,11,52.7,12z M66.3,23.5c2.9,1,7.3,2.5,9.8,3.4c2.5,0.9,5.9,2.1,7.5,2.7c9.2,3.5,23.2,2.3,36.4-3.2c9-3.7,12.7-4.8,17.1-5.1c3.9-0.2,4.4-0.2,6.9,1.1c3.4,1.6,4.5,3.7,4.5,8.3c0,13.4-12.1,37.7-22.6,45.5c-2.8,2-8.3,5-9.4,5c-0.3,0,0.8-1.8,2.3-4c5.4-7.5,11.4-20.3,10.8-23.2c-0.7-3.5-5.1-5-7.6-2.7c-0.6,0.6-2,3.1-2.9,5.7c-3,7.6-9.1,17-15.1,23.3l-2.6,2.8h-3.9c-8.8,0-15.3-0.8-19.1-2.3c-4.9-1.9-10.9-5.9-14.3-9.5c-7.2-7.7-13.6-20.5-15.9-31.9c-1.6-8.1-1.2-13.1,1.4-15.6C53,20.6,57.9,20.5,66.3,23.5z M79.5,94.1c-2.7,3.5-4.8,7.8-4.8,10.3c0,2.5,2,4.5,4.7,4.5c2.4,0,3.2-0.8,5.2-4.8c1.2-2.5,2.8-4.6,5.6-7.5l3.9-3.9h7.7c4.2,0,8.9-0.2,10.3-0.5c2.5-0.5,2.7-0.5,5.3,1c9.6,5.4,19.7,13.3,28.9,22.5c17.1,17.2,24.5,31.1,28.2,53.8c4.4,26.8,0.3,52.9-9.4,60.3c-2.4,1.8-5.8,3.2-11,4.3c-3.5,0.8-8.2,0.9-53.2,1c-29.7,0.1-51.3,0-54.1-0.3c-5.5-0.6-10.8-2-14-3.7c-6.1-3.4-10.4-12.2-12.4-25.9c-1-7.1-0.8-23.5,0.5-32.3c3.6-24.7,10.9-39.2,28.6-56.9c7.7-7.7,12.3-11.6,20.3-17.1c4.3-2.9,11-7.1,11.5-7.1C81.4,91.6,80.6,92.8,79.5,94.1z M235.2,125.8c1.2,1.4,1.2,1.6,1.2,8.7v7.2l-1.4,1.4l-1.4,1.4h-27.8H178l-2.9-5.9c-1.6-3.2-4.1-7.7-5.6-9.9c-1.5-2.3-2.7-4.1-2.7-4.2c0-0.1,15.1-0.1,33.5-0.1h33.5L235.2,125.8z M216.4,155.4l1.6,1.4l0.1,6.7c0.1,5.5,0,7-0.6,8.3c-1.4,2.7-2.5,2.9-18.2,2.9h-14.2l-0.3-1.8c-0.3-1.7-3-15.5-3.5-17.8l-0.2-1.1H198h16.8L216.4,155.4z M235.1,185.6l1.4,1.4v7.3v7.3l-1.4,1.4l-1.4,1.4h-24.1h-24.1l0.3-4c0.2-2.2,0.3-6.7,0.3-10.1v-6.1h23.8h23.8L235.1,185.6z M233.7,214.5c2.5,1.3,2.7,2.1,2.7,9.6v7.1l-1.4,1.5l-1.4,1.6l-29.8,0.1l-29.8,0.1l1.9-2.5c2.5-3.4,5.4-9.4,6.8-14.2l1.2-3.9h24.4C225.3,214,233,214.1,233.7,214.5z"/><path fill="white" d="M115.8,115.8c-1.5,1.5-2,3.4-1.2,5.1c0.4,0.8,2.4,2.8,4.5,4.7c15.1,13,26.9,34.1,31.2,55.6c1.4,7.3,2.3,8.6,5.6,8.6c1.6,0,2.2-0.3,3.4-1.6c1.4-1.6,1.4-1.8,1.2-4.7c-0.2-1.7-1.2-6.3-2.2-10.4c-4.8-19.1-14.1-35.8-27.3-49.4c-7-7.1-9.5-9-12-9C117.4,114.7,116.6,115,115.8,115.8z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <a  href="{{ route('earning') }}">
                            <div class="card total-revenue">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <div class="d-flex flex-wrap justify-content-start align-items-center">
                                                <h4 class="mb-2 booking-text fw-bold">{{ getPriceFormat($data['total_revenue']) }}</h4>
                                                <p class="mb-0 ml-3 text-danger fw-bold"></p>
                                            </div>
                                            <p class="mb-0 booking-text">{{ __('messages.total_name', ['name' => __('messages.revenue')]) }}</p>
                                        </div>
                                        <div class="col-auto d-flex flex-column">
                                            <div class="iq-card-icon iq-card-icon-revenue icon-shape text-white rounded-circle shadow">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 1024 1024">
                                                    <path fill="white" stroke="white" stroke-width="20"
                                                    d="M136.948 908.811c5.657 0 10.24-4.583 10.24-10.24V610.755c0-5.657-4.583-10.24-10.24-10.24h-81.92a10.238 10.238 0 00-10.24 10.24v287.816c0 5.657 4.583 10.24 10.24 10.24h81.92zm0 40.96h-81.92c-28.278 0-51.2-22.922-51.2-51.2V610.755c0-28.278 22.922-51.2 51.2-51.2h81.92c28.278 0 51.2 22.922 51.2 51.2v287.816c0 28.278-22.922 51.2-51.2 51.2zm278.414-40.96c5.657 0 10.24-4.583 10.24-10.24V551.322c0-5.657-4.583-10.24-10.24-10.24h-81.92a10.238 10.238 0 00-10.24 10.24v347.249c0 5.657 4.583 10.24 10.24 10.24h81.92zm0 40.96h-81.92c-28.278 0-51.2-22.922-51.2-51.2V551.322c0-28.278 22.922-51.2 51.2-51.2h81.92c28.278 0 51.2 22.922 51.2 51.2v347.249c0 28.278-22.922 51.2-51.2 51.2zm278.414-40.342c5.657 0 10.24-4.583 10.24-10.24V492.497c0-5.651-4.588-10.24-10.24-10.24h-81.92c-5.652 0-10.24 4.589-10.24 10.24v406.692c0 5.657 4.583 10.24 10.24 10.24h81.92zm0 40.96h-81.92c-28.278 0-51.2-22.922-51.2-51.2V492.497c0-28.271 22.924-51.2 51.2-51.2h81.92c28.276 0 51.2 22.929 51.2 51.2v406.692c0 28.278-22.922 51.2-51.2 51.2zm278.414-40.958c5.657 0 10.24-4.583 10.24-10.24V441.299c0-5.657-4.583-10.24-10.24-10.24h-81.92a10.238 10.238 0 00-10.24 10.24v457.892c0 5.657 4.583 10.24 10.24 10.24h81.92zm0 40.96h-81.92c-28.278 0-51.2-22.922-51.2-51.2V441.299c0-28.278 22.922-51.2 51.2-51.2h81.92c28.278 0 51.2 22.922 51.2 51.2v457.892c0 28.278-22.922 51.2-51.2 51.2zm-6.205-841.902C677.379 271.088 355.268 367.011 19.245 387.336c-11.29.683-19.889 10.389-19.206 21.679s10.389 19.889 21.679 19.206c342.256-20.702 670.39-118.419 964.372-284.046 9.854-5.552 13.342-18.041 7.79-27.896s-18.041-13.342-27.896-7.79z"/>
                                                    <path fill="white" stroke="white" stroke-width="20"
                                                    d="M901.21 112.64l102.39.154c11.311.017 20.494-9.138 20.511-20.449s-9.138-20.494-20.449-20.511l-102.39-.154c-11.311-.017-20.494 9.138-20.511 20.449s9.138 20.494 20.449 20.511z"/>
                                                    <path fill="white" stroke="white" stroke-width="20"
                                                    d="M983.151 92.251l-.307 101.827c-.034 11.311 9.107 20.508 20.418 20.542s20.508-9.107 20.542-20.418l.307-101.827c.034-11.311-9.107-20.508-20.418-20.542s-20.508 9.107-20.542 20.418z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <h4 class="">{{__('messages.monthly_revenue')}}</h4>
                        </div>

                        {{-- <div class="d-flex gap-2 align-items-center">
                            <select id="filter-type" class="form-select">
                                <option value="month" selected>{{ __('messages.monthly') }}</option>
                                <option value="week">{{ __('messages.weekly') }}</option>
                                <option value="year">{{ __('messages.yearly') }}</option>
                                <option value="custom">{{ __('messages.custom_range') }}</option>
                            </select>

                            <select id="filter-year" class="form-select">
                                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                    <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>

                            <select id="filter-month" class="form-select">
                                <option value="all" selected>{{ __('messages.all_months') }}</option>
                                @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $index => $month)
                                    <option value="{{ $index + 1 }}">{{ $month }}</option>
                                @endforeach
                            </select>

                            <input type="date" id="filter-start-date" class="form-control d-none" placeholder="{{ __('messages.start_date') }}">
                            <input type="date" id="filter-end-date" class="form-control d-none" placeholder="{{ __('messages.end_date') }}">
                        </div> --}}


                        <div id="monthly-revenue" class="custom-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-6">
                <div class="card top-providers card-block card-stretch card-height">
                    <div class="card-header d-flex justify-content-between gap-10">
                        <h4 class="fw-bold">{{ __('messages.recent_provider') }} ({{$data['dashboard']['count_total_provider']}})</h4>
                            <a href="{{ route('provider.index') }}" class="btn-link btn-link-hover"><u>{{__('messages.view_all')}} </u></a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="common-list list-unstyled">
                            @foreach($data['dashboard']['new_provider'] as $provider)
                            <li>
                                <div class="media gap-3">
                                    <div class="h-avatar is-medium h-5">
                                        <img class="avatar-50 rounded-circle bg-light" alt="user-icon" src="{{ getSingleMedia($provider,'profile_image', null) }}">
                                    </div>

                                    <div class="media-body ">
                                        <a href="{{ route('provider_info', $provider->id) }}">
                                            <h5 class="mb-1"><span class="fw-bold">{{ !empty($provider->display_name) ? $provider->display_name : '-' }}</span></h5>
                                            <span class="mb-1">{{ $provider->email ?? '-' }}</span>
                                        </a>
                                            <span class="common-list_rating d-flex gap-1">
                                                <i class="ri-star-s-fill"></i>
                                                {{round($provider->getServiceRating->avg('rating'), 1)}}
                                            </span>
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-6">
                <div class="card top-providers card-block card-stretch card-height">
                    <div class="card-header d-flex justify-content-between gap-10">
                        <h4 class="fw-bold">{{ __('messages.recent_customer') }} ({{$data['dashboard']['count_total_customer']}})</h4>
                        <a href="{{ route('user.index') }}" class="btn-link btn-link-hover"><u>{{__('messages.view_all')}}</u></a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="common-list list-unstyled">
                            @foreach($data['dashboard']['new_customer'] as $customer)
                            <li style="pointer-events:none;">
                                <div class="media gap-3">
                                    <div class="h-avatar is-medium h-5">
                                        <img class="avatar-50 rounded-circle bg-light" alt="user-icon" src="{{ getSingleMedia($customer,'profile_image', null) }}">
                                    </div>
                                    <div class="media-body ">
                                        <h5 class="mb-1"><span class="fw-bold">{{!empty($customer->display_name) ? $customer->display_name : '-'}}</span>  </h5>
                                        <span>
                                            {{
                                                optional($data['datetime'])->date_format && optional($data['datetime'])->time_format
                                                ? \Carbon\Carbon::parse($customer->created_at)
                                                    ->format(optional($data['datetime'])->date_format) .'  '. \Carbon\Carbon::parse($customer->created_at)
                                                    ->format(optional($data['datetime'])->time_format)
                                                : ''
                                            }}
                                        </span>


                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-sm-12">
                <div class="card recent-activities card-block card-stretch card-height">
                    <div class="card-header d-flex justify-content-between gap-10">
                        <h4>{{__('messages.recent_booking')}} ({{$data['dashboard']['count_total_booking']}})</h4>
                        <a href="{{ route('booking.index') }}" class="btn-link btn-link-hover"><u>{{__('messages.view_all')}}</u></a>
                    </div>
                        <div class="card-body">
                            <ul class="common-list p-0">

                                @foreach($data['dashboard']['upcomming_booking'] as $booking)
                                    <li class="d-flex gap-3 align-items-start align-items-lg-center justify-content-between flex-column flex-sm-row " >
                                        <div class="media align-items-center gap-3">
                                                <div class="h-avatar is-medium h-5">
                                                    <img class="avatar-50 rounded-circle bg-light" alt="user-icon" src="{{ getSingleMedia($booking->customer,'profile_image', null) }}">
                                                </div>
                                                <div class="media-body ">
                                                    <a href="{{ route('booking.show', $booking->id) }}">
                                                        <h5 class="mb-1">#{{$booking->id}}</h5>
                                                    </a>
                                                    <span>{{
        optional($data['datetime'])->date_format && optional($data['datetime'])->time_format
        ? date(optional($data['datetime'])->date_format, strtotime($booking->date)) .'  '. date(optional($data['datetime'])->time_format, strtotime($booking->date))
        : ''
    }}</span>
                                                    {{-- <span>{{(date("$data['datetime']->date_format $data['datetime']->time_format", strtotime($booking->date)))}}</span> --}}
                                                </div>
                                        </div>
                                        <span class="badge rounded-pill py-2 px-3 bg-primary-subtle text-capitalize">{{ucwords(str_replace('_', ' ', $booking->status))}}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
<script>
    var chartData = '<?php echo $data['category_chart']['chartdata']; ?>';
    var currency_data = '<?php echo json_encode(currency_data()); ?>';
    var currency_object = JSON.parse(currency_data);
    var chartArray = JSON.parse(chartData);
    var chartlabel = '<?php echo $data['category_chart']['chartlabel']; ?>';
    var labelsArray = JSON.parse(chartlabel);
    if(jQuery('#monthly-revenue').length){
        var options = {
        series: [{
            name: 'revenue',
            data: [ {{ implode ( ',' ,$data['revenueData'] ) }} ]
            // data: [30, 39, 20, 28, 36, 33,20]
        }],
        chart: {
            height: 265,
            type: 'line',
            toolbar:{
                show: true,
            },
            events: {
                click: function(chart, w, e) {
                }
            }
        },
        colors: ['var(--bs-primary)'],
        plotOptions: {
            bar: {
                horizontal: false,
                s̶t̶a̶r̶t̶i̶n̶g̶S̶h̶a̶p̶e̶: 'flat',
                e̶n̶d̶i̶n̶g̶S̶h̶a̶p̶e̶: 'flat',
                borderRadius: 0,
                columnWidth: '70%',
                barHeight: '70%',
                distributed: false,
                rangeBarOverlap: true,
                rangeBarGroupRows: false,
                colors: {
                    ranges: [{
                        from: 0,
                        to: 0,
                        color: undefined
                    }],
                    backgroundBarColors: [],
                    backgroundBarOpacity: 1,
                    backgroundBarRadius: 0,
                },
                dataLabels: {
                    position: 'top',
                    maxItems: 100,
                    hideOverflowingLabels: true,
                }
            }
        },
        dataLabels: {
          enabled: false
        },
        grid: {
            borderColor: 'var(--bs-border-color)',
            xaxis: {
                lines: {
                    show: false
                }
            },
            yaxis: {
                lines: {
                    show: true,
                }
            }
        },
        legend: {
          show: false
        },
        yaxis: {
            labels: {
                offsetY:0,
                minWidth: 60,
                maxWidth: 60,
                style: {
                    colors: 'var(--bs-body-color)',
                },
                formatter: function(value) {
                    return currency_object.currency_symbol + value;
                }
            },
        },
        xaxis: {
            categories: [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'June',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec'
            ],
            labels: {
                minHeight: 22,
                maxHeight: 22,
                style: {
                    colors: 'var(--bs-body-color)',
                    fontSize: '12px'
                }
            }
        }
        };

        var chart = new ApexCharts(document.querySelector("#monthly-revenue"), options);
        chart.render();

        $('#filter-type').on('change', function() {
    var type = $(this).val();

    if(type == 'custom') {
        $('#filter-start-date, #filter-end-date').removeClass('d-none');
        $('#filter-month').addClass('d-none');
    } else if(type == 'year') {
        $('#filter-month, #filter-start-date, #filter-end-date').addClass('d-none');
    } else if(type == 'week') {
        $('#filter-month, #filter-start-date, #filter-end-date').addClass('d-none');
    } else { // monthly
        $('#filter-month').removeClass('d-none');
        $('#filter-start-date, #filter-end-date').addClass('d-none');
    }
});

$('#filter-year, #filter-month, #filter-start-date, #filter-end-date, #filter-type').on('change', function() {
    var type = $('#filter-type').val();
    var year = $('#filter-year').val();
    var month = $('#filter-month').val();
    var start_date = $('#filter-start-date').val();
    var end_date = $('#filter-end-date').val();

    $.ajax({
        url: "{{ route('dashboard.revenue.filter') }}",
        type: "GET",
        data: { type: type, year: year, month: month, start_date: start_date, end_date: end_date },
        success: function(response) {
            console.log(response); // Check the response from the server

            // Ensure the chart gets updated correctly
            chart.updateSeries([{
                name: 'revenue',
                data: response.revenueData // Assuming `revenueData` is the array you need
            }]);

            chart.updateOptions({
                xaxis: { categories: response.chartLabels } // Ensure chartLabels are updated
            });
        },
        error: function(error) {
            console.error("Error fetching filtered data:", error);
        }
    });
});



    }

</script>
