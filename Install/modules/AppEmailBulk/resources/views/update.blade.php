@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ $result ? __('Update Email Campaign') : __('Create Bulk Email Campaign') }}"
        description="{{ __('Configure your SMTP rotation, target contact list, batch throttling, and personalized HTML email content.') }}"
    >
        <a href="{{ route('app.emailbulk.index') }}" class="btn btn-dark btn-sm">
            <i class="fa-light fa-arrow-left me-1"></i>{{ __('Back') }}
        </a>
    </x-sub-header>
@endsection

@section('content')
<div class="container pb-5">
    <form id="EmailBulkForm" class="actionForm" action="{{ route('app.emailbulk.save', ['id_secure' => $result->id_secure ?? null]) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <div class="card border-gray-200 h-100">
                    <div class="card-header bg-white py-3">
                        <div class="fw-6 fs-16 text-gray-900">{{ __('Campaign & Delivery Settings') }}</div>
                    </div>
                    <div class="card-body d-flex flex-column gap-16">
                        <div>
                            <label class="form-label fw-6">{{ __('Campaign Name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ $result->name ?? '' }}" placeholder="{{ __('Example: Product Launch 10k Blast') }}" required>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-6 mb-0">{{ __('Select SMTP Account(s)') }} <span class="text-danger">*</span></label>
                                <a href="{{ route('app.emailsmtp.index') }}" class="fs-12 text-primary">{{ __('Manage SMTPs') }}</a>
                            </div>
                            <select class="form-select min-h-45 h-auto" name="smtp_ids[]" data-control="select2" multiple="multiple" data-placeholder="{{ __('Choose one or more SMTP accounts') }}" required>
                                @foreach($smtps as $smtp)
                                    <option value="{{ $smtp->id_secure }}" {{ in_array((string)$smtp->id, $selectedSmtps ?? [], true) ? 'selected' : '' }}>
                                        {{ $smtp->name }} ({{ $smtp->from_email }})
                                    </option>
                                @endforeach
                                @if($hasAdminDefault)
                                    <option value="admin_default" {{ in_array('admin_default', $selectedSmtps ?? [], true) ? 'selected' : '' }}>
                                        {{ __('System Default SMTP') }}
                                    </option>
                                @endif
                            </select>
                            <div class="fs-12 text-gray-500 mt-1">{{ __('Select multiple SMTP accounts to rotate automatically and scale up to 10k+ emails safely.') }}</div>
                        </div>

                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-6 mb-0">{{ __('Target Contact Group') }} <span class="text-danger">*</span></label>
                                <a href="{{ route('app.emailcontact.index') }}" class="fs-12 text-primary">{{ __('Manage Lists') }}</a>
                            </div>
                            <select class="form-select" name="contact_group" data-control="select2" data-placeholder="{{ __('Select contact group') }}" required>
                                <option value="">{{ __('Select contact group') }}</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id_secure }}" {{ isset($result->group_id) && (int)$result->group_id === (int)$group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="border rounded-3 p-3 bg-light">
                            <div class="fs-12 fw-6 text-uppercase text-gray-600 mb-3">{{ __('Scalable Scheduling & Throttling') }}</div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-6">{{ __('Start Time') }}</label>
                                    <input type="text" class="form-control datetime" autocomplete="off" name="time_post" value="{{ !empty($result?->time_post) ? datetime_show($result->time_post) : datetime_show(time()) }}" placeholder="{{ __('Select start date and time') }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-6">{{ __('Batch Size') }}</label>
                                    <input type="number" min="1" max="500" class="form-control" name="batch_size" value="{{ $result->batch_size ?? 50 }}">
                                    <div class="fs-11 text-gray-500 mt-1">{{ __('Emails per cron cycle') }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-6">{{ __('Min Delay (s)') }}</label>
                                    <input type="number" min="0" max="60" class="form-control" name="min_delay" value="{{ $result->min_delay ?? 1 }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-6">{{ __('Max Delay (s)') }}</label>
                                    <input type="number" min="0" max="60" class="form-control" name="max_delay" value="{{ $result->max_delay ?? 2 }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-6">{{ __('Allowed Hours (Optional)') }}</label>
                                    <select class="form-select min-h-45 h-auto" data-control="select2" name="schedule_time[]" multiple="multiple" data-placeholder="{{ __('Leave empty to send anytime (24/7)') }}">
                                        @for($i = 0; $i <= 23; $i++)
                                            <option value="{{ $i }}" {{ in_array((string)$i, $scheduleHours ?? [], true) ? 'selected' : '' }}>{{ sprintf('%02d:00', $i) }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="card border-gray-200 h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-8">
                        <div class="fw-6 fs-16 text-gray-900">{{ __('Email Composer') }}</div>
                        <div class="d-flex flex-wrap gap-6">
                            <button type="button" class="btn btn-light btn-sm insert-email-var" data-var="[name]">[name]</button>
                            <button type="button" class="btn btn-light btn-sm insert-email-var" data-var="[email]">[email]</button>
                            <button type="button" class="btn btn-light btn-sm insert-email-var" data-var="[company]">[company]</button>
                            <button type="button" class="btn btn-light btn-sm insert-email-var" data-var="[unsubscribe_url]">[unsubscribe_url]</button>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column gap-16">
                        <div>
                            <label class="form-label fw-6">{{ __('Email Subject') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="subject" value="{{ $result->subject ?? '' }}" placeholder="{{ __('Example: {Hi|Hello} [name], special offer inside') }}" required>
                        </div>

                        <div>
                            <label class="form-label fw-6">{{ __('Email Body (HTML & Variables Supported)') }} <span class="text-danger">*</span></label>
                            <textarea id="email_campaign_body" class="textarea_editor form-control min-h-260" name="body" placeholder="{{ __('Write your HTML email message here...') }}">{{ $result->body ?? '<p>Hi [name],</p><p>Write your message here...</p>' }}</textarea>
                            <div class="fs-12 text-gray-500 mt-1">{{ __('Supports Spintax ({Hi|Hello|Hey}) and CSV columns like [name], [company], [city]. An unsubscribe link is added automatically if [unsubscribe_url] is omitted.') }}</div>
                        </div>

                        <div>
                            <label class="form-label fw-6">{{ __('Attachment (Optional)') }}</label>
                            <input type="file" class="form-control" name="attachment">
                            @if(!empty($result?->attachment))
                                <div class="d-flex align-items-center gap-12 mt-2 fs-12 text-gray-700">
                                    <span><i class="fa-light fa-paperclip me-1"></i>{{ basename($result->attachment) }}</span>
                                    <label class="form-check-label text-danger cursor-pointer">
                                        <input type="checkbox" class="form-check-input me-1" name="remove_attachment" value="1">{{ __('Remove current attachment') }}
                                    </label>
                                </div>
                            @endif
                        </div>

                        <div class="pt-3 mt-auto border-top d-flex justify-content-end gap-8">
                            <a href="{{ route('app.emailbulk.index') }}" class="btn btn-dark">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fa-light fa-paper-plane me-1"></i>{{ $result ? __('Update Campaign') : __('Launch Campaign') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('script')
<script type="text/javascript">
$(document).off('submit.emailCampaignTiny').on('submit.emailCampaignTiny', '#EmailBulkForm', function () {
    if (window.tinymce) {
        tinymce.triggerSave();
    }
});

$(document).off('click.insertEmailVar').on('click.insertEmailVar', '.insert-email-var', function () {
    var token = $(this).data('var');
    if (window.tinymce && tinymce.get('email_campaign_body')) {
        tinymce.get('email_campaign_body').execCommand('mceInsertContent', false, token);
    } else {
        var el = document.getElementById('email_campaign_body');
        if (el) el.value += ' ' + token;
    }
});
</script>
@endsection

