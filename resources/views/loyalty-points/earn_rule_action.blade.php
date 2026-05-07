<?php $auth_user = authSession(); ?>
{{ html()->form('POST', route('delete.earn_rule', $row->id))->attribute('data--submit', 'earn_rule' . $row->id)->open() }}
<div class="d-flex justify-content-center align-items-center">
    @if ($row->deleted_at)
        @if ($auth_user->can('loyalty delete'))
            <a href="{{ route('restore.earn_rule', $row->id) }}"
                title="{{ __('messages.restore_form_title', ['form' => __('messages.earn_rule')]) }}"
                data--submit="confirm_form" data--confirmation="true" data--ajax="true"
                data-title="{{ __('messages.restore_form_title', ['form' => __('messages.earn_rule')]) }}"
                data-message="{{ __('messages.restore_msg') }}" data-datatable="reload" class="me-2">
                <i class="fas fa-redo text-secondary"></i>
            </a>
            <a href="{{ route('force_delete.earn_rule', $row->id) }}"
                title="{{ __('messages.forcedelete_form_title', ['form' => __('messages.earn_rule')]) }}"
                data--submit="confirm_form" data--confirmation="true" data--ajax="true"
                data-title="{{ __('messages.forcedelete_form_title', ['form' => __('messages.earn_rule')]) }}"
                data-message="{{ __('messages.forcedelete_msg') }}" data-datatable="reload" class="me-2">
                <i class="far fa-trash-alt text-danger"></i>
            </a>
        @endif
    @else
        @if ($auth_user->can('loyalty edit'))
            <a href="javascript:void(0);" class="edit-earn-rule me-2" data-id="{{ $row->id }}">
                <i class="fas fa-pen text-secondary"></i>
            </a>
        @endif

        @if ($auth_user->can('loyalty delete'))
            <a href="{{ route('delete.earn_rule', $row->id) }}" data--submit="loyalty{{ $row->id }}"
                data--confirmation="true" data--ajax="true" data-datatable="reload"
                title="{{ __('messages.delete_form_title', ['form' => __('messages.earn_rule')]) }}"
                data-title="{{ __('messages.delete_form_title', ['form' => __('messages.earn_rule')]) }}"
                data-message="{{ __('messages.delete_msg') }}" class="me-2 text-danger">
                <i class="far fa-trash-alt"></i>
            </a>
        @endif
    @endif
</div>
{{ html()->form()->close() }}
