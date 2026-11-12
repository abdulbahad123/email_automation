@extends('layouts.app')

@section('sub_header')
    <x-sub-header
        title="{{ __('Landing Page Settings') }}"
        description="{{ __('Manage and customize every section of your public WhatsApp Bulk marketing page with instant live updates') }}"
    >
    </x-sub-header>
@endsection

@section('content')

<div class="container max-w-1000 pb-5">
    <form class="actionForm" action="{{ route('admin.landing-page.save') }}" method="POST" enctype="multipart/form-data" id="landingPageForm">
        @csrf
@php
    $cleanLandingVal = function($val) {
        if (empty($val) || !is_string($val)) return $val;
        $val = preg_replace('/automation(kkr|kr|sk|sr|ff|f|k)+/i', 'automation', $val);
        $val = preg_replace('/reports(kkr|kr|sk|sr|ff|f|k)*(\.|\b)/i', 'reports$2', $val);
        $val = preg_replace('/reporting(kkr|kr|sk|sr|ff|f|k)*(\.|\b)/i', 'reporting$2', $val);
        $val = preg_replace('/workspace(kkr|kr|sk|sr|ff|f|k)+/i', 'workspace', $val);
        $val = preg_replace('/modules(r|kr|sk|sr|ff|f|k)+/i', 'modules', $val);
        $val = preg_replace('/coverage(kkr|kr|sk|sr|ff|f|k)+/i', 'coverage', $val);
        $val = preg_replace('/workflow(kkr|kr|sk|sr|ff|f|k)+/i', 'workflow', $val);
        $val = preg_replace('/TOOL(kkr|kr|sk|sr|ff|f|k)+/i', 'TOOL', $val);
        $val = preg_replace('/(\w+)\.(k|kr|sk|kkr|sr|f|ff)\s*$/i', '$1.', $val);
        $val = preg_replace('/([a-zA-Z]{3,})(kkr|sk|kr|sr|ff)(\s*<|\s*$)/i', '$1$3', $val);
        $val = preg_replace('/\b(defaull|default)\s+launching\b/i', '', $val);
        return trim($val);
    };

    $adminHeroTitle = $cleanLandingVal(get_option('landing_hero_title', "WhatsApp operations,<br><span class=\"text-[#059669]\">campaigns,</span> and<br>automation"));
    $adminHeroTitle = str_replace(
        ['#ff5500', '#f97316', 'text-orange-500', 'color: #ff5500', 'color:#ff5500', 'color: #f97316', 'color:#f97316'],
        ['#059669', '#059669', 'text-[#059669]', 'color: #059669', 'color:#059669', 'color: #059669', 'color:#059669'],
        $adminHeroTitle
    );

    $adminStackTitle = $cleanLandingVal(get_option('landing_stack_title', 'Whatsappbulk <span class="text-[#059669]">Workspace</span>'));
    $adminStackTitle = str_replace(
        ['#ff5500', '#f97316', 'text-orange-500', 'color: #ff5500', 'color:#ff5500', 'color: #f97316', 'color:#f97316'],
        ['#059669', '#059669', 'text-[#059669]', 'color: #059669', 'color:#059669', 'color: #059669', 'color:#059669'],
        $adminStackTitle
    );

    $adminStackSubtitle = $cleanLandingVal(get_option('landing_stack_subtitle', 'Everything you need to run WhatsApp at scale'));
    if ($adminStackSubtitle === 'Product overview' || empty($adminStackSubtitle)) {
        $adminStackSubtitle = 'Everything you need to run WhatsApp at scale';
    }
