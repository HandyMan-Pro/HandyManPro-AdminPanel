<div class="swiper-slide swiper-slide-active" style="width: 319.5px; margin-right: 30px;">
    <div class="justify-content-center service-slide-items-4">
        <div class="col">
            <div class="service-box-card bg-light rounded-3 mb-5">
                <div class="iq-image position-relative">
                    <a href="{{ route('shop.detail', $data->id) }}" class="service-img">
                        <img src="{{ getSingleMedia($data, 'shop_attachment', null) }}" alt="service"
                            class="service-img w-100 object-cover img-fluid rounded-3">
                    </a>
                </div>
                <a href="{{ route('provider.detail', $data->provider_id) }}" class="service-heading mt-4 d-block p-0">
                    <h5 class="service-title font-size-18 line-count-2">{{ $data->translate('shop_name') }}</h5>
                </a>
                <div class="mt-3">
                    <div class="d-flex align-items-center gap-2">
                        <img src="{{ getSingleMedia($data->provider, 'profile_image', null) }}" alt="service"
                            class="img-fluid rounded-3 object-cover avatar-24">
                        <a href="{{ route('provider.detail', $data->provider_id) }}"><span
                                class="font-size-14 service-user-name">{{ $data->provider->display_name }}</span></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
