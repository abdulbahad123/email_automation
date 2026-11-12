@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ $contact->name }}"
        description="{{ __('Manage phone numbers in this contact group.') }}"
        :count="$stats['numbers']"
    >
        <a href="{{ route('app.whatsappcontact.popup_import_contact', ['id_secure' => $contact->id_secure]) }}" class="btn btn-primary btn-sm actionItem" data-popup="ImportContactModal">
            <span><i class="fa-light fa-file-import"></i></span>
            <span>{{ __('Import') }}</span>
        </a>
        <a href="{{ route('app.whatsappcontact.index') }}" class="btn btn-dark btn-sm">
            <span><i class="fa-light fa-arrow-left"></i></span>
            <span>{{ __('Back') }}</span>
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
                            <i class="fa-light fa-phone"></i>
                        </div>
                        <div class="text-end">
                            <div class="fs-12 text-gray-600">{{ __('Total numbers') }}</div>
                            <div class="fw-7 fs-16">{{ number_format($stats['numbers']) }}</div>
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
                            <i class="fa-light fa-layer-group"></i>
                        </div>
                        <div class="text-end">
                            <div class="fs-12 text-gray-600">{{ __('Current page') }}</div>
                            <div class="fw-7 fs-16">{{ number_format($stats['current_page']) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-gray-200 mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-16">
                        <div class="size-45 fs-20 text-info d-flex align-items-center justify-content-center bg-info-100 b-r-10">
                            <i class="fa-light fa-address-book"></i>
                        </div>
                        <div class="text-end min-w-0">
                            <div class="fs-12 text-gray-600">{{ __('Contact group') }}</div>
                            <div class="fw-7 fs-16 text-truncate">{{ $contact->name }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@component('components.datatable', ["Datatable" => $Datatable, "customTable" => true])
    <table id="{{ $Datatable['element'] }}" data-url="{{ route('app.whatsappcontact.phone_numbers_list', ['id_secure' => $contact->id_secure]) }}" class="display table table-bordered table-hide-footer w-100">
        <thead>
            <tr>
                <th class="align-middle w-10px pe-2">
                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                        <input class="form-check-input checkbox-all" type="checkbox" data-checkbox-parent=".table-responsive"/>
                    </div>
                </th>
                <th class="align-middle min-w-240">{{ __('Phone number') }}</th>
                <th class="align-middle min-w-260">{{ __('Params') }}</th>
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
                targets: 'phone:name',
                orderable: true,
                render: function (data, type, row) {
                    return `
                        <div class="d-flex gap-8 align-items-center">
                            <div class="size-36 d-flex align-items-center justify-content-center bg-success-100 text-success b-r-8 flex-shrink-0">
                                <i class="fa-light fa-phone"></i>
                            </div>
                            <div>
                                <div class="fw-6 text-gray-900">${data ?? ''}</div>
                                <div class="fs-12 text-gray-500">{{ __('WhatsApp target') }}</div>
                            </div>
                        </div>`;
                }
            },
            {
                targets: 'params:name',
                orderable: false,
                render: function (data) {
                    if (!data) {
                        return `<span class="text-gray-500">-</span>`;
                    }

                    try {
                        var parsed = JSON.parse(data);
                        var html = '';
                        Object.keys(parsed).forEach(function (key) {
                            html += `<span class="badge badge-light-secondary text-gray-700 px-3 py-2 me-1 mb-1">${key}: ${parsed[key]}</span>`;
                        });
                        return html || `<span class="text-gray-500">-</span>`;
                    } catch (e) {
                        return `<span class="text-gray-700">${data}</span>`;
                    }
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
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" data-id="${row.id_secure}" href="{{ route('app.whatsappcontact.delete_phone') }}" data-confirm="{{ __('Are you sure you want to delete this item?') }}" data-call-success="Main.DataTable_Reload('#{{ $Datatable['element'] }}')">
                                        <span class="size-16 me-1 text-center"><i class="fa-light fa-trash-can-list"></i></span>
                                        <span>{{ __('Delete') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>`;
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
