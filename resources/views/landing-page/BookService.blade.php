@extends('landing-page.layouts.default')

@section('content')
    <div class="section-padding">
        <div class="container">
            <booking-wizard :service="{{ $service }}" :shop_list="{{ $shop_list }}" :shop_id="'{{ $shop_id }}'"
                :coupons="{{ $coupons }}" :taxes="{{ $taxes }}" :user_id="{{ $user_id }}"
                :availableserviceslot="{{ json_encode($availableserviceslot) }}"
                :serviceaddon="{{ isset($serviceaddon) ? $serviceaddon : 'null' }}" :googlemapkey="'{{ $googlemapkey }}'"
                :wallet_amount="{{ $wallet_amount }}" :user_points="{{ $user_points }}"
                :redeem_points="{{ json_encode($redeem_points) }}"></booking-wizard>
        </div>
    </div>
@endsection
