@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('WhatsApp Profile Info') }}"
        description="{{ __('Inspect a connected unofficial WhatsApp profile, review its live connection state, and manage reconnect or reset actions from one screen.') }}"
        :count="$accounts->count()"
    />
@endsection

@section('content')
<div class="container-fluid">
    <div class="row g-4 align-items-stretch">
        <div class="col-12 col-xl-4 d-flex">
            <div class="card border-gray-200 shadow-none w-100 hp-100">
                <div class="card-body p-4 d-flex flex-column gap-20 hp-100">
                    <div>
                        <div class="fs-20 fw-6 text-gray-900 mb-2">{{ __('Choose a WhatsApp profile') }}</div>
                        <div class="text-gray-600 fs-14">{{ __('Select one connected profile to load its live info, stored session data, and webhook settings.') }}</div>
                    </div>
                    <div>
                        <label for="wa_profile_info_account" class="form-label fw-6">{{ __('WhatsApp account') }}</label>
                        <select id="wa_profile_info_account" class="form-select form-select-lg">
                            <option value="">{{ __('Select WhatsApp account') }}</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account->id_secure }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex flex-column gap-12 mt-auto">
                        <div class="d-flex gap-12 align-items-start p-3 rounded-3 bg-light">
                            <div class="w-40 h-40 rounded-circle bg-success-100 text-success d-flex align-items-center justify-content-center flex-shrink-0"><i class="fa-light fa-signal-stream"></i></div>
                            <div>
                                <div class="fw-6 text-gray-900">{{ __('Live connection state') }}</div>
                                <div class="fs-13 text-gray-600">{{ __('The module asks the running Node WhatsApp server for the current profile info when possible.') }}</div>
                            </div>
                        </div>
                        <div class="d-flex gap-12 align-items-start p-3 rounded-3 bg-light">
                            <div class="w-40 h-40 rounded-circle bg-primary-100 text-primary d-flex align-items-center justify-content-center flex-shrink-0"><i class="fa-light fa-webhook"></i></div>
                            <div>
                                <div class="fw-6 text-gray-900">{{ __('Stored webhook') }}</div>
                                <div class="fs-13 text-gray-600">{{ __('See which webhook URL is tied to the selected instance inside the local WhatsApp tables.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8 d-flex">
            <div class="wa-profile-info-result w-100 hp-100">
                <div class="card border-gray-200 shadow-none hp-100">
                    <div class="card-body p-5 text-center d-flex flex-column align-items-center justify-content-center hp-100 min-h-420">
                        <div class="d-inline-flex align-items-center justify-content-center w-80 h-80 rounded-circle bg-light mb-4"><i class="fa-light fa-circle-user text-gray-500 fs-28"></i></div>
                        <div class="fs-22 fw-6 text-gray-900 mb-2">{{ __('Ready to inspect a WhatsApp profile') }}</div>
                        <div class="fs-14 text-gray-600 max-w-520 mx-auto">{{ __('Pick a connected account on the left to see live session info, instance data, and recovery actions.') }}</div>
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
    var select = document.getElementById('wa_profile_info_account');
    var resultWrap = document.querySelector('.wa-profile-info-result');
    if (!select || !resultWrap) return;

    var emptyState = resultWrap.innerHTML;

    function injectHtml(target, html) {
        target.innerHTML = html;
        var scripts = target.querySelectorAll('script');
        scripts.forEach(function (oldScript) {
            var newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(function (attr) {
                newScript.setAttribute(attr.name, attr.value);
            });
            newScript.text = oldScript.text || oldScript.textContent || oldScript.innerHTML || '';
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    function loadInfo() {
        var account = select.value;
        if (!account) {
            resultWrap.innerHTML = emptyState;
            return;
        }

        $.ajax({
            url: '{{ route('app.whatsappprofileinfo.info') }}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                account: account
            },
            beforeSend: function () {
                injectHtml(resultWrap, '<div class="card border-gray-200 shadow-none hp-100"><div class="card-body p-5 text-center text-gray-600 d-flex align-items-center justify-content-center hp-100 min-h-420"><div><div class="spinner-border spinner-border-sm me-2"></div>{{ __('Loading WhatsApp profile info...') }}</div></div></div>');
            },
            success: function (result) {
                if (result.data) {
                    injectHtml(resultWrap, result.data);
                }
            },
            error: function () {
                injectHtml(resultWrap, '<div class="alert alert-danger">{{ __('Could not load WhatsApp profile info.') }}</div>');
            }
        });
    }

    $(document).off('change.waProfileInfo').on('change.waProfileInfo', '#wa_profile_info_account', loadInfo);
})();
</script>
@endsection
