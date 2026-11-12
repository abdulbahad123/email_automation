@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('Contact groups') }}"
        description="{{ __('Create and manage WhatsApp contact groups, keep phone numbers organized, and import large lists from CSV when needed.') }}"
        :count="$stats['groups']"
    >
        <a class="btn btn-primary btn-sm actionItem" href="{{ route('app.whatsappcontact.popup_update') }}" data-popup="ContactGroupModal">
            <span><i class="fa-light fa-address-book"></i></span>
            <span>{{ __('Add group') }}</span>
        </a>
    </x-sub-header>
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="card border-gray-200 mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-16">
                        <div class="size-45 fs-20 text-primary d-flex align-items-center justify-content-center bg-primary-100 b-r-10">
                            <i class="fa-light fa-address-book"></i>
                        </div>
                        <div class="text-end">
                            <div class="fs-12 text-gray-600">{{ __('Enabled') }}</div>
                            <div class="fw-7 fs-16">{{ number_format($stats['enabled_groups']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-gray-200 mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-16">
                        <div class="size-45 fs-20 text-warning d-flex align-items-center justify-content-center bg-warning-100 b-r-10">
                            <i class="fa-light fa-address-book"></i>
                        </div>
                        <div class="text-end">
                            <div class="fs-12 text-gray-600">{{ __('Disabled') }}</div>
                            <div class="fw-7 fs-16">{{ number_format($stats['disabled_groups']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-gray-200 mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-16">
                        <div class="size-45 fs-20 text-success d-flex align-items-center justify-content-center bg-success-100 b-r-10">
                            <i class="fa-light fa-phone"></i>
                        </div>
                        <div class="text-end">
                            <div class="fs-12 text-gray-600">{{ __('Numbers') }}</div>
                            <div class="fw-7 fs-16">{{ number_format($stats['numbers']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@component('components.datatable', ["Datatable" => $Datatable, "customTable" => true])
    <table id="{{ $Datatable['element'] }}" data-url="{{ route('app.whatsappcontact.list') }}" class="display table table-bordered table-hide-footer w-100">
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
                    var initials = (row.name || 'CG')
                        .split(' ')
                        .filter(Boolean)
                        .slice(0, 2)
                        .map(function(part){ return part.charAt(0).toUpperCase(); })
                        .join('') || 'CG';

                    return `
                        <div class="d-flex gap-10 align-items-center">
                            <a class="size-42 border b-r-10 text-gray-800 d-flex align-items-center justify-content-center bg-primary-100 fw-6 fs-14 actionItem" href="{{ route('app.whatsappcontact.popup_update') }}/${row.id_secure}" data-popup="ContactGroupModal">
                                ${initials}
                            </a>
                            <div class="text-start lh-1 text-truncate">
                                <div class="fw-5 text-gray-900 text-truncate">
                                    <div class="text-truncate">
                                        <a class="text-gray-800 text-hover-primary actionItem" href="{{ route('app.whatsappcontact.popup_update') }}/${row.id_secure}" data-popup="ContactGroupModal">
                                            ${row.name}
                                        </a>
                                    </div>
                                    <div class="text-truncate text-gray-500 fs-12">
                                        {{ __('WhatsApp contact group') }}
                                    </div>
                                </div>
                            </div>
                        </div>`;
                }
            },
            {
                targets: 'contacts_count:name',
                orderable: true,
                render: function (data, type, row) {
                    return `
                        <div class="fw-6 text-gray-900">${data ?? 0}</div>
                        <div class="text-gray-500 fs-12">{{ __('saved numbers') }}</div>`;
                }
            },
            {
                targets: 'status:name',
                orderable: true,
                className: 'min-w-120',
                render: function (data, type, row) {
                    var statusClass = data == 1 ? 'badge-light-success text-success' : 'badge-light-warning text-warning';
                    var statusText = data == 1 ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}';
                    var actionEnable = '{{ route('app.whatsappcontact.status', ['status' => 'enable']) }}';
                    var actionDisable = '{{ route('app.whatsappcontact.status', ['status' => 'disable']) }}';

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
                    return `<span class="text-gray-700">${data ?? ''}</span>`;
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
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" href="{{ route('app.whatsappcontact.popup_update') }}/${row.id_secure}" data-popup="ContactGroupModal">
                                        <span class="size-16 me-1 text-center"><i class="fa-light fa-pen-to-square"></i></span>
                                        <span>{{ __('Edit') }}</span>
                                    </a>
                                </li>
                                <li class="mx-2">
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6" href="{{ route('app.whatsappcontact.phone_numbers', ['id_secure' => '__ID__']) }}" data-dynamic-link="1">
                                        <span class="size-16 me-1 text-center"><i class="fa-light fa-list"></i></span>
                                        <span>{{ __('Phone numbers') }}</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li class="mx-2">
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" data-id="${row.id_secure}" href="{{ route('app.whatsappcontact.delete') }}" data-confirm="{{ __('Are you sure you want to delete this item?') }}" data-call-success="Main.DataTable_Reload('#{{ $Datatable['element'] }}')">
                                        <span class="size-16 me-1 text-center"><i class="fa-light fa-trash-can-list"></i></span>
                                        <span>{{ __('Delete') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    `.replace('__ID__', row.id_secure);
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
        DataTable.columns([]).visible(false);
    </script>
@endsection

