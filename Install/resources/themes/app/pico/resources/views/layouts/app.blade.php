@php
    $optionSidebarSmall = get_option('backend_sidebar_type', 1);
    $hasSidebarSmall = UserInfo::getDataUser("sidebar-small", $optionSidebarSmall);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  dir="{{ Language::getCurrent('dir') }}" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="{{ get_option("website_keyword", config('site.keywords')) }}">
    <meta name="description" content="{{ get_option("website_description", config('site.description')) }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ url( get_option("website_favicon", asset('public/img/favicon.png')) ) }}">
    <title>
        @hasSection('pagetitle')
            @yield('pagetitle')
        @else
            {{ (__(module("name")) ?? '' ) . " | " . get_option("website_title", config('site.title')) }}
        @endif
    </title>

    @yield('css')
    {!! Script::renderCss() !!}
    {!! Script::globals() !!}
    <link rel="stylesheet" href="{{ theme_public_asset('css/main.css') }}?version=9.0.8">
    @yield('head_embed_code')
        <style id="whatsappbulk-pico-light-green-theme">
        /* ==========================================================================
           WHATSAPPBULK REFINED LIGHT GREEN CANVAS (#edf7ea) & BLACK SIDEBAR (#0f172a)
           ========================================================================== */
        :root {
            --bs-body-bg: #edf7ea;
            --bs-primary: #15803d;
            --bs-primary-rgb: 21, 128, 61;
            --bs-success: #16a34a;
            --bs-success-rgb: 22, 163, 74;
        }

        html, body {
            background-color: #edf7ea !important;
            color: #0f172a;
        }

        .main {
            background-color: #edf7ea !important;
        }

        /* Top Header: Clean and crisp with soft green divider */
        .header {
            background-color: #ffffff !important;
            border-bottom: 1px solid rgba(34, 197, 94, 0.18) !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04) !important;
        }

        /* Left Sidebar: Sleek Black / Slate Theme (#0f172a) */
        .sidebar {
            background-color: #0f172a !important;
            border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.12) !important;
        }

        .sidebar-header {
            background-color: #0f172a !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        .sidebar-toggle {
            background-color: #1e293b !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
        }

        .sidebar-toggle a {
            color: #ffffff !important;
        }

        /* Sidebar Navigation Menu Links */
        .sidebar .menu-link {
            color: #94a3b8 !important;
            border-radius: 8px !important;
            transition: all 0.2s ease !important;
        }
        .sidebar .menu-link:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #ffffff !important;
        }
        .sidebar .menu-link i,
        .sidebar .menu-link span {
            color: #94a3b8;
            transition: color 0.2s ease;
        }
        .sidebar .menu-link:hover i,
        .sidebar .menu-link:hover span {
            color: #ffffff !important;
        }
        .sidebar .menu-item.active > .menu-link,
        .sidebar .menu-link.active,
        .sidebar .menu-link.show {
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(34, 197, 94, 0.35) !important;
        }
        .sidebar .menu-item.active > .menu-link i,
        .sidebar .menu-link.active i,
        .sidebar .menu-item.active > .menu-link span,
        .sidebar .menu-link.active span {
            color: #ffffff !important;
        }

        /* Sidebar Section Labels & Submenus */
        .sidebar .menu-section,
        .sidebar .text-gray-400,
        .sidebar span.fs-12 {
            color: #64748b !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px !important;
        }
        .sidebar .menu-sub {
            background-color: rgba(0, 0, 0, 0.2) !important;
            border-radius: 8px;
        }

        /* Sub-header background */
        .sub_header, .bg-polygon {
            background: #e2f4dc !important;
            border-bottom: 1px solid rgba(34, 197, 94, 0.18) !important;
        }

        /* Cards: Crisp white on soft light green canvas */
        .card {
            background-color: #ffffff !important;
            border: 1.5px solid rgba(34, 197, 94, 0.2) !important;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04) !important;
            border-radius: 1rem !important;
        }

        /* GRADIENT CLIENT GREEN BUTTONS */
        .btn-primary,
        .btn-success,
        .btn-dark,
        .btn-brand,
        .btn.btn-primary,
        .btn.btn-dark,
        .marketplace-wrapper .btn-dark,
        .card .btn-dark {
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%) !important;
            color: #ffffff !important;
            border: none !important;
            box-shadow: 0 4px 14px rgba(22, 163, 74, 0.32) !important;
            font-weight: 600 !important;
            transition: all 0.25s ease !important;
        }

        .btn-primary:hover,
        .btn-success:hover,
        .btn-dark:hover,
        .btn-brand:hover,
        .btn.btn-primary:hover,
        .btn.btn-dark:hover,
        .marketplace-wrapper .btn-dark:hover,
        .card .btn-dark:hover {
            background: linear-gradient(135deg, #16a34a 0%, #14532d 100%) !important;
            color: #ffffff !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(22, 163, 74, 0.42) !important;
        }

        /* Light / Outline buttons (e.g. Live Demo, Search, Filter) */
        .btn-light,
        .btn-outline,
        .marketplace-wrapper .btn-light {
            background: #ffffff !important;
            border: 1.5px solid rgba(34, 197, 94, 0.35) !important;
            color: #15803d !important;
            font-weight: 600 !important;
            transition: all 0.2s ease !important;
        }
        .btn-light:hover,
        .btn-outline:hover,
        .marketplace-wrapper .btn-light:hover {
            background: rgba(34, 197, 94, 0.12) !important;
            border-color: #22c55e !important;
            color: #14532d !important;
            transform: translateY(-1px) !important;
        }

        /* Badges and text accents */
        .text-primary {
            color: #15803d !important;
        }
        .bg-primary {
            background-color: #15803d !important;
        }
        .border-primary {
            border-color: #15803d !important;
        }

        /* Pagination active page */
        .page-item.active .page-link {
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%) !important;
            border-color: #15803d !important;
            color: #ffffff !important;
        }

        /* Form Controls Focus */
        .form-control:focus, .form-select:focus {
            border-color: #22c55e !important;
            box-shadow: 0 0 0 0.25rem rgba(34, 197, 94, 0.25) !important;
        }
    </style>
    <script>
        (function() {
            try {
                document.documentElement.classList.remove('sidebar-small');
            } catch(e) {}
        })();
    </script>
    <style id="permanent-sidebar-expanded">
    @media (min-width: 768px) {
        .sidebar-toggle { display: none !important; pointer-events: none !important; }
        html.sidebar-small .sidebar,
        .sidebar,
        .sidebar:not(:hover),
        html.sidebar-small .sidebar:not(:hover) {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;
            width: 250px !important;
            min-width: 250px !important;
            max-width: 250px !important;
            height: 100vh !important;
            z-index: 1000 !important;
            transform: none !important;
            transition: none !important;
            display: flex !important;
            flex-direction: column !important;
            background-color: #0f172a !important;
            padding: 0 !important;
            margin: 0 !important;
            overflow: hidden !important;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.12) !important;
            border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        .sidebar .sidebar-header,
        html.sidebar-small .sidebar .sidebar-header {
            width: 250px !important;
            min-width: 250px !important;
            max-width: 250px !important;
            height: 70px !important;
            min-height: 70px !important;
            max-height: 70px !important;
            flex-shrink: 0 !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 16px !important;
            box-sizing: border-box !important;
            background-color: #0f172a !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        .sidebar .menu,
        html.sidebar-small .sidebar .menu,
        .sidebar:not(:hover) .menu,
        html.sidebar-small .sidebar:not(:hover) .menu {
            width: 250px !important;
            min-width: 250px !important;
            max-width: 250px !important;
            flex-grow: 1 !important;
            height: calc(100vh - 70px) !important;
            max-height: calc(100vh - 70px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            padding: 10px 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .sidebar .menu-top,
        .sidebar .menu-bottom,
        html.sidebar-small .sidebar .menu-top,
        html.sidebar-small .sidebar .menu-bottom {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            padding: 0 10px !important;
            margin: 0 !important;
            box-sizing: border-box !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
        }

        .sidebar .menu-item,
        html.sidebar-small .sidebar .menu-item {
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            padding: 0 !important;
            margin: 1px 0 !important;
            text-align: left !important;
            box-sizing: border-box !important;
        }

        .sidebar .menu-link,
        html.sidebar-small .sidebar .menu-link,
        .sidebar:not(:hover) .menu-link,
        html.sidebar-small .sidebar:not(:hover) .menu-link {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            padding: 9px 12px !important;
            width: 100% !important;
            gap: 12px !important;
            border-radius: 8px !important;
            box-sizing: border-box !important;
            text-align: left !important;
        }

        .sidebar .menu-icon,
        html.sidebar-small .sidebar .menu-icon {
            width: 22px !important;
            min-width: 22px !important;
            max-width: 22px !important;
            margin: 0 !important;
            padding: 0 !important;
            text-align: center !important;
            flex-shrink: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 16px !important;
        }

        /* Keep text, labels, and arrows ALWAYS visible when cursor leaves sidebar */
        html.sidebar-small .sidebar .menu-item .menu-link .menu-title,
        .sidebar .menu-item .menu-link .menu-title,
        html.sidebar-small .sidebar:not(:hover) .menu-item .menu-link .menu-title,
        .sidebar:not(:hover) .menu-item .menu-link .menu-title {
            display: flex !important;
            opacity: 1 !important;
            visibility: visible !important;
            width: auto !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            flex-grow: 1 !important;
            font-size: 13.5px !important;
            line-height: 1.3 !important;
            text-align: left !important;
        }

        html.sidebar-small .sidebar .menu-item .menu-link .menu-arrow,
        .sidebar .menu-item .menu-link .menu-arrow,
        html.sidebar-small .sidebar:not(:hover) .menu-item .menu-link .menu-arrow,
        .sidebar:not(:hover) .menu-item .menu-link .menu-arrow {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            margin-left: auto !important;
        }

        html.sidebar-small .sidebar .large-show,
        .sidebar .large-show,
        html.sidebar-small .sidebar:not(:hover) .large-show,
        .sidebar:not(:hover) .large-show,
        .sidebar .sidebar-header .logo-large {
            display: block !important;
        }

        html.sidebar-small .sidebar .small-show,
        .sidebar .small-show,
        html.sidebar-small .sidebar:not(:hover) .small-show,
        .sidebar:not(:hover) .small-show,
        .sidebar .sidebar-header .logo-small {
            display: none !important;
        }

        html.sidebar-small .sidebar .menu-heading,
        .sidebar .menu-heading,
        html.sidebar-small .sidebar:not(:hover) .menu-heading,
        .sidebar:not(:hover) .menu-heading {
            text-align: left !important;
            padding: 12px 12px 4px 12px !important;
        }

        html.sidebar-small .sidebar .menu-heading span,
        .sidebar .menu-heading span,
        html.sidebar-small .sidebar:not(:hover) .menu-heading span,
        .sidebar:not(:hover) .menu-heading span {
            display: inline-block !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            letter-spacing: 0.5px !important;
            opacity: 1 !important;
            visibility: visible !important;
            color: #64748b !important;
            text-transform: uppercase !important;
        }

        html.sidebar-small .sidebar:not(:hover) .menu-heading:before,
        .sidebar:not(:hover) .menu-heading:before {
            content: "" !important;
            display: none !important;
        }

        html.sidebar-small .sidebar .sidebar-blocks,
        .sidebar .sidebar-blocks,
        html.sidebar-small .sidebar:not(:hover) .sidebar-blocks,
        .sidebar:not(:hover) .sidebar-blocks {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }

        .sidebar .menu-accordion {
            width: 100% !important;
            padding-left: 24px !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .sidebar .menu-accordion .menu-item {
            padding: 0 !important;
            margin: 1px 0 !important;
        }

        .sidebar .menu-accordion .menu-link {
            padding: 7px 10px !important;
            font-size: 13px !important;
        }

        .main,
        .header,
        html.sidebar-small .main,
        html.sidebar-small .header {
            margin-left: 250px !important;
            width: calc(100% - 250px) !important;
            box-sizing: border-box !important;
        }
    }

    /* ========================================================
       MOBILE DASHBOARD RESPONSIVENESS (Admin & User Portals)
       ======================================================== */
    @media (max-width: 767.98px) {
        .main,
        html.sidebar-small .main,
        body .main {
            position: relative !important;
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            left: 0 !important;
            right: 0 !important;
            top: 0 !important;
            padding-top: 70px !important;
            box-sizing: border-box !important;
            overflow-x: hidden !important;
        }
        .header,
        html.sidebar-small .header,
        body .header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            min-width: 100% !important;
            max-width: 100% !important;
            margin-left: 0 !important;
            z-index: 1020 !important;
            box-sizing: border-box !important;
        }
        /* Mobile Sidebar as a sleek offcanvas drawer */
        .sidebar,
        html.sidebar-small .sidebar {
            position: fixed !important;
            top: 0 !important;
            bottom: 0 !important;
            left: -280px !important;
            width: 270px !important;
            min-width: 270px !important;
            max-width: 270px !important;
            height: 100vh !important;
            z-index: 1050 !important;
            background-color: #0f172a !important;
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.3) !important;
            display: flex !important;
            flex-direction: column !important;
        }
        html.sidebar-open .sidebar,
        body.sidebar-open .sidebar {
            left: 0 !important;
        }
        /* Backdrop overlay on mobile */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(2px);
            z-index: 1040;
            cursor: pointer;
        }
        html.sidebar-open .sidebar-backdrop,
        body.sidebar-open .sidebar-backdrop {
            display: block !important;
        }
        .sidebar-toggle {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            z-index: 1025 !important;
        }
        /* Container and content padding on mobile */
        .container,
        .container-fluid,
        .container1 {
            padding-left: 12px !important;
            padding-right: 12px !important;
            max-width: 100% !important;
            width: 100% !important;
        }
        /* Tabs on mobile: horizontal scroll without wrap */
        .nav-pills,
        .nav-tabs {
            flex-wrap: nowrap !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
            padding-bottom: 6px !important;
            max-width: 100% !important;
        }
        .nav-pills .nav-item,
        .nav-tabs .nav-item {
            flex-shrink: 0 !important;
        }
        /* Tables responsive scroll */
        .table-responsive {
            width: 100% !important;
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }
        /* Cards responsive padding */
        .card {
            border-radius: 0.75rem !important;
            margin-bottom: 1rem !important;
            max-width: 100% !important;
        }
        .card-body {
            padding: 1rem !important;
        }
    }
    </style>
</head>
<body>
    <div class="loading">
        <div class="d-flex justify-content-center align-items-center hp-100">
            <div class="loader"></div>
        </div>
    </div>

    <div id="drag-overlay">
        <div class="overlay-box">
            <i class="fas fa-cloud-upload-alt"></i>
            <h2 class="mb-2">{{ __('Drag & Drop your files here') }}</h2>
            <p class="mb-0 text-muted">
                {{ __('Supported formats:') }}
                <span class="text-uppercase">
                    {{ get_option('file_allowed_file_types', 'jpeg,gif,png,jpg,webp,mp4,csv,pdf,mp3,wmv,json') }}
                </span>
            </p>
        </div>
    </div>

    @include('partials.header')
    @include('partials.sidebar')
    <div class="sidebar-backdrop" onclick="document.documentElement.classList.remove('sidebar-open'); document.body.classList.remove('sidebar-open');"></div>
    
    <div class="main">
        @php
            $pendingUpiPayment = null;
            try {
                $waUid = Auth::id();
                $waUser = Auth::user();
                if (!$waUser && class_exists('\Modules\Auth\Models\Auth')) {
                    $waUser = \Modules\Auth\Models\Auth::user();
                }
                if (!$waUser && $waUid) {
                    $waUser = \DB::table('users')->where('id', $waUid)->first();
                }

                $userPayStatus = strtolower(trim((string)($waUser->payment_status ?? '')));
                $userPayMethod = strtolower(trim((string)($waUser->payment_method ?? '')));

                // Check payment_manual table for any pending manual payments
                $pendingManualRecord = null;
                if ($waUid && class_exists('\Modules\AdminManualPayments\Models\PaymentManual')) {
                    $pendingManualRecord = \Modules\AdminManualPayments\Models\PaymentManual::where('uid', $waUid)
                        ->where(function($q) {
                            $q->where('status', 0)
                              ->orWhere('status', '0');
                        })
                        ->orderBy('id', 'desc')
                        ->first();
                }

                // If admin explicitly approved ($userPayStatus === 'approved' or '1')
                // AND there is no pending manual record, hide notice!
                if (in_array($userPayStatus, ['approved', '1']) && !$pendingManualRecord) {
                    $pendingUpiPayment = null;
                } else {
                    $isPending = false;
                    $pendingUtr = null;

                    // 1. Pending manual payment record found
                    if ($pendingManualRecord && !empty($pendingManualRecord->payment_id)) {
                        $pInfo = strtolower((string)$pendingManualRecord->notes . ' ' . (string)$pendingManualRecord->payment_info);
                        if (!str_contains($pInfo, 'razorpay') && !str_starts_with((string)$pendingManualRecord->payment_id, 'pay_')) {
                            $isPending = true;
                            $pendingUtr = $pendingManualRecord->payment_id;
                        }
                    }

                    // 2. Or user record has payment_status pending and method UPI
                    if (!$isPending && $waUser) {
                        if ($userPayMethod === 'upi' && in_array($userPayStatus, ['pending', '0', 'pending verification'])) {
                            $isPending = true;
                            $pendingUtr = $waUser->payment_reference ?? 'Pending';
                        }
                    }

                    if ($isPending && !empty($pendingUtr)) {
                        $pendingUpiPayment = (object)[
                            'utr_number' => $pendingUtr
                        ];
                    }
                }
            } catch (\Throwable $e) {}

            $waDaysLeft = null;
            $waIsExpiringSoon = false;
            $waIsExpired = false;
            $waExpiryNotice = "";
            $waIsLocked = false;
            try {
                if ($waUser && !empty($waUser->expiration_date) && $waUser->expiration_date != -1) {
                    $expTimestamp = is_numeric($waUser->expiration_date) ? (int)$waUser->expiration_date : strtotime($waUser->expiration_date);
                    if ($expTimestamp > 0) {
                        $diffSeconds = $expTimestamp - time();
                        $waExpFormatted = date('M d, Y h:i A', $expTimestamp);
                        $alertThresholdDays = 7;
                        if (function_exists('get_option')) {
                            $alertThresholdDays = (int)get_option('expire_alert_days', 7);
                        }

                        if ($diffSeconds <= 0) {
                            $waIsExpired = true;
                            if ($diffSeconds <= -120) {
                                $waIsLocked = true;
                            }
                            $waExpiryNotice = "Your plan has expired on " . date('M d, Y', $expTimestamp);
                        } elseif ($diffSeconds <= ($alertThresholdDays * 86400)) {
                            $waIsExpiringSoon = true;
                            if ($diffSeconds < 3600) {
                                $mins = max(1, (int)ceil($diffSeconds / 60));
                                $waExpiryNotice = "Your plan will expire in {$mins} minute(s) on {$waExpFormatted}";
                            } elseif ($diffSeconds < 86400) {
                                $hrs = (int)ceil($diffSeconds / 3600);
                                $waExpiryNotice = "Your plan will expire in {$hrs} hour(s) on {$waExpFormatted}";
                            } else {
                                $days = (int)ceil($diffSeconds / 86400);
                                $waExpiryNotice = "Your plan will expire in {$days} day(s) on " . date('M d, Y', $expTimestamp);
                            }
                        }
                    }
                }

                // If UPI payment is pending verification, do NOT lock the account!
                if ($pendingUpiPayment) {
                    $waIsLocked = false;
                }
            } catch (\Throwable $e) {}
        @endphp

        @if($pendingUpiPayment && !empty($pendingUpiPayment->utr_number))
            <div class="container-fluid pt-3">
                <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 mb-3 shadow-sm" style="background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; color: #92400e;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="font-size: 22px; color: #d97706;"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div>
                            <div class="fw-bold" style="font-size: 14.5px;">Notice: Your UPI payment (UTR: <code style="background:#fef3c7; color:#92400e; padding:2px 7px; border-radius:4px; font-weight:700;">{{ $pendingUpiPayment->utr_number }}</code>) is pending for admin verification.</div>
                            <div class="small" style="color: #b45309;">Your workspace features remain fully active while our admin team reviews your transaction reference.</div>
                        </div>
                    </div>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">Pending Verification</span>
                </div>
            </div>
        @endif

        @if(($waIsExpired || $waIsExpiringSoon) && !$pendingUpiPayment)
            <div class="container-fluid pt-2" id="wa-expire-header-banner" style="{{ $waIsLocked ? 'display: block !important;' : 'display: none;' }}">
                <div class="alert {{ $waIsExpired ? 'alert-danger' : 'alert-warning' }} d-flex align-items-center justify-content-between p-3 mb-3 shadow-sm position-relative" style="background: {{ $waIsExpired ? '#fef2f2' : '#fffbeb' }}; border: {{ $waIsLocked ? '2px solid #ef4444' : '1.5px solid ' . ($waIsExpired ? '#fecaca' : '#fde68a') }}; border-radius: 12px; color: {{ $waIsExpired ? '#991b1b' : '#92400e' }};">
                    <div class="d-flex align-items-center gap-3">
                        <div style="font-size: 22px; color: {{ $waIsExpired ? '#dc2626' : '#d97706' }};">
                            <i class="fa-solid {{ $waIsLocked ? 'fa-lock' : 'fa-triangle-exclamation' }}"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size: 14.5px;">{{ $waIsLocked ? '🔒 Account Locked: ' : ($waIsExpired ? 'Urgent: ' : 'Warning: ') }}{{ $waExpiryNotice }}.</div>
                            <div class="small" style="color: {{ $waIsExpired ? '#b91c1c' : '#b45309' }};">
                                {{ $waIsLocked ? 'All navigation links are locked. Please renew your plan now to restore full access.' : ($waIsExpired ? 'Features are paused. Please renew your plan now to restore full access.' : 'Please renew your subscription to avoid campaign and broadcast interruption.') }}
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ url('/app/profile/plan') }}" class="btn btn-sm {{ $waIsExpired ? 'btn-danger' : 'btn-warning text-dark' }} px-3 py-2 fw-bold rounded-pill text-white text-decoration-none" style="font-size: 12.5px; white-space: nowrap; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <i class="fa-solid fa-bolt me-1"></i> {{ $waIsLocked ? 'Renew Plan Now' : 'Renew Plan' }}
                        </a>
                        @if(!$waIsLocked)
                            <button type="button" class="btn btn-sm btn-link text-muted p-1 text-decoration-none" onclick="dismissWaExpireBanner()" title="Close Notification" style="font-size: 20px; line-height: 1; border: none; background: transparent; cursor: pointer;">
                                &times;
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            <script>
                (function() {
                    @if(!$waIsLocked)
                        if (!sessionStorage.getItem('wa_dismiss_expire_banner')) {
                            var el = document.getElementById('wa-expire-header-banner');
                            if (el) el.style.display = 'block';
                        }
                    @else
                        var el = document.getElementById('wa-expire-header-banner');
                        if (el) el.style.display = 'block';
                    @endif
                })();
                function dismissWaExpireBanner() {
                    sessionStorage.setItem('wa_dismiss_expire_banner', '1');
                    var el = document.getElementById('wa-expire-header-banner');
                    if (el) el.style.display = 'none';
                }
            </script>
        @endif

         @if(View::hasSection('sub_header') && View::hasSection('content') && View::hasSection('form'))
            @php
                // Decode the JSON attributes if provided
                $attributes = @json_decode( htmlspecialchars_decode( View::yieldContent('form') ), true) ?? [];
                // Convert to string format: key="value"
                $attributesString = collect($attributes)->map(function ($value, $key) {
                    return $key . '="' . e($value) . '"';
                })->implode(' ');
            @endphp

            <form {!! $attributesString !!}>
                @csrf

                @hasSection('sub_header')
                    <div class="border-bottom mb-5 bg-polygon">
                        <div class="container">
                            <div class="pt-4 pb-4">
                                @yield('sub_header')
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')
            </form>
        @else
            @hasSection('sub_header')
                <div class="border-bottom mb-5 bg-polygon">
                    <div class="container">
                        <div class="pt-4 pb-4">
                            @yield('sub_header')
                        </div>
                    </div>
                </div>
            @endif

            @yield('content')
        @endif
    </div>

    @include('partials.footer')

    <!-- FOOTER END -->
    <script type="text/javascript" src="{{ theme_public_asset('plugins/highcharts/highcharts.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/highcharts/modules/exporting.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/highcharts/modules/map.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/jquery/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/codemirror5/lib/codemirror.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/codemirror5/mode/htmlmixed/htmlmixed.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/codemirror5/mode/php/php.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/codemirror5/mode/css/css.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/codemirror5/mode/javascript/javascript.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/codemirror5/mode/xml/xml.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/izitoast/js/iziToast.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/lodash/lodash.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/moment/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/emojionearea/emojionearea.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/datetimepicker/timepicker-addon.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/daterangepicker/daterangepicker.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/select2/js/select2.full.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/sweetalert2/sweetalert2.all.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/ion.rangeSlider/js/ion.rangeSlider.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/tinymce/tinymce.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/inputTags/inputTags.jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/lazysizes/lazysizes.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/datatables/datatables.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('plugins/fullcalendar/index.global.min.js') }}"></script>
    <script type="text/javascript" src="{{ theme_public_asset('js/main.js') }}?version=9.0.5"></script>
    @yield('script')
    {!! Script::renderJs() !!}
    {!! Script::renderRaw() !!}

    @auth
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        try {
            var rawPending = localStorage.getItem('wb_pending_signup_plan');
            if (rawPending) {
                var pData = JSON.parse(rawPending);
                if (pData && pData.source === 'renewal') {
                    if (window.location.search.includes('renewed=1') || window.location.search.includes('payment_id=')) {
                        localStorage.removeItem('wb_pending_signup_plan');
                    }
                } else {
                    @if(empty(auth()->user()->plan_id) || auth()->user()->plan_id == 0)
                    fetch('{{ route("app.profile.sync_pending_plan") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(pData)
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res && res.status === 1) {
                            localStorage.removeItem('wb_pending_signup_plan');
                            window.location.reload();
                        }
                    })
                    .catch(function(){});
                    @else
                    localStorage.removeItem('wb_pending_signup_plan');
                    @endif
                }
            }
        } catch(e) {}
    });
    </script>
    @endauth
</body>
</html>