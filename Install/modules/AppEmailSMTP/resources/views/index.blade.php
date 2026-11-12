@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('SMTP Accounts') }}"
        description="{{ __('Connect your own SMTP servers (Gmail, Amazon SES, Mailgun, SendGrid, cPanel, or custom SMTP) to power bulk email campaigns and dashboard sending.') }}"
        :count="$stats['total']"
    >
        <a class="btn btn-primary btn-sm actionItem" href="{{ route('app.emailsmtp.popup_update') }}" data-popup="EmailSmtpModal">
            <span><i class="fa-light fa-plus"></i></span>
            <span>{{ __('Add SMTP Account') }}</span>
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
                        <i class="fa-light fa-server"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Total SMTPs') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['total']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-gray-200">
                <div class="card-body d-flex align-items-center justify-content-between gap-16">
                    <div class="size-45 fs-20 text-success d-flex align-items-center justify-content-center bg-success-100 b-r-10">
                        <i class="fa-light fa-circle-check"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Active') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['active']) }}</div>
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
                        <div class="fs-12 text-gray-600">{{ __('Disabled') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['disabled']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-gray-200">
                <div class="card-body d-flex align-items-center justify-content-between gap-16">
                    <div class="size-45 fs-20 text-info d-flex align-items-center justify-content-center bg-info-100 b-r-10">
                        <i class="fa-light fa-paper-plane"></i>
                    </div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Sent Today') }}</div>
                        <div class="fw-7 fs-16">{{ number_format($stats['sent_today']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@component('components.datatable', ["Datatable" => $Datatable, "customTable" => true])
    <table id="{{ $Datatable['element'] }}" data-url="{{ route('app.emailsmtp.list') }}" class="display table table-bordered table-hide-footer w-100">
        <thead>
            <tr>
                <th class="align-middle w-10px pe-2">
                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                        <input class="form-check-input checkbox-all" type="checkbox" data-checkbox-parent=".table-responsive"/>
                    </div>
                </th>
                <th class="align-middle min-w-240">{{ __('SMTP Account') }}</th>
                <th class="align-middle min-w-220">{{ __('Server & Auth') }}</th>
                <th class="align-middle">{{ __('Port') }}</th>
                <th class="align-middle">{{ __('Encryption') }}</th>
                <th class="align-middle">{{ __('From Email') }}</th>
                <th class="align-middle min-w-160">{{ __('Daily Quota') }}</th>
                <th class="align-middle">{{ __('Sent Today') }}</th>
                <th class="align-middle min-w-120">{{ __('Status') }}</th>
                <th class="align-middle">{{ __('Last Error') }}</th>
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
                    var errHtml = row.last_error
                        ? `<div class="fs-11 text-danger text-truncate max-w-280 mt-1"><i class="fa-light fa-triangle-exclamation me-1"></i>${row.last_error}</div>`
                        : '';
                    return `
                        <div class="d-flex gap-10 align-items-center">
                            <div class="size-40 d-flex align-items-center justify-content-center bg-info-100 text-info b-r-10 flex-shrink-0">
                                <i class="fa-light fa-server"></i>
                            </div>
                            <div class="min-w-0">
                                <a class="fw-6 text-gray-900 text-hover-primary actionItem" href="{{ route('app.emailsmtp.popup_update') }}/${row.id_secure}" data-popup="EmailSmtpModal">${row.name || ''}</a>
                                <div class="fs-12 text-gray-500 text-truncate">${row.from_email || ''}</div>
                                ${errHtml}
                            </div>
                        </div>`;
                }
            },
            {
                targets: 'host:name',
                orderable: true,
                render: function (data, type, row) {
                    var enc = (row.encryption || 'tls').toUpperCase();
                    return `
                        <div class="fw-5 text-gray-900">${row.host || ''}:${row.port || 587}</div>
                        <div class="fs-12 text-gray-500">{{ __('Encryption:') }} ${enc}</div>`;
                }
            },
            {
                targets: 'daily_limit:name',
                orderable: true,
                render: function (data, type, row) {
                    var limitText = (parseInt(row.daily_limit || 0, 10) > 0) ? row.daily_limit : '{{ __('Unlimited') }}';
                    return `
                        <div class="fw-6 text-gray-900">${row.sent_today || 0} / ${limitText}</div>
                        <div class="fs-12 text-gray-500">{{ __('Sent today') }}</div>`;
                }
            },
            {
                targets: 'status:name',
                orderable: true,
                render: function (data) {
                    var cls = data == 1 ? 'badge-light-success text-success' : 'badge-light-warning text-warning';
                    var txt = data == 1 ? '{{ __('Active') }}' : '{{ __('Disabled') }}';
                    return `<span class="badge badge-outline badge-sm ${cls}">${txt}</span>`;
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
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" href="{{ url('app/email/smtp/popup-test') }}/${row.id_secure}" data-popup="EmailSmtpTestModal">
                                        <span class="size-16 me-1 text-center text-info"><i class="fa-light fa-paper-plane"></i></span>
                                        <span>{{ __('Send Test Email') }}</span>
                                    </a>
                                </li>
                                <li class="mx-2">
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" href="{{ route('app.emailsmtp.popup_update') }}/${row.id_secure}" data-popup="EmailSmtpModal">
                                        <span class="size-16 me-1 text-center"><i class="fa-light fa-pen-to-square"></i></span>
                                        <span>{{ __('Edit') }}</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li class="mx-2">
                                    <a class="dropdown-item d-flex gap-8 fw-5 fs-14 b-r-6 actionItem" data-id="${row.id_secure}" href="{{ route('app.emailsmtp.delete') }}" data-confirm="{{ __('Are you sure you want to delete this SMTP account?') }}" data-call-success="Main.DataTable_Reload('#{{ $Datatable['element'] }}')">
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
        DataTable.columns([3, 4, 5, 7, 9]).visible(false);
    </script>
@endsection
