@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('WhatsApp Reports') }}"
        description="{{ __('Track WhatsApp automation activity across Auto Reply, Chatbot, AI Smart Reply, and Bulk campaigns with account-level filters.') }}"
        :count="$summary['records']"
    />
@endsection

@section('content')
<div class="container pb-5">
    <form method="GET" action="{{ route('app.whatsappreport.index') }}" class="card border-gray-300 shadow-none mb-4">
        <div class="card-body p-4 p-lg-5">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-xl-4">
                    <label class="form-label fw-6">{{ __('Date range') }}</label>
                    <div class="daterange d-none bg-white b-r-4 fs-12 border-gray-300 border no-submit" data-open="left"></div>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label fw-6">{{ __('WhatsApp account') }}</label>
                    <select class="form-select" name="account" data-control="select2" data-hide-search="0">
                        <option value="">{{ __('All accounts') }}</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id_secure }}" {{ $selectedAccount === $account->id_secure ? 'selected' : '' }}>{{ $account->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label fw-6">{{ __('Feature') }}</label>
                    <select class="form-select" name="feature" data-control="select2" data-hide-search="1">
                        @foreach($featureOptions as $value => $label)
                            <option value="{{ $value }}" {{ $selectedFeature === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-xl-2 d-flex gap-8">
                    <button type="submit" class="btn btn-primary flex-fill">{{ __('Apply') }}</button>
                    <a href="{{ route('app.whatsappreport.index') }}" class="btn btn-outline btn-dark">{{ __('Reset') }}</a>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-gray-300 shadow-none h-100">
                <div class="card-body d-flex align-items-center justify-content-between gap-16 p-4">
                    <div class="size-48 d-flex align-items-center justify-content-center rounded-3 bg-primary-100 text-primary fs-22"><i class="fa-light fa-chart-column"></i></div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Tracked records') }}</div>
                        <div class="fs-22 fw-7 text-gray-900">{{ number_format($summary['records']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-gray-300 shadow-none h-100">
                <div class="card-body d-flex align-items-center justify-content-between gap-16 p-4">
                    <div class="size-48 d-flex align-items-center justify-content-center rounded-3 bg-success-100 text-success fs-22"><i class="fa-light fa-paper-plane"></i></div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Sent') }}</div>
                        <div class="fs-22 fw-7 text-gray-900">{{ number_format($summary['sent']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-gray-300 shadow-none h-100">
                <div class="card-body d-flex align-items-center justify-content-between gap-16 p-4">
                    <div class="size-48 d-flex align-items-center justify-content-center rounded-3 bg-danger-100 text-danger fs-22"><i class="fa-light fa-circle-exclamation"></i></div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Failed') }}</div>
                        <div class="fs-22 fw-7 text-gray-900">{{ number_format($summary['failed']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-gray-300 shadow-none h-100">
                <div class="card-body d-flex align-items-center justify-content-between gap-16 p-4">
                    <div class="size-48 d-flex align-items-center justify-content-center rounded-3 bg-warning-100 text-warning fs-22"><i class="fa-light fa-bolt"></i></div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Active automations') }}</div>
                        <div class="fs-22 fw-7 text-gray-900">{{ number_format($summary['active']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-gray-300 shadow-none h-100">
                <div class="card-body d-flex align-items-center justify-content-between gap-16 p-4">
                    <div class="size-48 d-flex align-items-center justify-content-center rounded-3 bg-info-100 text-info fs-22"><i class="fa-light fa-messages-dollar"></i></div>
                    <div class="text-end">
                        <div class="fs-12 text-gray-600">{{ __('Messages left this month') }}</div>
                        <div class="fs-22 fw-7 text-gray-900">{{ $quota['is_unlimited'] ? __('Unlimited') : number_format($quota['remaining']) }}</div>
                        <div class="fs-11 text-gray-500">{{ __('Used: :count', ['count' => number_format($quota['sent_by_month'])]) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-gray-300 shadow-none overflow-hidden mb-4">
        <div class="card-header border-0 p-4 pb-0">
            <div>
                <div class="fw-6 fs-18 text-gray-900">{{ __('WhatsApp permissions') }}</div>
                <div class="fs-13 text-gray-600">{{ __('Review the active plan access and limits currently applied to WhatsApp Unofficial.') }}</div>
            </div>
            <div class="badge {{ $permissionsInfo['enabled'] ? 'badge-light-success text-success' : 'badge-light-danger text-danger' }}">{{ $permissionsInfo['enabled'] ? __('Enabled') : __('Disabled') }}</div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-12 col-xl-7">
                    <div class="d-flex flex-wrap gap-8">
                        @foreach($permissionsInfo['features'] as $item)
                            <span class="badge {{ $item['enabled'] ? 'badge-light-success text-success' : 'badge-light-danger text-danger' }}">{{ $item['label'] }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="col-12 col-xl-5">
                    <div class="row g-3">
                        @foreach($permissionsInfo['limits'] as $item)
                            <div class="col-12 col-sm-6">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="fs-12 text-gray-500 mb-1">{{ $item['label'] }}</div>
                                    <div class="fw-6 text-gray-900">{{ (int) $item['value'] === -1 ? __('Unlimited') : number_format((int) $item['value']) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xxl-5 d-flex">
            <div class="card border-gray-300 shadow-none w-100">
                <div class="card-header border-0 p-4 pb-0 d-flex flex-column align-items-start gap-4">
                    <div class="fw-6 fs-18 text-gray-900 mb-0">{{ __('Feature breakdown') }}</div>
                    <div class="fs-13 text-gray-600 w-100">{{ __('Compare volume and delivery health across WhatsApp features in the selected period.') }}</div>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex flex-column gap-12">
                        @foreach($featureRows as $row)
                            <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between gap-12 flex-wrap">
                                <div>
                                    <div class="fw-6 text-gray-900">{{ $row->label }}</div>
                                    <div class="fs-12 text-gray-500">{{ __('Records: :count', ['count' => $row->records]) }} &middot; {{ __('Active: :count', ['count' => $row->active]) }}</div>
                                </div>
                                <div class="d-flex gap-8 flex-wrap">
                                    <span class="badge badge-outline badge-light-success text-success">{{ __('Sent: :count', ['count' => $row->sent]) }}</span>
                                    <span class="badge badge-outline badge-light-danger text-danger">{{ __('Failed: :count', ['count' => $row->failed]) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xxl-7 d-flex">
            <div class="card border-gray-300 shadow-none w-100">
                <div class="card-header border-0 p-4 pb-0 d-flex flex-column align-items-start gap-4">
                    <div class="fw-6 fs-18 text-gray-900 mb-0">{{ __('Volume chart') }}</div>
                    <div class="fs-13 text-gray-600 w-100">{{ __('Quick comparison of sent versus failed counts by feature.') }}</div>
                </div>
                <div class="card-body p-4">
                    <div id="wa-report-chart" class="hp-350 export-chart"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-gray-300 shadow-none overflow-hidden mb-4">
        <div class="card-header border-0 p-4 pb-0 d-flex flex-column align-items-start gap-4">
            <div class="fw-6 fs-18 text-gray-900">{{ __('Daily trend') }}</div>
            <div class="fs-13 text-gray-600 w-100">{{ __('Sent and failed volume by day for the selected WhatsApp filters.') }}</div>
        </div>
        <div class="card-body p-4">
            <div id="wa-report-trend-chart" class="hp-350 export-chart"></div>
        </div>
    </div>

    <div class="card border-gray-300 shadow-none overflow-hidden">
        <div class="card-header border-0 p-4 pb-0 d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-12">
            <div class="d-flex flex-column gap-4">
                <div class="fw-6 fs-18 text-gray-900">{{ __('Account performance') }}</div>
                <div class="fs-13 text-gray-600">{{ __('See which connected WhatsApp profiles are carrying the workload in the selected window.') }}</div>
            </div>
            <div class="badge badge-outline badge-light-primary text-primary">{{ __('Profiles: :count', ['count' => $summary['accounts']]) }}</div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle mb-0">
                    <thead>
                        <tr class="fs-12 text-uppercase text-gray-500">
                            <th>{{ __('Account') }}</th>
                            <th class="text-center">{{ __('Records') }}</th>
                            <th class="text-center">{{ __('Active') }}</th>
                            <th class="text-center">{{ __('Sent') }}</th>
                            <th class="text-center">{{ __('Failed') }}</th>
                            <th>{{ __('Feature mix') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accountRows as $row)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-12 min-w-220">
                                        <div class="size-46 rounded-3 overflow-hidden border flex-shrink-0 bg-light">
                                            <img src="{{ Media::url($row->avatar) }}" class="wp-100 hp-100 object-fit-cover">
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-6 text-gray-900 text-truncate">{{ $row->name }}</div>
                                            <div class="fs-12 text-gray-500 text-truncate">{{ $row->username ?: __('WhatsApp profile') }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center fw-6">{{ number_format($row->records) }}</td>
                                <td class="text-center fw-6">{{ number_format($row->active) }}</td>
                                <td class="text-center fw-6 text-success">{{ number_format($row->sent) }}</td>
                                <td class="text-center fw-6 text-danger">{{ number_format($row->failed) }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-8">
                                        @foreach($row->features as $featureKey => $metrics)
                                            <span class="badge badge-outline badge-light-dark text-gray-700">
                                                {{ $featureOptions[$featureKey] ?? $featureKey }}: {{ $metrics['sent'] }}/{{ $metrics['failed'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-gray-500">{{ __('No WhatsApp report data found for the selected filters.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
(function () {
    Main.Select2();
    Main.dateRange();

    setTimeout(function () {
        Main.Chart('column', {!! json_encode($chart['series']) !!}, 'wa-report-chart', {
            categories: {!! json_encode($chart['categories']) !!},
            title: '{{ __('WhatsApp feature volume') }}',
            xAxis: {
                categories: {!! json_encode($chart['categories']) !!}
            },
            yAxis: {
                title: { text: '{{ __('Messages') }}' }
            },
            plotOptions: {
                column: {
                    borderRadius: 6,
                    pointPadding: 0.12,
                    groupPadding: 0.18
                }
            }
        });

        Main.Chart('areaspline', {!! json_encode($trendChart['series']) !!}, 'wa-report-trend-chart', {
            categories: {!! json_encode($trendChart['categories']) !!},
            title: '{{ __('WhatsApp daily trend') }}',
            xAxis: {
                categories: {!! json_encode($trendChart['categories']) !!}
            },
            yAxis: {
                title: { text: '{{ __('Messages') }}' }
            },
            plotOptions: {
                areaspline: {
                    fillOpacity: 0.12,
                    marker: {
                        enabled: false
                    }
                },
                line: {
                    marker: {
                        enabled: false
                    }
                }
            }
        });
    }, 100);
})();
</script>
@endsection




