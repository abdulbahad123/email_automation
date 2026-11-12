@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('WhatsApp Bulk Campaigns') }}"
        description="{{ __('Schedule bulk WhatsApp sends to contact groups with multiple profiles, media attachments, and controlled delivery intervals.') }}"
        :count="$stats['total']"
    >
        <a href="{{ route('app.whatsappbulk.create') }}" class="btn btn-primary btn-sm">
            <i class="fa-light fa-plus me-1"></i>{{ __('New campaign') }}
        </a>
    </x-sub-header>
@endsection

@section('content')
<div class="container">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-gray-200">
                <div class="card-body d-flex align-items-center justify-content-between gap-16">
                    <div class="size-45 fs-20 text-primary d-flex align-items-center justify-content-center bg-primary-100 b-r-10">
                        <i class="fa-light fa-envelopes-bulk"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Total') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-gray-200">
                <div class="card-body d-flex align-items-center justify-content-between gap-16">
                    <div class="size-45 fs-20 text-success d-flex align-items-center justify-content-center bg-success-100 b-r-10">
                        <i class="fa-light fa-play"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Running') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['running']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-gray-200">
                <div class="card-body d-flex align-items-center justify-content-between gap-16">
                    <div class="size-45 fs-20 text-warning d-flex align-items-center justify-content-center bg-warning-100 b-r-10">
                        <i class="fa-light fa-pause"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Paused') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['paused']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-gray-200">
                <div class="card-body d-flex align-items-center justify-content-between gap-16">
                    <div class="size-45 fs-20 text-info d-flex align-items-center justify-content-center bg-info-100 b-r-10">
                        <i class="fa-light fa-circle-check"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Completed') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['completed']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@component('components.datatable', ["Datatable" => $Datatable, "customTable" => true])
    <table id="{{ $Datatable['element'] }}" data-url="{{ route('app.whatsappbulk.list') }}" class="display table table-bordered table-hide-footer w-100">
        <thead>
            <tr>
                <th class="align-middle w-10px pe-2">
                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                        <input class="form-check-input checkbox-all" type="checkbox" data-checkbox-parent=".table-responsive"/>
                    </div>
                </th>
                <th class="align-middle min-w-260">{{ __('Campaign') }}</th>
                <th class="align-middle min-w-160">{{ __('Contact group') }}</th>
                <th class="align-middle min-w-120">{{ __('Profiles') }}</th>
                <th class="align-middle min-w-220">{{ __('Delivery') }}</th>
                <th class="align-middle min-w-180">{{ __('Next action') }}</th>
                <th class="align-middle min-w-120">{{ __('Status') }}</th>
                <th class="align-middle text-end min-w-120">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="fs-14"></tbody>
    </table>
@endcomponent
@endsection

