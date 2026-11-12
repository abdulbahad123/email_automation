@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('Contact Us Management') }}"
        description="{{ __('View customer enquiries with date filters, configure Google reCAPTCHA, and manage company location information') }}"
    >
    </x-sub-header>
@endsection

@section('content')

<div class="container max-w-1200 pb-5">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4 rounded-3 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-check fs-18 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4 rounded-3 d-flex align-items-center" role="alert">
            <i class="fa-solid fa-circle-exclamation fs-18 me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Top Navigation Tabs --}}
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-3 border gap-2 shadow-xs" id="contactTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-6 fs-13 position-relative" id="tab-enquiries-btn" data-bs-toggle="pill" data-bs-target="#tab-enquiries-content" type="button" role="tab">
                <i class="fa-light fa-inbox me-1 text-success"></i> {{ __('Enquiries') }}
                @if(($stats['unread'] ?? 0) > 0)
                    <span class="badge bg-danger ms-1 rounded-pill">{{ $stats['unread'] }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-6 fs-13" id="tab-location-btn" data-bs-toggle="pill" data-bs-target="#tab-location-content" type="button" role="tab">
                <i class="fa-light fa-location-dot me-1 text-primary"></i> {{ __('Location & Contact Details') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-6 fs-13" id="tab-recaptcha-btn" data-bs-toggle="pill" data-bs-target="#tab-recaptcha-content" type="button" role="tab">
                <i class="fa-light fa-shield-check me-1 text-warning"></i> {{ __('reCAPTCHA Settings') }}
            </button>
        </li>
    </ul>

    <div class="tab-content" id="contactTabsContent">
        
        {{-- ========================================================== --}}
        {{-- TAB 1: ENQUIRIES LIST (Filters: Today, Yesterday, Custom)  --}}
        {{-- ========================================================== --}}
        <div class="tab-pane fade show active" id="tab-enquiries-content" role="tabpanel">

            <!-- Stats Metric Highlights -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card border shadow-xs rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fs-12 text-muted fw-6 text-uppercase">{{ __('Total Enquiries') }}</span>
                            <div class="w-32 h-32 rounded-circle bg-light d-flex align-items-center justify-content-center text-secondary">
                                <i class="fa-light fa-envelope-open-text"></i>
                            </div>
                        </div>
                        <div class="fs-22 fw-7 text-dark">{{ number_format($stats['total'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border shadow-xs rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fs-12 text-muted fw-6 text-uppercase">{{ __('Today') }}</span>
                            <div class="w-32 h-32 rounded-circle bg-emerald-50 text-success d-flex align-items-center justify-content-center" style="background: #eafbf3;">
                                <i class="fa-light fa-calendar-day"></i>
                            </div>
                        </div>
                        <div class="fs-22 fw-7 text-success">{{ number_format($stats['today'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border shadow-xs rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fs-12 text-muted fw-6 text-uppercase">{{ __('Yesterday') }}</span>
                            <div class="w-32 h-32 rounded-circle bg-blue-50 text-primary d-flex align-items-center justify-content-center">
                                <i class="fa-light fa-calendar-clock"></i>
                            </div>
                        </div>
                        <div class="fs-22 fw-7 text-primary">{{ number_format($stats['yesterday'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border shadow-xs rounded-3 p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fs-12 text-muted fw-6 text-uppercase">{{ __('Unread / New') }}</span>
                            <div class="w-32 h-32 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center">
                                <i class="fa-light fa-bell"></i>
                            </div>
                        </div>
                        <div class="fs-22 fw-7 text-danger">{{ number_format($stats['unread'] ?? 0) }}</div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="card border shadow-xs rounded-3 mb-4 bg-white">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('admin.contact.index') }}" class="row g-2 align-items-center">
                        <!-- Quick Filter Buttons -->
                        <div class="col-12 col-lg-auto">
                            <div class="btn-group w-100" role="group">
                                <a href="{{ route('admin.contact.index', ['filter' => 'all']) }}" class="btn btn-sm {{ ($filter ?? 'all') === 'all' ? 'btn-success' : 'btn-outline-secondary' }}">
                                    {{ __('All') }}
                                </a>
                                <a href="{{ route('admin.contact.index', ['filter' => 'today']) }}" class="btn btn-sm {{ ($filter ?? '') === 'today' ? 'btn-success' : 'btn-outline-secondary' }}">
                                    {{ __('Today') }}
                                </a>
                                <a href="{{ route('admin.contact.index', ['filter' => 'yesterday']) }}" class="btn btn-sm {{ ($filter ?? '') === 'yesterday' ? 'btn-success' : 'btn-outline-secondary' }}">
                                    {{ __('Yesterday') }}
                                </a>
                            </div>
                        </div>

                        <!-- Custom Date Range -->
                        <div class="col-6 col-md-3 col-lg-2">
                            <input type="date" class="form-control form-control-sm" name="start_date" value="{{ request('start_date') }}" title="{{ __('Start Date') }}" placeholder="{{ __('From Date') }}">
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <input type="date" class="form-control form-control-sm" name="end_date" value="{{ request('end_date') }}" title="{{ __('End Date') }}" placeholder="{{ __('To Date') }}">
                        </div>
                        <input type="hidden" name="filter" value="custom">

                        <!-- Status filter -->
                        <div class="col-6 col-md-3 col-lg-2">
                            <select name="status" class="form-select form-select-sm">
                                <option value="all">{{ __('All Status') }}</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>{{ __('Unread (New)') }}</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>{{ __('Read') }}</option>
                            </select>
                        </div>

                        <!-- Search keyword -->
                        <div class="col-6 col-md-3 col-lg-2">
                            <input type="text" class="form-control form-control-sm" name="keyword" value="{{ request('keyword') }}" placeholder="{{ __('Search name, email...') }}">
                        </div>

                        <!-- Submit Filter Button -->
                        <div class="col-12 col-lg-auto d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-dark flex-grow-1">
                                <i class="fa-light fa-filter me-1"></i> {{ __('Filter') }}
                            </button>
                            <a href="{{ route('admin.contact.index') }}" class="btn btn-sm btn-light border" title="{{ __('Reset Filters') }}">
                                <i class="fa-light fa-rotate-left"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Enquiries Table -->
            <div class="card border shadow-xs rounded-3 bg-white overflow-hidden mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light fs-12 text-uppercase text-muted">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 200px;">{{ __('Customer / Sender') }}</th>
                                <th style="width: 140px;">{{ __('Contact') }}</th>
                                <th>{{ __('Subject & Message') }}</th>
                                <th style="width: 150px;">{{ __('Date') }}</th>
                                <th style="width: 110px;">{{ __('Status') }}</th>
                                <th style="width: 120px;" class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="fs-13">
                            @forelse($enquiries as $enquiry)
                                <tr class="{{ $enquiry->status == 0 ? 'bg-light/50 fw-6' : '' }}">
                                    <td class="text-muted fs-12">#{{ $enquiry->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="w-32 h-32 rounded-circle bg-light border d-flex align-items-center justify-center fs-12 fw-bold text-dark shrink-0">
                                                {{ strtoupper(substr($enquiry->name ?? 'U', 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="fw-bold text-dark text-truncate">{{ $enquiry->name }}</div>
                                                <div class="text-muted fs-11 text-truncate">{{ $enquiry->ip_address }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><a href="mailto:{{ $enquiry->email }}" class="text-primary text-decoration-none text-truncate d-block">{{ $enquiry->email }}</a></div>
                                        @if(!empty($enquiry->phone))
                                            <div class="text-muted fs-11 mt-0.5"><i class="fa-brands fa-whatsapp text-success me-1"></i>{{ $enquiry->phone }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($enquiry->subject))
                                            <span class="badge bg-light text-dark border me-1">{{ $enquiry->subject }}</span>
                                        @endif
                                        <span class="text-muted">{{ \Illuminate\Support\Str::limit($enquiry->message, 80) }}</span>
                                    </td>
                                    <td class="text-muted fs-12">
                                        @if(!empty($enquiry->created))
                                            <div>{{ date('d M Y, h:i A', $enquiry->created) }}</div>
                                            <div class="fs-11 text-slate-400">{{\Carbon\Carbon::createFromTimestamp($enquiry->created)->diffForHumans()}}</div>
                                        @elseif(!empty($enquiry->created_at))
                                            <div>{{ date('d M Y, h:i A', strtotime($enquiry->created_at)) }}</div>
                                            <div class="fs-11 text-slate-400">{{\Carbon\Carbon::parse($enquiry->created_at)->diffForHumans()}}</div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($enquiry->status == 0)
                                            <span class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1">{{ __('New / Unread') }}</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1">{{ __('Read') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <!-- View Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-light border px-2" data-bs-toggle="modal" data-bs-target="#enquiryModal{{ $enquiry->id }}" title="{{ __('View Details') }}">
                                                <i class="fa-light fa-eye text-primary"></i>
                                            </button>

                                            <!-- Toggle Status Form -->
                                            <form method="POST" action="{{ route('admin.contact.status', $enquiry->id) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $enquiry->status == 0 ? 1 : 0 }}">
                                                <button type="submit" class="btn btn-sm btn-light border px-2" title="{{ $enquiry->status == 0 ? __('Mark as read') : __('Mark as unread') }}">
                                                    <i class="fa-light {{ $enquiry->status == 0 ? 'fa-envelope-open text-success' : 'fa-envelope text-warning' }}"></i>
                                                </button>
                                            </form>

                                            <!-- Delete Form -->
                                            <form method="POST" action="{{ route('admin.contact.destroy', $enquiry->id) }}" class="d-inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this enquiry?') }}')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-light border px-2 text-danger" title="{{ __('Delete') }}">
                                                    <i class="fa-light fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Modal for Viewing Message -->
                                        <div class="modal fade" id="enquiryModal{{ $enquiry->id }}" tabindex="-1" aria-hidden="true" style="text-align: left;">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-3 border-0 shadow">
                                                    <div class="modal-header border-bottom">
                                                        <h5 class="modal-title fs-15 fw-bold">
                                                            <i class="fa-light fa-envelope-open-text text-success me-2"></i> {{ __('Enquiry Details') }}
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="mb-3">
                                                            <label class="fs-11 text-uppercase text-muted fw-bold d-block">{{ __('Sender Name') }}</label>
                                                            <div class="fs-15 fw-bold text-dark">{{ $enquiry->name }}</div>
                                                        </div>
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-6">
                                                                <label class="fs-11 text-uppercase text-muted fw-bold d-block">{{ __('Email') }}</label>
                                                                <a href="mailto:{{ $enquiry->email }}" class="fs-13 text-primary">{{ $enquiry->email }}</a>
                                                            </div>
                                                            <div class="col-6">
                                                                <label class="fs-11 text-uppercase text-muted fw-bold d-block">{{ __('Phone') }}</label>
                                                                <div class="fs-13 text-dark">{{ $enquiry->phone ?: __('Not provided') }}</div>
                                                            </div>
                                                        </div>
                                                        @if(!empty($enquiry->subject))
                                                            <div class="mb-3">
                                                                <label class="fs-11 text-uppercase text-muted fw-bold d-block">{{ __('Subject') }}</label>
                                                                <div class="fs-14 fw-semibold text-dark">{{ $enquiry->subject }}</div>
                                                            </div>
                                                        @endif
                                                        <div class="mb-3">
                                                            <label class="fs-11 text-uppercase text-muted fw-bold d-block">{{ __('Message') }}</label>
                                                            <div class="bg-light p-3 rounded-3 text-dark fs-13 border leading-relaxed" style="white-space: pre-wrap;">{{ $enquiry->message }}</div>
                                                        </div>
                                                        <div class="row g-2 text-muted fs-11">
                                                            <div class="col-6">
                                                                <span>{{ __('IP Address:') }}</span> {{ $enquiry->ip_address ?: 'N/A' }}
                                                            </div>
                                                            <div class="col-6 text-end">
                                                                <span>{{ __('Submitted:') }}</span> {{ !empty($enquiry->created) ? date('d M Y, h:i A', $enquiry->created) : date('d M Y, h:i A', strtotime($enquiry->created_at ?? 'now')) }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top bg-light">
                                                        @if(!empty($enquiry->phone))
                                                            @php
                                                                $cleanPhone = preg_replace('/[^0-9]/', '', $enquiry->phone);
                                                            @endphp
                                                            <a href="https://wa.me/{{ $cleanPhone }}" target="_blank" class="btn btn-sm btn-success rounded-pill px-3">
                                                                <i class="fa-brands fa-whatsapp me-1"></i> {{ __('Reply on WhatsApp') }}
                                                            </a>
                                                        @endif
                                                        <a href="mailto:{{ $enquiry->email }}?subject={{ urlencode('Re: ' . ($enquiry->subject ?: 'Enquiry from Whatsappbulk')) }}" class="btn btn-sm btn-primary rounded-pill px-3">
                                                            <i class="fa-light fa-paper-plane me-1"></i> {{ __('Reply by Email') }}
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">{{ __('Close') }}</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <div class="mb-2"><i class="fa-light fa-inbox fs-36 text-slate-300"></i></div>
                                        <div class="fw-bold">{{ __('No enquiries found') }}</div>
                                        <div class="fs-12">{{ __('Customer enquiries submitted from the contact form will appear here.') }}</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($enquiries->hasPages())
                    <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3">
                        <div class="fs-12 text-muted">
                            {{ __('Showing') }} {{ $enquiries->firstItem() }} {{ __('to') }} {{ $enquiries->lastItem() }} {{ __('of') }} {{ $enquiries->total() }} {{ __('entries') }}
                        </div>
                        <div>
                            {{ $enquiries->links() }}
                        </div>
                    </div>
                @endif
            </div>

        </div>

        {{-- ========================================================== --}}
        {{-- TAB 2: LOCATION & CONTACT DETAILS (Website, Email, Phone)  --}}
        {{-- ========================================================== --}}
        <div class="tab-pane fade" id="tab-location-content" role="tabpanel">
            <div class="card border shadow-xs rounded-3 bg-white mb-4">
                <div class="card-header bg-light py-3">
                    <div class="fw-bold fs-14 text-dark">
                        <i class="fa-light fa-map-location-dot me-1 text-primary"></i> {{ __('Public Contact Information & Location') }}
                    </div>
                    <div class="fs-12 text-muted">{{ __('These details will display across your contact page containers in a fully responsive layout.') }}</div>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.contact.settings') }}">
                        @csrf
                        
                        <div class="row g-4">
                            <!-- Website URL -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold fs-13">{{ __('Company Website URL') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-light fa-globe"></i></span>
                                    <input type="text" class="form-control" name="contact_company_website" value="{{ get_option('contact_company_website', 'https://yourcompany.com') }}" placeholder="https://yourcompany.com">
                                </div>
                            </div>

                            <!-- Email Address -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold fs-13">{{ __('Support / Contact Email') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-light fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="contact_email" value="{{ get_option('contact_email', 'support@yourcompany.com') }}" placeholder="support@yourcompany.com">
                                </div>
                            </div>

                            <!-- Phone Number -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold fs-13">{{ __('Phone Number / WhatsApp') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-light fa-phone"></i></span>
                                    <input type="text" class="form-control" name="contact_phone_number" value="{{ get_option('contact_phone_number', '+1 234 567 890') }}" placeholder="+1 234 567 890">
                                </div>
                            </div>

                            <!-- Working Hours -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold fs-13">{{ __('Working Hours') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-light fa-clock"></i></span>
                                    <input type="text" class="form-control" name="contact_working_hours" value="{{ get_option('contact_working_hours', 'Mon - Fri: 09:00 AM - 06:00 PM') }}" placeholder="Mon - Fri: 09:00 AM - 06:00 PM">
                                </div>
                            </div>

                            <!-- Address Location -->
                            <div class="col-12">
                                <label class="form-label fw-bold fs-13">{{ __('Office Address / Physical Location') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="fa-light fa-location-dot"></i></span>
                                    <input type="text" class="form-control" name="contact_location" value="{{ get_option('contact_location', '123 Main Street, City, Country') }}" placeholder="123 Main Street, City, Country">
                                </div>
                            </div>

                            <!-- Optional Map Embed URL / Iframe -->
                            <div class="col-12">
                                <label class="form-label fw-bold fs-13">{{ __('Google Maps Embed Iframe or URL (Optional)') }}</label>
                                <textarea class="form-control" name="contact_map_iframe" rows="3" placeholder="https://maps.google.com/... or <iframe>...</iframe>">{{ get_option('contact_map_iframe', '') }}</textarea>
                                <div class="fs-11 text-muted mt-1">{{ __('Paste either Google Maps Embed iframe code or embed URL to display a live map container on the contact page.') }}</div>
                            </div>

                            <!-- Contact Page Title & Subtitle -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold fs-13">{{ __('Page Header Title') }}</label>
                                <input type="text" class="form-control" name="contact_page_title" value="{{ get_option('contact_page_title', 'Talk to the team behind your WhatsApp workflow') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold fs-13">{{ __('Page Header Subtitle') }}</label>
                                <input type="text" class="form-control" name="contact_page_desc" value="{{ get_option('contact_page_desc', 'Reach out for onboarding help, pricing questions, infrastructure guidance, or support with a production WhatsApp setup.') }}">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn text-white px-5 py-2.5 rounded-pill fw-bold shadow-sm" style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%);">
                                <i class="fa-light fa-floppy-disk me-1"></i> {{ __('Save Location & Contact Details') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- TAB 3: RECAPTCHA KEYS & BOT PROTECTION CONFIGURATION       --}}
        {{-- ========================================================== --}}
        <div class="tab-pane fade" id="tab-recaptcha-content" role="tabpanel">
            <div class="card border shadow-xs rounded-3 bg-white mb-4">
                <div class="card-header bg-light py-3">
                    <div class="fw-bold fs-14 text-dark">
                        <i class="fa-light fa-shield-halved me-1 text-warning"></i> {{ __('Google reCAPTCHA v2 Verification') }}
                    </div>
                    <div class="fs-12 text-muted">{{ __('Protect your contact enquiry form against spam bots and automated attacks.') }}</div>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('admin.contact.settings') }}">
                        @csrf

                        <!-- Enable/Disable Status -->
                        <div class="mb-4">
                            <label class="form-label fw-bold fs-13 d-block">{{ __('reCAPTCHA Status for Contact Form') }}</label>
                            @php
                                $recaptchaStatus = get_option('contact_recaptcha_status', get_option('auth_google_recaptcha_status', 0));
                            @endphp
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="contact_recaptcha_status" id="recaptcha_enable" value="1" {{ $recaptchaStatus == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label fw-6" for="recaptcha_enable">
                                        <i class="fa-solid fa-circle-check text-success me-1"></i> {{ __('Enabled') }}
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="contact_recaptcha_status" id="recaptcha_disable" value="0" {{ $recaptchaStatus == 0 ? 'checked' : '' }}>
                                    <label class="form-check-label fw-6" for="recaptcha_disable">
                                        <i class="fa-solid fa-circle-xmark text-muted me-1"></i> {{ __('Disabled') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <!-- Site Key -->
                            <div class="col-12">
                                <label class="form-label fw-bold fs-13">{{ __('Google reCAPTCHA v2 Site Key') }}</label>
                                <input type="text" class="form-control" name="contact_recaptcha_site_key" value="{{ get_option('contact_recaptcha_site_key', get_option('auth_google_recaptcha_site_key', '')) }}" placeholder="e.g. 6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI">
                                <div class="fs-11 text-muted mt-1">{{ __('Public client-side site key generated in your Google reCAPTCHA v2 (Checkbox) admin console.') }}</div>
                            </div>

                            <!-- Secret Key -->
                            <div class="col-12">
                                <label class="form-label fw-bold fs-13">{{ __('Google reCAPTCHA v2 Secret Key') }}</label>
                                <input type="text" class="form-control" name="contact_recaptcha_secret_key" value="{{ get_option('contact_recaptcha_secret_key', get_option('auth_google_recaptcha_secret_key', '')) }}" placeholder="e.g. 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe">
                                <div class="fs-11 text-muted mt-1">{{ __('Secret API key for verifying responses securely on your server.') }}</div>
                            </div>
                        </div>

                        <!-- Instructions Banner -->
                        <div class="p-3 bg-light rounded-3 border mt-4">
                            <div class="d-flex gap-2">
                                <i class="fa-light fa-circle-info text-primary fs-18 shrink-0 mt-0.5"></i>
                                <div class="fs-12 text-muted leading-relaxed">
                                    <strong class="text-dark">{{ __('How to get your reCAPTCHA keys:') }}</strong><br>
                                    1. Visit the <a href="https://www.google.com/recaptcha/admin" target="_blank" class="text-primary fw-bold">Google reCAPTCHA Admin Console <i class="fa-light fa-arrow-up-right-from-square"></i></a>.<br>
                                    2. Register a new site, choose <strong>reCAPTCHA v2 ("I'm not a robot" Checkbox)</strong>.<br>
                                    3. Add your domain(s) (e.g., <code>{{ request()->getHost() }}</code>).<br>
                                    4. Copy the Site Key and Secret Key into the fields above and click Save.
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn text-white px-5 py-2.5 rounded-pill fw-bold shadow-sm" style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%);">
                                <i class="fa-light fa-shield-check me-1"></i> {{ __('Save reCAPTCHA Settings') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
