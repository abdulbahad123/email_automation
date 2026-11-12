<div class="modal fade" id="EmailSmtpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 px-4 px-lg-5 pt-4 pb-3 bg-white">
                <div class="d-flex align-items-center gap-12">
                    <div class="size-48 d-flex align-items-center justify-content-center bg-info-100 text-info b-r-12 flex-shrink-0">
                        <i class="fa-light fa-server fs-20"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-22 fw-6 text-gray-900 mb-1">
                            {{ $result ? __('Update SMTP Account') : __('Connect SMTP Account') }}
                        </h5>
                        <div class="fs-13 text-gray-500">
                            {{ __('Add your SMTP credentials to send single or bulk emails directly from your own mail server.') }}
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 px-lg-5 pb-4 pt-0 bg-white">
                <form class="actionForm" action="{{ route('app.emailsmtp.save', ['id_secure' => $result->id_secure ?? null]) }}" method="POST" data-redirect="{{ route('app.emailsmtp.index') }}">
                    @csrf

                    <div class="d-flex flex-wrap gap-8 mb-4">
                        <button type="button" class="btn btn-light btn-sm smtp-preset" data-host="smtp.gmail.com" data-port="587" data-enc="tls">Gmail (587 TLS)</button>
                        <button type="button" class="btn btn-light btn-sm smtp-preset" data-host="smtp.office365.com" data-port="587" data-enc="tls">Outlook / 365 (587 TLS)</button>
                        <button type="button" class="btn btn-light btn-sm smtp-preset" data-host="email-smtp.us-east-1.amazonaws.com" data-port="587" data-enc="tls">Amazon SES (587 TLS)</button>
                        <button type="button" class="btn btn-light btn-sm smtp-preset" data-host="smtp.sendgrid.net" data-port="587" data-enc="tls">SendGrid (587 TLS)</button>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-6">{{ __('Account Label') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ $result->name ?? '' }}" placeholder="{{ __('Example: Marketing SES / Gmail') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-6">{{ __('Status') }}</label>
                            <select class="form-select" name="status">
                                <option value="1" {{ !isset($result->status) || (int)$result->status === 1 ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="0" {{ isset($result->status) && (int)$result->status === 0 ? 'selected' : '' }}>{{ __('Disabled') }}</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-6">{{ __('SMTP Host') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="smtp_host_input" name="host" value="{{ $result->host ?? '' }}" placeholder="smtp.gmail.com" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-6">{{ __('Port') }} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="smtp_port_input" name="port" value="{{ $result->port ?? 587 }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-6">{{ __('Encryption') }}</label>
                            <select class="form-select" id="smtp_enc_input" name="encryption">
                                <option value="tls" {{ ($result->encryption ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS (587)</option>
                                <option value="ssl" {{ ($result->encryption ?? '') === 'ssl' ? 'selected' : '' }}>SSL (465)</option>
                                <option value="none" {{ ($result->encryption ?? '') === 'none' ? 'selected' : '' }}>None</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-6">{{ __('SMTP Username') }}</label>
                            <input type="text" class="form-control" name="username" value="{{ $result->username ?? '' }}" placeholder="you@yourdomain.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-6">{{ __('SMTP Password / App Password') }}</label>
                            <input type="password" class="form-control" name="password" value="" placeholder="{{ $result ? __('Leave blank to keep existing password') : __('Enter SMTP password') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-6">{{ __('From Email') }} <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="from_email" value="{{ $result->from_email ?? '' }}" placeholder="noreply@yourdomain.com" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-6">{{ __('From Name') }}</label>
                            <input type="text" class="form-control" name="from_name" value="{{ $result->from_name ?? '' }}" placeholder="{{ config('app.name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-6">{{ __('Reply-To Email') }}</label>
                            <input type="email" class="form-control" name="reply_to" value="{{ $result->reply_to ?? '' }}" placeholder="support@yourdomain.com">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-6">{{ __('Daily Sending Limit (0 = Unlimited)') }}</label>
                            <input type="number" min="0" class="form-control" name="daily_limit" value="{{ $result->daily_limit ?? 0 }}">
                            <div class="fs-12 text-gray-500 mt-1">{{ __('Recommended: 500 for Gmail/Workspace, 2000–10000+ for Amazon SES/SendGrid/Mailgun. Multiple active SMTPs rotate automatically.') }}</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between gap-12 pt-4 mt-4 border-top border-gray-200">
                        <button type="button" class="btn btn-outline btn-dark px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary px-4">
                            {{ $result ? __('Update SMTP') : __('Save SMTP') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).off('click.smtpPreset').on('click.smtpPreset', '.smtp-preset', function() {
    $('#smtp_host_input').val($(this).data('host'));
    $('#smtp_port_input').val($(this).data('port'));
    $('#smtp_enc_input').val($(this).data('enc'));
});
</script>

