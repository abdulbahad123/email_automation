@php
    \Modules\AppEmailSMTP\Services\EmailSchemaService::ensureTables();

    $teamId = (int) request('team_id');
    $smtps = $teamId
        ? \Illuminate\Support\Facades\DB::table('email_smtp_accounts')
            ->where('team_id', $teamId)
            ->where('status', 1)
            ->orderByDesc('created')
            ->get()
        : collect();

    $hasAdminDefault = \Modules\AppEmailSMTP\Services\DynamicMailerService::resolveAccount('admin_default') !== null;

    $totalSmtps = $teamId ? (int) \Illuminate\Support\Facades\DB::table('email_smtp_accounts')->where('team_id', $teamId)->where('status', 1)->count() : 0;
    $totalContacts = $teamId ? (int) \Illuminate\Support\Facades\DB::table('email_contacts')->where('team_id', $teamId)->where('unsubscribed', 0)->count() : 0;
    $runningCampaigns = $teamId ? (int) \Illuminate\Support\Facades\DB::table('email_campaigns')->where('team_id', $teamId)->where('status', 1)->count() : 0;
    $emailsSent = $teamId ? (int) \Illuminate\Support\Facades\DB::table('email_logs')->where('team_id', $teamId)->where('status', 1)->count() : 0;
    $emailsFailed = $teamId ? (int) \Illuminate\Support\Facades\DB::table('email_logs')->where('team_id', $teamId)->where('status', 2)->count() : 0;
@endphp

<div class="card border-gray-200 mb-4 overflow-hidden">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-12">
        <div class="d-flex align-items-center gap-12">
            <div class="size-42 d-flex align-items-center justify-content-center bg-primary-100 text-primary b-r-10">
                <i class="fa-light fa-envelopes-bulk fs-18"></i>
            </div>
            <div>
                <div class="fw-6 fs-16 text-gray-900">{{ __('Email Automation & Quick Send') }}</div>
                <div class="fs-12 text-gray-500">{{ __('Manage SMTPs, run 10–10k+ bulk email campaigns, or send a single email directly from your dashboard.') }}</div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-8">
            <a href="{{ route('app.emailsmtp.index') }}" class="btn btn-light btn-sm">
                <i class="fa-light fa-server me-1"></i>{{ __('SMTPs') }}
            </a>
            <a href="{{ route('app.emailcontact.index') }}" class="btn btn-light btn-sm">
                <i class="fa-light fa-address-book me-1"></i>{{ __('Email Lists') }}
            </a>
            <a href="{{ route('app.emailbulk.index') }}" class="btn btn-primary btn-sm">
                <i class="fa-light fa-envelopes-bulk me-1"></i>{{ __('Bulk Campaigns') }}
            </a>
        </div>
    </div>

    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <div class="fs-12 text-gray-600">{{ __('Emails Sent') }}</div>
                    <div class="fw-7 fs-20 text-success mt-1">{{ number_format($emailsSent) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <div class="fs-12 text-gray-600">{{ __('Failed') }}</div>
                    <div class="fw-7 fs-20 text-danger mt-1">{{ number_format($emailsFailed) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <div class="fs-12 text-gray-600">{{ __('Running Campaigns') }}</div>
                    <div class="fw-7 fs-20 text-primary mt-1">{{ number_format($runningCampaigns) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 bg-light h-100">
                    <div class="fs-12 text-gray-600">{{ __('Contacts / SMTPs') }}</div>
                    <div class="fw-7 fs-20 text-gray-900 mt-1">{{ number_format($totalContacts) }} <span class="fs-13 fw-5 text-gray-500">/ {{ $totalSmtps }}</span></div>
                </div>
            </div>
        </div>

        <div class="border rounded-3 p-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-8">
                <div class="fw-6 fs-15 text-gray-900">
                    <i class="fa-light fa-paper-plane text-primary me-2"></i>{{ __('Quick Send Single Email') }}
                </div>
                <a href="{{ route('app.emailsend.index') }}" class="fs-12 text-primary">{{ __('Open Full Composer & History') }}</a>
            </div>

            @if($smtps->isEmpty() && !$hasAdminDefault)
                <div class="alert alert-warning d-flex align-items-center justify-content-between gap-12 mb-0">
                    <div class="fs-13">{{ __('No active SMTP account found. Connect your SMTP first to start sending single or bulk emails.') }}</div>
                    <a href="{{ route('app.emailsmtp.index') }}" class="btn btn-primary btn-sm flex-shrink-0">{{ __('Add SMTP') }}</a>
                </div>
            @else
                <form id="DashboardQuickEmailForm" class="actionForm" action="{{ route('app.emailsend.submit') }}" method="POST" enctype="multipart/form-data" data-call-success="document.getElementById('DashboardQuickEmailForm').reset();">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fs-13 fw-6">{{ __('SMTP Account') }}</label>
                            <select class="form-select" name="smtp_id">
                                @foreach($smtps as $smtp)
                                    <option value="{{ $smtp->id_secure }}">{{ $smtp->name }} ({{ $smtp->from_email }})</option>
                                @endforeach
                                @if($hasAdminDefault)
                                    <option value="admin_default">{{ __('System Default SMTP') }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-13 fw-6">{{ __('To Email') }} <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="to_email" placeholder="recipient@example.com" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-13 fw-6">{{ __('Subject') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" placeholder="{{ __('Quick email subject') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fs-13 fw-6">{{ __('Message') }} <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="body" rows="3" placeholder="{{ __('Write your message here...') }}" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-12 fw-6">{{ __('Body Image (Optional)') }}</label>
                            <input type="file" class="form-control form-control-sm" name="inline_image" accept="image/jpeg,image/png,image/gif,image/webp">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-12 fw-6">{{ __('Attachment (Optional)') }}</label>
                            <input type="file" class="form-control form-control-sm" name="attachment">
                        </div>
                        <div class="col-md-4 d-flex align-items-end justify-content-end">
                            <button type="submit" class="btn btn-primary px-4 w-100 w-md-auto">
                                <i class="fa-light fa-paper-plane me-1"></i>{{ __('Send Single Email') }}
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

