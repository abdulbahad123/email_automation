<div class="modal fade" id="EmailSmtpTestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 px-4 pt-4 pb-3 bg-white">
                <div class="d-flex align-items-center gap-12">
                    <div class="size-44 d-flex align-items-center justify-content-center bg-info-100 text-info b-r-12 flex-shrink-0">
                        <i class="fa-light fa-paper-plane fs-18"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-20 fw-6 text-gray-900 mb-0">{{ __('Test SMTP Connection') }}</h5>
                        <div class="fs-12 text-gray-500">{{ $result->name }} ({{ $result->host }}:{{ $result->port }})</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 pb-4 pt-2 bg-white">
                <form class="actionForm" action="{{ route('app.emailsmtp.test_send', ['id_secure' => $result->id_secure]) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-6">{{ __('Recipient Email Address') }}</label>
                        <input type="email" class="form-control form-control-lg" name="test_email" value="{{ auth()->user()->email ?? '' }}" placeholder="test@example.com" required>
                        <div class="fs-12 text-gray-500 mt-1">{{ __('We will immediately send a verification email using this SMTP account.') }}</div>
                    </div>
                    <div class="d-flex justify-content-end gap-8">
                        <button type="button" class="btn btn-outline btn-dark" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa-light fa-paper-plane me-1"></i>{{ __('Send Test Email') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

