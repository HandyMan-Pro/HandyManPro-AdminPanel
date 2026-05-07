<div class="card card-block card-stretch">
    <div class="card-body p-0">
        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
            <h5 class="fw-bold">{{__('messages.provider_detail')}}</h5>
        </div>
    </div>
</div>
<div class="mb-3">
    <ul class="nav nav-tabs pay-tabs nav--tabs__style2 provider-detail-tab">
        <li class="nav-item {{request()->routeIs('provider_info') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('provider_info',$providerdata->id) }}"> {{__('messages.overview')}}</a>
        </li>
        @if (default_earning_type() === 'subscription')
        <li class="nav-item {{request()->routeIs('plan.view') ? 'active' : ''}}">
                <a class="nav-link" href="{{ route('plan.view',$providerdata->id) }}"> {{__('messages.plan')}}</a>
        </li>
        @endif

        <li class="nav-item {{request()->routeIs('booking.details') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('booking.details',$providerdata->id) }}"> {{__('messages.Bookings')}}</a>
        </li>
        <li class="nav-item {{request()->routeIs('handyman.show') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('handyman.show',$providerdata->id) }}">{{__('messages.handyman')}}</a>
        </li>
        <li class="nav-item {{request()->routeIs('setting.comission') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('setting.comission',$providerdata->id) }}">{{__('messages.commission')}}</a>
        </li>
        <!-- <li class="nav-item {{request()->routeIs('bank.show') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('bank.show',$providerdata->id) }}">{{__('messages.Bank_info')}}</a>
        </li> -->
        <li class="nav-item {{request()->routeIs('provider.review') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('provider.review',$providerdata->id) }}">{{__('messages.Reviews')}}</a>
        </li>
        <li class="nav-item {{request()->routeIs('providerdocument.show') || request()->routeIs('providerdocument.detail') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('providerdocument.show',$providerdata->id) }}">{{__('messages.list_form_title',['form' => __('messages.document')])}}</a>
        </li>
        <li class="nav-item {{request()->routeIs('providerpayout.show') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('providerpayout.show',$providerdata->id) }}">{{__('messages.list_form_title',['form' => __('messages.provider_payout')])}}</a>
        </li>
        <li class="nav-item {{request()->routeIs('provideraddress.show') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('provideraddress.show',$providerdata->id) }}">{{__('messages.list_form_title',['form' => __('messages.provider_address')])}}</a>
        </li>
        @if ($auth_user->can('bank list'))
        <li class="nav-item {{request()->routeIs('bank.list') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('bank.list',$providerdata->id) }}">{{__('messages.list_form_title',['form' => __('messages.bank')])}}</a>
        </li>
        @endif
        <li class="nav-item {{request()->routeIs('provider.time-slot') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('provider.time-slot',$providerdata->id) }}">{{__('messages.list_form_title',['form' => __('messages.manage_slot')])}}</a>
        </li>
        @php
            $serviceconfig = \App\Models\Setting::getValueByKey('service-configurations', 'service-configurations');
            $shop_enabled = isset($serviceconfig->shop) ? (int) $serviceconfig->shop : 1;
        @endphp
        @if($shop_enabled && $auth_user->can('shop list'))
        <li class="nav-item {{request()->routeIs('provider.shop-list') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('provider.shop-list',$providerdata->id) }}">{{__('messages.list_form_title',['form' => __('messages.shop')])}}</a>
        </li>
        @endif
        @if($shop_enabled && $auth_user->can('shopdocument list'))
        <li class="nav-item {{request()->routeIs('shopdocument.show') ? 'active' : ''}}">
            <a class="nav-link" href="{{ route('shopdocument.show',$providerdata->id) }}">{{__('messages.list_form_title',['form' => __('messages.shop_document')])}}</a>
        </li>
        @endif
    </ul>
</div>
