@if(isset($query))
    @if(auth()->user()->can('shopdocument edit'))
        <a href="{{ route('shopdocument.create', ['id' => $query->id,'providerdocument' => $query->shop->provider_id]) }}">
            <div class="d-flex gap-3 align-items-center">
                <img src="{{ getSingleMedia($query->shop,'shop_attachment', null) }}" alt="avatar" class="avatar avatar-40 rounded-pill">
                <div class="text-start">
                    <h6 class="m-0 tn-link btn-link-hover">{{$query->shop->shop_name }} </h6>
                    <span class="btn-link btn-link-hover">{{ $query->shop->email }}</span>
                </div>
            </div>
        </a>
    @else
        <div class="d-flex gap-3 align-items-center">
            <img src="{{ getSingleMedia($query->shop,'shop_attachment', null) }}" alt="avatar" class="avatar avatar-40 rounded-pill">
            <div class="text-start">
                <h6 class="m-0 tn-link btn-link-hover">{{$query->shop->shop_name }} </h6>
                <span class="btn-link btn-link-hover">{{ $query->shop->email }}</span>
            </div>
        </div>
    @endif
@else
    <div class="align-items-center">
        <h6 class="text-center">{{ '-' }} </h6>
    </div>
@endif
