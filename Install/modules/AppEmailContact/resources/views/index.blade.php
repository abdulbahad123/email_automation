@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('Email Contact Groups') }}"
        description="{{ __('Create email lists, import up to 10k+ contacts from CSV or paste directly, and use custom merge variables in campaigns.') }}"
        :count="$stats['groups']"
    >
        <a class="btn btn-primary btn-sm actionItem" href="{{ route('app.emailcontact.popup_update') }}" data-popup="EmailContactGroupModal">
            <span><i class="fa-light fa-plus"></i></span>
            <span>{{ __('Add group') }}</span>
        </a>
    </x-sub-header>
@endsection

@section('content')
<div class="container">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-gray-200">
                <div class="card-body d-flex align-items-center justify-content-between gap-16">
                    <div class="size-45 fs-20 text-primary d-flex align-items-center justify-content-center bg-primary-100 b-r-10">
                        <i class="fa-light fa-address-book"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Enabled Groups') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['enabled_groups']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-gray-200">
                <div class="card-body d-flex align-items-center justify-content-between gap-16">
                    <div class="size-45 fs-20 text-warning d-flex align-items-center justify-content-center bg-warning-100 b-r-10">
                        <i class="fa-light fa-eye-slash"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Disabled Groups') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['disabled_groups']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-gray-200">
                <div class="card-body d-flex align-items-center justify-content-between gap-16">
                    <div class="size-45 fs-20 text-success d-flex align-items-center justify-content-center bg-success-100 b-r-10">
                        <i class="fa-light fa-envelope"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Total Email Contacts') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['contacts']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@component('components.datatable', ["Datatable" => $Datatable, "customTable" => true])
    <table id="{{ $Datatable['element'] }}" data-url="{{ route('app.emailcontact.list') }}" class="display table table-bordered table-hide-footer w-100">
        <thead>
            <tr>
                <th class="align-middle w-10px pe-2">
                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                        <input class="form-check-input checkbox-all" type="checkbox" data-checkbox-parent=".table-responsive"/>
                    </div>
                </th>
                <th class="align-middle min-w-280">{{ __('Group Info') }}</th>
                <th class="align-middle min-w-120">{{ __('Contacts') }}</th>
                <th class="align-middle min-w-120">{{ __('Status') }}</th>
                <th class="align-middle min-w-140">{{ __('Updated') }}</th>
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
                        </div>`;
                }
            },
            {
                targets: 'name:name',
                orderable: true,
                render: function (data, type, row) {
                    var initials = (row.name || 'EG')
                        .split(' ')
                        .filter(Boolean)
                        .slice(0, 2)
                        .map(function(part){ return part.charAt(0).toUpperCase(); })
                        .join('') || 'EG';

                    return `
                        <div class="d-flex gap-10 align-items-center">
                            <a class="size-42 border b-r-10 text-gray-800 d-flex align-items-center justify-content-center bg-success-100 text-success fw-6 fs-14" href="{{ url('app/email/contact/emails') }}/${row.id_secure}">
                                ${initials}
                            </a>
                            <div class="text-start lh-1 text-truncate">
                                <div class="fw-6 text-gray-900 text-truncate">
                                    <a class="text-gray-800 text-hover-primary" href="{{ url('app/email/contact/emails') }}/${row.id_secure}">
                                        ${row.name}
                                    </a>
                                </div>
                                <div class="text-truncate text-gray-500 fs-12 mt-1">
                                    {{ __('Click to manage & import emails') }}
                                </div>
                            </div>
                        </div>`;
                }
            },
            {
                targets: 'contacts_count:name',
                orderable: true,
                render: function (data) {
                    return `
                        <div class="fw-6 text-gray-900">${data ?? 0}</div>
                        <div class="text-gray-500 fs-12">{{ __('saved emails') }}</div>`;
                }
            },
            {
                targets: 'status:name',
                orderable: true,
                className: 'min-w-120',
                render: function (data, type, row) {
                    var statusClass = data == 1 ? 'badge-light-success text-success' : 'badge-light-warning text-warning';
                    var statusText = data == 1 ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}';
                    var actionEnable = '{{ route('app.emailcontact.status', ['status' => 'enable']) }}';
                    var actionDisable = '{{ route('app.emailcontact.status', ['status' => 'disable']) }}';

                    return `
                        <div class="btn-group">
                            <span class="badge badge-outline badge-sm ${statusClass} dropdown-toggle dropdown-arrow-hide" data-bs-toggle="dropdown">${statusText}</span>
                            <ul class="dropdown-menu dropdown-menu-end border-1 border-gray-300 px-2 w-auto max-w-160 min-w-140">
                                <li>
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" data-id="${row.id_secure}" href="${actionEnable}" data-call-success="Main.DataTable_Reload('#{{ $Datatable['element'] }}')">
                                        <span class="size-16 me-1 text-center"><i class="fa-light fa-eye"></i></span>
                                        <span>{{ __('Enable') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" data-id="${row.id_secure}" href="${actionDisable}" data-call-success="Main.DataTable_Reload('#{{ $Datatable['element'] }}')">
                                        <span class="size-16 me-1 text-center"><i class="fa-light fa-eye-slash"></i></span>
                                        <span>{{ __('Disable') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>`;
                }
            },
            {
                targets: 'changed:name',
                orderable: true,
                render: function (data) {
                    if (!data) return '-';
                    var ts = parseInt(data, 10);
                    if (!isNaN(ts) && ts > 1000000000) {
                        return `<span class="text-gray-700">${new Date(ts * 1000).toLocaleString()}</span>`;
                    }
                    return `<span class="text-gray-700">${data}</span>`;
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
                            <ul class="dropdown-menu border-1 border-gray-300 w-auto max-w-200 min-w-160">
                                <li class="mx-2">
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6" href="{{ url('app/email/contact/emails') }}/${row.id_secure}">
                                        <span class="size-16 me-1 text-center text-primary"><i class="fa-light fa-list"></i></span>
                                        <span>{{ __('View & Import Emails') }}</span>
                                    </a>
                                </li>
                                <li class="mx-2">
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" href="{{ route('app.emailcontact.popup_update') }}/${row.id_secure}" data-popup="EmailContactGroupModal">
                                        <span class="size-16 me-1 text-center"><i class="fa-light fa-pen-to-square"></i></span>
                                        <span>{{ __('Rename Group') }}</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li class="mx-2">
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" data-id="${row.id_secure}" href="{{ route('app.emailcontact.delete') }}" data-confirm="{{ __('Are you sure you want to delete this contact group and all its emails?') }}" data-call-success="Main.DataTable_Reload('#{{ $Datatable['element'] }}')">
                                        <span class="size-16 me-1 text-center text-danger"><i class="fa-light fa-trash-can-list"></i></span>
                                        <span>{{ __('Delete') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>`;
                }
            }
        ]);

        var dtConfig = {
            columns: {!! json_encode($Datatable['columns'] ?? []) !!},
            lengthMenu: {!! json_encode($Datatable['lengthMenu'] ?? []) !!},
            order: {!! json_encode($Datatable['order'] ?? []) !!},
            columnDefs: {!! json_encode($Datatable['columnDefs'] ?? []) !!}
        };
        dtConfig.columnDefs = dtConfig.columnDefs.concat(columnDefs);
        var DataTable = Main.DataTable('#{{ $Datatable['element'] }}', dtConfig);
        DataTable.columns([]).visible(false);
    </script>
@endsection

