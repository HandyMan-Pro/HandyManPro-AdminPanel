<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                @can('mail-templates.create')
                                <a href="{{ route('mail-templates.create') }}" class="btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> {{ __('messages.add_form_title', ['form' => __('messages.mail_templates')]) }}</a>
                                @endcan
                                @can('mail-templates.restore')
                                <a href="{{ route('mail-templates.trashed') }}" class="btn btn-sm btn-secondary"><i class="fas fa-eye-slash"></i> {{ __('messages.view_trash') }}</a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mt-4">
                            <div class="col">
                                <div class="table-responsive">
                                    <table id="datatable" class="table table-striped border">
                                        <thead>
                                            <tr>
                                                <th>{{ __('messages.id') }}</th>
                                                <th>{{ __('messages.name') }}</th>
                                                <th>{{ __('messages.updated_at') }}</th>
                                                <th class="text-end">{{ __('messages.action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($data as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>
                                                    <a href="{{ route('mail-templates.show', $item->id) }}">{{ $item->label ?? $item->name }}</a>
                                                </td>
                                                <td>{{ $item->updated_at->diffForHumans() }}</td>
                                                <td class="text-end">
                                                    <a href="{{ route('mail-templates.edit', $item->id) }}" class="btn btn-sm btn-primary mt-1" data-bs-toggle="tooltip" title="{{ __('messages.edit') }}"><i class="fas fa-edit"></i></a>
                                                    <a href="{{ route('mail-templates.show', $item->id) }}" class="btn btn-sm btn-success mt-1" data-bs-toggle="tooltip" title="{{ __('messages.show') }}"><i class="fas fa-eye"></i></a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
