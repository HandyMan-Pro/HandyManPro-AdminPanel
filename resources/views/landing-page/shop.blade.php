@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <shop-page link="{{ route('shop.data',['id' => $id, 'type' => $type, 'latitude' => $latitude, 'longitude' => $longitude]) }}"></shop-page>
            </div>
        </div>
    </div>
</div>
@endsection
