<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <a href="{{ route('mail-templates.index') }}" class="btn btn-sm btn-secondary" data-bs-toggle="tooltip" title="{{ __('messages.list') }}"><i class="fas fa-list"></i> {{ __('messages.list') }}</a>
                                @can('mail-templates.edit')
                                <a href="{{ route('mail-templates.edit', $data->id) }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('messages.edit') }}"><i class="fas fa-edit"></i> {{ __('messages.edit') }}</a>
                                @endcan
                                <a href="javascript:history.back()" class="btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered show-table">
                                        <tbody>
                                            <tr>
                                                <th>{{ __('messages.name') }}</th>
                                                <td>{{ $data->label ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('messages.type') }}</th>
                                                <td>{{ $data->type ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __('messages.status') }}</th>
                                                <td>
                                                    @if($data->status)
                                                        <span class="badge bg-success">{{ __('messages.active') }}</span>
                                                    @else
                                                        <span class="badge bg-danger">{{ __('messages.inactive') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col">
                                <small class="float-end text-muted">
                                    {{ __('messages.updated_at') }}: {{ $data->updated_at->diffForHumans() }},
                                    {{ __('messages.created_at') }}: {{ $data->created_at->isoFormat('LLLL') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
