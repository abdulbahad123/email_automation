@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('WA Export participants') }}"
        description="{{ __('Export participant lists from your WhatsApp groups into CSV files. Pick an account, load its groups, then download the member list you need.') }}"
        :count="$stats['accounts']"
    />
@endsection

@section('content')
    <div class="container-fluid px-4 px-lg-5 py-4">
        <div class="d-flex flex-column gap-24">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card border-gray-200 shadow-none mb-0 hp-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between gap-16">
                                <div class="size-45 fs-20 text-primary d-flex align-items-center justify-content-center bg-primary-100 b-r-10">
                                    <i class="fa-light fa-users"></i>
                                </div>
                                <div class="text-end">
                                    <div class="fs-12 text-gray-600">{{ __('Profiles') }}</div>
                                    <div class="fw-7 fs-16">{{ number_format($stats['accounts']) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-gray-200 shadow-none mb-0 hp-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between gap-16">
                                <div class="size-45 fs-20 text-success d-flex align-items-center justify-content-center bg-success-100 b-r-10">
                                    <i class="fa-light fa-circle-check"></i>
                                </div>
                                <div class="text-end">
                                    <div class="fs-12 text-gray-600">{{ __('Connected') }}</div>
                                    <div class="fw-7 fs-16">{{ number_format($stats['connected']) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 align-items-stretch">
                <div class="col-12 col-xl-4 d-flex">
                    <div class="card shadow-none border-gray-300 overflow-hidden w-100 hp-100">
                        <div class="card-body p-4 d-flex flex-column gap-20 hp-100">
                            <div>
                                <div class="fs-20 fw-6 text-gray-900 mb-2">{{ __('Choose a WhatsApp profile') }}</div>
                                <div class="text-gray-600 fs-14 max-w-420">
                                    {{ __('Select one connected WhatsApp profile to load its available groups and export participants from any group below.') }}
                                </div>
                            </div>

                            <div>
                                <label for="wa_export_participants_account" class="form-label fw-6">{{ __('WhatsApp account') }}</label>
                                <select id="wa_export_participants_account" class="form-select form-select-lg">
                                    <option value="">{{ __('Select WhatsApp account') }}</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id_secure }}">{{ $account->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="card shadow-none border-gray-200 bg-light mb-0 mt-auto">
                                <div class="card-body p-3">
                                    <div class="fs-13 fw-6 text-gray-900 mb-2">{{ __('How it works') }}</div>
                                    <div class="d-flex flex-column gap-10 fs-13 text-gray-600">
                                        <div class="d-flex gap-8 align-items-start"><span class="text-primary fw-6">1.</span><span>{{ __('Send at least one message in the WhatsApp group you want to export.') }}</span></div>
                                        <div class="d-flex gap-8 align-items-start"><span class="text-primary fw-6">2.</span><span>{{ __('Pick the connected WhatsApp account that belongs to that group.') }}</span></div>
                                        <div class="d-flex gap-8 align-items-start"><span class="text-primary fw-6">3.</span><span>{{ __('Load the groups list and click download on the group you want to export.') }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8 d-flex">
                    <div class="wa-export-participants-result w-100 hp-100">
                        <div class="card shadow-none border-gray-300 overflow-hidden hp-100">
                            <div class="card-body p-5 text-center d-flex flex-column align-items-center justify-content-center hp-100 min-h-400">
                                <div class="d-inline-flex align-items-center justify-content-center w-80 h-80 rounded-circle bg-light mb-4">
                                    <i class="fa-light fa-download text-gray-500 fs-28"></i>
                                </div>
                                <div class="fs-22 fw-6 text-gray-900 mb-2">{{ __('Ready to export group members') }}</div>
                                <div class="fs-14 text-gray-600 max-w-520 mx-auto">
                                    {{ __('Choose one WhatsApp profile from the left. The module will fetch its available groups and show a download button for each group participant list.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script type="text/javascript">
(function () {
    var select = $('#wa_export_participants_account');
    var resultWrap = $('.wa-export-participants-result');

    if (!select.length || !resultWrap.length) return;

    var emptyState = '<div class="card shadow-none border-gray-300 overflow-hidden hp-100"><div class="card-body p-5 text-center d-flex flex-column align-items-center justify-content-center hp-100 min-h-400"><div class="d-inline-flex align-items-center justify-content-center w-80 h-80 rounded-circle bg-light mb-4"><i class="fa-light fa-download text-gray-500 fs-28"></i></div><div class="fs-22 fw-6 text-gray-900 mb-2">{{ __('Ready to export group members') }}</div><div class="fs-14 text-gray-600 max-w-520 mx-auto">{{ __('Choose one WhatsApp profile from the left. The module will fetch its available groups and show a download button for each group participant list.') }}</div></div></div>';

    var loadingState = '<div class="card shadow-none border-gray-300 overflow-hidden hp-100">'
        + '<div class="card-header bg-white border-bottom px-4 py-3 d-flex align-items-center justify-content-between">'
        + '<div class="wp-50"><div class="h-18 bg-gray-200 b-r-6 mb-2"></div><div class="h-12 bg-gray-100 b-r-6 wp-60"></div></div>'
        + '<div class="h-36 bg-gray-100 b-r-8 wp-120"></div>'
        + '</div>'
        + '<div class="card-body p-4 d-flex flex-column gap-16">'
        + '<div class="border rounded-3 px-4 py-3"><div class="d-flex align-items-center gap-12"><div class="size-48 rounded-circle bg-gray-100"></div><div class="flex-fill"><div class="h-18 bg-gray-200 b-r-6 mb-2 wp-40"></div><div class="h-12 bg-gray-100 b-r-6 wp-60"></div></div><div class="h-38 bg-gray-100 b-r-8 wp-140"></div></div></div>'
        + '<div class="border rounded-3 px-4 py-3"><div class="d-flex align-items-center gap-12"><div class="size-48 rounded-circle bg-gray-100"></div><div class="flex-fill"><div class="h-18 bg-gray-200 b-r-6 mb-2 wp-35"></div><div class="h-12 bg-gray-100 b-r-6 wp-55"></div></div><div class="h-38 bg-gray-100 b-r-8 wp-140"></div></div></div>'
        + '<div class="border rounded-3 px-4 py-3"><div class="d-flex align-items-center gap-12"><div class="size-48 rounded-circle bg-gray-100"></div><div class="flex-fill"><div class="h-18 bg-gray-200 b-r-6 mb-2 wp-45"></div><div class="h-12 bg-gray-100 b-r-6 wp-50"></div></div><div class="h-38 bg-gray-100 b-r-8 wp-140"></div></div></div>'
        + '</div></div>';

    var injectHtml = function (html) {
        resultWrap.html(html);
    };

    var loadGroups = function () {
        var account = select.val();

        if (!account) {
            injectHtml(emptyState);
            return;
        }

        $.ajax({
            url: '{{ route('app.whatsappparticipantsexport.groups') }}',
            type: 'POST',
            dataType: 'json',
            data: { _token: '{{ csrf_token() }}', account: account },
            beforeSend: function () {
                injectHtml(loadingState);
            },
            success: function (result) {
                if (result.data) {
                    injectHtml(result.data);
                } else {
                    injectHtml('<div class="card shadow-none border-danger-subtle overflow-hidden hp-100"><div class="card-body p-5 text-center d-flex align-items-center justify-content-center min-h-400 text-danger">{{ __('Could not load groups.') }}</div></div>');
                }
            },
            error: function (xhr) {
                var message = '{{ __('Could not load groups.') }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                injectHtml('<div class="card shadow-none border-danger-subtle overflow-hidden hp-100"><div class="card-body p-5 text-center d-flex align-items-center justify-content-center min-h-400 text-danger">' + message + '</div></div>');
            }
        });
    };

    $(document).off('change.waExportParticipantsAccount').on('change.waExportParticipantsAccount', '#wa_export_participants_account', loadGroups);
    $(document).off('click.waExportParticipantsReload').on('click.waExportParticipantsReload', '.wa-export-participants-reload', function (e) {
        e.preventDefault();
        loadGroups();
        return false;
    });
})();
</script>
@endsection
