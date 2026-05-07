<x-master-layout>
    <style>
        /* Provider document detail page only */
        .document-preview-img {
            width: 300px;
            height: 200px;
            max-height: 280px;
            object-fit: contain;
            display: block;
        }
        .document-detail-no-border.card,
        .document-detail-no-border .card {
            border: none !important;
            box-shadow: none !important;
        }
        .document-detail-no-border .statistics-card,
        .document-detail-no-border .statistics-card__order-overview {
            border: none !important;
        }
        .document-detail-no-border table.table tr,
        .document-detail-no-border table.table td,
        .document-detail-no-border table.table th {
            border: none !important;
        }
        .document-detail-no-border .statistics-card {
            padding-left: 0 !important;
        }
        
        /* Table alignment */
        .document-detail-no-border .table {
            table-layout: fixed;
            width: 100%;
        }
        .document-detail-no-border .table td {
            vertical-align: middle;
            padding: 0.5rem 0.75rem 0.5rem 0;
        }
        .document-detail-no-border .table td:first-child {
            width: 12rem;
            max-width: 35%;
            color: var(--bs-body-color);
            text-align: left;
            font-weight: bold;
        }
        .document-detail-no-border .table td:last-child {
            word-break: break-word;
            color: var(--bs-body-color);
        }
        /* Attachment section: align with table */
        .document-detail-no-border .attachment-section {
            text-align: left;
        }
        .document-detail-no-border .attachment-section .document-preview-img {
            margin-left: 0;
        }
    </style>
    <main class="main-area">
        <div class="main-content">
            <div class="container-fluid">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ __('messages.provider_document_detail') }}</h5>
                            <a href="{{ route('providerdocument.show', ['providerdocument' => $providerDocument->provider_id]) }}" class="float-end btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                        </div>
                    </div>
                </div>
                <div class="card document-detail-no-border">
                    <div class="card-body p-30">
                        <div class="statistics-card statistics-card__style2 statistics-card__order-overview">
                            <table class="table table-border-none mb-0">
                                <tbody>
                                    <tr>
                                        <td>{{ __('messages.id') }} :</td>
                                        <td>#{{ !empty($providerDocument->id) ? $providerDocument->id : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('messages.datetime') }} :</td>
                                        <td>{{ !empty($providerDocument->updated_at) ? date("$datetime->date_format $datetime->time_format", strtotime($providerDocument->updated_at)) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('messages.provider') }} :</td>
                                        <td>{{ optional($providerDocument->providers)->display_name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('messages.document') }} :</td>
                                        <td>{{ optional($providerDocument->document)->name ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ __('messages.is_verified') }} :</td>
                                        <td>
                                            @if($providerDocument->is_verified == 1)
                                                <span class="badge text-white bg-success text-uppercase">{{ __('messages.verified') }}</span>
                                            @else
                                                <span class="badge text-white bg-danger text-uppercase">{{ __('messages.not_verified') }}</span>
                                            @endif
                                        </td>
                                    </tr>                                  
                                </tbody>
                            </table>
                        </div>

                        <div class="attachment-section pt-4 mt-3 border-top">
                            <h5 class="mb-3">{{ __('messages.document_file') }}</h5>
                            @if(getMediaFileExit($providerDocument, 'provider_document'))
                                @php
                                    $file_extention = config('constant.IMAGE_EXTENTIONS');
                                    $image = getSingleMedia($providerDocument, 'provider_document');
                                    $extention = in_array(strtolower(imageExtention($image)), $file_extention);
                                @endphp
                                @if($extention)
                                    <div style="display: inline-block;">
                                        <a href="{{ $image }}" data-fslightbox="gallery">
                                            <img src="{{ $image }}" alt="{{ __('messages.document') }}" class="img-fluid rounded document-preview-img">
                                        </a>
                                    </div>
                                @else
                                    <div class="text-center py-5 bg-light rounded">
                                        <i class="ri-file-text-line ri-4x text-muted"></i>
                                        <p class="text-muted mt-2 mb-0">{{ optional($providerDocument->document)->name ?? __('messages.document') }}</p>
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-5 bg-light rounded">
                                    <i class="ri-image-line ri-3x text-muted"></i>
                                    <p class="text-muted mt-3 mb-0">{{ __('messages.no_data_found') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>   
</x-master-layout>

