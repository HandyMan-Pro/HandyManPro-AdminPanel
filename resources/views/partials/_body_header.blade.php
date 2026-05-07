<div class="iq-top-navbar">
    <div class="iq-navbar-custom">
        <nav class="navbar navbar-expand-lg navbar-light p-0">
            <div class="side-menu-bt-sidebar small-device-toggle">
                <svg xmlns="http://www.w3.org/2000/svg" class="text-secondary wrapper-menu" width="30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </div>
            <div class="d-flex align-items-center">
                <div class="change-mode">
                    <div class="custom-control custom-switch custom-switch-icon custom-control-inline">
                        <div class="custom-switch-inner">
                            <p class="mb-0"> </p>
                            <input type="checkbox" class="custom-control-input" id="dark-mode" data-active="true">
                            <label class="custom-control-label" for="dark-mode" data-mode="toggle">
                                <span class="switch-icon-left">
                                    <svg class="svg-icon" id="h-sun" height="20" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </span>
                                <span class="switch-icon-right">
                                    <svg class="svg-icon" id="h-moon" height="20" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                    </svg>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-label="Toggle navigation">
                    <svg xmlns="http://www.w3.org/2000/svg" class="text-secondary" width="30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto navbar-list align-items-center">
                        @if(auth()->user()->hasAnyRole(['admin', 'demo_admin']))
                            <li class="nav-item dropdown iq-dropdown">
                                <a class="search-toggle nav-link shortcut-toogle" data-bs-toggle="dropdown" href="#" aria-expanded="true">
                                    <div class="d-flex align-items-center gap-3 btn btn-warning px-2 py-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 256 256"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm48-88a8,8,0,0,1-8,8H136v32a8,8,0,0,1-16,0V136H88a8,8,0,0,1,0-16h32V88a8,8,0,0,1,16,0v32h32A8,8,0,0,1,176,128Z"/></svg>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-widget dropdown-menu-end shadow" data-bs-popper="static">
                                    <div class="widget-data grid-container">
                                        <!-- Booking -->
                                        <a href="{{url('booking')}}" class="widget-card">
                                            <span class="icon-wrapper">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M2 12C2 8.22876 2 6.34315 3.17157 5.17157C4.34315 4 6.22876 4 10 4H14C17.7712 4 19.6569 4 20.8284 5.17157C22 6.34315 22 8.22876 22 12V14C22 17.7712 22 19.6569 20.8284 20.8284C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.8284C2 19.6569 2 17.7712 2 14V12Z" stroke="currentColor" stroke-width="1.5"></path>
                                                    <path d="M18 16L16 16M16 16L14 16M16 16L16 14M16 16L16 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    <path d="M7 4V2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    <path d="M17 4V2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                    <path d="M2.5 9H21.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                </svg>
                                            </span>
                                            <span class="label">Booking</span>
                                        </a>

                                        <!-- Service -->
                                        <a href="{{url('service')}}" class="widget-card">
                                            <span class="icon-wrapper">
                                                {{-- <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M232.49,215.51,185,168a92.12,92.12,0,1,0-17,17l47.53,47.54a12,12,0,0,0,17-17ZM44,112a68,68,0,1,1,68,68A68.07,68.07,0,0,1,44,112Z"/></svg> --}}
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M2.5 6.5C2.5 4.29086 4.29086 2.5 6.5 2.5C8.70914 2.5 10.5 4.29086 10.5 6.5V9.16667C10.5 9.47666 10.5 9.63165 10.4659 9.75882C10.3735 10.1039 10.1039 10.3735 9.75882 10.4659C9.63165 10.5 9.47666 10.5 9.16667 10.5H6.5C4.29086 10.5 2.5 8.70914 2.5 6.5Z" stroke="currentColor" stroke-width="1.5"></path>
                                                <path d="M13.5 14.8333C13.5 14.5233 13.5 14.3683 13.5341 14.2412C13.6265 13.8961 13.8961 13.6265 14.2412 13.5341C14.3683 13.5 14.5233 13.5 14.8333 13.5H17.5C19.7091 13.5 21.5 15.2909 21.5 17.5C21.5 19.7091 19.7091 21.5 17.5 21.5C15.2909 21.5 13.5 19.7091 13.5 17.5V14.8333Z" stroke="currentColor" stroke-width="1.5"></path>
                                                <path d="M2.5 17.5C2.5 15.2909 4.29086 13.5 6.5 13.5H8.9C9.46005 13.5 9.74008 13.5 9.95399 13.609C10.1422 13.7049 10.2951 13.8578 10.391 14.046C10.5 14.2599 10.5 14.5399 10.5 15.1V17.5C10.5 19.7091 8.70914 21.5 6.5 21.5C4.29086 21.5 2.5 19.7091 2.5 17.5Z" stroke="currentColor" stroke-width="1.5"></path>
                                                <path d="M13.5 6.5C13.5 4.29086 15.2909 2.5 17.5 2.5C19.7091 2.5 21.5 4.29086 21.5 6.5C21.5 8.70914 19.7091 10.5 17.5 10.5H14.6429C14.5102 10.5 14.4438 10.5 14.388 10.4937C13.9244 10.4415 13.5585 10.0756 13.5063 9.61196C13.5 9.55616 13.5 9.48982 13.5 9.35714V6.5Z" stroke="currentColor" stroke-width="1.5"></path>
                                                </svg>
                                            </span>
                                            <span class="label">Service</span>
                                        </a>

                                        <!-- Provider -->
                                        <a href="{{url('provider')}}" class="widget-card">
                                            <span class="icon-wrapper">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M256,136a8,8,0,0,1-8,8H232v16a8,8,0,0,1-16,0V144H200a8,8,0,0,1,0-16h16V112a8,8,0,0,1,16,0v16h16A8,8,0,0,1,256,136Zm-57.87,58.85a8,8,0,0,1-12.26,10.3C165.75,181.19,138.09,168,108,168s-57.75,13.19-77.87,37.15a8,8,0,0,1-12.25-10.3c14.94-17.78,33.52-30.41,54.17-37.17a68,68,0,1,1,71.9,0C164.6,164.44,183.18,177.07,198.13,194.85ZM108,152a52,52,0,1,0-52-52A52.06,52.06,0,0,0,108,152Z"/></svg>
                                            </span>
                                            <span class="label">Provider</span>
                                        </a>

                                        <!-- Handyman-->
                                        <a href="{{url('handyman')}}" class="widget-card">
                                            <span class="icon-wrapper">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M230.92,212c-15.23-26.33-38.7-45.21-66.09-54.16a72,72,0,1,0-73.66,0C63.78,166.78,40.31,185.66,25.08,212a8,8,0,1,0,13.85,8c18.84-32.56,52.14-52,89.07-52s70.23,19.44,89.07,52a8,8,0,1,0,13.85-8ZM72,96a56,56,0,1,1,56,56A56.06,56.06,0,0,1,72,96Z"/></svg>
                                            </span>
                                            <span class="label">Handyman</span>
                                        </a>

                                        <!-- Earning -->
                                        <a href="{{route('earning')}}" class="widget-card">
                                            <span class="icon-wrapper">
                                                <svg width="16" height="16" class="sidebar-menu-icon" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <g clip-path="url(#clip0_2357_751)">
                                                        <path d="M8.84999 8.175C7.14749 7.7325 6.59999 7.275 6.59999 6.5625C6.59999 5.745 7.35749 5.175 8.62499 5.175C9.95999 5.175 10.455 5.8125 10.5 6.75H12.1575C12.105 5.46 11.3175 4.275 9.74999 3.8925V2.25H7.49999V3.87C6.04499 4.185 4.87499 5.13 4.87499 6.5775C4.87499 8.31 6.30749 9.1725 8.39999 9.675C10.275 10.125 10.65 10.785 10.65 11.4825C10.65 12 10.2825 12.825 8.62499 12.825C7.07999 12.825 6.47249 12.135 6.38999 11.25H4.73999C4.82999 12.8925 6.05999 13.815 7.49999 14.1225V15.75H9.74999V14.1375C11.2125 13.86 12.375 13.0125 12.375 11.475C12.375 9.345 10.5525 8.6175 8.84999 8.175Z" fill="currentColor" />
                                                    </g>
                                                    <defs>
                                                        <clipPath id="clip0_2357_751">
                                                            <rect width="18" height="18" fill="white" />
                                                        </clipPath>
                                                    </defs>
                                                </svg>
                                            </span>
                                            <span class="label">Earning</span>
                                        </a>
                                        {{-- Setting --}}
                                        <a href="{{route('setting.index')}}" class="widget-card">
                                            <span class="icon-wrapper">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M128,80a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160Zm109.94-52.79a8,8,0,0,0-3.89-5.4l-29.83-17-.12-33.62a8,8,0,0,0-2.83-6.08,111.91,111.91,0,0,0-36.72-20.67,8,8,0,0,0-6.46.59L128,41.85,97.88,25a8,8,0,0,0-6.47-.6A112.1,112.1,0,0,0,54.73,45.15a8,8,0,0,0-2.83,6.07l-.15,33.65-29.83,17a8,8,0,0,0-3.89,5.4,106.47,106.47,0,0,0,0,41.56,8,8,0,0,0,3.89,5.4l29.83,17,.12,33.62a8,8,0,0,0,2.83,6.08,111.91,111.91,0,0,0,36.72,20.67,8,8,0,0,0,6.46-.59L128,214.15,158.12,231a7.91,7.91,0,0,0,3.9,1,8.09,8.09,0,0,0,2.57-.42,112.1,112.1,0,0,0,36.68-20.73,8,8,0,0,0,2.83-6.07l.15-33.65,29.83-17a8,8,0,0,0,3.89-5.4A106.47,106.47,0,0,0,237.94,107.21Zm-15,34.91-28.57,16.25a8,8,0,0,0-3,3c-.58,1-1.19,2.06-1.81,3.06a7.94,7.94,0,0,0-1.22,4.21l-.15,32.25a95.89,95.89,0,0,1-25.37,14.3L134,199.13a8,8,0,0,0-3.91-1h-.19c-1.21,0-2.43,0-3.64,0a8.08,8.08,0,0,0-4.1,1l-28.84,16.1A96,96,0,0,1,67.88,201l-.11-32.2a8,8,0,0,0-1.22-4.22c-.62-1-1.23-2-1.8-3.06a8.09,8.09,0,0,0-3-3.06l-28.6-16.29a90.49,90.49,0,0,1,0-28.26L61.67,97.63a8,8,0,0,0,3-3c.58-1,1.19-2.06,1.81-3.06a7.94,7.94,0,0,0,1.22-4.21l.15-32.25a95.89,95.89,0,0,1,25.37-14.3L122,56.87a8,8,0,0,0,4.1,1c1.21,0,2.43,0,3.64,0a8.08,8.08,0,0,0,4.1-1l28.84-16.1A96,96,0,0,1,188.12,55l.11,32.2a8,8,0,0,0,1.22,4.22c.62,1,1.23,2,1.8,3.06a8.09,8.09,0,0,0,3,3.06l28.6,16.29A90.49,90.49,0,0,1,222.9,142.12Z"/></svg>
                                            </span>
                                            <span class="label">Setting</span>
                                        </a>
                                    </div>
                                </ul>
                            </li>
                        @endif
                        <li class="nav-item nav-icon dropdown">
                            <a href="#" class="search-toggle dropdown-toggle notification_list" id="notification-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" class="h-6 w-6 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span class="bg-primary"></span>
                                <span class="badge badge-pill badge-up notify_count count-mail d-none"></span>
                                <span class=" dots d-none"></span>
                            </a>
                            <div class="iq-sub-dropdown dropdown-menu " aria-labelledby="notification-dropdown">
                                <div class="card shadow-none m-0 border-0 notification_data"></div>
                            </div>
                        </li>
                        <li class="nav-item nav-icon dropdown">
                            <a href="#" class="search-toggle dropdown-toggle language-toggle" id="languageDropdownMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php
                                $selected_lang_flag = file_exists(public_path('/images/flags/' . app()->getLocale() . '.png')) ? asset('/images/flags/' . app()->getLocale() . '.png') : asset('/images/language.png');
                                ?>
                                <img src="{{ $selected_lang_flag }}" class="img-fluid" alt="lang" style="height: 30px; min-width: 30px; width: 30px;">
                                <span class="bg-primary"></span>
                            </a>
                            <div class="iq-sub-dropdown dropdown-menu language-dropdown-menu" aria-labelledby="languageDropdownMenu">
                                <div class="card shadow-none m-0 border-0">
                                    <div class=" p-0 ">
                                        <ul class="dropdown-menu-1 list-group list-group-flush">
                                            <?php
                                            $language_option = sitesetupSession('get')->language_option ?? ["ar","nl","en","fr","de","hi","it"];
                                            if (!empty($language_option)) {
                                                $language_array = languagesArray($language_option);
                                            }
                                            ?>
                                            @if(count($language_array) > 0 )
                                            @foreach( $language_array as $lang )
                                            <li class="dropdown-item-1 list-group-item px-2 {{ app()->getLocale() == $lang['id'] ? 'active' : '' }}">
                                                <a class="p-0" data-lang="{{ $lang['id'] }}" href="{{ route('switch-language',['locale'=> $lang['id'] ]) }}">
                                                    <?php
                                                    $flag_path = file_exists(public_path('/images/flags/' . $lang['id'] . '.png')) ? asset('/images/flags/' . $lang['id'] . '.png') : asset('/images/language.png');
                                                    ?>
                                                    <img src="{{ $flag_path }}" alt="img-flag-{{ $lang['id'] }}" class="img-fluid" style="width: 20px;height: auto;min-width: 15px;" />
                                                    {{ $lang['title'] }}
                                                </a>
                                            </li>
                                            @endforeach
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </li>

                        <li class="nav-item nav-icon dropdown">
                            <a href="#" class="nav-item nav-icon dropdown-toggle search-toggle" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img src="{{ getSingleMedia(auth()->user(),'profile_image') }}" class="img-fluid avatar-rounded bg-light" alt="user">
                                <span class="mb-0  user-name">{{ auth()->user()->first_name." ".auth()->user()->last_name }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end user-dropdown shadow" aria-labelledby="dropdownMenuButton">
                                <li class="dropdown-item d-flex svg-icon">
                                    {{-- <svg class="svg-icon me-0 text-secondary" id="h-01-p" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg> --}}
                                    <svg class="svg-icon me-0 text-secondary" id="h-01-p" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"></path>
                                    </svg>
                                    <a href="{{ route('frontend.index') }}">{{ __('messages.home') }}</a>
                                </li>
                                <li class="dropdown-item d-flex svg-icon">
                                    <svg class="svg-icon me-0 text-secondary" id="h-01-p" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <a href="{{ route('setting.index',['page' => 'profile_form']) }}">{{ __('messages.my_profile') }}</a>
                                </li>
                                @role('provider')
                                <li class="dropdown-item d-flex svg-icon">
                                    <svg class="svg-icon me-0 text-secondary" id="h-01-p" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <a href="{{ route('provider_info', ['id' => auth()->id()]) }}">{{ __('messages.my_info') }}</a>
                                </li>
                                @endrole
                                @role('handyman')
                                <li class="dropdown-item d-flex svg-icon">
                                    <svg class="svg-icon me-0 text-secondary" id="h-01-p" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <a href="{{ route('handyman.detail', ['id' => auth()->id()]) }}">{{ __('messages.my_info') }}</a>
                                </li>
                                @endrole
                                @role('provider')
                                @if(default_earning_type() === 'subscription')
                                    @php
                                        $hasSubscription = \App\Models\ProviderSubscription::where('user_id', auth()->user()->id)->exists();
                                    @endphp
                                    @if($hasSubscription)
                                    <li class="dropdown-item d-flex svg-icon">
                                        <svg class="svg-icon me-0 text-secondary" id="h-01-p" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path d="M3 10H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M15 6L17 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            <path d="M21 13V11C21 7.22876 21 5.34315 19.7595 4.17157C18.519 3 16.5225 3 12.5294 3H11.4706C7.47751 3 5.48098 3 4.24049 4.17157C3 5.34315 3 7.22876 3 11V13C3 16.7712 3 18.6569 4.24049 19.8284C5.48098 21 7.47752 21 11.4706 21H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                            <path d="M17.5 17.5L17.5 22M17.5 22L15.5 20M17.5 22L19.5 20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <a href="{{ route('provider.my-billing') }}">{{ __('messages.my_billing') }}</a>
                                    </li>
                                    @endif
                                @endif
                                @endrole
                                <li class="dropdown-item d-flex svg-icon border-top">
                                    <svg class="svg-icon me-0 text-secondary" id="h-03-p" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <a href="{{  route('setting.index') }}">{{ __('messages.Settings') }}</a>
                                </li>
                                <li class="dropdown-item  d-flex svg-icon border-top">
                                    <form method="POST" class="d-flex align-items-center" action="{{ route('logout') }}">
                                        @csrf
                                        <svg class="svg-icon me-0 text-secondary" id="h-05-p" width="20" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        <a class="logout-link" href="javascript:void(0)" onclick="event.preventDefault();
                                        this.closest('form').submit();">
                                            {{ __('messages.log_out') }}
                                        </a>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</div>