@endphp

        {{-- Nav Tabs --}}
        <ul class="nav nav-pills mb-4 bg-white p-2 rounded-3 border gap-2 shadow-xs" id="landingTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-6 fs-13" id="tab-hero" data-bs-toggle="pill" data-bs-target="#content-hero" type="button" role="tab">
                    <i class="fa-light fa-sparkles me-1 text-success"></i> {{ __('Hero & Stats') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-6 fs-13" id="tab-stack" data-bs-toggle="pill" data-bs-target="#content-stack" type="button" role="tab">
                    <i class="fa-light fa-layer-group me-1 text-primary"></i> {{ __('Live Module Stack') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-6 fs-13" id="tab-modules" data-bs-toggle="pill" data-bs-target="#content-modules" type="button" role="tab">
                    <i class="fa-light fa-grid-2 me-1 text-warning"></i> {{ __('8 Product Modules') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-6 fs-13" id="tab-pricing" data-bs-toggle="pill" data-bs-target="#content-pricing" type="button" role="tab">
                    <i class="fa-light fa-tags me-1 text-info"></i> {{ __('Pricing Section') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-6 fs-13" id="tab-testi" data-bs-toggle="pill" data-bs-target="#content-testi" type="button" role="tab">
                    <i class="fa-light fa-comments me-1 text-danger"></i> {{ __('Testimonials') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-6 fs-13" id="tab-faqs" data-bs-toggle="pill" data-bs-target="#content-faqs" type="button" role="tab">
                    <i class="fa-light fa-circle-question me-1 text-secondary"></i> {{ __('FAQs') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-6 fs-13" id="tab-cta" data-bs-toggle="pill" data-bs-target="#content-cta" type="button" role="tab">
                    <i class="fa-light fa-bullhorn me-1 text-success"></i> {{ __('Bottom CTA & Footer') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-6 fs-13" id="tab-contact" data-bs-toggle="pill" data-bs-target="#content-contact" type="button" role="tab">
                    <i class="fa-light fa-envelope-open-text me-1 text-primary"></i> {{ __('Contact Us & Location') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-6 fs-13" id="tab-otp-notice" data-bs-toggle="pill" data-bs-target="#content-otp-notice" type="button" role="tab">
                    <i class="fa-brands fa-whatsapp me-1 text-success"></i> {{ __('OTP Notice Box') }}
                </button>
            </li>
        </ul>

        {{-- Tab Content Panes --}}
        <div class="tab-content" id="landingTabsContent">
            
            {{-- TAB 1: HERO & METRICS --}}
            <div class="tab-pane fade show active" id="content-hero" role="tabpanel">
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-heading me-1 text-success"></i> {{ __('Hero Section Content') }}</div>
                    </div>
                    <div class="card-body">
@php
    $adminHeroBadge = get_option('landing_hero_badge', 'ALL-IN-ONE WHATSAPP MARKETING TOOL');
    $adminHeroBadge = trim(strip_tags((string)$adminHeroBadge));
    $adminHeroBadge = rtrim($adminHeroBadge, '<> ');
    if (preg_match('/toolk$/i', $adminHeroBadge) || preg_match('/tool<$/i', $adminHeroBadge)) {
        $adminHeroBadge = preg_replace('/tool[k<]$/i', 'TOOL', $adminHeroBadge);
    }
    if (empty($adminHeroBadge)) {
        $adminHeroBadge = 'ALL-IN-ONE WHATSAPP MARKETING TOOL';
    }
@endphp
                        <div class="mb-3">
                            <label class="form-label fw-6 fs-13">{{ __('Top Pill Badge') }}</label>
                            <input type="text" class="form-control" name="landing_hero_badge" value="{{ $adminHeroBadge }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-6 fs-13">{{ __('Main Headline (Supports HTML span for green highlight)') }}</label>
                            <textarea class="form-control" name="landing_hero_title" rows="2">{{ $adminHeroTitle }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-6 fs-13">{{ __('Bold Subtitle') }}</label>
                            <input type="text" class="form-control" name="landing_hero_subtitle_bold" value="{{ get_option('landing_hero_subtitle_bold', 'One WhatsApp workspace for campaigns, replies, bots, contacts, and reports.') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-6 fs-13">{{ __('Descriptive Paragraph') }}</label>
                            <textarea class="form-control" name="landing_hero_subtitle_desc" rows="2">{{ get_option('landing_hero_subtitle_desc', 'Built around the modules teams actually use every day: bulk outreach, smart replies, auto replies, chatbots, contacts, participant export, profile lookups, and reporting.') }}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Primary CTA Button Text') }}</label>
                                <input type="text" class="form-control" name="landing_hero_btn1_text" value="{{ get_option('landing_hero_btn1_text', 'Create your workspace') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Primary CTA Button URL') }}</label>
                                <input type="text" class="form-control" name="landing_hero_btn1_url" value="{{ get_option('landing_hero_btn1_url', 'auth/signup') }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Secondary CTA Button Text') }}</label>
                                <input type="text" class="form-control" name="landing_hero_btn2_text" value="{{ get_option('landing_hero_btn2_text', 'Explore modules') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Secondary CTA Button URL') }}</label>
                                <input type="text" class="form-control" name="landing_hero_btn2_url" value="{{ get_option('landing_hero_btn2_url', '#modules') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Metric Highlights --}}
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-chart-line me-1 text-success"></i> {{ __('3 Hero Metric Cards') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-12">{{ __('Card 1 Icon (FontAwesome)') }}</label>
                                <input type="text" class="form-control mb-1" name="landing_stat1_icon" value="{{ get_option('landing_stat1_icon', 'fas fa-cube') }}">
                                <label class="form-label fw-6 fs-12">{{ __('Card 1 Number') }}</label>
                                <input type="text" class="form-control mb-1" name="landing_stat1_number" value="{{ get_option('landing_stat1_number', '8') }}">
                                <label class="form-label fw-6 fs-12">{{ __('Card 1 Label') }}</label>
                                <input type="text" class="form-control" name="landing_stat1_label" value="{{ get_option('landing_stat1_label', 'Operational modules included') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-12">{{ __('Card 2 Icon (FontAwesome)') }}</label>
                                <input type="text" class="form-control mb-1" name="landing_stat2_icon" value="{{ get_option('landing_stat2_icon', 'fas fa-link') }}">
                                <label class="form-label fw-6 fs-12">{{ __('Card 2 Number') }}</label>
                                <input type="text" class="form-control mb-1" name="landing_stat2_number" value="{{ get_option('landing_stat2_number', '1') }}">
                                <label class="form-label fw-6 fs-12">{{ __('Card 2 Label') }}</label>
                                <input type="text" class="form-control" name="landing_stat2_label" value="{{ get_option('landing_stat2_label', 'Unified WhatsApp workflow') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-12">{{ __('Card 3 Icon (FontAwesome)') }}</label>
                                <input type="text" class="form-control mb-1" name="landing_stat3_icon" value="{{ get_option('landing_stat3_icon', 'fas fa-bolt') }}">
                                <label class="form-label fw-6 fs-12">{{ __('Card 3 Number') }}</label>
                                <input type="text" class="form-control mb-1" name="landing_stat3_number" value="{{ get_option('landing_stat3_number', '24/7') }}">
                                <label class="form-label fw-6 fs-12">{{ __('Card 3 Label') }}</label>
                                <input type="text" class="form-control" name="landing_stat3_label" value="{{ get_option('landing_stat3_label', 'Automation & reply coverage') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Dashboard & Phone Preview Controls --}}
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-display me-1 text-success"></i> {{ __('Dashboard & Smartphone Live Preview Controls') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-12">{{ __('Dashboard User Display') }}</label>
                                <input type="text" class="form-control" name="landing_dash_user" value="{{ get_option('landing_dash_user', 'HI, Admin') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-12">{{ __('Performance Chart Title') }}</label>
                                <input type="text" class="form-control" name="landing_dash_chart_title" value="{{ get_option('landing_dash_chart_title', 'Message Performance') }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-6 fs-12">{{ __('Total Messages Value') }}</label>
                                <input type="text" class="form-control mb-1" name="landing_dash_m1_val" value="{{ get_option('landing_dash_m1_val', '125,890') }}">
                                <input type="text" class="form-control text-muted fs-11" name="landing_dash_m1_trend" value="{{ get_option('landing_dash_m1_trend', '▲ 20.4%') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-6 fs-12">{{ __('Delivered Value') }}</label>
                                <input type="text" class="form-control mb-1" name="landing_dash_m2_val" value="{{ get_option('landing_dash_m2_val', '118,420') }}">
                                <input type="text" class="form-control text-muted fs-11" name="landing_dash_m2_trend" value="{{ get_option('landing_dash_m2_trend', '▲ 16.1%') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-6 fs-12">{{ __('Replied Value') }}</label>
                                <input type="text" class="form-control mb-1" name="landing_dash_m3_val" value="{{ get_option('landing_dash_m3_val', '28,460') }}">
                                <input type="text" class="form-control text-muted fs-11" name="landing_dash_m3_trend" value="{{ get_option('landing_dash_m3_trend', '▲ 14.2%') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label fw-6 fs-12">{{ __('Active Contacts Value') }}</label>
                                <input type="text" class="form-control mb-1" name="landing_dash_m4_val" value="{{ get_option('landing_dash_m4_val', '96,350') }}">
                                <input type="text" class="form-control text-muted fs-11" name="landing_dash_m4_trend" value="{{ get_option('landing_dash_m4_trend', '▲ 12.6%') }}">
                            </div>
                        </div>
                        <div class="row border-top pt-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-12">{{ __('Smartphone Preview Heading') }}</label>
                                <input type="text" class="form-control" name="landing_phone_title" value="{{ get_option('landing_phone_title', 'Campaign Overview') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-12">{{ __('Smartphone Performance Percentage') }}</label>
                                <input type="text" class="form-control" name="landing_phone_percent" value="{{ get_option('landing_phone_percent', '95.8%') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: LIVE MODULE STACK (4 Cards) --}}
            <div class="tab-pane fade" id="content-stack" role="tabpanel">
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-layer-group me-1 text-primary"></i> {{ __('Section Headings') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Badge') }}</label>
                                <input type="text" class="form-control" name="landing_stack_badge" value="{{ get_option('landing_stack_badge', 'LIVE MODULE STACK') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Title') }}</label>
                                <input type="text" class="form-control" name="landing_stack_title" value="{{ $adminStackTitle }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Subtitle') }}</label>
                                <input type="text" class="form-control" name="landing_stack_subtitle" value="{{ $adminStackSubtitle }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    @for($i = 1; $i <= 4; $i++)
                        @php
                            $tags = [1 => 'CAMPAIGN CONTROL', 2 => 'INBOUND HANDLING', 3 => 'LEAD FLOW', 4 => 'VISIBILITY'];
                            $titles = [1 => 'Bulk Campaigns', 2 => 'AI + Auto Reply', 3 => 'Chatbot + Contacts', 4 => 'Reports + Export'];
                            $descs = [
                                1 => 'Segment contacts, send at scale, and track response flow.',
                                2 => 'Combine automation with faster human response quality.',
                                3 => 'Capture, qualify, and manage audience data in one place.',
                                4 => 'Export participants and measure what the team actually ships.'
                            ];
                        @endphp
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-none border h-100">
                                <div class="card-header bg-light py-2">
                                    <span class="fw-bold fs-13">Card {{ $i }}: {{ $titles[$i] }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <label class="form-label fw-6 fs-12">{{ __('Tag') }}</label>
                                        <input type="text" class="form-control" name="landing_stack{{ $i }}_tag" value="{{ get_option('landing_stack'.$i.'_tag', $tags[$i]) }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fw-6 fs-12">{{ __('Title') }}</label>
                                        <input type="text" class="form-control" name="landing_stack{{ $i }}_title" value="{{ get_option('landing_stack'.$i.'_title', $titles[$i]) }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fw-6 fs-12">{{ __('Description') }}</label>
                                        <textarea class="form-control" name="landing_stack{{ $i }}_desc" rows="2">{{ get_option('landing_stack'.$i.'_desc', $descs[$i]) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- TAB 3: 8 PRODUCT MODULES --}}
            <div class="tab-pane fade" id="content-modules" role="tabpanel">
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-cubes me-1 text-warning"></i> {{ __('Product Modules Section Headings') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Badge') }}</label>
                                <input type="text" class="form-control" name="landing_modules_badge" value="{{ get_option('landing_modules_badge', 'PRODUCT MODULES') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Title') }}</label>
                                <input type="text" class="form-control" name="landing_modules_title" value="{{ get_option('landing_modules_title', 'Built from the <span class=\"text-[#059669]\">WhatsApp features</span> teams actually need') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Subtitle') }}</label>
                                <input type="text" class="form-control" name="landing_modules_subtitle" value="{{ get_option('landing_modules_subtitle', 'Every module supports a real operational job, from outreach and automation to contact management and reporting.') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    @php
                        $modDefaults = [
                            1 => ['AI Smart Reply', 'Generate fast, context-aware WhatsApp replies so agents can handle conversations with less manual effort.'],
                            2 => ['Auto Reply', 'Trigger automatic responses for inbound messages, off-hours support, lead capture, and common request handling.'],
                            3 => ['Bulk Campaigns', 'Launch high-volume WhatsApp campaigns to segmented audiences while keeping timing and response flow under control.'],
                            4 => ['Chatbot', 'Build conversational flows that qualify leads, answer questions, and move contacts into the next step automatically.'],
                            5 => ['Contacts', 'Manage contact lists, segments, and campaign targets from one structured database.'],
                            6 => ['Export Participants', 'Extract participants from WhatsApp groups for outreach, qualification, migration, and audience building workflows.'],
                            7 => ['Profile Info', 'Retrieve WhatsApp profile information quickly to enrich lead context and improve handoff quality.'],
                            8 => ['Reports', 'Track campaign output, reply activity, and workflow performance with clear reporting for real operational decisions.']
                        ];
                    @endphp
                    @foreach($modDefaults as $idx => $m)
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-none border h-100">
                                <div class="card-header bg-light py-2">
                                    <span class="fw-bold fs-13">Module {{ $idx }}: {{ $m[0] }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <label class="form-label fw-6 fs-12">{{ __('Title') }}</label>
                                        <input type="text" class="form-control" name="landing_mod{{ $idx }}_title" value="{{ get_option('landing_mod'.$idx.'_title', $m[0]) }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fw-6 fs-12">{{ __('Description') }}</label>
                                        <textarea class="form-control" name="landing_mod{{ $idx }}_desc" rows="2">{{ get_option('landing_mod'.$idx.'_desc', $m[1]) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- TAB 4: PRICING (4 Tiers & Left/Right Split Layout) --}}
            <div class="tab-pane fade" id="content-pricing" role="tabpanel">
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-tags me-1 text-info"></i> {{ __('Pricing Section Headings') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Section Badge') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_badge" value="{{ get_option('landing_pricing_badge', 'PRICING') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Section Title') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_title" value="{{ get_option('landing_pricing_title', 'Simple and transparent pricing') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Section Subtitle') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_subtitle" value="{{ get_option('landing_pricing_subtitle', 'Choose the plan that fits your business needs. Upgrade or downgrade anytime.') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Left Side Split Panel (WhatsAppBulk Overview & Capabilities) --}}
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-sparkles me-1 text-success"></i> {{ __('Left Panel: WhatsAppBulk Plan Overview & Capabilities') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Left Pre-Title Badge') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_left_badge" value="{{ get_option('landing_pricing_left_badge', '✨ WHY CHOOSE WHATSAPPBULK') }}">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Left Main Heading') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_left_title" value="{{ get_option('landing_pricing_left_title', 'Autonomous WhatsApp Marketing & AI Automation Suite') }}">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Left Description Paragraph') }}</label>
                                <textarea class="form-control" rows="2" name="landing_pricing_left_desc">{{ get_option('landing_pricing_left_desc', 'Broadcast targeted bulk campaigns, automate conversational sales with AI chatbots, and engage your customers 24/7 with zero risk of bans.') }}</textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Capability Highlight 1') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_left_feat1" value="{{ get_option('landing_pricing_left_feat1', 'Anti-Ban Protection & Safe Delays: Smart interval scheduling keeps your numbers safe') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Capability Highlight 2') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_left_feat2" value="{{ get_option('landing_pricing_left_feat2', 'AI Smart Responder & Chatbot Flows: Instant contextual replies to customer queries') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Capability Highlight 3') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_left_feat3" value="{{ get_option('landing_pricing_left_feat3', 'Broadcast & Bulk Campaigns: Send rich media, buttons, and documents effortlessly') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Capability Highlight 4') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_left_feat4" value="{{ get_option('landing_pricing_left_feat4', 'Detailed Analytics & Contact Management: Track delivery rates and export participants') }}">
                            </div>
                        </div>

                        {{-- Statistics Row --}}
                        <div class="border-top pt-3 mt-2">
                            <div class="fw-6 fs-14 text-gray-700 mb-2">{{ __('Trust Statistics') }}</div>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="landing_pricing_stat1_val" value="{{ get_option('landing_pricing_stat1_val', '99.8%') }}" placeholder="Value (e.g. 99.8%)">
                                        <input type="text" class="form-control" name="landing_pricing_stat1_lbl" value="{{ get_option('landing_pricing_stat1_lbl', 'Inbox Delivery') }}" placeholder="Label">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="landing_pricing_stat2_val" value="{{ get_option('landing_pricing_stat2_val', '10M+') }}" placeholder="Value (e.g. 10M+)">
                                        <input type="text" class="form-control" name="landing_pricing_stat2_lbl" value="{{ get_option('landing_pricing_stat2_lbl', 'Messages Sent') }}" placeholder="Label">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="landing_pricing_stat3_val" value="{{ get_option('landing_pricing_stat3_val', '24/7') }}" placeholder="Value (e.g. 24/7)">
                                        <input type="text" class="form-control" name="landing_pricing_stat3_lbl" value="{{ get_option('landing_pricing_stat3_lbl', 'Auto Responder') }}" placeholder="Label">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Dynamic Floating Badges / Elements --}}
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-badge-check me-1 text-primary"></i> {{ __('Dynamic Floating Badges & Pills') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Floating Badge 1 (Left)') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_float1" value="{{ get_option('landing_pricing_float1', '⚡ 99.8% msg Delivery') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Floating Badge 2 (Left)') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_float2" value="{{ get_option('landing_pricing_float2', '🤖 24/7 support') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Floating Badge 3 (Right)') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_float3" value="{{ get_option('landing_pricing_float3', '🔒 Anti-Ban Protection Shield') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Floating Badge 4 (Right)') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_float4" value="{{ get_option('landing_pricing_float4', '📈 5x Higher Open Rates') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Floating Badge 5 (Right)') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_float5" value="{{ get_option('landing_pricing_float5', '🎯 WhatsApp Bulk Campaigns') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Side Split Panel (Plan Advantages & Guarantees) --}}
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-shield-check me-1 text-primary"></i> {{ __('Right Panel: Plan Advantages & Guarantees') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Right Pre-Title Badge') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_right_badge" value="{{ get_option('landing_pricing_right_badge', '✨ PLAN ADVANTAGES & ROI') }}">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Right Main Heading') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_right_title" value="{{ get_option('landing_pricing_right_title', 'Scale Conversations With Zero Risk') }}">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Right Description Paragraph') }}</label>
                                <textarea class="form-control" rows="2" name="landing_pricing_right_desc">{{ get_option('landing_pricing_right_desc', 'Everything you need to automate communications, boost customer retention, and close more sales with WhatsApp.') }}</textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Advantage Highlight 1') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_right_feat1" value="{{ get_option('landing_pricing_right_feat1', '⚡ Instant Onboarding: Get set up and broadcasting within 2 minutes.') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Advantage Highlight 2') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_right_feat2" value="{{ get_option('landing_pricing_right_feat2', '🔒 Enterprise Safe Protocol: Humanized pacing prevents account flagging.') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Advantage Highlight 3') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_right_feat3" value="{{ get_option('landing_pricing_right_feat3', '🔄 API & Webhook Hooks: Integrate with your website.') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Advantage Highlight 4') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_right_feat4" value="{{ get_option('landing_pricing_right_feat4', '📞 24/7 Priority Support: Direct WhatsApp and ticket support whenever you need.') }}">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Trust Guarantee Note') }}</label>
                                <input type="text" class="form-control" name="landing_pricing_right_trust" value="{{ get_option('landing_pricing_right_trust', '🛡️ 100% Risk-Free Guarantee') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-none border bg-light-success border-success mb-3">
                    <div class="card-body p-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <h6 class="fw-bold text-success mb-1">
                                <i class="fa-solid fa-layer-group me-1"></i> {{ __('Dynamic Plans & Recurring Pricing Active') }}
                            </h6>
                            <p class="fs-12 text-muted mb-0">
                                {{ __('All subscription tiers, pricing (Monthly/Yearly), and feature quotas are managed dynamically in Plan Management. Any changes made there update the public landing page and signup checkout automatically.') }}
                            </p>
                        </div>
                        <a href="{{ url('admin/plans') }}" class="btn btn-sm btn-success fw-bold px-3 py-2">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> {{ __('Manage Plans in Plan Management') }}
                        </a>
                    </div>
                </div>
            </div>

            {{-- TAB 5: TESTIMONIALS (Dynamic, Max 40 Limit, Ratings & Avatar Images) --}}
            <div class="tab-pane fade" id="content-testi" role="tabpanel">
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-comments me-1 text-danger"></i> {{ __('Testimonials Headings') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Badge') }}</label>
                                <input type="text" class="form-control" name="landing_testi_badge" value="{{ get_option('landing_testi_badge', 'TESTIMONIALS') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Title') }}</label>
                                <input type="text" class="form-control" name="landing_testi_title" value="{{ get_option('landing_testi_title', 'What our customers say') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Subtitle') }}</label>
                                <input type="text" class="form-control" name="landing_testi_subtitle" value="{{ get_option('landing_testi_subtitle', 'Trusted by businesses worldwide to streamline their WhatsApp operations.') }}">
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $rawTestiData = get_option('landing_testimonials_data', null);
                    $adminTestimonials = [];
                    if (!empty($rawTestiData)) {
                        $parsed = is_string($rawTestiData) ? json_decode($rawTestiData, true) : $rawTestiData;
                        if (is_array($parsed) && count($parsed) > 0) {
                            $adminTestimonials = $parsed;
                        }
                    }
                    if (empty($adminTestimonials)) {
                        $adminTestimonials = [
                            [
                                'name' => get_option('landing_testi1_name', 'Priya Sharma'),
                                'role' => get_option('landing_testi1_role', 'Marketing Manager, RetailHub'),
                                'text' => get_option('landing_testi1_text', 'Whatsappbulk has completely transformed how we handle customer communication. Our response time improved by 70%!'),
                                'rating' => (int)get_option('landing_testi1_rating', 5),
                                'img' => get_option('landing_testi1_img', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120&auto=format&fit=crop&q=80')
                            ],
                            [
                                'name' => get_option('landing_testi2_name', 'Rahul Mehta'),
                                'role' => get_option('landing_testi2_role', 'Founder, GrowKart'),
                                'text' => get_option('landing_testi2_text', 'The bulk campaign feature is a game-changer. We can now reach thousands of customers in minutes with excellent delivery rates.'),
                                'rating' => (int)get_option('landing_testi2_rating', 5),
                                'img' => get_option('landing_testi2_img', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80')
                            ],
                            [
                                'name' => get_option('landing_testi3_name', 'Sneha Patel'),
                                'role' => get_option('landing_testi3_role', 'Operations Head, Foodies'),
                                'text' => get_option('landing_testi3_text', 'Easy to use, powerful features, and great support. Highly recommended for any business using WhatsApp.'),
                                'rating' => (int)get_option('landing_testi3_rating', 5),
                                'img' => get_option('landing_testi3_img', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=120&auto=format&fit=crop&q=80')
                            ],
                            [
                                'name' => get_option('landing_testi4_name', 'Vikram Malhotra'),
                                'role' => get_option('landing_testi4_role', 'E-commerce Director, UrbanStyle'),
                                'text' => get_option('landing_testi4_text', 'Whatsappbulk automated our entire client follow-up workflow. We closed 35% more orders in our very first week with instant replies.'),
                                'rating' => (int)get_option('landing_testi4_rating', 5),
                                'img' => get_option('landing_testi4_img', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80')
                            ]
                        ];
                    }
                    $adminTestimonials = array_slice($adminTestimonials, 0, 40);
                    $currentTestiCount = count($adminTestimonials);
                @endphp

                <!-- Top Testimonials Manager Action Bar -->
                <div class="card shadow-none border mb-3">
                    <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold fs-14 text-dark"><i class="fa-solid fa-users-viewfinder me-1 text-primary"></i> {{ __('Customer Reviews') }}</span>
                            <span id="testiCounterBadge" class="badge bg-primary px-3 py-1.5 fs-12 rounded-pill">
                                <span id="testiCountDisplay">{{ $currentTestiCount }}</span> / 40 Testimonials
                            </span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-success px-3 fw-bold" id="btnAddNewTestimonial" {{ $currentTestiCount >= 40 ? 'disabled' : '' }}>
                                <i class="fa-solid fa-plus me-1"></i> {{ __('Add Testimonial') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 40 Testimonials Limit Warning Banner -->
                <div id="testiLimitReachedAlert" class="alert alert-warning py-2.5 px-3 mb-3 d-flex align-items-center justify-content-between shadow-xs" style="display: {{ $currentTestiCount >= 40 ? 'flex' : 'none' }} !important;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-warning fs-18"></i>
                        <span class="fs-13 fw-semibold">
                            <strong>{{ __('Maximum Limit Reached:') }}</strong> {{ __('You have added 40 testimonials. The system limit is 40. Please remove an existing review if you want to add a new one.') }}
                        </span>
                    </div>
                    <span class="badge bg-dark text-white px-2 py-1 fs-11">40 Max</span>
                </div>

                <!-- Testimonials Grid Container -->
                <div class="row" id="testimonialsListGrid">
                    @foreach($adminTestimonials as $tidx => $testi)
                        @php
                            $cIdx = $tidx + 1;
                            $tRating = isset($testi['rating']) ? (int)$testi['rating'] : 5;
                            $tImg = $testi['img'] ?? '';
                        @endphp
                        <div class="col-md-6 mb-3 testimonial-card-col" data-index="{{ $tidx }}">
                            <div class="card shadow-none border h-100 position-relative">
                                <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                                    <span class="fw-bold fs-13 text-dark">
                                        <i class="fa-solid fa-user-pen me-1 text-primary"></i> <span class="testi-header-title">Customer {{ $cIdx }}: {{ $testi['name'] ?? 'Review' }}</span>
                                    </span>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-testi" title="Delete Testimonial">
                                        <i class="fa-solid fa-trash fs-11"></i>
                                    </button>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-sm-6">
                                            <label class="form-label fw-6 fs-12 mb-1">{{ __('Full Name') }}</label>
                                            <input type="text" class="form-control form-control-sm testi-field-name" value="{{ $testi['name'] ?? '' }}" placeholder="e.g. Priya Sharma" required>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fw-6 fs-12 mb-1">{{ __('Role / Company') }}</label>
                                            <input type="text" class="form-control form-control-sm testi-field-role" value="{{ $testi['role'] ?? '' }}" placeholder="e.g. Marketing Director">
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-sm-5">
                                            <label class="form-label fw-6 fs-12 mb-1">{{ __('Star Rating') }}</label>
                                            <select class="form-select form-select-sm testi-field-rating">
                                                <option value="5" {{ $tRating == 5 ? 'selected' : '' }}>⭐⭐⭐⭐⭐ (5 Stars)</option>
                                                <option value="4" {{ $tRating == 4 ? 'selected' : '' }}>⭐⭐⭐⭐ (4 Stars)</option>
                                                <option value="3" {{ $tRating == 3 ? 'selected' : '' }}>⭐⭐⭐ (3 Stars)</option>
                                                <option value="2" {{ $tRating == 2 ? 'selected' : '' }}>⭐⭐ (2 Stars)</option>
                                                <option value="1" {{ $tRating == 1 ? 'selected' : '' }}>⭐ (1 Star)</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-7">
                                            <label class="form-label fw-6 fs-12 mb-1">{{ __('Avatar Image (Upload or URL)') }}</label>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="{{ !empty($tImg) ? (str_starts_with($tImg, 'http') ? $tImg : asset($tImg)) : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png' }}" 
                                                     class="rounded-circle border testi-avatar-preview shrink-0" 
                                                     style="width: 32px; height: 32px; object-fit: cover;"
                                                     onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';">
                                                <input type="text" class="form-control form-control-sm testi-field-img" value="{{ $tImg }}" placeholder="URL or upload photo">
                                                <label class="btn btn-sm btn-light border py-1 px-2 mb-0 cursor-pointer text-muted" title="Upload Image File">
                                                    <i class="fa-solid fa-upload"></i>
                                                    <input type="file" class="d-none testi-field-file" name="testi_avatar_files[{{ $tidx }}]" accept="image/*">
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label fw-6 fs-12 mb-1">{{ __('Review Quote') }}</label>
                                        <textarea class="form-control form-control-sm testi-field-text" rows="3" placeholder="Customer review quote...">{{ $testi['text'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Hidden serialized payload --}}
                <input type="hidden" name="testimonials_data" id="hiddenTestimonialsData" value="">
            </div>

            {{-- TAB 6: FAQS (7 Questions) --}}
            <div class="tab-pane fade" id="content-faqs" role="tabpanel">
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-circle-question me-1 text-secondary"></i> {{ __('FAQs Section Headings') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Badge') }}</label>
                                <input type="text" class="form-control" name="landing_faqs_badge" value="{{ get_option('landing_faqs_badge', 'FAQS') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Title') }}</label>
                                <input type="text" class="form-control" name="landing_faqs_title" value="{{ get_option('landing_faqs_title', 'Frequently asked questions') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Subtitle') }}</label>
                                <input type="text" class="form-control" name="landing_faqs_subtitle" value="{{ get_option('landing_faqs_subtitle', 'Get answers to common questions about Whatsappbulk.') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    @php
                        $faqDefaults = [
                            1 => ['What is Whatsappbulk?', 'Whatsappbulk is an all-in-one WhatsApp marketing tool that helps you manage campaigns, automate replies, handle contacts, and track performance — all in one workspace.'],
                            2 => ['Is it safe to use?', 'Yes, our platform employs anti-ban mechanisms, customizable message delays, and adheres to standard messaging policies to protect your accounts.'],
                            3 => ['Can I upgrade my plan later?', 'Absolutely. You can upgrade, downgrade, or cancel your subscription at any time directly from your billing settings.'],
                            4 => ['Do you offer a free trial?', 'Yes, we provide a free starter tier so you can test campaigns and automation features before committing.'],
                            5 => ['What payment methods do you accept?', 'We accept major credit and debit cards, UPI, PayPal, Stripe, and multiple international payment gateways.'],
                            6 => ['Can I use multiple WhatsApp numbers?', 'Yes, our Professional and Business plans support multiple concurrent WhatsApp numbers and team members.'],
                            7 => ['Do you provide customer support?', 'Our dedicated support team is available 24/7 via live chat, email, and WhatsApp to help you set up and scale.']
                        ];
                    @endphp
                    @foreach($faqDefaults as $fidx => $f)
                        <div class="col-md-6 mb-3">
                            <div class="card shadow-none border h-100">
                                <div class="card-header bg-light py-2">
                                    <span class="fw-bold fs-13">FAQ {{ $fidx }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <label class="form-label fw-6 fs-12">{{ __('Question') }}</label>
                                        <input type="text" class="form-control" name="landing_faq{{ $fidx }}_q" value="{{ get_option('landing_faq'.$fidx.'_q', $f[0]) }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fw-6 fs-12">{{ __('Answer') }}</label>
                                        <textarea class="form-control" name="landing_faq{{ $fidx }}_a" rows="3">{{ get_option('landing_faq'.$fidx.'_a', $f[1]) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- TAB 7: BOTTOM CTA & FOOTER --}}
            <div class="tab-pane fade" id="content-cta" role="tabpanel">
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-bullhorn me-1 text-success"></i> {{ __('Bottom Call to Action Banner') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-6 fs-13">{{ __('Headline') }}</label>
                            <input type="text" class="form-control" name="landing_cta_title" value="{{ get_option('landing_cta_title', 'Ready to scale your WhatsApp marketing?') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-6 fs-13">{{ __('Subtitle') }}</label>
                            <textarea class="form-control" name="landing_cta_subtitle" rows="2">{{ get_option('landing_cta_subtitle', 'Connect your WhatsApp account in seconds. Start sending bulk campaigns, automating customer replies, and converting more leads with zero coding required.') }}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Button Text') }}</label>
                                <input type="text" class="form-control" name="landing_cta_btn_text" value="{{ get_option('landing_cta_btn_text', 'Get Started Free') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Button URL') }}</label>
                                <input type="text" class="form-control" name="landing_cta_btn_url" value="{{ get_option('landing_cta_btn_url', 'auth/signup') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-window-minimize me-1 text-secondary"></i> {{ __('Footer Information') }}</div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-6 fs-13">{{ __('Footer Tagline') }}</label>
                            <input type="text" class="form-control" name="landing_footer_tagline" value="{{ get_option('landing_footer_tagline', 'WhatsApp Marketing & Automation Tool') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 8: CONTACT US & LOCATION DETAILS --}}
            <div class="tab-pane fade" id="content-contact" role="tabpanel">
                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-location-dot me-1 text-primary"></i> {{ __('Company Location & Contact Information') }}</div>
                        <a href="{{ url('admin/contact-us') }}" class="btn btn-sm btn-outline-success">
                            <i class="fa-light fa-inbox me-1"></i> {{ __('View Customer Enquiries') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Website URL') }}</label>
                                <input type="text" class="form-control" name="contact_company_website" value="{{ get_option('contact_company_website', 'https://yourcompany.com') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Contact Email') }}</label>
                                <input type="email" class="form-control" name="contact_email" value="{{ get_option('contact_email', 'support@yourcompany.com') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Phone / WhatsApp') }}</label>
                                <input type="text" class="form-control" name="contact_phone_number" value="{{ get_option('contact_phone_number', '+1 234 567 890') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Working Hours') }}</label>
                                <input type="text" class="form-control" name="contact_working_hours" value="{{ get_option('contact_working_hours', 'Mon - Fri: 09:00 AM - 06:00 PM') }}">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Address / Physical Location') }}</label>
                                <input type="text" class="form-control" name="contact_location" value="{{ get_option('contact_location', '123 Main Street, City, Country') }}">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Google Maps Embed Iframe or URL (Optional)') }}</label>
                                <textarea class="form-control" name="contact_map_iframe" rows="2">{{ get_option('contact_map_iframe', '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-none border mb-4">
                    <div class="card-header bg-light">
                        <div class="fw-6 fs-15 text-gray-800"><i class="fa-light fa-shield-check me-1 text-warning"></i> {{ __('Google reCAPTCHA Protection') }}</div>
                    </div>
                    <div class="card-body">
                        @php
                            $adminRecaptchaStatus = get_option('contact_recaptcha_status', get_option('auth_google_recaptcha_status', 0));
                        @endphp
                        <div class="mb-3">
                            <label class="form-label fw-6 fs-13 d-block">{{ __('reCAPTCHA on Contact Form') }}</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="contact_recaptcha_status" id="lp_recaptcha_1" value="1" {{ $adminRecaptchaStatus == 1 ? 'checked' : '' }}>
                                    <label class="form-check-label fw-6" for="lp_recaptcha_1">{{ __('Enable') }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="contact_recaptcha_status" id="lp_recaptcha_0" value="0" {{ $adminRecaptchaStatus == 0 ? 'checked' : '' }}>
                                    <label class="form-check-label fw-6" for="lp_recaptcha_0">{{ __('Disable') }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-6 fs-13">{{ __('reCAPTCHA Site Key') }}</label>
                            <input type="text" class="form-control" name="contact_recaptcha_site_key" value="{{ get_option('contact_recaptcha_site_key', get_option('auth_google_recaptcha_site_key', '')) }}" placeholder="Enter Google reCAPTCHA v2 Site Key">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-6 fs-13">{{ __('reCAPTCHA Secret Key') }}</label>
                            <input type="text" class="form-control" name="contact_recaptcha_secret_key" value="{{ get_option('contact_recaptcha_secret_key', get_option('auth_google_recaptcha_secret_key', '')) }}" placeholder="Enter Google reCAPTCHA v2 Secret Key">
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 9: SIGNUP & CHECKOUT OTP NOTICE BOX --}}
            <div class="tab-pane fade" id="content-otp-notice" role="tabpanel">
                <div class="card shadow-none border mb-4" style="border-left: 4px solid #10b981 !important;">
                    <div class="card-header bg-light d-flex align-items-center justify-content-between">
                        <div class="fw-bold fs-15 text-gray-800">
                            <i class="fa-brands fa-whatsapp text-success me-2 fs-18"></i> {{ __('Checkout & Registration OTP Verification Notice Box') }}
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold px-2 py-1">
                            <i class="fa-solid fa-check me-1"></i> {{ __('Dynamic Delivery') }}
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-4">
                            {{ __('Customize the left profile photo/logo and right-hand instruction text shown to clients when receiving verification OTPs in WhatsApp Bulk.') }}
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Upload Notice Profile / Logo Image') }}</label>
                                <input type="file" class="form-control" name="otp_notice_image_file" id="otpNoticeFileInput" accept="image/*">
                                <div class="form-text small text-muted">{{ __('Upload an icon or profile photo (PNG, JPG, SVG) for the left side of the OTP notice.') }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Or Image URL / Path') }}</label>
                                <input type="text" class="form-control" name="otp_notice_image" id="otpNoticeImageInput" value="{{ get_option('otp_notice_image', 'assets/whatsappbulklogo.png') }}" placeholder="assets/whatsappbulklogo.png">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-6 fs-13">{{ __('Right Side Verification Notice Message') }}</label>
                                <input type="text" class="form-control" name="otp_notice_text" id="otpNoticeTextInput" value="{{ get_option('otp_notice_text', 'Check Your WhatsApp & Email for Verification Code') }}" placeholder="Check Your WhatsApp & Email for Verification Code">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label fw-6 fs-13 text-muted">{{ __('Live Preview in Checkout / Registration Form:') }}</label>
                                <div class="p-3 rounded-3" style="border: 2px dashed #22c55e; background: #f0fdf4; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.05);">
                                    <div style="flex-shrink: 0; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; background: #ffffff; border-radius: 8px; border: 1px solid #dcfce7; padding: 4px;">
                                        @php
                                            $adminOtpImgRaw = get_option('otp_notice_image', 'assets/whatsappbulklogo.png');
                                            $adminOtpImg = asset('assets/whatsappbulklogo.png');
                                            if (!empty($adminOtpImgRaw)) {
                                                $adminOtpImg = str_starts_with($adminOtpImgRaw, 'http')
                                                    ? $adminOtpImgRaw
                                                    : (str_starts_with($adminOtpImgRaw, 'assets/') || str_starts_with($adminOtpImgRaw, 'uploads/') || str_starts_with($adminOtpImgRaw, 'img/') ? asset($adminOtpImgRaw) : asset('uploads/otp_notice/' . $adminOtpImgRaw));
                                            }
                                        @endphp
                                        <img id="otpNoticeImgPreview" src="{{ $adminOtpImg }}" alt="OTP Logo" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                    </div>
                                    <div style="flex-grow: 1;">
                                        <span id="otpNoticeTextPreview" style="font-size: 14px; font-weight: 700; color: #166534; line-height: 1.4; display: block;">{{ get_option('otp_notice_text', 'Check Your WhatsApp & Email for Verification Code') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Floating / Fixed Save Button --}}
        <div class="card shadow-sm border bg-white mt-4 sticky-bottom p-3 text-end rounded-3">
            <button type="submit" class="btn btn-success fw-bold px-5 py-2">
                <i class="fa-light fa-floppy-disk me-1"></i> {{ __('Save Landing Page Settings') }}
            </button>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const grid = document.getElementById('testimonialsListGrid');
    const btnAdd = document.getElementById('btnAddNewTestimonial');
    const counterDisplay = document.getElementById('testiCountDisplay');
    const limitAlert = document.getElementById('testiLimitReachedAlert');
    const hiddenDataInput = document.getElementById('hiddenTestimonialsData');
    const form = document.getElementById('landingPageForm');

    function updateTestimonialCounts() {
        if (!grid) return;
        const cards = grid.querySelectorAll('.testimonial-card-col');
        const count = cards.length;
        
        if (counterDisplay) counterDisplay.textContent = count;
        
        if (count >= 40) {
            if (btnAdd) btnAdd.disabled = true;
            if (limitAlert) limitAlert.style.setProperty('display', 'flex', 'important');
        } else {
            if (btnAdd) btnAdd.disabled = false;
            if (limitAlert) limitAlert.style.setProperty('display', 'none', 'important');
        }

        cards.forEach((card, idx) => {
            card.setAttribute('data-index', idx);
            const titleEl = card.querySelector('.testi-header-title');
            const nameInput = card.querySelector('.testi-field-name');
            const nameVal = nameInput ? nameInput.value.trim() : '';
            if (titleEl) {
                titleEl.textContent = `Customer ${idx + 1}: ${nameVal || 'Review'}`;
            }
            const fileInput = card.querySelector('.testi-field-file');
            if (fileInput) {
                fileInput.name = `testi_avatar_files[${idx}]`;
            }
        });
    }

    // Dynamic Title update as user types customer name
    grid?.addEventListener('input', function(e) {
        if (e.target.classList.contains('testi-field-name')) {
            const card = e.target.closest('.testimonial-card-col');
            if (card) {
                const idx = Array.from(grid.children).indexOf(card);
                const titleEl = card.querySelector('.testi-header-title');
                if (titleEl) {
                    titleEl.textContent = `Customer ${idx + 1}: ${e.target.value.trim() || 'Review'}`;
                }
            }
        }
        if (e.target.classList.contains('testi-field-img')) {
            const card = e.target.closest('.testimonial-card-col');
            const preview = card?.querySelector('.testi-avatar-preview');
            if (preview && e.target.value.trim()) {
                preview.src = e.target.value.trim();
            }
        }
    });

    // File input preview
    grid?.addEventListener('change', function(e) {
        if (e.target.classList.contains('testi-field-file')) {
            const file = e.target.files && e.target.files[0];
            if (file) {
                const card = e.target.closest('.testimonial-card-col');
                const preview = card?.querySelector('.testi-avatar-preview');
                const urlInput = card?.querySelector('.testi-field-img');
                if (preview) {
                    const reader = new FileReader();
                    reader.onload = function(re) {
                        preview.src = re.target.result;
                    };
                    reader.readAsDataURL(file);
                }
                if (urlInput) {
                    urlInput.value = file.name;
                }
            }
        }
    });

    // Delete Testimonial
    grid?.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove-testi');
        if (btn) {
            const card = btn.closest('.testimonial-card-col');
            if (card) {
                card.remove();
                updateTestimonialCounts();
            }
        }
    });

    // Add New Testimonial
    btnAdd?.addEventListener('click', function() {
        const currentCount = grid.querySelectorAll('.testimonial-card-col').length;
        if (currentCount >= 40) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Limit Reached',
                    text: 'You cannot add more than 40 testimonials. Limit is 40.'
                });
            } else if (typeof iziToast !== 'undefined') {
                iziToast.warning({
                    title: 'Limit Reached',
                    message: 'You cannot add more than 40 testimonials. Limit is 40.',
                    position: 'topRight'
                });
            } else {
                alert('Warning: You cannot add more than 40 testimonials.');
            }
            return;
        }

        const newIdx = currentCount;
        const newCustomerNum = newIdx + 1;
        const col = document.createElement('div');
        col.className = 'col-md-6 mb-3 testimonial-card-col';
        col.setAttribute('data-index', newIdx);
        col.innerHTML = `
            <div class="card shadow-none border h-100 position-relative">
                <div class="card-header bg-light py-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold fs-13 text-dark">
                        <i class="fa-solid fa-user-pen me-1 text-primary"></i> <span class="testi-header-title">Customer ${newCustomerNum}: New Customer</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 btn-remove-testi" title="Delete Testimonial">
                        <i class="fa-solid fa-trash fs-11"></i>
                    </button>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2 mb-2">
                        <div class="col-sm-6">
                            <label class="form-label fw-6 fs-12 mb-1">Full Name</label>
                            <input type="text" class="form-control form-control-sm testi-field-name" value="" placeholder="e.g. John Doe" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-6 fs-12 mb-1">Role / Company</label>
                            <input type="text" class="form-control form-control-sm testi-field-role" value="" placeholder="e.g. Founder, Startup">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-sm-5">
                            <label class="form-label fw-6 fs-12 mb-1">Star Rating</label>
                            <select class="form-select form-select-sm testi-field-rating">
                                <option value="5" selected>⭐⭐⭐⭐⭐ (5 Stars)</option>
                                <option value="4">⭐⭐⭐⭐ (4 Stars)</option>
                                <option value="3">⭐⭐⭐ (3 Stars)</option>
                                <option value="2">⭐⭐ (2 Stars)</option>
                                <option value="1">⭐ (1 Star)</option>
                            </select>
                        </div>
                        <div class="col-sm-7">
                            <label class="form-label fw-6 fs-12 mb-1">Avatar Image (Upload or URL)</label>
                            <div class="d-flex align-items-center gap-2">
                                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" 
                                     class="rounded-circle border testi-avatar-preview shrink-0" 
                                     style="width: 32px; height: 32px; object-fit: cover;">
                                <input type="text" class="form-control form-control-sm testi-field-img" value="" placeholder="URL or upload photo">
                                <label class="btn btn-sm btn-light border py-1 px-2 mb-0 cursor-pointer text-muted" title="Upload Image File">
                                    <i class="fa-solid fa-upload"></i>
                                    <input type="file" class="d-none testi-field-file" name="testi_avatar_files[${newIdx}]" accept="image/*">
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-6 fs-12 mb-1">Review Quote</label>
                        <textarea class="form-control form-control-sm testi-field-text" rows="3" placeholder="Customer review quote..."></textarea>
                    </div>
                </div>
            </div>
        `;
        grid.appendChild(col);
        updateTestimonialCounts();
    });

    // Form submit serialization
    form?.addEventListener('submit', function() {
        const cards = grid.querySelectorAll('.testimonial-card-col');
        const list = [];
        cards.forEach((card) => {
            const name = card.querySelector('.testi-field-name')?.value.trim() || '';
            const role = card.querySelector('.testi-field-role')?.value.trim() || '';
            const rating = parseInt(card.querySelector('.testi-field-rating')?.value || '5', 10);
            const img = card.querySelector('.testi-field-img')?.value.trim() || '';
            const text = card.querySelector('.testi-field-text')?.value.trim() || '';
            if (name || text) {
                list.push({ name, role, rating, img, text });
            }
        });
        if (hiddenDataInput) {
            hiddenDataInput.value = JSON.stringify(list);
        }
    });

    // Live update OTP preview
    const otpImgInput = document.getElementById('otpNoticeImageInput');
    const otpFileInput = document.getElementById('otpNoticeFileInput');
    const otpTextInput = document.getElementById('otpNoticeTextInput');
    const otpImgPreview = document.getElementById('otpNoticeImgPreview');
    const otpTextPreview = document.getElementById('otpNoticeTextPreview');

    if (otpTextInput && otpTextPreview) {
        otpTextInput.addEventListener('input', function() {
            otpTextPreview.textContent = this.value || 'Check Your WhatsApp & Email for Verification Code';
        });
    }
    if (otpImgInput && otpImgPreview) {
        otpImgInput.addEventListener('input', function() {
            if (this.value.trim()) {
                otpImgPreview.src = this.value.trim();
            }
        });
    }
    if (otpFileInput && otpImgPreview) {
        otpFileInput.addEventListener('change', function(e) {
            const file = e.target.files && e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(re) {
                    otpImgPreview.src = re.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Initialize counts on page load
    updateTestimonialCounts();
});
</script>

@endsection