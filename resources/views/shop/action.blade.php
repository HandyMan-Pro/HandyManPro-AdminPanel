<?php $auth_user = authSession(); ?>
{{ html()->form('POST', route('shop.delete', $shop->id))->attribute('data--submit', 'shop' . $shop->id)->open() }}
<div class="d-flex justify-content-center align-items-center">

    <a href="{{ route('shop.manage-hour', $shop->id) }}" class="me-2" title="{{ __('messages.business_hours') }}">
        <i class="far fa-clock text-success"></i>
    </a>

    {{-- If shop is soft-deleted --}}
    @if($shop->deleted_at)
        @if($auth_user->can('shop delete'))
            {{-- Restore Shop --}}
            <a href="{{ route('shop.restore', $shop->id) }}"
                title="{{ __('messages.restore_form_title', ['form' => __('messages.shop')]) }}"
                data--submit="confirm_form"
                data--confirmation="true"
                data--ajax="true"
                data-title="{{ __('messages.restore_form_title', ['form' => __('messages.shop')]) }}"
                data-message="{{ __('messages.restore_msg') }}"
                data-datatable="reload"
                class="me-2">
                <i class="fas fa-redo text-secondary"></i>
            </a>

            {{-- Force Delete Shop --}}
            <a href="{{ route('shop.force_delete', $shop->id) }}"
                title="{{ __('messages.forcedelete_form_title', ['form' => __('messages.shop')]) }}"
                data--submit="confirm_form"
                data--confirmation="true"
                data--ajax="true"
                data-title="{{ __('messages.forcedelete_form_title', ['form' => __('messages.shop')]) }}"
                data-message="{{ __('messages.forcedelete_msg') }}"
                data-datatable="reload"
                class="me-2">
                <i class="far fa-trash-alt text-danger"></i>
            </a>
        @endif

    {{-- If shop is active --}}
    @else
        @if($auth_user->can('shop edit'))
            {{-- Edit Shop --}}
            <a href="{{ route('shop.edit', ['id' => $shop->id]) }}"
                title="{{ __('messages.update_form_title', ['form' => __('messages.shop')]) }}"
                class="me-2">
                <i class="fas fa-pen text-secondary"></i>
            </a>
        @endif

        @if($auth_user->can('shop delete'))
            {{-- Soft Delete Shop --}}
            <a href="{{ route('shop.delete', $shop->id) }}"
                data--submit="shop{{ $shop->id }}"
                data--confirmation="true"
                data--ajax="true"
                data-datatable="reload"
                title="{{ __('messages.delete_form_title', ['form' => __('messages.shop')]) }}"
                data-title="{{ __('messages.delete_form_title', ['form' => __('messages.shop')]) }}"
                data-message="{{ __('messages.delete_msg') }}"
                class="me-2 text-danger">
                <i class="far fa-trash-alt"></i>
            </a>
        @endif
    @endif

</div>
{{ html()->form()->close() }}