@section('script')
    @component('components.datatable_script', ["Datatable" => $Datatable, "column_actions" => false, "column_status" => false]) @endcomponent
    <script type="text/javascript">
        columnDefs = columnDefs.concat([
            {
                targets: 'id_secure:name',
                orderable: false,
                render: function (data) {
                    return `
                        <div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input checkbox-item" name="id[]" type="checkbox" value="${data}" />
                        </div>`.replaceAll('__ID__', row.id_secure);
                }
            },
            {
                targets: 'name:name',
                orderable: true,
                render: function (data, type, row) {
                    return `
                        <div class="min-w-0">
                            <div class="fw-6 text-truncate max-w-320"><a class="text-gray-900 text-hover-primary" href="{{ route('app.whatsappbulk.edit', ['id_secure' => '__ID__']) }}" data-dynamic-link="1">${row.name || ''}</a></div>
                            <div class="fs-12 text-gray-500 text-truncate max-w-320 mb-1">${row.caption || '{{ __('Media campaign') }}'}</div>
                            <div class="d-flex flex-wrap gap-12 fs-12 text-gray-600">
                                <span><i class="fa-light fa-address-book me-1"></i>${row.contact_name || '{{ __('Unknown group') }}'}</span>
                                <span><i class="fa-light fa-users me-1"></i>${row.total_phone_number || 0} {{ __('contacts') }}</span>
                                <span><i class="fa-light fa-user-group me-1"></i>${row.accounts_count || 0} {{ __('profiles') }}</span>
                            </div>
                        </div>`.replaceAll('__ID__', row.id_secure);
                }
            },
            {
                targets: 'contact_name:name',
                orderable: true,
                render: function (data, type, row) {
                    return `
                        <div class="fw-5 text-gray-900">${data || '{{ __('Unknown group') }}'}</div>
                        <div class="fs-12 text-gray-500">${row.total_phone_number || 0} {{ __('contacts') }}</div>`;
                }
            },
            {
                targets: 'accounts_count:name',
                orderable: true,
                render: function (data) {
                    return `
                        <div class="fw-6 text-gray-900">${data || 0}</div>
                        <div class="fs-12 text-gray-500">{{ __('selected profiles') }}</div>`;
                }
            },
            {
                targets: 'sent:name',
                orderable: false,
                render: function (data, type, row) {
                    return `
                        <div class="d-flex flex-wrap gap-8 fs-12 mb-1">
                            <span class="badge badge-outline badge-success">{{ __('Sent:') }} ${row.sent || 0}</span>
                            <span class="badge badge-outline badge-primary">{{ __('Pending:') }} ${row.pending || 0}</span>
                            <span class="badge badge-outline badge-danger">{{ __('Failed:') }} ${row.failed || 0}</span>
                        </div>
                        <div class="fw-5 text-gray-900">${row.time_post || ''}</div>
                        <div class="fs-12 text-gray-500">{{ __('Min') }} ${row.min_delay || 0} s / {{ __('Max') }} ${row.max_delay || 0} s</div>`;
                }
            },
            {
                targets: 'time_post:name',
                orderable: true,
                render: function (data, type, row) {
                    return `
                        <div class="fw-5 text-gray-900">${data || ''}</div>
                        <div class="fs-12 text-gray-500">{{ __('Min') }} ${row.min_delay || 0} s / {{ __('Max') }} ${row.max_delay || 0} s</div>`;
                }
            },
            {
                targets: 'status:name',
                orderable: true,
                render: function (data) {
                    var statusClass = data == 1 ? 'badge-light-success text-success' : (data == 2 ? 'badge-light-info text-info' : 'badge-light-warning text-warning');
                    var statusText = data == 1 ? '{{ __('Running') }}' : (data == 2 ? '{{ __('Completed') }}' : '{{ __('Paused') }}');
                    return `<span class="badge badge-outline badge-sm ${statusClass}">${statusText}</span>`;
                }
            },
            {
                targets: -1,
                data: null,
                orderable: false,
                className: 'text-end',
                render: function (data, type, row) {
                    return `
                        <div class="dropdown">
                            <button class="btn btn-light btn-active-light-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ __('Actions') }}
                            </button>
                            <ul class="dropdown-menu border-1 border-gray-300 w-auto max-w-180 min-w-150">
                                <li class="mx-2">
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6" href="{{ route('app.whatsappbulk.edit', ['id_secure' => '__ID__']) }}" data-dynamic-link="1">
                                        <span class="size-16 me-1 text-center"><i class="fa-light fa-pen-to-square"></i></span>
                                        <span>{{ __('Edit') }}</span>
                                    </a>
                                </li>
                                <li class="mx-2">
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" href="{{ route('app.whatsappbulk.status', ['id_secure' => '__ID__']) }}" data-dynamic-link="1" data-call-success="Main.DataTable_Reload('#{{ $Datatable['element'] }}')">
                                        <span class="size-16 me-1 text-center"><i class="fa-light ${row.status == 1 ? 'fa-pause' : 'fa-play'}"></i></span>
                                        <span>${row.status == 1 ? '{{ __('Pause') }}' : '{{ __('Run') }}'}</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li class="mx-2">
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" data-id="${row.id_secure}" href="{{ route('app.whatsappbulk.delete') }}" data-confirm="{{ __('Are you sure you want to delete this item?') }}" data-call-success="Main.DataTable_Reload('#{{ $Datatable['element'] }}')">
                                        <span class="size-16 me-1 text-center"><i class="fa-light fa-trash-can-list"></i></span>
                                        <span>{{ __('Delete') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>`.replaceAll('__ID__', row.id_secure);
                },
            },
        ]);

        var dtConfig = {
            columns: {!! json_encode($Datatable['columns'] ?? []) !!},
            lengthMenu: {!! json_encode($Datatable['lengthMenu'] ?? []) !!},
            order: {!! json_encode($Datatable['order'] ?? []) !!},
            columnDefs: {!! json_encode($Datatable['columnDefs'] ?? []) !!}
        };

        dtConfig.columnDefs = dtConfig.columnDefs.concat(columnDefs);
        var DataTable = Main.DataTable('#{{ $Datatable['element'] }}', dtConfig);
        DataTable.columns([2, 3, 5, 6, 9, 10, 11, 12]).visible(false);
    </script>
@endsection
