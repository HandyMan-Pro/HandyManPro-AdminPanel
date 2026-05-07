@php
    $auth_user = authSession();
@endphp

{{ html()->form('DELETE', route('shopdocument.destroy', $query->id))
    ->attribute('data--submit', 'shopdocument'.$query->id)
    ->open() }}

<div class="d-flex justify-content-end align-items-center ms-2">

    @if(!$query->trashed() && $auth_user->can('shopdocument delete'))
        <a href="{{ route('shopdocument.destroy', $query->id) }}"
           data--submit="shopdocument{{ $query->id }}"
           data--confirmation="true"
           data--ajax="true"
           data-datatable="reload"
           data-title="{{ __('messages.delete_form_title', ['form'=> __('messages.shop_document') ]) }}"
           data-message='{{ __("messages.delete_msg") }}'
           title="{{ __('messages.delete_form_title', ['form'=> __('messages.shop_document') ]) }}"
           class="me-3">
            <i class="far fa-trash-alt text-danger"></i>
        </a>
    @endif

    @if(auth()->user()->hasRole(['admin', 'demo_admin']) && $query->trashed())
        <a href="{{ route('shopdocument.action', ['id' => $query->id, 'type' => 'restore']) }}"
           title="{{ __('messages.restore_form_title', ['form' => __('messages.shop_document') ]) }}"
           data--submit="confirm_form"
           data--confirmation="true"
           data--ajax="true"
           data-title="{{ __('messages.restore_form_title', ['form'=> __('messages.shop_document') ]) }}"
           data-message="{{ __('messages.restore_msg') }}"
           data-datatable="reload"
           class="me-2">
            <i class="fas fa-redo text-secondary"></i>
        </a>

        <a href="{{ route('shopdocument.action', ['id' => $query->id, 'type' => 'forcedelete']) }}"
           title="{{ __('messages.forcedelete_form_title', ['form' => __('messages.shop_document') ]) }}"
           data--submit="confirm_form"
           data--confirmation="true"
           data--ajax="true"
           data-title="{{ __('messages.forcedelete_form_title', ['form'=> __('messages.shop_document') ]) }}"
           data-message="{{ __('messages.forcedelete_msg') }}"
           data-datatable="reload"
           class="me-2">
            <i class="far fa-trash-alt text-danger"></i>
        </a>
    @endif

</div>

{{ html()->form()->close() }}
