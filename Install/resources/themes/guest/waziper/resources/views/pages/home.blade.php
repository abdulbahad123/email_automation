@extends('layouts.app')

@section('content')

@php
    $cleanLandingHome = function($val) {
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

    // Hero Title Sanitization & Reference Match (clean test trailing 'k's)
    $rawTitle = get_option('landing_hero_title', "WhatsApp operations,<br><span class=\"text-[#059669]\">campaigns,</span> and<br>automation");
    $heroTitle = $cleanLandingHome($rawTitle);
    $heroTitle = str_replace(
        ['#ff5500', '#f97316', 'text-orange-500', 'color: #ff5500', 'color:#ff5500', 'color: #f97316', 'color:#f97316'],
        ['#059669', '#059669', 'text-[#059669]', 'color: #059669', 'color:#059669', 'color: #059669', 'color:#059669'],
        $heroTitle
    );

    // Dynamic Live Stack Title
    $stackTitle = $cleanLandingHome(get_option('landing_stack_title', 'Whatsappbulk <span class="text-[#059669]">Workspace</span>'));
    $stackTitle = str_replace(
        ['#ff5500', '#f97316', 'text-orange-500', 'color: #ff5500', 'color:#ff5500', 'color: #f97316', 'color:#f97316'],
        ['#059669', '#059669', 'text-[#059669]', 'color: #059669', 'color:#059669', 'color: #059669', 'color:#059669'],
        $stackTitle
    );

    // Dynamic Live Stack Subtitle
    $stackSubtitle = $cleanLandingHome(get_option('landing_stack_subtitle', 'Everything you need to run WhatsApp at scale'));
    if ($stackSubtitle === 'Product overview' || empty($stackSubtitle)) {
        $stackSubtitle = 'Everything you need to run WhatsApp at scale';
    }

    // Hero Badge Sanitization
    $rawBadge = get_option('landing_hero_badge', 'ALL-IN-ONE WHATSAPP MARKETING TOOL');
    $heroBadge = trim(strip_tags((string)$rawBadge));
    $heroBadge = $cleanLandingHome($heroBadge);
    $heroBadge = trim($heroBadge, " \t\n\r\0\x0B.,<>");
    if (empty($heroBadge) || str_contains($heroBadge, 'TOOLK')) {
        $heroBadge = 'ALL-IN-ONE WHATSAPP MARKETING TOOL';
    }

    // Subtitles Sanitization
    $heroSubtitleBold = $cleanLandingHome(get_option('landing_hero_subtitle_bold', 'One WhatsApp workspace for campaigns, replies, bots, contacts, and reports.'));
    
    $heroSubtitleDesc = $cleanLandingHome(get_option('landing_hero_subtitle_desc', 'Built around the modules teams actually use every day: bulk outreach, smart replies, auto replies, chatbots, contacts, participant export, profile lookups, and reporting.'));

    // CTAs
    $heroBtn1Text = $cleanLandingHome(get_option('landing_hero_btn1_text', 'Create your workspace'));
    $heroBtn2Text = $cleanLandingHome(get_option('landing_hero_btn2_text', 'Explore modules'));

    // Stat cards numbers & labels (exact match with Reference Image 2)
    $stat1Num = preg_replace('/[kK]+$/', '', (string)get_option('landing_stat1_number', '8')) ?: '8';
    $stat1Label = $cleanLandingHome(get_option('landing_stat1_label', 'Operational modules included'));
    if ($stat1Label === 'Operational modules' || empty($stat1Label)) {
        $stat1Label = 'Operational modules included';
    }

    $stat2Num = preg_replace('/[kK]+$/', '', (string)get_option('landing_stat2_number', '1')) ?: '1';
    $stat2Label = $cleanLandingHome(get_option('landing_stat2_label', 'Unified WhatsApp workflow'));
    if ($stat2Label === 'Unified workflow' || empty($stat2Label)) {
        $stat2Label = 'Unified WhatsApp workflow';
    }

    $stat3Num = preg_replace('/[kK]+$/', '', (string)get_option('landing_stat3_number', '24/7')) ?: '24/7';
    $stat3Label = $cleanLandingHome(get_option('landing_stat3_label', 'Automation & reply coverage'));
    if ($stat3Label === 'Automation coverage' || empty($stat3Label)) {
        $stat3Label = 'Automation & reply coverage';
    }

    // Dynamic Live Dashboard & Phone Preview Controls
    $dashUser = get_option('landing_dash_user', 'HI, Admin');
    $dashChartTitle = get_option('landing_dash_chart_title', 'Message Performance');
    $dashM1Val = get_option('landing_dash_m1_val', '125,890');
    $dashM1Trend = get_option('landing_dash_m1_trend', '▲ 20.4%');
    $dashM2Val = get_option('landing_dash_m2_val', '118,420');
    $dashM2Trend = get_option('landing_dash_m2_trend', '▲ 16.1%');
    $dashM3Val = get_option('landing_dash_m3_val', '28,460');
    $dashM3Trend = get_option('landing_dash_m3_trend', '▲ 14.2%');
    $dashM4Val = get_option('landing_dash_m4_val', '96,350');
    $dashM4Trend = get_option('landing_dash_m4_trend', '▲ 12.6%');
    $phoneTitle = get_option('landing_phone_title', 'Campaign Overview');
    $phonePercent = get_option('landing_phone_percent', '95.8%');
@endphp

<!-- ============================================================== -->
<!-- SECTION 1: HERO & DASHBOARD MOCKUP (Exact Match Image 2)       -->
<!-- ============================================================== -->
<section class="relative pt-12 pb-24 md:pt-16 md:pb-32 overflow-hidden" style="background-color: #eafbf3; background: linear-gradient(180deg, #d8f5e5 0%, #ebfcf3 35%, #e1f8ec 100%);">
    <!-- Ambient Green Background Glows -->
    <div class="absolute -top-20 -right-20 w-[600px] h-[600px] bg-gradient-to-bl from-emerald-200/50 via-teal-100/30 to-transparent rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute top-28 left-8 w-80 h-80 bg-emerald-200/35 rounded-full blur-3xl pointer-events-none"></div>

    <div class="container mx-auto px-4 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
            
            <!-- Left Hero Text Content -->
            <div class="lg:col-span-6">
                <!-- Pill Badge -->
                <div class="mb-5">
                    <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100/90 text-[#065f46] border border-emerald-300/70 shadow-xs">
                        <i class="fas fa-bolt text-[11px] text-[#059669]"></i>
                        <span>{{ $heroBadge }}</span>
                    </span>
                </div>

                <!-- Main Headline -->
                <h1 class="text-4xl sm:text-5xl lg:text-[54px] font-black text-slate-900 tracking-tight leading-[1.12] mb-5">
                    {!! $heroTitle !!}
                </h1>

                <!-- Subtitles -->
                <p class="text-base sm:text-lg font-bold text-slate-800 mb-2 leading-snug">
                    {{ $heroSubtitleBold }}
                </p>
                <p class="text-sm text-slate-500 font-normal leading-relaxed mb-8 max-w-lg">
                    {{ $heroSubtitleDesc }}
                </p>

                <!-- Dual CTA Buttons -->
                <div class="flex flex-wrap items-center gap-4 mb-10">
                    <a href="{{ url(get_option('landing_hero_btn1_url', 'auth/signup')) }}" class="inline-flex items-center gap-2 px-7 py-3.5 rounded-full text-white font-bold text-sm shadow-lg shadow-emerald-600/30 transition transform hover:-translate-y-0.5 hover:opacity-95" style="background: linear-gradient(135deg, #01502f 0%, #036f45 45%, #059669 100%);">
                        <span>{{ $heroBtn1Text }}</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                    <a href="{{ get_option('landing_hero_btn2_url', '#modules') }}" class="inline-flex items-center px-7 py-3.5 rounded-full text-slate-800 font-bold text-sm bg-white border border-slate-200 hover:bg-slate-50 transition shadow-xs">
                        {{ $heroBtn2Text }}
                    </a>
                </div>

                <!-- 3 Metric Highlight Cards (Responsive for Mobile & Desktop) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 max-w-xl">
                    <!-- Card 1 -->
                    <div class="bg-white rounded-2xl p-3.5 border border-emerald-200/60 shadow-[0_4px_20px_rgba(5,150,105,0.06)] flex items-center gap-3">
                        <div class="hidden sm:flex w-11 h-11 rounded-xl bg-emerald-50 text-[#059669] items-center justify-center shrink-0 text-lg border border-emerald-100">
                            <i class="{{ get_option('landing_stat1_icon', 'fas fa-cube') }}"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-2xl font-black text-slate-900 leading-none">{{ $stat1Num }}</span>
                            <span class="text-[11px] font-semibold text-slate-500 leading-tight block mt-1">{{ $stat1Label }}</span>
                        </div>
                    </div>
                    <!-- Card 2 -->
                    <div class="bg-white rounded-2xl p-3.5 border border-emerald-200/60 shadow-[0_4px_20px_rgba(5,150,105,0.06)] flex items-center gap-3">
                        <div class="hidden sm:flex w-11 h-11 rounded-xl bg-emerald-50 text-[#059669] items-center justify-center shrink-0 text-lg border border-emerald-100">
                            <i class="{{ get_option('landing_stat2_icon', 'fas fa-link') }}"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-2xl font-black text-slate-900 leading-none">{{ $stat2Num }}</span>
                            <span class="text-[11px] font-semibold text-slate-500 leading-tight block mt-1">{{ $stat2Label }}</span>
                        </div>
                    </div>
                    <!-- Card 3 -->
                    <div class="bg-white rounded-2xl p-3.5 border border-emerald-200/60 shadow-[0_4px_20px_rgba(5,150,105,0.06)] flex items-center gap-3">
                        <div class="hidden sm:flex w-11 h-11 rounded-xl bg-emerald-50 text-[#059669] items-center justify-center shrink-0 text-lg border border-emerald-100">
                            <i class="{{ get_option('landing_stat3_icon', 'fas fa-bolt') }}"></i>
                        </div>
                        <div class="min-w-0">
                            <span class="block text-2xl font-black text-slate-900 leading-none">{{ $stat3Num }}</span>
                            <span class="text-[11px] font-semibold text-slate-500 leading-tight block mt-1">{{ $stat3Label }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Hero Visual: Realistic Tablet + Smartphone + Hand-drawn Doodles -->
            <div class="lg:col-span-6 relative pt-4 pb-12 sm:pb-16">
                
                <!-- Hand-drawn SVG Doodles (Exact Pixel Match of Reference Image) - Hidden on Mobile View Only -->
                <!-- 1. Hand-Drawn Crown Squiggle with Radiating Rays above Tablet Bezel -->
                <svg class="hidden sm:block absolute -top-11 left-1/2 -translate-x-1/2 w-28 h-12 text-[#059669] pointer-events-none z-20 overflow-visible" viewBox="0 0 80 32" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <!-- Motion curves on left -->
                    <path d="M6,13 Q2,20 6,27" />
                    <path d="M1,9 Q-4,20 1,31" />
                    
                    <!-- Crown Body (3 peaks) -->
                    <path d="M12,27 L20,13 L28,21 L36,9 L44,21 L52,13 L60,27" />
                    
                    <!-- Radiant rays above center peak -->
                    <path d="M36,4 L36,-4" />
                    <path d="M29,6 L24,-1" />
                    <path d="M43,6 L48,-1" />
                    
                    <!-- Radiant diagonal rays on right -->
                    <path d="M66,13 L73,8" />
                    <path d="M68,20 L76,19" />
                    <path d="M66,27 L74,30" />
                </svg>

                <!-- 2. Sparkle Star 1 (Top Left) -->
                <svg class="hidden sm:block absolute -top-5 left-6 sm:left-12 w-6 h-6 text-[#059669] pointer-events-none z-20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 0 L13.8 8.2 L22 12 L13.8 15.8 L12 24 L10.2 15.8 L2 12 L10.2 8.2 Z" />
                </svg>

                <!-- 3. Sparkle Star 2 (Right of Tablet) -->
                <svg class="hidden sm:block absolute top-10 -right-6 w-7 h-7 text-[#059669] pointer-events-none z-20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 0 L13.8 8.2 L22 12 L13.8 15.8 L12 24 L10.2 15.8 L2 12 L10.2 8.2 Z" />
                </svg>
                <svg class="hidden sm:block absolute top-24 -right-3 w-4 h-4 text-[#059669]/70 pointer-events-none z-20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 0 L13.8 8.2 L22 12 L13.8 15.8 L12 24 L10.2 15.8 L2 12 L10.2 8.2 Z" />
                </svg>
                <span class="hidden sm:block absolute top-5 -right-2 w-1.5 h-1.5 rounded-full bg-[#059669] pointer-events-none z-20"></span>
                <span class="hidden sm:block absolute top-18 -right-6 w-2 h-2 rounded-full bg-[#059669]/80 pointer-events-none z-20"></span>

                <!-- 4. Squiggle Doodle (Bottom under Phone) -->
                <svg class="absolute -bottom-8 right-28 sm:right-36 w-16 h-8 text-[#059669] pointer-events-none z-20 hidden sm:block" viewBox="0 0 60 25" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                    <path d="M4 14 Q14 2 24 14 T44 14 Q52 18 56 12" />
                </svg>

                <!-- Outer Realistic Tablet Frame with Dark Slate Bezel (Image 3) -->
                <div class="border-[7px] border-slate-900 rounded-[34px] bg-slate-900 shadow-[0_30px_70px_rgba(15,23,42,0.22)] overflow-hidden">
                    <!-- Tablet Screen Content -->
                    <div class="bg-white rounded-[26px] overflow-hidden">
                        
                        <!-- Screen Top Header Bar -->
                        <div class="bg-white border-b border-slate-100 px-5 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px]">
                                    <i class="fab fa-whatsapp"></i>
                                </div>
                                <span class="text-sm font-extrabold text-[#059669]">Whatsappbulk</span>
                                <span class="text-slate-300 font-light">|</span>
                                <span class="text-xs font-bold text-slate-800">Dashboard</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-orange-500 text-white flex items-center justify-center text-[11px] font-bold">A</div>
                                <span class="text-[11px] font-bold text-slate-700">{{ $dashUser }}</span>
                                <i class="fas fa-chevron-down text-[9px] text-slate-400"></i>
                            </div>
                        </div>

                        <!-- Inner Responsive Dashboard Layout -->
                        <div class="grid grid-cols-12 gap-2 sm:gap-3.5 p-2.5 sm:p-4 bg-white">
                            <!-- Left Mini App Sidebar (Desktop & Tablet) -->
                            <div class="hidden sm:block sm:col-span-4 border-r border-slate-100 pr-2.5 space-y-1 text-[11px] font-semibold text-slate-600">
                                <div class="px-2.5 py-1.5 rounded-lg bg-emerald-50 text-[#059669] font-bold flex items-center gap-2">
                                    <i class="fas fa-chart-pie text-xs"></i> Dashboard
                                </div>
                                <div class="px-2.5 py-1.5 rounded-lg hover:bg-slate-50 flex items-center gap-2 text-slate-500">
                                    <i class="fas fa-bullhorn text-xs text-slate-400"></i> Bulk Campaigns
                                </div>
                                <div class="px-2.5 py-1.5 rounded-lg hover:bg-slate-50 flex items-center gap-2 text-slate-500">
                                    <i class="fas fa-wand-magic-sparkles text-xs text-slate-400"></i> AI Smart Reply
                                </div>
                                <div class="px-2.5 py-1.5 rounded-lg hover:bg-slate-50 flex items-center gap-2 text-slate-500">
                                    <i class="fas fa-rotate-left text-xs text-slate-400"></i> Auto Reply
                                </div>
                                <div class="px-2.5 py-1.5 rounded-lg hover:bg-slate-50 flex items-center gap-2 text-slate-500">
                                    <i class="fas fa-robot text-xs text-slate-400"></i> Chatbot
                                </div>
                                <div class="px-2.5 py-1.5 rounded-lg hover:bg-slate-50 flex items-center gap-2 text-slate-500">
                                    <i class="fas fa-address-book text-xs text-slate-400"></i> Contacts
                                </div>
                                <div class="px-2.5 py-1.5 rounded-lg hover:bg-slate-50 flex items-center gap-2 text-slate-500">
                                    <i class="fas fa-file-export text-xs text-slate-400"></i> Export Participants
                                </div>
                                <div class="px-2.5 py-1.5 rounded-lg hover:bg-slate-50 flex items-center gap-2 text-slate-500">
                                    <i class="fas fa-id-card text-xs text-slate-400"></i> Profile Info
                                </div>
                                <div class="px-2.5 py-1.5 rounded-lg hover:bg-slate-50 flex items-center gap-2 text-slate-500">
                                    <i class="fas fa-chart-column text-xs text-slate-400"></i> Reports
                                </div>
                            </div>

                            <!-- Right Live Metrics & Graph View -->
                            <div class="col-span-12 sm:col-span-8 pl-0 sm:pl-1">
                                <!-- 4 Stats Cards in 2x2 Grid -->
                                <div class="grid grid-cols-2 gap-2 mb-3">
                                    <div class="bg-slate-50/90 rounded-xl p-2.5 border border-slate-100">
                                        <span class="text-[9px] text-slate-400 font-bold block">{{ __('Total Messages') }}</span>
                                        <span class="text-sm sm:text-base font-black text-slate-900 block">{{ $dashM1Val }}</span>
                                        <span class="text-[9px] font-bold text-emerald-600 block">{{ $dashM1Trend }}</span>
                                    </div>
                                    <div class="bg-slate-50/90 rounded-xl p-2.5 border border-slate-100">
                                        <span class="text-[9px] text-slate-400 font-bold block">{{ __('Delivered') }}</span>
                                        <span class="text-sm sm:text-base font-black text-slate-900 block">{{ $dashM2Val }}</span>
                                        <span class="text-[9px] font-bold text-emerald-600 block">{{ $dashM2Trend }}</span>
                                    </div>
                                    <div class="bg-slate-50/90 rounded-xl p-2.5 border border-slate-100">
                                        <span class="text-[9px] text-slate-400 font-bold block">{{ __('Replied') }}</span>
                                        <span class="text-sm sm:text-base font-black text-slate-900 block">{{ $dashM3Val }}</span>
                                        <span class="text-[9px] font-bold text-emerald-600 block">{{ $dashM3Trend }}</span>
                                    </div>
                                    <div class="bg-slate-50/90 rounded-xl p-2.5 border border-slate-100">
                                        <span class="text-[9px] text-slate-400 font-bold block">{{ __('Active Contacts') }}</span>
                                        <span class="text-sm sm:text-base font-black text-slate-900 block">{{ $dashM4Val }}</span>
                                        <span class="text-[9px] font-bold text-emerald-600 block">{{ $dashM4Trend }}</span>
                                    </div>
                                </div>

                                <!-- Message Performance Area Chart -->
                                <div class="bg-slate-50/90 rounded-xl p-2.5 border border-slate-100 mb-3">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-[11px] font-bold text-slate-700">{{ $dashChartTitle }}</span>
                                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-orange-100 text-orange-600">Last 30 Days</span>
                                    </div>
                                    <svg class="w-full h-12" viewBox="0 0 300 65" preserveAspectRatio="none">
                                        <defs>
                                             <linearGradient id="heroAreaGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                                                 <stop offset="0%" stop-color="#f97316" stop-opacity="0.35"/>
                                                 <stop offset="100%" stop-color="#f97316" stop-opacity="0.0"/>
                                             </linearGradient>
                                        </defs>
                                        <path d="M0,50 Q40,40 80,25 T160,30 T240,15 T300,28 L300,65 L0,65 Z" fill="url(#heroAreaGrad)"/>
                                        <path d="M0,50 Q40,40 80,25 T160,30 T240,15 T300,28" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round"/>
                                    </svg>
                                </div>

                                <!-- Recent Campaigns Table -->
                                <div class="space-y-1.5 text-[10px]">
                                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                                        <span class="font-bold text-slate-800"><i class="fas fa-star text-amber-500 mr-1 text-[9px]"></i> Product Launch</span>
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-bold text-[9px]">Completed</span>
                                        <span class="font-bold text-slate-600 flex items-center gap-1">12,450 <i class="fas fa-circle-check text-emerald-500 text-[9px]"></i></span>
                                    </div>
                                    <div class="flex items-center justify-between py-1 border-b border-slate-100">
                                        <span class="font-bold text-slate-800"><i class="fas fa-bullhorn text-orange-500 mr-1 text-[9px]"></i> Festival Offer</span>
                                        <span class="px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 font-bold text-[9px]">Sending</span>
                                        <span class="font-bold text-slate-600 flex items-center gap-1">25,600 <i class="fas fa-clock text-orange-400 text-[9px]"></i></span>
                                    </div>
                                    <div class="flex items-center justify-between py-1">
                                        <span class="font-bold text-slate-800"><i class="fas fa-headset text-blue-500 mr-1 text-[9px]"></i> Customer Support</span>
                                        <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold text-[9px]">Active</span>
                                        <span class="font-bold text-slate-600 flex items-center gap-1">18,220 <i class="fas fa-circle-check text-emerald-500 text-[9px]"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overlapping Vertical Smartphone Mockup (Pixel-by-pixel match with Image 2) -->
                <div class="absolute -bottom-6 -right-2 sm:-right-4 w-48 sm:w-52 border-[6px] border-slate-900 rounded-[38px] bg-white shadow-2xl p-3.5 hidden sm:block z-30">
                    <!-- Top Speaker Bar Notch -->
                    <div class="w-12 h-1 bg-slate-900 rounded-full mx-auto mb-3"></div>

                    <!-- Inner Phone Screen -->
                    <div class="text-center pt-1 pb-2">
                        <!-- Green WhatsApp Icon -->
                        <div class="w-11 h-11 rounded-full bg-emerald-500 text-white mx-auto flex items-center justify-center text-2xl mb-1.5 shadow-md shadow-emerald-500/30">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <span class="text-[11px] font-bold text-slate-500 block">{{ $phoneTitle }}</span>
                        <span class="text-3xl font-black text-orange-500 block tracking-tight my-0.5">{{ $phonePercent }}</span>
                    </div>

                    <!-- Multi-bar Chart with Green and Orange Bars -->
                    <div class="flex items-end justify-center gap-1.5 h-12 pt-2 border-t border-slate-100">
                        <div class="w-3 bg-orange-400 rounded-t h-5"></div>
                        <div class="w-3 bg-orange-500 rounded-t h-9"></div>
                        <div class="w-3 bg-orange-400 rounded-t h-6"></div>
                        <div class="w-3 bg-orange-600 rounded-t h-11"></div>
                        <div class="w-3 bg-orange-500 rounded-t h-8"></div>
                        <div class="w-3 bg-emerald-500 rounded-t h-12"></div>
                    </div>

                    <!-- Floating Green WhatsApp Circle Badge with Telephone Icon -->
                    <div class="absolute -bottom-3 -right-3 w-10 h-10 rounded-full bg-[#25D366] text-white flex items-center justify-center shadow-lg text-lg border-2 border-white">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                </div>

            </div>

        </div>
    </div>
</section>

<!-- ============================================================== -->
<!-- SECTION 2: LIVE MODULE STACK (Alternating: White Background)     -->
<!-- ============================================================== -->
<section class="py-20 bg-white border-y border-slate-100">
    <div class="container mx-auto px-4 lg:px-8">
        <!-- Section Header -->
        <div class="max-w-3xl mx-auto text-center mb-14">
            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-[#065f46] border border-emerald-300/60 mb-3">
                <i class="fas fa-layer-group text-[11px] text-[#059669]"></i>
                <span>{!! get_option('landing_stack_badge', 'LIVE MODULE STACK') !!}</span>
            </span>
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-3">
                {!! $stackTitle !!}
            </h2>
            <p class="text-sm md:text-base text-slate-600 font-medium max-w-2xl mx-auto">
                {{ $stackSubtitle }}
            </p>
        </div>

        <!-- 4 Rounded Cards Grid (2 cols on mobile, 4 on desktop) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 items-stretch">
            
            <!-- Stack Card 1: Bulk Campaigns -->
            <div class="bg-[#f8fdfa] rounded-[26px] p-7 sm:p-8 border border-emerald-100/80 shadow-[0_4px_25px_rgba(0,0,0,0.03)] hover:shadow-xl hover:border-emerald-500/40 transition duration-300 flex flex-col justify-between group">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-[#059669] text-white flex items-center justify-center text-2xl mb-6 shadow-md shadow-emerald-600/20 group-hover:scale-105 transition-transform">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-400 block mb-2">
                        {{ get_option('landing_stack1_tag', 'CAMPAIGN CONTROL') }}
                    </span>
                    <h3 class="text-xl font-black text-slate-900 mb-2">
                        {{ get_option('landing_stack1_title', 'Bulk Campaigns') }}
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed font-normal">
                        {{ get_option('landing_stack1_desc', 'Segment contacts, send at scale, and track response flow.') }}
                    </p>
                </div>
                <!-- Green Circle Arrow Button Aligned to Bottom Left (Image 2) -->
                <div class="mt-8 flex justify-start">
                    <span class="w-9 h-9 rounded-full bg-[#059669] text-white flex items-center justify-center text-xs group-hover:bg-emerald-700 shadow-md shadow-emerald-600/25 transition">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </div>

            <!-- Stack Card 2: AI + Auto Reply -->
            <div class="bg-[#f8fdfa] rounded-[26px] p-7 sm:p-8 border border-emerald-100/80 shadow-[0_4px_25px_rgba(0,0,0,0.03)] hover:shadow-xl hover:border-emerald-500/40 transition duration-300 flex flex-col justify-between group">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-[#059669] text-white flex items-center justify-center text-2xl mb-6 shadow-md shadow-emerald-600/20 group-hover:scale-105 transition-transform">
                        <i class="fas fa-comments"></i>
                    </div>
                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-400 block mb-2">
                        {{ get_option('landing_stack2_tag', 'INBOUND HANDLING') }}
                    </span>
                    <h3 class="text-xl font-black text-slate-900 mb-2">
                        {{ get_option('landing_stack2_title', 'AI + Auto Reply') }}
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed font-normal">
                        {{ get_option('landing_stack2_desc', 'Combine automation with faster human response quality.') }}
                    </p>
                </div>
                <!-- Green Circle Arrow Button Aligned to Bottom Left (Image 2) -->
                <div class="mt-8 flex justify-start">
                    <span class="w-9 h-9 rounded-full bg-[#059669] text-white flex items-center justify-center text-xs group-hover:bg-emerald-700 shadow-md shadow-emerald-600/25 transition">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </div>

            <!-- Stack Card 3: Chatbot + Contacts -->
            <div class="bg-[#f8fdfa] rounded-[26px] p-7 sm:p-8 border border-emerald-100/80 shadow-[0_4px_25px_rgba(0,0,0,0.03)] hover:shadow-xl hover:border-emerald-500/40 transition duration-300 flex flex-col justify-between group">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-[#059669] text-white flex items-center justify-center text-2xl mb-6 shadow-md shadow-emerald-600/20 group-hover:scale-105 transition-transform">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-400 block mb-2">
                        {{ get_option('landing_stack3_tag', 'LEAD FLOW') }}
                    </span>
                    <h3 class="text-xl font-black text-slate-900 mb-2">
                        {{ get_option('landing_stack3_title', 'Chatbot + Contacts') }}
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed font-normal">
                        {{ get_option('landing_stack3_desc', 'Capture, qualify, and manage audience data in one place.') }}
                    </p>
                </div>
                <!-- Green Circle Arrow Button Aligned to Bottom Left (Image 2) -->
                <div class="mt-8 flex justify-start">
                    <span class="w-9 h-9 rounded-full bg-[#059669] text-white flex items-center justify-center text-xs group-hover:bg-emerald-700 shadow-md shadow-emerald-600/25 transition">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </div>

            <!-- Stack Card 4: Reports + Export -->
            <div class="bg-[#f8fdfa] rounded-[26px] p-7 sm:p-8 border border-emerald-100/80 shadow-[0_4px_25px_rgba(0,0,0,0.03)] hover:shadow-xl hover:border-emerald-500/40 transition duration-300 flex flex-col justify-between group">
                <div>
                    <div class="w-14 h-14 rounded-2xl bg-[#059669] text-white flex items-center justify-center text-2xl mb-6 shadow-md shadow-emerald-600/20 group-hover:scale-105 transition-transform">
                        <i class="fas fa-chart-column"></i>
                    </div>
                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-400 block mb-2">
                        {{ get_option('landing_stack4_tag', 'VISIBILITY') }}
                    </span>
                    <h3 class="text-xl font-black text-slate-900 mb-2">
                        {{ get_option('landing_stack4_title', 'Reports + Export') }}
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed font-normal">
                        {{ get_option('landing_stack4_desc', 'Export participants and measure what the team actually ships.') }}
                    </p>
                </div>
                <!-- Green Circle Arrow Button Aligned to Bottom Left (Image 2) -->
                <div class="mt-8 flex justify-start">
                    <span class="w-9 h-9 rounded-full bg-[#059669] text-white flex items-center justify-center text-xs group-hover:bg-emerald-700 shadow-md shadow-emerald-600/25 transition">
                        <i class="fas fa-arrow-right"></i>
                    </span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================== -->
<!-- SECTION 3: PRODUCT MODULES (8 Cards, Screenshot 3)              -->
<!-- ============================================================== -->
<section id="modules" class="py-24 relative" style="background-color: #eafbf3; background: linear-gradient(180deg, #e4f9ee 0%, #edfcf4 50%, #e2f8ec 100%);">
    <div class="container mx-auto px-4 lg:px-8">
        <!-- Section Header -->
        <div class="max-w-3xl mx-auto text-center mb-16">
            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-[#065f46] border border-emerald-300/60 mb-3">
                <i class="fas fa-cubes text-[11px] text-[#059669]"></i>
                <span>{!! get_option('landing_modules_badge', 'PRODUCT MODULES') !!}</span>
            </span>
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-3">
                {!! get_option('landing_modules_title', 'Built from the <span class="text-[#059669]">WhatsApp features</span> teams actually need') !!}
            </h2>
            <p class="text-sm md:text-base text-slate-600 font-medium max-w-2xl mx-auto">
                {{ get_option('landing_modules_subtitle', 'Every module supports a real operational job, from outreach and automation to contact management and reporting.') }}
            </p>
        </div>

        @php
            $rawMod = function($idx, $defaultTitle, $defaultDesc, $icon, $bg, $isComingSoon = false) {
                $rawTitle = get_option('landing_mod'.$idx.'_title', $defaultTitle);
                $cleanTitle = preg_replace('/(Reply|Campaigns|Chatbot|Contacts|Reports)r$/i', '$1', (string)$rawTitle);
                $cleanTitle = rtrim($cleanTitle, " \t\n\r\0\x0B");
                $desc = get_option('landing_mod'.$idx.'_desc', $defaultDesc);
                return [
                    'key' => $idx,
                    'title' => $cleanTitle,
                    'desc' => $desc,
                    'icon' => $icon,
                    'bg' => $bg,
                    'coming_soon' => $isComingSoon,
                ];
            };

            // Active Core Modules (Row 1)
            $activeModules = [
                $rawMod(3, 'Bulk Campaigns', 'Launch high-volume WhatsApp campaigns to segmented audiences while keeping timing and response flow under control.', 'fas fa-bullhorn', 'bg-[#ea580c]', false),
                $rawMod(5, 'Contacts', 'Manage contact lists, segments, and campaign targets from one structured database.', 'fas fa-address-book', 'bg-[#ea580c]', false),
                $rawMod(6, 'Export Participants', 'Extract participants from WhatsApp groups for outreach, qualification, migration, and audience building workflows.', 'fas fa-users', 'bg-[#059669]', false),
                $rawMod(7, 'Profile Info', 'Retrieve WhatsApp profile information quickly to enrich lead context and improve handoff quality.', 'fas fa-id-card', 'bg-[#f97316]', false),
                $rawMod(8, 'Reports', 'Track campaign output, reply activity, and workflow performance with clear reporting for real operational decisions.', 'fas fa-chart-column', 'bg-[#ea580c]', false),
            ];

            // Coming Soon Modules (Row 2)
            $comingSoonModules = [
                $rawMod(1, 'AI Smart Reply', 'Generate fast, context-aware WhatsApp replies so agents can handle conversations with less manual effort.', 'fas fa-wand-magic-sparkles', 'bg-[#059669]', true),
                $rawMod(2, 'Auto Reply', 'Trigger automatic responses for inbound messages, off-hours support, lead capture, and common request handling.', 'fas fa-rotate-left', 'bg-[#f97316]', true),
                $rawMod(4, 'Chatbot', 'Build conversational flows that qualify leads, answer questions, and move contacts into the next step automatically.', 'fas fa-robot', 'bg-[#f59e0b]', true),
            ];
        @endphp

        <!-- Row 1: Active Core Modules (5 Cards) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6 items-stretch mb-6 sm:mb-8">
            @foreach($activeModules as $pm)
                <div class="bg-white rounded-[24px] p-6 sm:p-7 border border-slate-100/90 shadow-[0_4px_25px_rgba(0,0,0,0.03)] hover:shadow-xl hover:border-emerald-500/40 transition duration-300 flex flex-col justify-between group">
                    <div>
                        {{-- Container Header: Left Icon, Right Badge --}}
                        <div class="flex items-center justify-between gap-2 mb-5">
                            <div class="w-12 h-12 rounded-2xl {{ $pm['bg'] }} text-white flex items-center justify-center text-xl shadow-sm group-hover:scale-105 transition-transform shrink-0">
                                <i class="{{ $pm['icon'] }}"></i>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-emerald-50 text-[#059669] border border-emerald-200/90 shadow-2xs whitespace-nowrap">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#059669]"></span>
                                Active
                            </span>
                        </div>
                        <h3 class="text-base sm:text-lg font-black text-slate-900 mb-2 leading-snug">{{ $pm['title'] }}</h3>
                        <p class="text-xs text-slate-500 leading-relaxed font-normal">{{ $pm['desc'] }}</p>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <span class="w-7 h-7 rounded-full bg-slate-100/90 text-slate-400 flex items-center justify-center text-[10px] group-hover:bg-[#059669] group-hover:text-white transition">
                            <i class="fas fa-arrow-right"></i>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Row 2: Coming Soon Modules (3 Cards Centered) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 items-stretch max-w-5xl mx-auto">
            @foreach($comingSoonModules as $pm)
                <div class="bg-white rounded-[24px] p-6 sm:p-7 border border-amber-100/80 shadow-[0_4px_25px_rgba(245,158,11,0.04)] hover:shadow-xl hover:border-amber-400/50 transition duration-300 flex flex-col justify-between group relative overflow-hidden">
                    <div>
                        {{-- Container Header: Left Icon, Right Badge --}}
                        <div class="flex items-center justify-between gap-2 mb-5">
                            <div class="w-12 h-12 rounded-2xl {{ $pm['bg'] }} text-white flex items-center justify-center text-xl shadow-sm group-hover:scale-105 transition-transform shrink-0">
                                <i class="{{ $pm['icon'] }}"></i>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200/90 shadow-2xs whitespace-nowrap">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                Coming Soon
                            </span>
                        </div>
                        <h3 class="text-base sm:text-lg font-black text-slate-900 mb-2 leading-snug">{{ $pm['title'] }}</h3>
                        <p class="text-xs text-slate-500 leading-relaxed font-normal">{{ $pm['desc'] }}</p>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <span class="w-7 h-7 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-[10px] group-hover:bg-amber-500 group-hover:text-white transition">
                            <i class="fas fa-clock"></i>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- ============================================================== -->
<!-- ============================================================== -->
<!-- SECTION 4: PRICING (Dynamic Database Plans & Real Features)     -->
<!-- ============================================================== -->
@include('partials.pricing')

<!-- ============================================================== -->
<!-- SECTION 5: TESTIMONIALS (Interactive Auto & Manual Slider)      -->
<!-- ============================================================== -->
<section class="py-24 relative overflow-hidden" style="background-color: #eafbf3; background: linear-gradient(180deg, #e4f9ee 0%, #edfcf4 50%, #e2f8ec 100%);">
    <div class="container mx-auto px-4 lg:px-8">
        <!-- Section Header -->
        <div class="max-w-3xl mx-auto text-center mb-14">
            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-[#065f46] border border-emerald-300/60 mb-3">
                <i class="fas fa-comment-dots text-[11px] text-[#059669]"></i>
                <span>{!! get_option('landing_testi_badge', 'TESTIMONIALS') !!}</span>
            </span>
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-3">
                {{ get_option('landing_testi_title', 'What our customers say') }}
            </h2>
            <p class="text-sm md:text-base text-slate-600 font-medium max-w-2xl mx-auto">
                {{ get_option('landing_testi_subtitle', 'Trusted by businesses worldwide to streamline their WhatsApp operations.') }}
            </p>
        </div>

        @php
            $rawLandingTestimonials = get_option('landing_testimonials_data', null);
            $testimonialsList = [];
            if (!empty($rawLandingTestimonials)) {
                $parsedList = is_string($rawLandingTestimonials) ? json_decode($rawLandingTestimonials, true) : $rawLandingTestimonials;
                if (is_array($parsedList) && count($parsedList) > 0) {
                    $testimonialsList = $parsedList;
                }
            }
            if (empty($testimonialsList)) {
                $testimonialsList = [
                    [
                        'name' => get_option('landing_testi1_name', 'Priya Sharma'),
                        'role' => get_option('landing_testi1_role', 'Marketing Manager, RetailHub'),
                        'text' => get_option('landing_testi1_text', 'Whatsappbulk has completely transformed how we handle customer communication. Our response time improved by 70%!'),
                        'img' => get_option('landing_testi1_img', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120&auto=format&fit=crop&q=80'),
                        'rating' => (int)get_option('landing_testi1_rating', 5)
                    ],
                    [
                        'name' => get_option('landing_testi2_name', 'Rahul Mehta'),
                        'role' => get_option('landing_testi2_role', 'Founder, GrowKart'),
                        'text' => get_option('landing_testi2_text', 'The bulk campaign feature is a game-changer. We can now reach thousands of customers in minutes with excellent delivery rates.'),
                        'img' => get_option('landing_testi2_img', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=120&auto=format&fit=crop&q=80'),
                        'rating' => (int)get_option('landing_testi2_rating', 5)
                    ],
                    [
                        'name' => get_option('landing_testi3_name', 'Sneha Patel'),
                        'role' => get_option('landing_testi3_role', 'Operations Head, Foodies'),
                        'text' => get_option('landing_testi3_text', 'Easy to use, powerful features, and great support. Highly recommended for any business using WhatsApp.'),
                        'img' => get_option('landing_testi3_img', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=120&auto=format&fit=crop&q=80'),
                        'rating' => (int)get_option('landing_testi3_rating', 5)
                    ],
                    [
                        'name' => get_option('landing_testi4_name', 'Vikram Malhotra'),
                        'role' => get_option('landing_testi4_role', 'E-commerce Director, UrbanStyle'),
                        'text' => get_option('landing_testi4_text', 'Whatsappbulk automated our entire client follow-up workflow. We closed 35% more orders in our very first week with instant replies.'),
                        'img' => get_option('landing_testi4_img', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80'),
                        'rating' => (int)get_option('landing_testi4_rating', 5)
                    ],
                ];
            }
            $testiCount = count($testimonialsList);
            // Cycle through seamlessly for slider
            $allTestimonials = $testimonialsList;
            if ($testiCount > 0 && $testiCount < 6) {
                $allTestimonials = array_merge($testimonialsList, $testimonialsList);
            }
        @endphp

        <!-- Testimonials Carousel Component (Alpine.js) -->
        <div x-data="{
                activeSlide: 0,
                totalSlides: {{ count($allTestimonials) }},
                visibleCount: 3,
                autoTimer: null,
                touchStartX: 0,
                init() {
                    this.updateVisible();
                    window.addEventListener('resize', () => this.updateVisible());
                    this.startAuto();
                },
                updateVisible() {
                    if (window.innerWidth >= 1024) {
                        this.visibleCount = 3;
                    } else if (window.innerWidth >= 640) {
                        this.visibleCount = 2;
                    } else {
                        this.visibleCount = 1;
                    }
                },
                get maxIndex() {
                    return Math.max(0, this.totalSlides - this.visibleCount);
                },
                get slidePercent() {
                    return 100 / this.visibleCount;
                },
                startAuto() {
                    this.stopAuto();
                    if (this.totalSlides > 1) {
                        this.autoTimer = setInterval(() => {
                            this.next();
                        }, 3800);
                    }
                },
                stopAuto() {
                    if (this.autoTimer) {
                        clearInterval(this.autoTimer);
                        this.autoTimer = null;
                    }
                },
                next() {
                    if (this.activeSlide >= this.maxIndex) {
                        this.activeSlide = 0;
                    } else {
                        this.activeSlide++;
                    }
                },
                prev() {
                    if (this.activeSlide <= 0) {
                        this.activeSlide = this.maxIndex;
                    } else {
                        this.activeSlide--;
                    }
                },
                goTo(i) {
                    this.activeSlide = Math.min(i, this.maxIndex);
                    this.startAuto();
                },
                handleTouchStart(e) {
                    if (e.touches && e.touches.length > 0) {
                        this.touchStartX = e.touches[0].clientX;
                        this.stopAuto();
                    }
                },
                handleTouchEnd(e) {
                    if (e.changedTouches && e.changedTouches.length > 0) {
                        const diff = this.touchStartX - e.changedTouches[0].clientX;
                        if (Math.abs(diff) > 40) {
                            if (diff > 0) this.next();
                            else this.prev();
                        }
                    }
                    this.startAuto();
                }
            }"
            @mouseenter="stopAuto()"
            @mouseleave="startAuto()"
            @touchstart="handleTouchStart($event)"
            @touchend="handleTouchEnd($event)"
            class="relative max-w-7xl mx-auto">

            <!-- Carousel Sliding Viewport -->
            <div class="overflow-hidden py-4 -mx-2.5 px-2.5">
                <div class="flex transition-transform duration-500 ease-out"
                     :style="'transform: translateX(-' + (activeSlide * slidePercent) + '%)'">
                    @foreach($allTestimonials as $tItem)
                        @php
                            $starsNum = isset($tItem['rating']) ? (int)$tItem['rating'] : (isset($tItem['stars']) ? (int)$tItem['stars'] : 5);
                            $userImg = $tItem['img'] ?? '';
                            if (!empty($userImg) && !str_starts_with($userImg, 'http')) {
                                $userImg = asset($userImg);
                            }
                        @endphp
                        <div class="w-full sm:w-1/2 lg:w-1/3 shrink-0 p-2.5">
                            <div class="bg-white rounded-[24px] sm:rounded-[26px] p-6 sm:p-8 border border-slate-100 shadow-[0_4px_25px_rgba(0,0,0,0.03)] hover:shadow-xl transition duration-300 flex flex-col justify-between h-full">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-3xl font-serif text-emerald-600 leading-none font-black">“</span>
                                        <div class="text-amber-400 text-xs flex gap-0.5">
                                            @for($s = 0; $s < $starsNum; $s++)
                                                <i class="fas fa-star"></i>
                                            @endfor
                                        </div>
                                    </div>
                                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed font-normal mb-6 min-h-[64px]">
                                        &quot;{{ $tItem['text'] }}&quot;
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                                    @if(!empty($userImg))
                                        <img class="w-10 h-10 sm:w-11 sm:h-11 rounded-full object-cover border-2 border-emerald-200 shrink-0" 
                                             src="{{ $userImg }}" 
                                             alt="{{ $tItem['name'] }}"
                                             onerror="this.onerror=null; this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png';">
                                    @else
                                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-emerald-100 text-[#059669] font-bold text-sm flex items-center justify-center shrink-0 border border-emerald-200">
                                            {{ strtoupper(substr($tItem['name'] ?? 'U', 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <h4 class="text-sm font-bold text-slate-900 leading-tight truncate">{{ $tItem['name'] }}</h4>
                                        <span class="text-[11px] sm:text-xs text-slate-400 font-medium truncate block">{{ $tItem['role'] }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Manual Slider Controls: Prev/Next Arrows & Pagination Dots -->
            <div class="flex items-center justify-center gap-3 mt-6">
                <!-- Prev Button -->
                <button @click="prev()"
                        type="button"
                        class="w-9 h-9 rounded-full bg-white text-slate-700 border border-slate-200/90 flex items-center justify-center text-xs shadow-xs hover:bg-[#059669] hover:text-white hover:border-[#059669] transition cursor-pointer active:scale-95"
                        aria-label="Previous review">
                    <i class="fas fa-chevron-left text-[11px]"></i>
                </button>

                <!-- Dynamic Pagination Dots -->
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <template x-for="i in Math.min(6, maxIndex + 1)" :key="i - 1">
                        <button @click="goTo(i - 1)"
                                type="button"
                                class="transition-all duration-300 rounded-full cursor-pointer focus:outline-none"
                                :class="activeSlide === (i - 1) ? 'w-6 h-2 bg-[#059669]' : 'w-2 h-2 bg-slate-300 hover:bg-slate-400'"
                                :aria-label="'Go to slide ' + i">
                        </button>
                    </template>
                </div>

                <!-- Next Button -->
                <button @click="next()"
                        type="button"
                        class="w-9 h-9 rounded-full bg-white text-slate-700 border border-slate-200/90 flex items-center justify-center text-xs shadow-xs hover:bg-[#059669] hover:text-white hover:border-[#059669] transition cursor-pointer active:scale-95"
                        aria-label="Next review">
                    <i class="fas fa-chevron-right text-[11px]"></i>
                </button>
            </div>

        </div>
    </div>
</section>
<!-- ============================================================== -->
<!-- SECTION 6: FAQS (Alternating: White Background)                 -->
<!-- ============================================================== -->
<section id="faqs" class="py-24 bg-white border-y border-slate-100 relative" x-data="{ activeFaq: 1 }">
    <div class="container mx-auto px-4 lg:px-8">
        <!-- Section Header -->
        <div class="max-w-3xl mx-auto text-center mb-16">
            <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-100 text-[#065f46] border border-emerald-300/60 mb-3">
                <i class="fas fa-circle-question text-[11px] text-[#059669]"></i>
                <span>{!! get_option('landing_faqs_badge', 'FAQS') !!}</span>
            </span>
            <h2 class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight mb-3">
                {{ get_option('landing_faqs_title', 'Frequently asked questions') }}
            </h2>
            <p class="text-sm md:text-base text-slate-600 font-medium max-w-2xl mx-auto">
                {{ get_option('landing_faqs_subtitle', 'Get answers to common questions about Whatsappbulk.') }}
            </p>
        </div>

        <!-- 2 Columns Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-5xl mx-auto">
            
            <!-- Left Column FAQs -->
            <div class="space-y-4">
                <!-- FAQ 1 (Open by default) -->
                <div class="bg-[#f8fdfa] rounded-2xl border border-emerald-100/80 overflow-hidden shadow-xs">
                    <button @click="activeFaq = (activeFaq === 1 ? null : 1)" class="w-full px-6 py-5 text-left flex items-center justify-between font-bold text-sm text-slate-900 cursor-pointer">
                        <span>{{ get_option('landing_faq1_q', 'What is Whatsappbulk?') }}</span>
                        <span class="text-slate-400 font-bold text-lg" x-text="activeFaq === 1 ? '−' : '+'"></span>
                    </button>
                    <div x-show="activeFaq === 1" x-collapse class="px-6 pb-5 text-xs text-slate-500 leading-relaxed font-normal border-t border-slate-100 pt-3">
                        {{ get_option('landing_faq1_a', 'Whatsappbulk is an all-in-one WhatsApp marketing tool that helps you manage campaigns, automate replies, handle contacts, and track performance — all in one workspace.') }}
                    </div>
                </div>

                <!-- FAQ 2 -->
                <div class="bg-[#f8fdfa] rounded-2xl border border-emerald-100/80 overflow-hidden shadow-xs">
                    <button @click="activeFaq = (activeFaq === 2 ? null : 2)" class="w-full px-6 py-5 text-left flex items-center justify-between font-bold text-sm text-slate-900 cursor-pointer">
                        <span>{{ get_option('landing_faq2_q', 'Is it safe to use?') }}</span>
                        <span class="text-slate-400 font-bold text-lg" x-text="activeFaq === 2 ? '−' : '+'"></span>
                    </button>
                    <div x-show="activeFaq === 2" x-collapse class="px-6 pb-5 text-xs text-slate-500 leading-relaxed font-normal border-t border-slate-100 pt-3">
                        {{ get_option('landing_faq2_a', 'Yes, our platform employs anti-ban mechanisms, customizable message delays, and adheres to standard messaging policies to protect your accounts.') }}
                    </div>
                </div>

                <!-- FAQ 3 -->
                <div class="bg-[#f8fdfa] rounded-2xl border border-emerald-100/80 overflow-hidden shadow-xs">
                    <button @click="activeFaq = (activeFaq === 3 ? null : 3)" class="w-full px-6 py-5 text-left flex items-center justify-between font-bold text-sm text-slate-900 cursor-pointer">
                        <span>{{ get_option('landing_faq3_q', 'Can I upgrade my plan later?') }}</span>
                        <span class="text-slate-400 font-bold text-lg" x-text="activeFaq === 3 ? '−' : '+'"></span>
                    </button>
                    <div x-show="activeFaq === 3" x-collapse class="px-6 pb-5 text-xs text-slate-500 leading-relaxed font-normal border-t border-slate-100 pt-3">
                        {{ get_option('landing_faq3_a', 'Absolutely. You can upgrade, downgrade, or cancel your subscription at any time directly from your billing settings.') }}
                    </div>
                </div>
            </div>

            <!-- Right Column FAQs -->
            <div class="space-y-4">
                <!-- FAQ 4 -->
                <div class="bg-[#f8fdfa] rounded-2xl border border-emerald-100/80 overflow-hidden shadow-xs">
                    <button @click="activeFaq = (activeFaq === 4 ? null : 4)" class="w-full px-6 py-5 text-left flex items-center justify-between font-bold text-sm text-slate-900 cursor-pointer">
                        <span>{{ get_option('landing_faq4_q', 'Do you offer a free trial?') }}</span>
                        <span class="text-slate-400 font-bold text-lg" x-text="activeFaq === 4 ? '−' : '+'"></span>
                    </button>
                    <div x-show="activeFaq === 4" x-collapse class="px-6 pb-5 text-xs text-slate-500 leading-relaxed font-normal border-t border-slate-100 pt-3">
                        {{ get_option('landing_faq4_a', 'Yes, we provide a free starter tier so you can test campaigns and automation features before committing.') }}
                    </div>
                </div>

                <!-- FAQ 5 -->
                <div class="bg-[#f8fdfa] rounded-2xl border border-emerald-100/80 overflow-hidden shadow-xs">
                    <button @click="activeFaq = (activeFaq === 5 ? null : 5)" class="w-full px-6 py-5 text-left flex items-center justify-between font-bold text-sm text-slate-900 cursor-pointer">
                        <span>{{ get_option('landing_faq5_q', 'What payment methods do you accept?') }}</span>
                        <span class="text-slate-400 font-bold text-lg" x-text="activeFaq === 5 ? '−' : '+'"></span>
                    </button>
                    <div x-show="activeFaq === 5" x-collapse class="px-6 pb-5 text-xs text-slate-500 leading-relaxed font-normal border-t border-slate-100 pt-3">
                        {{ get_option('landing_faq5_a', 'We accept major credit and debit cards, UPI, PayPal, Stripe, and multiple international payment gateways.') }}
                    </div>
                </div>

                <!-- FAQ 6 -->
                <div class="bg-[#f8fdfa] rounded-2xl border border-emerald-100/80 overflow-hidden shadow-xs">
                    <button @click="activeFaq = (activeFaq === 6 ? null : 6)" class="w-full px-6 py-5 text-left flex items-center justify-between font-bold text-sm text-slate-900 cursor-pointer">
                        <span>{{ get_option('landing_faq6_q', 'Can I use multiple WhatsApp numbers?') }}</span>
                        <span class="text-slate-400 font-bold text-lg" x-text="activeFaq === 6 ? '−' : '+'"></span>
                    </button>
                    <div x-show="activeFaq === 6" x-collapse class="px-6 pb-5 text-xs text-slate-500 leading-relaxed font-normal border-t border-slate-100 pt-3">
                        {{ get_option('landing_faq6_a', 'Yes, our Professional and Business plans support multiple concurrent WhatsApp numbers and team members.') }}
                    </div>
                </div>

                <!-- FAQ 7 -->
                <div class="bg-[#f8fdfa] rounded-2xl border border-emerald-100/80 overflow-hidden shadow-xs">
                    <button @click="activeFaq = (activeFaq === 7 ? null : 7)" class="w-full px-6 py-5 text-left flex items-center justify-between font-bold text-sm text-slate-900 cursor-pointer">
                        <span>{{ get_option('landing_faq7_q', 'Do you provide customer support?') }}</span>
                        <span class="text-slate-400 font-bold text-lg" x-text="activeFaq === 7 ? '−' : '+'"></span>
                    </button>
                    <div x-show="activeFaq === 7" x-collapse class="px-6 pb-5 text-xs text-slate-500 leading-relaxed font-normal border-t border-slate-100 pt-3">
                        {{ get_option('landing_faq7_a', 'Our dedicated support team is available 24/7 via live chat, email, and WhatsApp to help you set up and scale.') }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ============================================================== -->
<!-- SECTION 7: BOTTOM CTA BANNER (Image 2 Pixel Match)              -->
<!-- ============================================================== -->
<section class="py-16 md:py-20 bg-white">
    <div class="container mx-auto px-4 lg:px-8">
        <div class="rounded-[28px] sm:rounded-[34px] p-8 sm:p-10 md:p-12 relative overflow-hidden shadow-2xl text-white"
             style="background: linear-gradient(90deg, #01502f 0%, #036f45 35%, #059669 75%, #014d2d 100%);">
            
            <!-- Subtle Watermark Background Shapes (Image 2 Match) -->
            <div class="absolute -top-16 -left-16 w-56 h-56 rounded-full bg-white/5 pointer-events-none"></div>
            <div class="absolute -bottom-20 left-28 w-64 h-64 rounded-full bg-emerald-400/10 pointer-events-none"></div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 md:gap-8 items-center relative z-10">
                <!-- WhatsApp App Icon in Rounded Box (Left, Image 2 Match) -->
                <div class="lg:col-span-2 flex justify-center lg:justify-start">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-[22px] bg-white/12 border border-white/20 flex items-center justify-center text-3xl sm:text-4xl text-white shadow-inner shrink-0">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                </div>

                <!-- Text Content (Center, Image 2 Match) -->
                <div class="lg:col-span-6 text-center lg:text-left">
                    <h2 class="text-2xl sm:text-3xl md:text-[32px] font-black tracking-tight mb-2 leading-tight text-white">
                        {{ get_option('landing_cta_title', 'Ready to scale your WhatsApp marketing?') }}
                    </h2>
                    <p class="text-xs sm:text-sm text-emerald-100/90 font-normal leading-relaxed max-w-xl">
                        {{ get_option('landing_cta_subtitle', 'Connect your WhatsApp account in seconds. Start sending bulk campaigns, automating customer replies, and converting more leads with zero coding required.') }}
                    </p>
                </div>

                <!-- Right Action Buttons (Image 2 Match) -->
                <div class="lg:col-span-4 flex flex-col items-center lg:items-end justify-center gap-2.5">
                    <a href="{{ url(get_option('landing_cta_btn_url', 'auth/signup')) }}" class="px-8 py-3.5 rounded-full font-bold text-sm bg-white text-[#025030] shadow-xl hover:bg-slate-50 transition transform hover:-translate-y-0.5 inline-flex items-center gap-2">
                        <span>{{ get_option('landing_cta_btn_text', 'Get Started Free') }}</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                    <a href="#pricing" class="inline-flex items-center gap-1.5 text-xs font-semibold text-white/90 hover:text-white transition">
                        <span>{{ __('View Pricing Plans') }}</span>
                        <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Floating Green WhatsApp Badge on Corner (Image 2 Match) -->
            <div class="absolute -bottom-2.5 -right-2.5 sm:bottom-4 sm:right-6 w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-[#25D366] text-white flex items-center justify-center text-lg sm:text-xl shadow-lg border-2 border-white">
                <i class="fab fa-whatsapp"></i>
            </div>
        </div>
    </div>
</section>

<!-- Floating WhatsApp Chat Widget (Bottom Right, Image 3 & 4 Match) -->
<a href="https://api.whatsapp.com" target="_blank" rel="noopener" class="fixed bottom-6 right-6 w-12 h-12 rounded-full bg-[#25D366] text-white flex items-center justify-center shadow-2xl text-2xl z-50 hover:scale-110 transition border-2 border-white">
    <i class="fab fa-whatsapp"></i>
</a>

@endsection