<x-master-layout>
    {{ html()->form('DELETE', route('provider.destroy', $providerdata->id))->attribute('data--submit', 'provider' . $providerdata->id)->open()}}
    <main class="main-area">
        <div class="main-content">
            <div class="container-fluid">
                @include('partials._provider')
               <div class="card">
                    @if($providerdata->providerHandyman->isNotEmpty())
                        <div class="card-body p-30">
                            <div class="service-man-list">
                                @foreach($providerdata->providerHandyman as $handyman)
                                    <div class="service-man-list__item">
                                        <div class="service-man-list__item_header">
                                            <div class="attach-img-box position-relative">
                                                @php
                                                    $extention = imageExtention(getSingleMedia($handyman, 'profile_image'));
                                                @endphp
                                                <img id="profile_image_preview"
                                                    src="{{ getSingleMedia($handyman, 'profile_image') }}"
                                                    alt="#"
                                                    class="attachment-image mt-1"
                                                    style="background-color:{{ $extention == 'svg' ? $providerdata->color : '' }}">
                                            </div>
                                            <h4 class="service-man-name">{{ $handyman->display_name ?? '-' }}</h4>
                                            <a class="service-man-phone" href="tel:{{ $handyman->contact_number }}">
                                                {{ $handyman->contact_number ?? '-' }}
                                            </a>
                                        </div>
                                        <div class="service-man-list__item_body">
                                            <a class="service-man-mail" href="mailto:{{ $handyman->email }}">
                                                {{ $handyman->email ?? '-' }}
                                            </a>
                                            <p class="service-man-address">{{ $handyman->address ?? '-' }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div>
                            <p class="text-center text-muted mt-3">{{ __('messages.no_handyman_found') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>
    {{ html()->form()->close() }}
    @section('bottom_script')
    @endsection
</x-master-layout>
