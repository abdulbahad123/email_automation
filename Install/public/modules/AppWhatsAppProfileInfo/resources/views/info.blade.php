@if($status === 'success' && !empty($account))
<div class="card border-gray-200 shadow-none hp-100 overflow-hidden">
    <div class="card-body p-4 p-lg-5 d-flex flex-column gap-24 hp-100">
        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-20">
            <div class="d-flex gap-16 align-items-center min-w-0">
                <div class="size-72 min-w-72 overflow-hidden rounded-3 border bg-light d-flex align-items-center justify-content-center">
                    <img src="{{ Media::url($liveInfo['avatar'] ?? $account->avatar) }}" class="wp-100 hp-100 object-fit-cover">
                </div>
                <div class="min-w-0">
                    <div class="fs-24 fw-6 text-gray-900 text-truncate">{{ $liveInfo['name'] ?? $account->name }}</div>
                    <div class="fs-14 text-gray-600 text-truncate">{{ $liveInfo['phone'] ?? $account->pid ?? __('WhatsApp profile') }}</div>
                    <div class="fs-13 text-gray-500 text-truncate mt-1">{{ $account->token }}</div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-8">
                @if((int) $account->status === 1)
                    <button type="button" class="btn btn-danger btn-sm wa-profile-action" data-action="logout" data-account="{{ $account->id_secure }}">{{ __('Logout') }}</button>
                @else
                    <a href="{{ route('app.channelwhatsappunofficial.oauth', ['instance_id' => $account->token]) }}" class="btn btn-primary btn-sm">{{ __('Re-login') }}</a>
                @endif
                <a href="{{ route('app.channelwhatsappunofficial.oauth', ['instance_id' => $account->token]) }}" class="btn btn-outline btn-dark btn-sm">{{ __('Reconnect') }}</a>
                <button type="button" class="btn btn-outline btn-danger btn-sm wa-profile-action" data-action="reset" data-account="{{ $account->id_secure }}">{{ __('Reset instance') }}</button>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-md-6">
                <div class="border rounded-3 p-4 hp-100 bg-light">
                    <div class="fs-12 text-uppercase text-gray-500 fw-6 mb-2">{{ __('Live status') }}</div>
                    <div class="d-flex align-items-center gap-8 mb-2"><span class="badge {{ !empty($liveInfo) ? 'badge-light-success text-success' : 'badge-light-warning text-warning' }}">{{ !empty($liveInfo) ? __('Connected') : __('Unknown / offline') }}</span></div>
                    <div class="fs-13 text-gray-600">{{ !empty($liveInfo) ? __('The Node server returned a live profile snapshot for this instance.') : __('No live snapshot was returned by the WhatsApp server for this instance.') }}</div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="border rounded-3 p-4 hp-100 bg-light">
                    <div class="fs-12 text-uppercase text-gray-500 fw-6 mb-2">{{ __('Webhook') }}</div>
                    <div class="fw-6 text-gray-900 mb-2 text-break">{{ $webhook->webhook_url ?? __('No webhook saved') }}</div>
                    <div class="fs-13 text-gray-600">{{ !empty($webhook) ? __('This webhook is stored in whatsapp_webhook for the selected instance.') : __('No webhook record exists for this instance yet.') }}</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="border rounded-3 p-4 hp-100">
                    <div class="fs-12 text-uppercase text-gray-500 fw-6 mb-3">{{ __('Instance data') }}</div>
                    <div class="d-flex flex-column gap-12">
                        <div><div class="fs-12 text-gray-500 mb-1">{{ __('Instance ID') }}</div><div class="fw-6 text-gray-900">{{ $account->token }}</div></div>
                        <div><div class="fs-12 text-gray-500 mb-1">{{ __('Access token') }}</div><div class="fw-6 text-gray-900 text-break">{{ $accessToken }}</div></div>
                        <div><div class="fs-12 text-gray-500 mb-1">{{ __('Account status') }}</div><div class="fw-6 text-gray-900">{{ (int) $account->status === 1 ? __('Enabled') : __('Disabled') }}</div></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="border rounded-3 p-4 hp-100">
                    <div class="fs-12 text-uppercase text-gray-500 fw-6 mb-3">{{ __('Session cache') }}</div>
                    <div class="d-flex flex-column gap-12">
                        <div><div class="fs-12 text-gray-500 mb-1">{{ __('Session row') }}</div><div class="fw-6 text-gray-900">{{ $session ? __('Available') : __('Missing') }}</div></div>
                        <div><div class="fs-12 text-gray-500 mb-1">{{ __('Session status') }}</div><div class="fw-6 text-gray-900">{{ !empty($session) ? ((int) $session->status === 1 ? __('Connected') : __('Pending / logged out')) : __('Unknown') }}</div></div>
                        <div><div class="fs-12 text-gray-500 mb-1">{{ __('Last update') }}</div><div class="fw-6 text-gray-900">{{ !empty($account->changed) ? date('d/m/Y H:i', (int) $account->changed) : __('N/A') }}</div></div>
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($liveInfo))
            <div class="border rounded-3 p-4 bg-light">
                <div class="fs-12 text-uppercase text-gray-500 fw-6 mb-3">{{ __('Live profile payload') }}</div>
                <pre class="mb-0 fs-12 text-gray-800">{{ json_encode($liveInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif

        <div class="alert alert-light border mb-0 wa-profile-feedback">{{ __('Use reconnect to open the profile again, logout to disable it, or reset to remove the stored instance completely.') }}</div>
    </div>
</div>
@else
<div class="alert alert-danger">{{ $message ?? __('Could not load WhatsApp profile info.') }}</div>
@endif

<script type="text/javascript">
(function () {
    $(document).off('click.waProfileAction').on('click.waProfileAction', '.wa-profile-action', function () {
        var button = $(this);
        var action = button.data('action');
        var account = button.data('account');
        var feedback = $('.wa-profile-feedback').first();
        var url = action === 'reset' ? '{{ route('app.whatsappprofileinfo.reset') }}' : '{{ route('app.whatsappprofileinfo.logout') }}';

        if (action === 'reset' && !confirm('{{ __('Are you sure you want to reset this instance?') }}')) {
            return false;
        }

        $.post(url, {_token: '{{ csrf_token() }}', account: account}, function (result) {
            feedback.removeClass('alert-light alert-success alert-danger').addClass(result.status == 1 ? 'alert-success' : 'alert-danger').text(result.message || '{{ __('Action completed.') }}');
        }, 'json');

        return false;
    });
})();
</script>

