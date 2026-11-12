@extends('layouts.app')

@section('content')
    <div class="container px-4 max-w-900">

        <div class="mt-4 mb-5">
            <div class="d-flex flex-column flex-lg-row flex-md-column align-items-md-start align-items-lg-center justify-content-between">
                <div class="my-3 d-flex flex-column gap-8">
                    <h1 class="fs-20 font-medium lh-1 text-gray-900">
                        {{ __('Add WhatsApp Unofficial profiles') }}
                    </h1>
                    <div class="d-flex align-items-center gap-20 fw-5 fs-14">
                        <div class="d-flex gap-8">
                            <span class="text-gray-600">{{ __('Scan the QR code in WhatsApp to connect your profile.') }}</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-8">
                    <a class="btn btn-light btn-sm" href="{{ url('app/channels') }}">
                        <span><i class="fa-light fa-angle-left"></i></span>
                        <span>{{ __('Back') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="fw-6 d-flex align-items-center gap-8">
                            <i class="fab fa-whatsapp text-success"></i>
                            <span>{{ __('Connect WhatsApp Unofficial profile') }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($can_add_account)
                            <div class="border rounded p-3 mb-4 bg-light">
                                <div class="fs-14 fw-6">{{ __('Instance ID') }}: <span class="text-success">{{ $instance_id }}</span></div>
                                <div class="text-gray-600 fs-13 mt-1">{{ __('Use this instance ID if you want to reconnect the same WhatsApp profile later.') }}</div>
                            </div>

                            <div class="whatsapp-qr-wrap text-center"
                                data-qrcode-url="{{ route('app.channelwhatsappunofficial.qrcode', $instance_id) }}"
                                data-check-url="{{ route('app.channelwhatsappunofficial.check_login', $instance_id) }}">
                                <div class="whatsapp-qr-box border rounded d-flex align-items-center justify-content-center mx-auto bg-light" style="width: 320px; height: 320px;">
                                    <div class="text-center text-gray-600">
                                        <div class="spinner-border text-success mb-3" role="status"></div>
                                        <div>{{ __('Generating QR code...') }}</div>
                                    </div>
                                </div>
                                <div class="text-gray-600 fs-13 mt-3">{{ __('Open WhatsApp on your phone, go to Linked devices, then scan this code.') }}</div>
                            </div>
                        @else
                            <div class="alert alert-danger mb-0">
                                {{ __('You have added the maximum number of allowed channels.') }}
                            </div>
                        @endif
                    </div>
                </div>

                @if($accounts->isNotEmpty())
                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="fw-6">{{ __('Reconnect an existing instance') }}</div>
                        </div>
                        <div class="card-body">
                            @foreach($accounts as $account)
                                <div class="d-flex justify-content-between align-items-center gap-12 {{ !$loop->last ? 'border-bottom pb-3 mb-3' : '' }}">
                                    <div class="d-flex align-items-center gap-12">
                                        <div class="size-44 size-child overflow-hidden rounded-circle border bg-light">
                                            <img src="{{ \Media::url($account->avatar) }}" alt="{{ $account->name }}" class="w-100 h-100 object-fit-cover">
                                        </div>
                                        <div>
                                            <div class="fw-6 text-gray-900">{{ $account->name }}</div>
                                            <div class="fs-12 text-gray-600">{{ $account->pid ? strtok($account->pid, ':') : $account->token }}</div>
                                        </div>
                                    </div>
                                    <a href="{{ module_url('oauth/' . $account->token) }}" class="btn btn-light btn-sm">
                                        {{ __('Reconnect') }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <div class="text-gray-600 fs-14 mb-3">
                            {{ __('If the QR code expires, reload this page to generate a new one.') }}
                        </div>
                        <a href="{{ module_url('oauth') }}" class="btn btn-dark btn-sm">
                            {{ __('Generate another QR code') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('script')
<script type="text/javascript">
    (function () {
        var wrap = document.querySelector('.whatsapp-qr-wrap');
        if (!wrap) return;

        var box = wrap.querySelector('.whatsapp-qr-box');
        var qrcodeUrl = wrap.dataset.qrcodeUrl;
        var checkUrl = wrap.dataset.checkUrl;

        var renderLoading = function (message) {
            box.innerHTML = '<div class="text-center text-gray-600"><div class="spinner-border text-success mb-3" role="status"></div><div>' + message + '</div></div>';
        };

        var renderError = function (message) {
            box.innerHTML = '<div class="alert alert-danger m-3 mb-0 text-start">' + message + '</div>';
        };

        var isTransientQrError = function (message) {
            var text = String(message || '').toLowerCase();
            return text.indexOf('refreshing session') !== -1
                || text.indexOf('initializing a new qr code') !== -1
                || text.indexOf('try again in a moment') !== -1
                || text.indexOf('please retry in') !== -1;
        };

        var renderQrImage = function (rawBase64) {
            if (!rawBase64) {
                renderError('{{ __('Could not load QR code.') }}');
                return;
            }

            var normalized = String(rawBase64).trim().replace(/\s+/g, '');
            var mime = 'image/png';
            var pureBase64 = normalized;

            if (normalized.indexOf('data:image') === 0) {
                var parts = normalized.split(',');
                var header = parts[0] || '';
                pureBase64 = parts.slice(1).join(',');
                var mimeMatch = header.match(/^data:(image\/[a-zA-Z0-9.+-]+);base64$/);
                if (mimeMatch && mimeMatch[1]) {
                    mime = mimeMatch[1];
                }
            }

            try {
                var binary = atob(pureBase64);
                var bytes = new Uint8Array(binary.length);
                for (var i = 0; i < binary.length; i++) {
                    bytes[i] = binary.charCodeAt(i);
                }

                var blob = new Blob([bytes], { type: mime });
                var blobUrl = URL.createObjectURL(blob);
                var img = new Image();
                img.alt = 'QR Code';
                img.style.maxWidth = '280px';
                img.style.maxHeight = '280px';

                img.onload = function () {
                    box.innerHTML = '';
                    box.appendChild(img);
                };

                img.onerror = function () {
                    URL.revokeObjectURL(blobUrl);
                    renderError('{{ __('QR code data is invalid.') }}');
                };

                img.src = blobUrl;
            } catch (e) {
                renderError('{{ __('QR code data is invalid.') }}');
            }
        };

        var loadQr = function (attempt) {
            attempt = attempt || 0;
            $.ajax({
                url: qrcodeUrl,
                type: 'GET',
                dataType: 'json',
                success: function (result) {
                    var status = result.status;
                    var base64 = result.base64 || (result.data && (result.data.base64 || result.data.qrcode || result.data.qr));
                    var success = status == 1 || status === '1' || status === 'success';

                    if (success && base64) {
                        renderQrImage(base64);
                        return;
                    }

                    if (attempt < 6 && isTransientQrError(result.message)) {
                        renderLoading(result.message || '{{ __('Refreshing QR code...') }}');
                        setTimeout(function () { loadQr(attempt + 1); }, 2500);
                        return;
                    }

                    renderError(result.message || '{{ __('Could not load QR code.') }}');
                },
                error: function () {
                    if (attempt < 4) {
                        renderLoading('{{ __('Retrying QR request...') }}');
                        setTimeout(function () { loadQr(attempt + 1); }, 2500);
                        return;
                    }
                    renderError('{{ __('Could not connect to the WhatsApp server.') }}');
                }
            });
        };

        var checkLogin = function () {
            $.ajax({
                url: checkUrl,
                type: 'GET',
                dataType: 'json',
                success: function (result) {
                    if (result.status == 1 && result.redirect) {
                        window.location.href = result.redirect;
                        return;
                    }

                    setTimeout(checkLogin, 2000);
                },
                error: function () {
                    setTimeout(checkLogin, 4000);
                }
            });
        };

        renderLoading('{{ __('Generating QR code...') }}');
        loadQr(0);
        checkLogin();
    })();
</script>
@endsection