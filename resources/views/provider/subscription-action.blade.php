<?php
    $auth_user= authSession();
?>
@if($subscriptionId)
    <div class="d-flex justify-content-end align-items-center">
        @if($auth_user->can('provider edit'))
            <a class="me-2" href="{{ route('provider.subscription-detail', $subscriptionId) }}" title="{{ __('messages.view_form_title',['form' => __('messages.subscription') ]) }}">
                <i class="fas fa-eye text-secondary"></i>
            </a>
        @endif
    </div>
@else
    <span class="text-muted">-</span>
@endif
