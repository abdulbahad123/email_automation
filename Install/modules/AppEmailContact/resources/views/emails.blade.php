@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ $group->name }}"
        description="{{ __('Manage and import email addresses in this contact group.') }}"
        :count="$stats['total']"
    >
        <a href="{{ route('app.emailcontact.popup_import_contact', ['id_secure' => $group->id_secure]) }}" class="btn btn-primary btn-sm actionItem" data-popup="ImportEmailContactModal">
            <span><i class="fa-light fa-file-import"></i></span>
            <span>{{ __('Import Emails') }}</span>
        </a>
        <a href="{{ route('app.emailcontact.index') }}" class="btn btn-dark btn-sm">
            <span><i class="fa-light fa-arrow-left"></i></span>
            <span>{{ __('Back') }}</span>
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
                        <i class="fa-light fa-envelope"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Total Contacts') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-gray-200">
                <div class="card-body d-flex align-items-center justify-content-between gap-16">
                    <div class="size-45 fs-20 text-success d-flex align-items-center justify-content-center bg-success-100 b-r-10">
                        <i class="fa-light fa-circle-check"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Subscribed') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['subscribed']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-gray-200">
                <div class="card-body d-flex align-items-center justify-content-between gap-16">
                    <div class="size-45 fs-20 text-danger d-flex align-items-center justify-content-center bg-danger-100 b-r-10">
                        <i class="fa-light fa-ban"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Unsubscribed') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['unsubscribed']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@component('components.datatable', ["Datatable" => $Datatable, "customTable" => true])
    <table id="{{ $Datatable['element'] }}" data-url="{{ route('app.emailcontact.emails_list', ['id_secure' => $group->id_secure]) }}" class="display table table-bordered table-hide-footer w-100">
        <thead>
            <tr>
                <th class="align-middle w-10px pe-2">
                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                        <input class="form-check-input checkbox-all" type="checkbox" data-checkbox-parent=".table-responsive"/>
                    </div>
                </th>
                <th class="align-middle min-w-240">{{ __('Email') }}</th>
                <th class="align-middle min-w-160">{{ __('Name') }}</th>
                <th class="align-middle min-w-220">{{ __('Variables') }}</th>
                <th class="align-middle min-w-120">{{ __('Subscription') }}</th>
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
                targets: 'email:name',
                orderable: true,
                render: function (data) {
                    return `
                        <div class="d-flex gap-8 align-items-center">
                            <div class="size-36 d-flex align-items-center justify-content-center bg-primary-100 text-primary b-r-8 flex-shrink-0">
                                <i class="fa-light fa-envelope"></i>
                            </div>
                            <div class="fw-6 text-gray-900">${data ?? ''}</div>
                        </div>`;
                }
            },
            {
                targets: 'name:name',
                orderable: true,
                render: function (data) {
                    return `<span class="text-gray-800">${data || '-'}</span>`;
                }
            },
            {
                targets: 'params:name',
                orderable: false,
                render: function (data) {
                    if (!data) return `<span class="text-gray-500">-</span>`;
                    try {
                        var parsed = JSON.parse(data);
                        var html = '';
                        Object.keys(parsed).forEach(function (key) {
                            html += `<span class="badge badge-light-secondary text-gray-700 px-2 py-1 me-1 mb-1">${key}: ${parsed[key]}</span>`;
                        });
                        return html || `<span class="text-gray-500">-</span>`;
                    } catch (e) {
                        return `<span class="text-gray-700">${data}</span>`;
                    }
                }
            },
            {
                targets: 'unsubscribed:name',
                orderable: true,
                render: function (data) {
                    return data == 1
                        ? `<span class="badge badge-outline badge-sm badge-light-danger text-danger">{{ __('Unsubscribed') }}</span>`
                        : `<span class="badge badge-outline badge-sm badge-light-success text-success">{{ __('Subscribed') }}</span>`;
                }
            },
            {
                targets: -1,
                data: null,
                orderable: false,
                className: 'text-end',
                render: function (data, type, row) {
                    return `
                        <a class="btn btn-light btn-sm text-danger actionItem" data-id="${row.id_secure}" href="{{ route('app.emailcontact.delete_email') }}" data-confirm="{{ __('Delete this contact?') }}" data-call-success="Main.DataTable_Reload('#{{ $Datatable['element'] }}')">
                            <i class="fa-light fa-trash-can"></i>
                        </a>`;
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

