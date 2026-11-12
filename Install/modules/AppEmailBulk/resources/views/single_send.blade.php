@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('Send Single Email') }}"
        description="{{ __('Send an instant one-to-one email using any of your connected SMTP accounts.') }}"
    >
        <a href="{{ route('app.emailsmtp.index') }}" class="btn btn-light btn-sm">
            <i class="fa-light fa-server me-1"></i>{{ __('SMTP Accounts') }}
        </a>
        <a href="{{ route('app.emailbulk.index') }}" class="btn btn-primary btn-sm">
            <i class="fa-light fa-envelopes-bulk me-1"></i>{{ __('Bulk Campaigns') }}
        </a>
    </x-sub-header>
@endsection

@section('content')
<div class="container pb-5">
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card border-gray-200">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-8">
                    <div class="fw-6 fs-16 text-gray-900">{{ __('Compose Single Email') }}</div>
                    <span class="fs-12 text-gray-500">{{ __('Choose Custom Message or Default Marketing Format') }}</span>
                </div>
                <div class="card-body">
                    @if($smtps->isEmpty() && !$hasAdminDefault)
                        <div class="alert alert-warning d-flex align-items-center justify-content-between gap-12 mb-4">
                            <div>
                                <div class="fw-6">{{ __('No SMTP Account Connected') }}</div>
                                <div class="fs-13">{{ __('Connect your Gmail, Amazon SES, or custom SMTP account before sending emails.') }}</div>
                            </div>
                            <a href="{{ route('app.emailsmtp.index') }}" class="btn btn-primary btn-sm flex-shrink-0">{{ __('Connect SMTP') }}</a>
                        </div>
                    @endif

                    <form id="SingleEmailForm" class="actionForm" action="{{ route('app.emailsend.submit') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-6">{{ __('From SMTP Account') }}</label>
                                <select class="form-select" name="smtp_id">
                                    @foreach($smtps as $smtp)
                                        <option value="{{ $smtp->id_secure }}">{{ $smtp->name }} ({{ $smtp->from_email }})</option>
                                    @endforeach
                                    @if($hasAdminDefault)
                                        <option value="admin_default">{{ __('System Default SMTP') }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-6">{{ __('Recipient Email') }} <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="to_email" placeholder="customer@example.com" required>
                            </div>
                        </div>

                        @include('appemailbulk::partials.email-composer', [
                            'prefix' => 'single',
                            'formId' => 'SingleEmailForm',
                            'subject' => '',
                            'body' => '<p>Hi,</p><p>Write your email message here...</p>',
                            'existingAttachment' => '',
                            'templates' => $templates ?? [],
                            'isBulk' => false,
                        ])

                        <div class="pt-3 mt-3 border-top d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fa-light fa-paper-plane me-1"></i>{{ __('Send Email') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-gray-200">
                <div class="card-header bg-white py-3">
                    <div class="fw-6 fs-16 text-gray-900">{{ __('Recent Single Emails') }}</div>
                </div>
                <div class="card-body p-0">
                    @if($recentLogs->isEmpty())
                        <div class="p-4 text-center text-gray-500 fs-13">{{ __('No single emails sent yet.') }}</div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($recentLogs as $log)
                                <div class="list-group-item px-4 py-3">
                                    <div class="d-flex justify-content-between align-items-start gap-8">
                                        <div class="min-w-0">
                                            <div class="fw-6 text-gray-900 text-truncate">{{ $log->to_email }}</div>
                                            <div class="fs-12 text-gray-600 text-truncate">{{ $log->subject }}</div>
                                            @if(!empty($log->error_message))
                                                <div class="fs-11 text-danger mt-1">{{ $log->error_message }}</div>
                                            @endif
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            @if((int)$log->status === 1)
                                                <span class="badge badge-outline badge-sm badge-light-success text-success">{{ __('Sent') }}</span>
                                            @else
                                                <span class="badge badge-outline badge-sm badge-light-danger text-danger">{{ __('Failed') }}</span>
                                            @endif
                                            <div class="fs-11 text-gray-500 mt-1">{{ !empty($log->sent_at) ? date('M d, H:i', (int)$log->sent_at) : '' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

