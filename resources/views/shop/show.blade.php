<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ __('messages.shop') }} {{ __('messages.detail') }}</h5>
                            <a href="{{ route('shop.index') }}" class="float-end btn btn-sm btn-primary">
                                <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-lg-3 col-md-4">
                        <div class="shop-image">
                            <div class="border rounded p-2 bg-light">
                                <div class="ratio ratio-4x3">
                                    <img src="{{ getSingleMedia($shop, 'shop_attachment', null) }}" alt="Shop Image"
                                        class="w-100 h-100 rounded" style="object-fit: cover;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9 col-md-8">
                        <div class="shop-details">
                            <div class="row">
                                <div class="mb-3">
                                    <span class="fw-bold me-2">{{ __('messages.provider') }}:</span>
                                    {{ $shop->provider->first_name }} {{ $shop->provider->last_name }}
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">{{ __('messages.shop_name') }}:</span>
                                    {{ $shop->translate('shop_name') }}
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">{{ __('messages.address') }}:</span>
                                    {{ $shop->address }}{{ $shop->city ? ', ' . $shop->city->name : '' }}{{ $shop->state ? ', ' . $shop->state->name : '' }}{{ $shop->country ? ', ' . $shop->country->name : '' }}
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">{{ __('messages.coordinates') }}:</span>
                                    {{ $shop->lat }}, {{ $shop->long }}
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">{{ __('messages.registration_number') }}:</span>
                                    {{ $shop->registration_number }}
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">{{ __('messages.opening_hours') }}:</span>
                                    @if ($shop->shopHours && $shop->shopHours->count() > 0)
                                        <div class="mt-2">
                                            @php
                                                $dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                                $hoursMap = $shop->shopHours->keyBy(function($hour) {
                                                    return strtolower($hour->day);
                                                });
                                            @endphp
                                            @foreach ($dayOrder as $day)
                                                @php
                                                    $hour = $hoursMap->get($day);
                                                @endphp
                                                <div class="mb-2">
                                                    <strong>{{ __('messages.' . $day) }}:</strong>
                                                    @if ($hour)
                                                        @if ($hour->is_holiday)
                                                            <span class="text-muted">{{ __('messages.holiday') }}</span>
                                                        @else
                                                            {{ $hour->start_time }} - {{ $hour->end_time }}
                                                            @if ($hour->breaks && is_array($hour->breaks) && count($hour->breaks) > 0)
                                                                <br>
                                                                <small class="text-muted">
                                                                    {{ __('messages.breaks') }}:
                                                                    @foreach ($hour->breaks as $index => $break)
                                                                        {{ $break['start_break'] ?? '' }} - {{ $break['end_break'] ?? '' }}@if (!$loop->last), @endif
                                                                    @endforeach
                                                                </small>
                                                            @endif
                                                        @endif
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        -
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">{{ __('messages.contact_number') }}:</span>
                                    @if ($shop->contact_country_code)
                                        +{{ $shop->contact_country_code }}-{{ $shop->contact_number }}
                                    @else
                                        {{ $shop->contact_number }}
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <span class="fw-bold me-2">{{ __('messages.email') }}:</span>
                                    {{ $shop->email }}
                                </div>
                                <div class="mb-3 ">
                                    <div class="mb-3">
                                        <span class="fw-bold me-2">{{ __('messages.status') }}:</span>
                                        <span class="badge bg-{{ $shop->is_active ? 'success' : 'secondary' }}">
                                            {{ $shop->is_active ? __('messages.active') : __('messages.inactive') }}
                                        </span>
                                    </div>
                                    <div class="mb-0">
                                        <span class="fw-bold me-2">{{ __('messages.services') }}:</span>
                                        @if ($shop->services && $shop->services->count())
                                            <div class="d-flex flex-wrap gap-2 mt-2">
                                                @foreach ($shop->services as $service)
                                                    <span class="badge bg-primary">{{ $service->name }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">{{ __('messages.no_services_assigned') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
