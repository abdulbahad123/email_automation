@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('Delivery Logs: ') . $campaign->name }}"
        description="{{ __('Track sent, queued, and failed recipients for this campaign.') }}"
    >
        <a href="{{ route('app.emailbulk.process_now', ['id_secure' => $campaign->id_secure]) }}" class="btn btn-light btn-sm actionItem" data-call-success="Main.DataTable_Reload('#{{ $Datatable['element'] }}')">
            <i class="fa-light fa-bolt me-1 text-warning"></i>{{ __('Run Batch Now') }}
        </a>
        <a href="{{ route('app.emailbulk.index') }}" class="btn btn-dark btn-sm">
            <i class="fa-light fa-arrow-left me-1"></i>{{ __('Back to Campaigns') }}
        </a>
    </x-sub-header>
@endsection

@section('content')
@component('components.datatable', ["Datatable" => $Datatable, "customTable" => true])
    <table id="{{ $Datatable['element'] }}" data-url="{{ route('app.emailbulk.logs_list', ['id_secure' => $campaign->id_secure]) }}" class="display table table-bordered table-hide-footer w-100">
        <thead>
            <tr>
                <th class="align-middle w-10px pe-2">#</th>
                <th class="align-middle min-w-220">{{ __('Recipient') }}</th>
                <th class="align-middle min-w-220">{{ __('Subject') }}</th>
                <th class="align-middle min-w-100">{{ __('Status') }}</th>
                <th class="align-middle min-w-220">{{ __('Details') }}</th>
                <th class="align-middle min-w-160">{{ __('Sent At') }}</th>
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
                render: function () { return `<i class="fa-light fa-envelope text-gray-500"></i>`; }
            },
            {
                targets: 'status:name',
                orderable: true,
                render: function (data) {
                    if (data == 1) return `<span class="badge badge-outline badge-sm badge-light-success text-success">{{ __('Sent') }}</span>`;
                    if (data == 2) return `<span class="badge badge-outline badge-sm badge-light-danger text-danger">{{ __('Failed') }}</span>`;
                    return `<span class="badge badge-outline badge-sm badge-light-primary text-primary">{{ __('Queued') }}</span>`;
                }
            },
            {
                targets: 'error_message:name',
                orderable: false,
                render: function (data) {
                    return data ? `<span class="text-danger fs-12">${data}</span>` : `<span class="text-success fs-12">{{ __('Delivered to SMTP') }}</span>`;
                }
            },
            {
                targets: 'sent_at:name',
                orderable: true,
                render: function (data) {
                    if (!data) return '-';
                    var ts = parseInt(data, 10);
                    return !isNaN(ts) ? new Date(ts * 1000).toLocaleString() : data;
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

