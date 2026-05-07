<x-master-layout>
    <div class="container-fluid">
    @include('partials._provider')
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            @if($auth_user->can('shopdocument list'))
                                <a href="{{ route('shopdocument.show', $providerdata->id) }}" class=" float-end btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('shopdocument.store') }}" id="shop" enctype="multipart/form-data" data-toggle="validator">
                            @csrf
                            @if($shopDocument)
                                <input type="hidden" name="id" value="{{ $shopDocument->id }}">
                            @endif
                            <input type="hidden" name="provider_id" value="{{ $providerdata->id }}">
                            <div class="row">
                                <div class="form-group col-md-4 mb-3">
                                    <label for="name" class="form-label">{{ __('messages.select_shop') }} <span class="text-danger">*</span></label>
                                    <select name="shop_id" id="shop_id" class="select2js form-group" required
                                            data-initial-shop="{{ $shopDocument && $shopDocument->shop_id ? $shopDocument->shop_id : '' }}">
                                        <option value="" disabled {{ empty($shopDocument->shop_id) ? 'selected' : '' }}>{{ __('messages.select_shop') }}</option>
                                        @foreach($shop_list as $shop)
                                            <option value="{{ $shop->id }}"
                                                {{ (old('shop_id', $shopDocument->shop_id ?? $shopDocument->shop->id ?? '') == $shop->id) ? 'selected' : '' }}>
                                                {{ $shop->shop_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="help-block with-errors text-danger"></small>
                                </div>

                                <div class="form-group col-md-4 mb-3">
                                    <label id="documentLabel" for="document_id" class="form-label">{{ __('messages.select_document') }} <span class="text-danger">*</span></label>
                                    <select name="document_id" id="document_id" class="select2js form-group" required>
                                        <option value="" disabled selected>{{ __('messages.select_document') }}</option>
                                    </select>
                                    <small class="help-block with-errors text-danger"></small>
                                    @if(auth()->user()->can('document add'))
                                        <a href="{{ route('document.create') }}">
                                            <i class="fa fa-plus-circle mt-2"></i>
                                            {{ trans('messages.add_form_title', ['form' => trans('messages.document')]) }}
                                        </a>
                                    @endif
                                </div>
                                @if(auth()->user()->hasAnyRole(['admin','demo_admin']))
                                    <div class="form-group col-md-4 mb-3">
                                        <label for="file" class="form-label">{{__('messages.is_verify')}} <span class="text-danger">*</span></label>
                                        <select name="is_verified" id="is_verified" class="select2js form-group" required>
                                            <option value="1" {{ old('is_verified', $shopDocument->is_verified ?? 0) == 1 ? 'selected' : '' }}>
                                                {{ __('messages.verified') }}
                                            </option>
                                            <option value="0" {{ old('is_verified', $shopDocument->is_verified ?? 0) == 0 ? 'selected' : '' }}>
                                                {{ __('messages.not_verified') }}
                                            </option>
                                        </select>
                                        <small class="help-block with-errors text-danger"></small>
                                    </div>
                                @endif
                                <div class="form-group col-md-4 mb-3">
                                    <label for="shop_document" class="form-label">{{ __('messages.upload_document') }} <span id="requiredStar" class="text-danger" style="display:none;">*</span></label>
                                    <input type="file" id="shop_document" name="shop_document" class="form-control">
                                    <small class="form-text text-muted"></small>
                                    @if($shopDocument && $media = $shopDocument->getFirstMedia('shop_document'))
                                        <div class="border rounded p-2 mt-3 position-relative">
                                            <button type="button" onclick="removeShopDocument({{ $shopDocument->id }})" style="position: absolute;top: 15px;right: 15px;background: rgba(255, 0, 0, 0.7);color: white;border: none;border-radius: 50%;width: 25px;height: 25px;display: flex;align-items: center;justify-content: center;cursor: pointer;transition: all 0.3s ease;z-index: 10;" class="remove-btn">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            @if(in_array($media->mime_type, ['image/jpeg','image/png','image/jpg']))
                                                <img src="{{ getSingleMedia($shopDocument,'shop_document') }}" alt="Document Preview" class="img-fluid rounded">
                                                <div class="text-center mt-2">
                                                    <a href="{{ getSingleMedia($shopDocument,'shop_document') }}" target="_blank" download class="btn btn-sm btn-primary">
                                                        <i class="fas fa-download"></i><span class="ms-2">Download Document</span>
                                                    </a>
                                                </div>
                                            @else
                                                <div class="text-center" style="margin: 50px 0;">
                                                    <p>{{ $media->file_name }}</p>
                                                    <a href="{{ getSingleMedia($shopDocument,'shop_document') }}" target="_blank" download class="btn btn-sm btn-primary">
                                                        <i class="fas fa-download"></i><span class="ms-2">{{ __('messages.download') }} {{ __('messages.document') }}</span>
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <button type="submit" id="submit-btn" class="btn btn-md btn-primary float-end">{{ __('messages.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @section('bottom_script')
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const documentSelect = $('#document_id');
                const requiredStar   = $('#requiredStar');
                const fileInput      = $('#shop_document');
                const hasOldFile     = {{ $shopDocument && $shopDocument->getFirstMedia('shop_document') ? 'true' : 'false' }};
                const isEditMode     = {{ $shopDocument && $shopDocument->id ? 'true' : 'false' }};
                const oldDocumentId  = {{ $shopDocument ? ($shopDocument->document_id ?? 'null') : 'null' }};
                const initialShopId  = $('#shop_id').data('initial-shop');

                function loadDocuments() {
                    const shopId = $('#shop_id').val();
                    const currentDocumentId = oldDocumentId;

                    $.ajax({
                        url: '{{ route("shopdocument.documentlist") }}',
                        type: 'GET',
                        data: {
                            shop_id: shopId,
                            document_id: currentDocumentId
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status && response.data) {
                                documentSelect.empty();
                                documentSelect.append('<option value="" selected disabled>{{ __("messages.select_document") }}</option>');

                                if (response.data.length > 0) {
                                    response.data.forEach(function(document) {
                                        const option = $('<option></option>')
                                            .val(document.id)
                                            .text(document.name)
                                            .data('required', document.is_required);

                                        documentSelect.append(option);
                                    });

                                    if (isEditMode && oldDocumentId) {
                                        documentSelect.val(oldDocumentId).trigger('change');
                                    }
                                }

                                if (documentSelect.hasClass('select2-hidden-accessible')) {
                                    documentSelect.select2('destroy');
                                }
                                documentSelect.select2();

                                toggleRequiredStar();
                            } else {
                                documentSelect.html('<option value="" disabled>{{ __("messages.error_loading_documents") }}</option>');
                            }
                        },
                        error: function(xhr, status, error) {
                            documentSelect.html('<option value="" disabled>{{ __("messages.error_loading_documents") }}</option>');
                        }
                    });
                }

                function toggleRequiredStar() {
                    let selectedOption = documentSelect.find(':selected');
                    let isRequired = selectedOption.data('required');

                    if (isRequired == 1) {
                        requiredStar.show();
                        if (hasOldFile) {
                           fileInput.removeAttr('required');
                        }else{
                            fileInput.attr('required', true);
                        }
                    } else {
                        requiredStar.hide();
                        fileInput.removeAttr('required');
                    }
                }

                if ($('#shop_id').val()) {
                    loadDocuments();
                }

                documentSelect.on('change', toggleRequiredStar);

                $('#shop_id').on('change', function() {
                    const newShopId = $(this).val();
                    documentSelect.val('').trigger('change');
                    loadDocuments();
                });
            });

            function removeShopDocument(shopDocumentId) {
                const removeBtn = event.target.closest('.remove-btn');
                const originalContent = removeBtn.innerHTML;
                removeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                removeBtn.disabled = true;

                $.ajax({
                    url: '{{ route("remove.file") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: shopDocumentId,
                        type: 'shop_document'
                    },
                    success: function(response) {
                        if (response.status) {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: '{{ __("messages.success") }}',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                location.reload();
                            }
                        } else {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    title: '{{ __("messages.error") }}',
                                    text: response.message || '{{ __("messages.something_wrong") }}',
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                            removeBtn.innerHTML = originalContent;
                            removeBtn.disabled = false;
                        }
                    },
                    error: function(xhr, status, error) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                title: '{{ __("messages.error") }}',
                                text: '{{ __("messages.something_wrong") }}',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                        removeBtn.innerHTML = originalContent;
                        removeBtn.disabled = false;
                    }
                });
            }
        </script>
    @endsection
</x-master-layout>

