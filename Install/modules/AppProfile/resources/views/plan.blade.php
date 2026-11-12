@php
    $user = $user ?? auth()->user();
    $plan = $user ? $user->plan : null;

    // Local INR price formatter – scoped only to this plan page (does not affect global price() helper)
    $inrPrice = function($amount) {
        if (!is_numeric($amount)) return '₹0';
        $formatted = rtrim(rtrim(number_format((float)$amount, 2, '.', ''), '0'), '.');
        return '₹' . $formatted;
    };
    if($plan){
        $plan_detail = \Pricing::plansWithFeatures($plan->id);
    }

    $pricing = \Pricing::plansWithFeatures();
    $rawPlanTypes = \Modules\AdminPlans\Facades\Plan::getTypes();
    // Exclude Lifetime plans so only Monthly & Yearly tabs are displayed
    $planTypes = collect($rawPlanTypes)->filter(function($label, $key) use ($pricing) {
        return (int)$key !== 3 && stripos((string)$label, 'lifetime') === false && !empty($pricing[$key]);
    })->all();

    $expired = false;
    if ($user && !empty($user->expiration_date)) {
        if ($user->expiration_date == -1) {
            $expired = false;
        } else {
            $expTimestamp = is_numeric($user->expiration_date) ? (int)$user->expiration_date : strtotime($user->expiration_date);
            $expired = ($expTimestamp > 0 && $expTimestamp < time());
        }
    } else {
        $expired = true;
    }

    $credit_summary = Credit::getCreditUsageSummary();

    $dbPlans = class_exists('\Modules\AdminPlans\Models\Plans') ? \Modules\AdminPlans\Models\Plans::where('status', 1)->get() : collect([]);
    $dynamicUpiId = get_option('payment_upi_id', env('PAYMENT_UPI_ID', ''));
    $dynamicUpiHolderName = get_option('payment_upi_name', env('APP_NAME', 'WhatsApp Bulk'));

    // Current expiry formatted for display & date calculation
    $currExpireIso = ($user && $user->expiration_date && $user->expiration_date > 0) ? date('c', $user->expiration_date) : '';
    $currExpireFormatted = ($user && $user->expiration_date && $user->expiration_date > 0) ? date_show($user->expiration_date) : 'Expired / Inactive';

    // Group plans for modal selector
    $modalPlanOptions = [];
    foreach ($dbPlans as $dp) {
        $cleanName = trim(str_ireplace(['monthly', 'yearly', 'annual', 'plan'], '', $dp->name));
        if (!isset($modalPlanOptions[$cleanName])) {
            $modalPlanOptions[$cleanName] = [
                'name' => $cleanName,
                'monthly_id' => $dp->type == 1 ? $dp->id : null,
                'monthly_price' => $dp->type == 1 ? (float)$dp->price : 0,
                'yearly_id' => $dp->type == 2 ? $dp->id : null,
                'yearly_price' => $dp->type == 2 ? (float)$dp->price : 0,
            ];
        } else {
            if ($dp->type == 1) {
                $modalPlanOptions[$cleanName]['monthly_id'] = $dp->id;
                $modalPlanOptions[$cleanName]['monthly_price'] = (float)$dp->price;
            } elseif ($dp->type == 2) {
                $modalPlanOptions[$cleanName]['yearly_id'] = $dp->id;
                $modalPlanOptions[$cleanName]['yearly_price'] = (float)$dp->price;
            }
        }
    }
    foreach ($modalPlanOptions as $k => $opt) {
        if (!$opt['yearly_price'] && $opt['monthly_price']) {
            $modalPlanOptions[$k]['yearly_price'] = round($opt['monthly_price'] * 10);
            $modalPlanOptions[$k]['yearly_id'] = $opt['monthly_id'];
        }
        if (!$opt['monthly_price'] && $opt['yearly_price']) {
            $modalPlanOptions[$k]['monthly_price'] = round($opt['yearly_price'] / 10);
            $modalPlanOptions[$k]['monthly_id'] = $opt['yearly_id'];
        }
    }

    $activeModalOption = null;
    if ($plan) {
        foreach ($modalPlanOptions as $opt) {
            if ($plan->id == $opt['monthly_id'] || $plan->id == $opt['yearly_id'] || stripos($plan->name, $opt['name']) !== false) {
                $activeModalOption = $opt;
                break;
            }
        }
    }
    if (!$activeModalOption && !empty($modalPlanOptions)) {
        $activeModalOption = reset($modalPlanOptions);
    }
    $hasMonthlyModalPlans = $dbPlans->where('type', 1)->isNotEmpty();
    $hasYearlyModalPlans = $dbPlans->where('type', 2)->isNotEmpty();
    $initMonthlyPrice = ($activeModalOption && $activeModalOption['monthly_price']) ? (float)$activeModalOption['monthly_price'] : 0;
    $initYearlyPrice = ($activeModalOption && $activeModalOption['yearly_price']) ? (float)$activeModalOption['yearly_price'] : 0;
    $initPlanName = $activeModalOption ? $activeModalOption['name'] : ($plan->name ?? 'Standard');
    $initPlanId = $activeModalOption ? ($activeModalOption['monthly_id'] ?: $activeModalOption['yearly_id']) : ($plan->id ?? 1);
@endphp

@extends('layouts.app')

@section('css')
<style>
    /* ── WA Plan Feature Items (Website Builder Style) ── */
    .wa-feature-item {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        font-size: 14.5px !important;
        line-height: 1.5 !important;
        margin-bottom: 10px !important;
        transition: all 0.2s ease !important;
    }

    /* Badge container */
    .wa-feat-badge {
        width: 22px !important;
        height: 22px !important;
        min-width: 22px !important;
        border-radius: 50% !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }

    /* ── ENABLED: Solid emerald green circle with white checkmark ── */
    .wa-feat-enabled .wa-feat-badge {
        background: #10b981 !important;
        color: #ffffff !important;
        box-shadow: 0 2px 7px rgba(16, 185, 129, 0.35) !important;
    }
    .wa-feat-enabled .wa-feat-label {
        color: #1e293b !important;
        font-weight: 600 !important;
        text-decoration: none !important;
        letter-spacing: -0.1px !important;
    }
    .wa-feat-enabled:hover .wa-feat-badge {
        transform: scale(1.15) !important;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.45) !important;
    }

    /* ── DISABLED: Soft coral-red circle with white horizontal minus + attractive strikeout ── */
    .wa-feat-disabled .wa-feat-badge {
        background: #f87171 !important;
        color: #ffffff !important;
        opacity: 0.95 !important;
        box-shadow: 0 2px 6px rgba(248, 113, 113, 0.25) !important;
    }
    .wa-feat-disabled .wa-feat-label {
        color: #94a3b8 !important;
        font-weight: 500 !important;
        text-decoration: line-through !important;
        text-decoration-thickness: 2px !important;
        text-decoration-color: #cbd5e1 !important;
        letter-spacing: -0.1px !important;
    }
    .wa-feat-disabled:hover .wa-feat-label {
        color: #64748b !important;
    }
</style>
@endsection

@section('content')
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @include("appprofile::partials.profile-header")
    
    <style>
        .wa-feature-item { display: flex !important; align-items: center !important; gap: 10px !important; font-size: 14.5px !important; line-height: 1.5 !important; margin-bottom: 10px !important; transition: all 0.2s ease !important; }
        .wa-feat-badge { width: 22px !important; height: 22px !important; min-width: 22px !important; border-radius: 50% !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0 !important; transition: transform 0.2s ease, box-shadow 0.2s ease !important; }
        .wa-feat-enabled .wa-feat-badge { background: #10b981 !important; color: #ffffff !important; box-shadow: 0 2px 7px rgba(16, 185, 129, 0.35) !important; }
        .wa-feat-enabled .wa-feat-label { color: #1e293b !important; font-weight: 600 !important; text-decoration: none !important; letter-spacing: -0.1px !important; }
        .wa-feat-enabled:hover .wa-feat-badge { transform: scale(1.15) !important; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.45) !important; }
        .wa-feat-disabled .wa-feat-badge { background: #f87171 !important; color: #ffffff !important; opacity: 0.95 !important; box-shadow: 0 2px 6px rgba(248, 113, 113, 0.25) !important; }
        .wa-feat-disabled .wa-feat-label { color: #94a3b8 !important; font-weight: 500 !important; text-decoration: line-through !important; text-decoration-thickness: 2px !important; text-decoration-color: #cbd5e1 !important; letter-spacing: -0.1px !important; }
        .wa-feat-disabled:hover .wa-feat-label { color: #64748b !important; }
    </style>

    <div class="container py-5 pricing">

        @include("components.main-message")

        @if(request()->has('renewed') || request()->has('payment_id'))
            <div class="alert alert-success d-flex align-items-center mb-4 shadow-sm" style="border-radius: 14px; background: #f0fdf4; border: 1.5px solid #86efac; color: #15803d; padding: 18px;">
                <i class="fa-solid fa-circle-check fs-24 me-3 text-success"></i>
                <div>
                    <h5 class="fw-bold mb-1 text-success">{{ __('Subscription Successfully Renewed / Activated!') }}</h5>
                    <p class="mb-0 fs-14 text-dark opacity-75">{{ __('Your plan has been extended successfully. All WhatsApp campaigns, bulk messaging, and automation features are fully active.') }}</p>
                </div>
            </div>
        @endif
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-2 fs-35">{{ __('Choose a plan that suits you. Grow business fast.') }}</h2>
            <p class="mx-auto text-lg text-muted max-w-500">
                {{ __('Choose an affordable plan packed with top features to engage your audience, create loyalty, and boost sales.') }}
            </p>
        </div>

        <div class="d-flex mx-auto justify-content-center mb-5">
            @if(count($planTypes) > 1)
            <ul class="nav nav-tabs justify-content-center mb-4 gap-0 b-r-20 border border-gray-300 overflow-hidden" id="pricingTab" role="tablist">
                @foreach($planTypes as $typeKey => $typeLabel)
                    <li class="nav-item {{ $typeKey==2?"border-start border-end border-gray-300":"" }}" role="presentation">
                        <button
                            class="nav-link px-4 border-bottom-0 py-2 fw-bold bg-active-gray-200 text-active-gray-800 @if($loop->first) active @endif"
                            id="tab-{{ $typeKey }}"
                            data-bs-toggle="tab"
                            data-bs-target="#content-{{ $typeKey }}"
                            type="button"
                            role="tab"
                            aria-controls="content-{{ $typeKey }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        >
                            {{ __($typeLabel) }}
                            
                        </button>
                    </li>
                @endforeach
            </ul>
            @endif
        </div>

        {{-- Plans content --}}
        <div class="tab-content" id="pricingTabContent">
            @foreach($planTypes as $typeKey => $typeLabel)
                <div class="tab-pane fade @if($loop->first) show active @endif" 
                    id="content-{{ $typeKey }}" 
                    role="tabpanel"
                    aria-labelledby="tab-{{ $typeKey }}">
                    
                    @php
                        $tabPlans = collect($pricing[$typeKey] ?? []);
                        // Current plan for user
                        $renewPlan = $tabPlans->firstWhere('id', $user->plan_id ?? 0);
                        if (!$renewPlan && $tabPlans->isNotEmpty()) {
                            $renewPlan = $tabPlans->first();
                        }
                        $currPrice = $renewPlan ? (float)($renewPlan['price'] ?? 0) : 0;

                        // Upgrade plans: higher price than current plan, or alternative plans if price match
                        $upgradePlans = $tabPlans->filter(function($p) use ($renewPlan, $currPrice) {
                            if (!$renewPlan) return true;
                            if ($p['id'] == $renewPlan['id']) return false;
                            return (float)($p['price'] ?? 0) > $currPrice;
                        })->values();

                        if ($upgradePlans->isEmpty() && count($tabPlans) > 1) {
                            $upgradePlans = $tabPlans->filter(function($p) use ($renewPlan) {
                                return !$renewPlan || $p['id'] != $renewPlan['id'];
                            })->values();
                        }

                        $isTabYearly = (stripos($typeLabel, 'year') !== false || (isset($typeKey) && $typeKey == 2));
                    @endphp

                    @if($expired)
                        {{-- CASE 1: EXPIRED SUBSCRIPTION -> HORIZONTAL RECTANGLE CARD --}}
                        @if($renewPlan)
                            @php
                                $cleanRenewName = trim(str_ireplace(['monthly', 'yearly', 'annual', 'plan'], '', $renewPlan['name'] ?? ''));
                                $rOpt = $modalPlanOptions[$cleanRenewName] ?? null;
                                $renewMonthlyPrice = $rOpt ? $rOpt['monthly_price'] : ($typeKey == 1 ? (float)($renewPlan['price'] ?? 39) : round((float)($renewPlan['price'] ?? 390) / 10));
                                $renewYearlyPrice = $rOpt ? $rOpt['yearly_price'] : ($typeKey == 2 ? (float)($renewPlan['price'] ?? 390) : round((float)($renewPlan['price'] ?? 39) * 10));
                                $renewPlanId = $renewPlan['id'] ?? ($rOpt ? ($typeKey == 2 ? $rOpt['yearly_id'] : $rOpt['monthly_id']) : 1);
                            @endphp
                            <div class="mb-5">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <div style="width: 4px; height: 26px; background: #ef4444; border-radius: 4px;"></div>
                                    <h3 class="h4 fw-bold text-dark mb-0">
                                        {{ __('Please renew your current plan to use the website') }}
                                    </h3>
                                </div>

                                <div class="card shadow-sm border-0 mb-4" style="border-radius: 20px; background: #ffffff; border: 2px solid #ef4444 !important; box-shadow: 0 10px 30px rgba(239, 68, 68, 0.08);">
                                    <div class="card-body p-4 p-md-5">
                                        <div class="row align-items-center">
                                            <!-- Left: Title, Badges & Feature Highlights -->
                                            <div class="col-lg-7 col-md-12 mb-4 mb-lg-0 border-end-lg pe-lg-4">
                                                <div class="mb-3">
                                                    <h2 class="h3 fw-bold text-dark mb-2" style="font-size: 28px; line-height: 1.2;">{{ $renewPlan['name'] }}</h2>
                                                    <div class="mt-1">
                                                        <span class="badge bg-danger px-3 py-1 fw-bold text-white shadow-sm" style="border-radius: 8px; font-size: 12px; background: #ef4444 !important; display: inline-flex; align-items: center; gap: 4px;">
                                                            <i class="fa-solid fa-crown me-1"></i> {{ __('Current Plan') }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <p class="text-muted mb-3 fw-medium">
                                                    {{ $renewPlan['desc'] ?? __('Your subscription has expired. Renew your current plan now to immediately restore full access to WhatsApp campaigns, automation, and messages.') }}
                                                </p>

                                                <div class="row g-2 pt-2">
                                                    @foreach(array_slice($renewPlan['features'] ?? [], 0, 6) as $feature)
                                                        <div class="col-sm-6 mb-2">
                                                            <div class="wa-feature-item d-flex align-items-center gap-2 {{ $feature['check'] ? 'wa-feat-enabled' : 'wa-feat-disabled' }}">
                                                                <span class="wa-feat-badge">
                                                                    @if($feature['check'])
                                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                                            <polyline points="2.5 6.5 4.8 9 9.5 3.5"></polyline>
                                                                        </svg>
                                                                    @else
                                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                                                                            <line x1="3" y1="6" x2="9" y2="6"></line>
                                                                        </svg>
                                                                    @endif
                                                                </span>
                                                                <span class="wa-feat-label">{{ __($feature['label']) }}</span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <!-- Right: Price & Pay Now Button -->
                                            <div class="col-lg-5 col-md-12 text-center text-lg-end ps-lg-4">
                                                <div class="mb-2">
                                                    <span class="h1 fw-bold text-dark" style="font-size: 42px; color: #1e293b;">
                                                        {{ !empty($renewPlan['free_plan']) ? $inrPrice(0) : $inrPrice($renewPlan['price'] ?? 0) }}
                                                    </span>
                                                    <span class="text-muted fw-bold" style="font-size: 16px;">/{{ strtolower(__($typeLabel)) }}</span>
                                                    
                                                </div>
                                                <div class="text-muted small mb-3">
                                                    {{ __('Instant automatic reactivation upon payment confirmation') }}
                                                </div>
                                                <button type="button" class="btn btn-danger fw-bold px-5 py-3 shadow" style="border-radius: 12px; font-size: 16px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; letter-spacing: 0.3px; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4); text-decoration: none; color: #fff;" onclick="openWaRenewModal({{ $renewPlanId }}, '{{ addslashes($renewPlan['name']) }}', {{ $renewMonthlyPrice }}, {{ $renewYearlyPrice }}, '{{ $isTabYearly ? 'yearly' : 'monthly' }}')">
                                                    <i class="fa-regular fa-bolt me-2"></i> {{ __('Pay Now & Renew Plan') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- UPGRADE PLAN TO EXPLORE NEW FEATURES (EXPIRED STATE) --}}
                        @if(count($upgradePlans) > 0)
                            <div class="mb-5">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <div style="width: 4px; height: 26px; background: #6366f1; border-radius: 4px;"></div>
                                    <h3 class="h4 fw-bold text-dark mb-0">
                                        {{ __('Upgrade your plan to explore new features') }}
                                    </h3>
                                </div>

                                @foreach($upgradePlans as $upPlan)
                                    @php
                                        $cleanUpName = trim(str_ireplace(['monthly', 'yearly', 'annual', 'plan'], '', $upPlan['name'] ?? ''));
                                        $uOpt = $modalPlanOptions[$cleanUpName] ?? null;
                                        $upMonthlyPrice = $uOpt ? $uOpt['monthly_price'] : ($typeKey == 1 ? (float)($upPlan['price'] ?? 39) : round((float)($upPlan['price'] ?? 390) / 10));
                                        $upYearlyPrice = $uOpt ? $uOpt['yearly_price'] : ($typeKey == 2 ? (float)($upPlan['price'] ?? 390) : round((float)($upPlan['price'] ?? 39) * 10));
                                        $upPlanId = $upPlan['id'] ?? ($uOpt ? ($typeKey == 2 ? $uOpt['yearly_id'] : $uOpt['monthly_id']) : 1);
                                    @endphp
                                    <div class="card shadow-sm border-0 mb-4" style="border-radius: 20px; background: #ffffff; border: 2px solid #6366f1 !important; box-shadow: 0 10px 30px rgba(99, 102, 241, 0.08);">
                                        <div class="card-body p-4 p-md-5">
                                            <div class="row align-items-center">
                                                <!-- Left: Title, Badges & Feature Highlights -->
                                                <div class="col-lg-7 col-md-12 mb-4 mb-lg-0 border-end-lg pe-lg-4">
                                                    <div class="mb-3">
                                                        <h2 class="h3 fw-bold text-dark mb-2" style="font-size: 28px; line-height: 1.2;">{{ $upPlan['name'] }}</h2>
                                                        <div class="mt-1">
                                                            <span class="badge bg-primary px-3 py-1 fw-bold text-white shadow-sm" style="border-radius: 8px; font-size: 12px; background: #6366f1 !important; display: inline-flex; align-items: center; gap: 4px;">
                                                                <i class="fa-solid fa-arrow-up me-1"></i> {{ __('Upgrade Option') }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <p class="text-muted mb-3 fw-medium">
                                                        {{ $upPlan['desc'] ?? __('Upgrade to this plan to unlock more WhatsApp messaging credits, faster automation, and premium features.') }}
                                                    </p>

                                                    <div class="row g-2 pt-2">
                                                        @foreach(array_slice($upPlan['features'] ?? [], 0, 6) as $feature)
                                                            <div class="col-sm-6 mb-2">
                                                                <div class="wa-feature-item d-flex align-items-center gap-2 {{ $feature['check'] ? 'wa-feat-enabled' : 'wa-feat-disabled' }}">
                                                                    <span class="wa-feat-badge">
                                                                        @if($feature['check'])
                                                                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                                                <polyline points="2.5 6.5 4.8 9 9.5 3.5"></polyline>
                                                                            </svg>
                                                                        @else
                                                                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                                                                                <line x1="3" y1="6" x2="9" y2="6"></line>
                                                                            </svg>
                                                                        @endif
                                                                    </span>
                                                                    <span class="wa-feat-label">{{ __($feature['label']) }}</span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <!-- Right: Price & Upgrade Button -->
                                                <div class="col-lg-5 col-md-12 text-center text-lg-end ps-lg-4">
                                                    <div class="mb-2">
                                                        <span class="h1 fw-bold text-dark" style="font-size: 42px; color: #1e293b;">
                                                            {{ !empty($upPlan['free_plan']) ? $inrPrice(0) : $inrPrice($upPlan['price'] ?? 0) }}
                                                        </span>
                                                        <span class="text-muted fw-bold" style="font-size: 16px;">/{{ strtolower(__($typeLabel)) }}</span>
                                                        
                                                    </div>
                                                    <div class="text-muted small mb-3">
                                                        {{ __('Switch plan instantly with seamless feature upgrade') }}
                                                    </div>
                                                    <button type="button" class="btn btn-primary fw-bold px-5 py-3 shadow" style="border-radius: 12px; font-size: 16px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; letter-spacing: 0.3px; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4); text-decoration: none; color: #fff;" onclick="openWaRenewModal({{ $upPlanId }}, '{{ addslashes($upPlan['name']) }}', {{ $upMonthlyPrice }}, {{ $upYearlyPrice }}, '{{ $isTabYearly ? 'yearly' : 'monthly' }}')">
                                                        <i class="fa-regular fa-rocket me-2"></i> {{ __('Upgrade & Pay Now') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if(!$renewPlan && count($upgradePlans) == 0)
                            <div class="text-center text-muted py-5">{{ __('No plans available.') }}</div>
                        @endif

                    @else
                        {{-- CASE 2: ACTIVE SUBSCRIPTION -> SQUARE SHAPE 3-COLUMN CARDS GRID --}}
                        <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                            @forelse($tabPlans as $planItem)
                                @php
                                    $isCurrent = isset($user) && $user->plan_id == ($planItem['id'] ?? null);
                                    $cleanItemName = trim(str_ireplace(['monthly', 'yearly', 'annual', 'plan'], '', $planItem['name'] ?? ''));
                                    $itemOpt = $modalPlanOptions[$cleanItemName] ?? null;
                                    $itemMonthlyPrice = $itemOpt ? $itemOpt['monthly_price'] : ($typeKey == 1 ? (float)($planItem['price'] ?? 39) : round((float)($planItem['price'] ?? 390) / 10));
                                    $itemYearlyPrice = $itemOpt ? $itemOpt['yearly_price'] : ($typeKey == 2 ? (float)($planItem['price'] ?? 390) : round((float)($planItem['price'] ?? 39) * 10));
                                    $itemPlanId = $planItem['id'] ?? ($itemOpt ? ($typeKey == 2 ? $itemOpt['yearly_id'] : $itemOpt['monthly_id']) : 1);
                                    $isFreePlan = !empty($planItem['free_plan']);
                                @endphp
                                <div class="col">
                                    <div class="card h-100 shadow-sm border-0 position-relative d-flex flex-column justify-content-between p-4" 
                                         style="border-radius: 20px; background: #ffffff; transition: transform 0.2s ease, box-shadow 0.2s ease; {{ $isCurrent ? 'border: 2px solid #10b981 !important; box-shadow: 0 10px 30px rgba(16, 185, 129, 0.12) !important;' : 'border: 1px solid #e2e8f0 !important;' }}">
                                        
                                        @if($isCurrent)
                                            <div class="position-absolute top-0 start-50 translate-middle">
                                                <span class="badge px-3 py-1 text-white shadow-sm fw-bold" style="background: #10b981; border-radius: 20px; font-size: 11px; letter-spacing: 0.5px;">
                                                    <i class="fa-solid fa-circle-check me-1"></i> {{ __('CURRENT PLAN') }}
                                                </span>
                                            </div>
                                        @endif

                                        <div>
                                            {{-- Plan Title --}}
                                            <div class="text-center mb-3 pt-2">
                                                <h3 class="fw-bold text-dark mb-1" style="font-size: 24px;">{{ $planItem['name'] }}</h3>
                                                <p class="text-muted small mb-0">{{ $planItem['desc'] ?? '' }}</p>
                                            </div>

                                            {{-- Plan Price --}}
                                            <div class="text-center mb-4 pb-2 border-bottom">
                                                <span class="h1 fw-bold text-dark" style="font-size: 38px; color: #1e293b;">
                                                    {{ $isFreePlan ? $inrPrice(0) : $inrPrice($planItem['price'] ?? 0) }}
                                                </span>
                                                <span class="text-muted fw-bold" style="font-size: 14px;">/{{ strtolower(__($typeLabel)) }}</span>
                                                @if($isTabYearly)
                                                    <div class="mt-1">
                                                        <span class="badge" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: #ffffff !important; font-size: 11px; font-weight: 800; padding: 3px 8px; border-radius: 8px;">
                                                            <i class="fa-regular fa-tag me-1"></i>20% OFF
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>

                                            {{-- Plan Features List --}}
                                            <ul class="list-unstyled mb-4">
                                                @foreach($planItem['features'] ?? [] as $feature)
                                                    <li class="wa-feature-item mb-2 {{ $feature['check'] ? 'wa-feat-enabled' : 'wa-feat-disabled' }}">
                                                        <span class="wa-feat-badge">
                                                            @if($feature['check'])
                                                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                                    <polyline points="2.5 6.5 4.8 9 9.5 3.5"></polyline>
                                                                </svg>
                                                            @else
                                                                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                                                                    <line x1="3" y1="6" x2="9" y2="6"></line>
                                                                </svg>
                                                            @endif
                                                        </span>
                                                        <span class="wa-feat-label">{{ __($feature['label']) }}</span>
                                                        @if(!empty($feature['subfeature']))
                                                            <div class="feature-popup-wrapper position-relative ms-1 d-inline-block">
                                                                <span class="info-hover-icon" tabindex="0">
                                                                    <i class="fa fa-info-circle text-primary" style="cursor:pointer;"></i>
                                                                </span>
                                                                <div class="features-popup shadow-lg">
                                                                    @foreach($feature['subfeature'] as $group)
                                                                        <div class="fw-bold mb-1 small px-3 pt-2">
                                                                            {{ __($group['tab_name'] ?? '') }}
                                                                        </div>
                                                                        <ul class="list-unstyled mb-2 px-3">
                                                                            @foreach($group['items'] as $item)
                                                                                <li class="wa-feature-item d-flex align-items-center mb-1 gap-2 {{ $feature['check'] ? 'wa-feat-enabled' : 'wa-feat-disabled' }}">
                                                                                    <span class="wa-feat-badge" style="width:18px;height:18px;min-width:18px;">
                                                                                        @if($feature['check'])
                                                                                            <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                                                                                <polyline points="2.5 6.5 4.8 9 9.5 3.5"></polyline>
                                                                                            </svg>
                                                                                        @else
                                                                                            <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                                                                                                <line x1="3" y1="6" x2="9" y2="6"></line>
                                                                                            </svg>
                                                                                        @endif
                                                                                    </span>
                                                                                    <span class="wa-feat-label">{{ __($item['label']) }}</span>
                                                                                </li>
                                                                            @endforeach
                                                                        </ul>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>

                                        {{-- Card Action Button: On current plan, ONLY Extend Plan --}}
                                        <div class="pt-2">
                                            @if($isCurrent)
                                                <button type="button" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" style="border-radius: 12px; font-size: 15px; padding: 12px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; letter-spacing: 0.2px;" onclick="openWaRenewModal({{ $itemPlanId }}, '{{ addslashes($planItem['name']) }}', {{ $itemMonthlyPrice }}, {{ $itemYearlyPrice }}, '{{ $isTabYearly ? 'yearly' : 'monthly' }}')">
                                                    <i class="fa-solid fa-arrows-rotate me-1"></i> {{ __('Extend Plan') }}
                                                </button>
                                            @elseif($isFreePlan)
                                                <a href="{{ route('plan.activate', $planItem['id_secure']) }}" data-confirm="{{ __('Are you sure you want to switch to this plan?') }}" class="btn btn-light btn-lg w-100 fw-semibold actionItem" style="border-radius: 12px; font-size: 15px; padding: 12px; border: 1.5px solid #cbd5e1;" data-redirect="">
                                                    {{ __('Start for Free') }}
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-dark btn-lg w-100 fw-semibold" style="border-radius: 12px; font-size: 15px; padding: 12px; background: #1e293b; border: none;" onclick="openWaRenewModal({{ $itemPlanId }}, '{{ addslashes($planItem['name']) }}', {{ $itemMonthlyPrice }}, {{ $itemYearlyPrice }}, '{{ $isTabYearly ? 'yearly' : 'monthly' }}')">
                                                    <i class="fa-solid fa-arrow-up me-1"></i> {{ __('Upgrade Plan') }}
                                                </button>
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center text-muted py-5">{{ __('No plans available.') }}</div>
                            @endforelse
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    </div>

    {{-- INTERACTIVE RENEWAL & UPGRADE MODAL --}}
    <div class="modal fade" id="waRenewModal" tabindex="-1" aria-labelledby="waRenewModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg" style="border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden;">
                
                {{-- Modal Header --}}
                <div class="modal-header px-4 py-3 text-white d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);">
                    <div class="d-flex align-items-center" style="gap: 12px;">
                        <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(34, 197, 94, 0.18); display: flex; align-items: center; justify-content: center; font-size: 20px; color: #22c55e;">
                            <i class="fa-solid fa-crown"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="waRenewModalLabel" style="font-size: 1.15rem;">
                                {{ __('Renew / Upgrade Subscription') }}
                            </h5>
                            <small style="color: #94a3b8; font-size: 0.82rem;">
                                {{ __('Instant plan renewal & activation via Razorpay or direct UPI') }}
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="closeWaRenewModal()"></button>
                </div>

                {{-- Modal Form --}}
                <form id="waRenewPlanForm" action="{{ route('app.profile.renew_plan') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="plan_id" id="wa_form_plan_id" value="{{ $plan->id ?? 1 }}">
                    <input type="hidden" name="term" id="wa_form_term" value="monthly">
                    <input type="hidden" name="price" id="wa_form_price" value="39">
                    <input type="hidden" name="payment_method" id="wa_form_payment_method" value="razorpay">

                    <div class="modal-body p-4">
                        <div class="row g-4">
                            
                            {{-- Left Column: Plan Select & Billing Cycle Cards --}}
                            <div class="col-md-6 border-end-md">
                                <label class="form-label fw-bold text-dark mb-2" style="font-size: 0.92rem;">
                                    <i class="fa-solid fa-box text-primary me-1"></i> {{ __('Select Subscription Plan') }}
                                </label>
                                <div class="mb-3">
                                    <select id="wa_modal_plan_select" class="form-select fw-bold" style="border-radius: 12px; height: 46px; border: 1.5px solid #cbd5e1; background-color: #f8fafc;" onchange="onWaModalPlanChanged(this)">
                                        @foreach($modalPlanOptions as $opt)
                                            <option value="{{ $opt['monthly_id'] ?? $opt['yearly_id'] }}" 
                                                    data-name="{{ $opt['name'] }}"
                                                    data-monthly-id="{{ $opt['monthly_id'] }}"
                                                    data-yearly-id="{{ $opt['yearly_id'] }}"
                                                    data-monthly="{{ $opt['monthly_price'] }}"
                                                    data-yearly="{{ $opt['yearly_price'] }}"
                                                    {{ ($plan && (stripos($plan->name, $opt['name']) !== false || $plan->id == $opt['monthly_id'] || $plan->id == $opt['yearly_id'])) ? 'selected' : '' }}>
                                                {{ $opt['name'] }} (&#8377;{{ $opt['monthly_price'] }}/mo &bull; &#8377;{{ $opt['yearly_price'] }}/yr)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Billing Cycle Selector Cards --}}
                                <label class="form-label fw-bold text-dark mb-2" style="font-size: 0.92rem;">
                                    <i class="fa-solid fa-clock text-primary me-1"></i> {{ __('Billing Cycle') }}
                                </label>
                                <div class="row g-2 mb-3">
                                    @if($hasMonthlyModalPlans)
                                    <div class="{{ $hasYearlyModalPlans ? 'col-6' : 'col-12' }}">
                                        <div class="p-3 text-center border cursor-pointer wa-term-card active" id="waTermMonthly" style="border-radius: 12px; transition: all 0.2s;" onclick="setWaModalBillingTerm('monthly')">
                                            <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">{{ __('Monthly') }}</div>
                                            <div class="h5 fw-bold text-primary mb-0" id="waModalPriceMonthly">&#8377;{{ $initMonthlyPrice }}</div>
                                            <small class="text-muted" style="font-size: 0.75rem;">+30 Days validity</small>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="col-6">
                                        <div class="p-3 text-center border cursor-pointer wa-term-card" id="waTermYearly" style="border-radius: 12px; transition: all 0.2s;" onclick="setWaModalBillingTerm('yearly')">
                                            <div class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">{{ __('Yearly') }}</div>
                                            <div class="h5 fw-bold text-success mb-0" id="waModalPriceYearly">&#8377;{{ $initYearlyPrice }}</div>
                                            <small class="text-muted" style="font-size: 0.75rem;">+365 Days validity</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Specification Summary Box --}}
                                <div class="p-3 bg-light rounded-3 mb-2" style="border: 1px solid #e2e8f0; font-size: 0.88rem;">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">{{ __('Product') }}:</span>
                                        <strong class="text-dark">{{ __('WhatsApp Bulk Automation') }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">{{ __('Current Expiry') }}:</span>
                                        <span class="fw-bold text-dark" id="waModalCurrentExpiryLabel">{{ $currExpireFormatted }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">{{ __('New Expiry Date') }}:</span>
                                        <span class="fw-bold text-success" id="waModalNewExpiryLabel">+30 Days</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark" style="font-size: 1rem;">{{ __('Total Amount') }}:</span>
                                        <span class="h4 fw-bold text-primary mb-0" id="waModalSummaryPrice">&#8377;{{ $initMonthlyPrice }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Right Column: Payment Gateway Platform Selection --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark mb-2" style="font-size: 0.92rem;">
                                    <i class="fa-solid fa-credit-card text-primary me-1"></i> {{ __('Choose Payment Gateway') }} <span class="text-danger">*</span>
                                </label>

                                {{-- Payment Gateway Option Cards --}}
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="p-3 border cursor-pointer wa-gw-card active" id="gwCardRazorpay" style="border-radius: 12px; transition: all 0.2s; background: #f8fafc;" onclick="selectWaPaymentGateway('razorpay')">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <i class="fa-solid fa-bolt text-primary"></i>
                                                <span class="fw-bold text-dark" style="font-size: 0.88rem;">Razorpay</span>
                                            </div>
                                            <small class="text-muted d-block" style="font-size: 0.72rem; line-height: 1.2;">
                                                Cards, NetBanking, GPay, PhonePe Online Gateway
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 border cursor-pointer wa-gw-card" id="gwCardUpi" style="border-radius: 12px; transition: all 0.2s; background: #f8fafc;" onclick="selectWaPaymentGateway('upi')">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <i class="fa-solid fa-qrcode text-success"></i>
                                                <span class="fw-bold text-dark" style="font-size: 0.88rem;">Direct UPI</span>
                                            </div>
                                            <small class="text-muted d-block" style="font-size: 0.72rem; line-height: 1.2;">
                                                Scan QR Code (Stay On Page)
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                {{-- RAZORPAY ONLINE PANEL --}}
                                <div id="panelRazorpay" class="mb-3">
                                    <div class="p-3 bg-light rounded-3 mb-3" style="border: 1.5px dashed #cbd5e1;">
                                        <div class="d-flex align-items-center mb-2" style="gap: 8px;">
                                            <i class="fa-solid fa-shield-halved text-success"></i>
                                            <span class="fw-bold text-dark" style="font-size: 0.88rem;">{{ __('Automated Gateway Checkout') }}</span>
                                        </div>
                                        <p class="text-muted mb-2" style="font-size: 0.8rem; line-height: 1.4;">
                                            {{ __('Complete instant payment directly via Razorpay secure popup. Your selected plan and billing terms will activate automatically.') }}
                                        </p>
                                        <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
                                            <span class="badge bg-white text-dark border px-2 py-1 fw-semibold" style="font-size: 11px;">UPI</span>
                                            <span class="badge bg-white text-dark border px-2 py-1 fw-semibold" style="font-size: 11px;">Credit/Debit Cards</span>
                                            <span class="badge bg-white text-dark border px-2 py-1 fw-semibold" style="font-size: 11px;">NetBanking</span>
                                            <span class="badge bg-white text-dark border px-2 py-1 fw-semibold" style="font-size: 11px;">Wallets</span>
                                        </div>
                                    </div>

                                    <button type="button" id="btnSubmitWaRenew" class="btn btn-primary w-100 py-3 fw-bold shadow" style="border-radius: 12px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; font-size: 16px;" onclick="submitWaRenewProcess()">
                                        <i class="fa-solid fa-lock me-2"></i> {{ __('Proceed to Razorpay Checkout (') }}<span id="btnWaPayPrice">&#8377;{{ $initMonthlyPrice }}</span>{{ __(')') }}
                                    </button>
                                </div>

                                {{-- UPI ON-PAGE PAYMENT PANEL --}}
                                <div id="panelUpi" class="mb-3 d-none">
                                    <div class="p-3 rounded-3 mb-3" style="background: #f0fdf4; border: 1.5px solid #86efac;">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center" style="gap: 8px;">
                                                <i class="fa-solid fa-qrcode text-success" style="font-size: 18px;"></i>
                                                <strong class="text-success" style="font-size: 0.9rem;">{{ __('Scan & Pay via any UPI App') }}</strong>
                                            </div>
                                            <span id="wa_upi_amount_badge" class="badge bg-success text-white px-2 py-1 fw-bold" style="font-size: 12px;">
                                                Payable: &#8377;{{ $initMonthlyPrice }}
                                            </span>
                                        </div>

                                        {{-- Dynamic QR Code --}}
                                        <div class="text-center p-3 bg-white rounded-3 my-2 shadow-sm" style="border: 1px solid #bbf7d0;">
                                            <div class="mb-2">
                                                <img id="wa_upi_qr_img" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode('upi://pay?pa=' . $dynamicUpiId . '&pn=' . $dynamicUpiHolderName . '&am=' . $initMonthlyPrice . '&cu=INR') }}" 
                                                     alt="UPI QR Code" 
                                                     style="width: 165px; height: 165px; border-radius: 12px; border: 2px solid #86efac; padding: 6px; background: #fff;" />
                                            </div>
                                            <div class="mt-2 mb-2 text-center">
                                                <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3 py-1" style="border-radius: 20px; font-size: 12px;" onclick="downloadWaModalQrCode()">
                                                    <i class="fa-solid fa-download me-1"></i> {{ __('Download QR Code') }}
                                                </button>
                                            </div>
                                            <small class="text-muted d-block mb-2 fw-medium" style="font-size: 0.78rem;">
                                                {{ __('Scan with GPay, PhonePe, Paytm, BHIM or download the QR code') }}
                                            </small>

                                            {{-- Copyable VPA --}}
                                            <div class="d-flex align-items-center justify-content-center flex-wrap" style="gap: 6px;">
                                                <span class="text-dark fw-bold" style="font-size: 0.82rem;">{{ __('UPI ID:') }}</span>
                                                <code id="wa_upi_id_text" class="px-2 py-1 bg-light rounded border text-primary fw-bold" style="font-size: 0.85rem; letter-spacing: 0.3px;">{{ $dynamicUpiId }}</code>
                                                <button type="button" id="btnCopyWaUpi" class="btn btn-sm btn-outline-primary py-0 px-2 fw-bold" style="font-size: 11px; height: 26px; border-radius: 6px;" onclick="copyWaUpiVpa(this)">
                                                    <i class="fa-regular fa-copy me-1"></i> {{ __('Copy') }}
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Payment Screenshot File --}}
                                        <div class="mb-1">
                                            <label class="form-label fw-bold text-dark mb-1" style="font-size: 0.82rem;">
                                                {{ __('Payment Screenshot Proof (Optional)') }}
                                            </label>
                                            <input type="file" name="receipt" id="wa_receipt_file" accept="image/*,.pdf" class="form-control" style="border-radius: 8px; font-size: 0.82rem; background: #fff;">
                                        </div>
                                    </div>

                                    <button type="button" id="btnSubmitWaRenewUpi" class="btn btn-success w-100 py-3 fw-bold shadow" style="border-radius: 12px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; font-size: 16px;" onclick="submitWaRenewProcess()">
                                        <i class="fa-solid fa-circle-check me-2"></i> {{ __('Complete Payment & Activate Plan (') }}<span id="btnWaPayPriceUpi">&#8377;{{ $initMonthlyPrice }}</span>{{ __(')') }}
                                    </button>
                                </div>

                                <div id="waRenewAlertMessage" class="alert d-none mt-3" style="border-radius: 12px; font-size: 14px; font-weight: 600;"></div>

                                <div class="text-center mt-2 text-muted" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-shield-halved text-success me-1"></i> {{ __('256-Bit SSL Encrypted &bull; Instant Automatic Activation') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .wa-term-card.active {
            border-color: #6366f1 !important;
            background-color: #eef2ff !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        }
        .wa-term-card:hover {
            border-color: #a5b4fc;
        }
        .wa-gw-card.active {
            border-color: #6366f1 !important;
            background-color: #eef2ff !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.12);
        }
        .wa-gw-card:hover {
            border-color: #a5b4fc;
        }
        .cursor-pointer {
            cursor: pointer;
        }
    </style>

    <script>
        var selectedModalPlanId = {{ $initPlanId }};
        var selectedModalPlanName = '{{ addslashes($initPlanName) }}';
        var selectedModalMonthlyPrice = {{ $initMonthlyPrice }};
        var selectedModalYearlyPrice = {{ $initYearlyPrice }};
        var selectedModalTerm = {{ ($hasMonthlyModalPlans || !$hasYearlyModalPlans) ? "'monthly'" : "'yearly'" }};
        var selectedWaGateway = 'razorpay';
        var currentCustomerExpireIso = "{{ $currExpireIso }}";
        var dynamicUpiVpa = "{{ $dynamicUpiId }}";
        var dynamicUpiHolder = "{{ $dynamicUpiHolderName }}";

        // Open Interactive Renewal / Upgrade Modal
        function openWaRenewModal(planId, planName, mPrice, yPrice, term) {
            selectedModalPlanId = planId;
            selectedModalPlanName = planName;
            selectedModalMonthlyPrice = parseFloat(mPrice) || 39;
            selectedModalYearlyPrice = parseFloat(yPrice) || 390;
            selectedModalTerm = term || 'monthly';

            // Sync Plan dropdown in modal
            var planSelect = document.getElementById('wa_modal_plan_select');
            if (planSelect) {
                var cleanTarget = (planName || '').toLowerCase().replace(/monthly|yearly|annual|plan/gi, '').trim();
                for (var i = 0; i < planSelect.options.length; i++) {
                    var optName = (planSelect.options[i].getAttribute('data-name') || '').toLowerCase();
                    var optVal = planSelect.options[i].value;
                    if (optVal == planId || (cleanTarget && optName.includes(cleanTarget))) {
                        planSelect.selectedIndex = i;
                        selectedModalPlanName = planSelect.options[i].getAttribute('data-name');
                        selectedModalMonthlyPrice = parseFloat(planSelect.options[i].getAttribute('data-monthly')) || selectedModalMonthlyPrice;
                        selectedModalYearlyPrice = parseFloat(planSelect.options[i].getAttribute('data-yearly')) || selectedModalYearlyPrice;
                        break;
                    }
                }
            }

            // Sync Prices displayed in modal
            if (document.getElementById('waModalPriceMonthly')) document.getElementById('waModalPriceMonthly').innerHTML = '&#8377;' + selectedModalMonthlyPrice;
            if (document.getElementById('waModalPriceYearly')) document.getElementById('waModalPriceYearly').innerHTML = '&#8377;' + selectedModalYearlyPrice;

            // Set Billing Term
            setWaModalBillingTerm(selectedModalTerm);

            // Select Default Gateway
            selectWaPaymentGateway('razorpay');

            // Show Modal (Bootstrap 5 modal with fallback)
            var modalEl = document.getElementById('waRenewModal');
            if (window.bootstrap && bootstrap.Modal) {
                var myModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                myModal.show();
            } else {
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                document.body.classList.add('modal-open');
            }
        }

        function closeWaRenewModal() {
            var modalEl = document.getElementById('waRenewModal');
            if (window.bootstrap && bootstrap.Modal) {
                var myModal = bootstrap.Modal.getInstance(modalEl);
                if (myModal) myModal.hide();
            }
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
            document.body.classList.remove('modal-open');
        }

        function setWaModalBillingTerm(term) {
            selectedModalTerm = term;
            var cardM = document.getElementById('waTermMonthly');
            var cardY = document.getElementById('waTermYearly');

            if (term === 'monthly' && !cardM && cardY) { term = 'yearly'; selectedModalTerm = 'yearly'; }
            if (term === 'yearly') {
                if (cardY) cardY.classList.add('active');
                if (cardM) cardM.classList.remove('active');
            } else {
                if (cardM) cardM.classList.add('active');
                if (cardY) cardY.classList.remove('active');
            }

            var finalPrice = (term === 'yearly') ? selectedModalYearlyPrice : selectedModalMonthlyPrice;

            // Update form hidden fields
            document.getElementById('wa_form_plan_id').value = selectedModalPlanId;
            document.getElementById('wa_form_term').value = term;
            document.getElementById('wa_form_price').value = finalPrice;

            // Update UI elements
            document.getElementById('waModalSummaryPrice').innerHTML = '&#8377;' + finalPrice;
            var btnRzp = document.getElementById('btnWaPayPrice');
            if (btnRzp) btnRzp.innerHTML = '&#8377;' + finalPrice;
            var btnUpi = document.getElementById('btnWaPayPriceUpi');
            if (btnUpi) btnUpi.innerHTML = '&#8377;' + finalPrice;

            calculateWaModalDates(term);
            updateWaUpiQrCode();
        }

        function calculateWaModalDates(term) {
            var now = new Date();
            var baseDate = now;

            if (currentCustomerExpireIso) {
                var expDate = new Date(currentCustomerExpireIso);
                if (expDate > now) {
                    baseDate = expDate;
                }
            }

            var newDate = new Date(baseDate.getTime());
            if (term === 'yearly') {
                newDate.setFullYear(newDate.getFullYear() + 1);
            } else {
                newDate.setMonth(newDate.getMonth() + 1);
            }

            var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
            var newFormatted = monthNames[newDate.getMonth()] + ' ' + newDate.getDate() + ', ' + newDate.getFullYear();

            var label = document.getElementById('waModalNewExpiryLabel');
            if (label) label.textContent = newFormatted;
        }

        function onWaModalPlanChanged(selectEl) {
            var opt = selectEl.options[selectEl.selectedIndex];
            selectedModalPlanId = opt.value;
            selectedModalPlanName = opt.getAttribute('data-name');
            selectedModalMonthlyPrice = parseFloat(opt.getAttribute('data-monthly')) || 39;
            selectedModalYearlyPrice = parseFloat(opt.getAttribute('data-yearly')) || 390;

            if (document.getElementById('waModalPriceMonthly')) document.getElementById('waModalPriceMonthly').innerHTML = '&#8377;' + selectedModalMonthlyPrice;
            if (document.getElementById('waModalPriceYearly')) document.getElementById('waModalPriceYearly').innerHTML = '&#8377;' + selectedModalYearlyPrice;

            setWaModalBillingTerm(selectedModalTerm);
        }

        function selectWaPaymentGateway(gw) {
            selectedWaGateway = gw;
            document.getElementById('wa_form_payment_method').value = gw;

            var rzpCard = document.getElementById('gwCardRazorpay');
            var upiCard = document.getElementById('gwCardUpi');
            var panelRzp = document.getElementById('panelRazorpay');
            var panelUpi = document.getElementById('panelUpi');

            if (gw === 'upi') {
                if (upiCard) upiCard.classList.add('active');
                if (rzpCard) rzpCard.classList.remove('active');
                if (panelUpi) panelUpi.classList.remove('d-none');
                if (panelRzp) panelRzp.classList.add('d-none');
                updateWaUpiQrCode();
            } else {
                if (rzpCard) rzpCard.classList.add('active');
                if (upiCard) upiCard.classList.remove('active');
                if (panelRzp) panelRzp.classList.remove('d-none');
                if (panelUpi) panelUpi.classList.add('d-none');
            }
        }

        function updateWaUpiQrCode() {
            var price = parseFloat(document.getElementById('wa_form_price').value) || {{ $initMonthlyPrice }};
            var upiUri = 'upi://pay?pa=' + encodeURIComponent(dynamicUpiVpa) + '&pn=' + encodeURIComponent(dynamicUpiHolder) + '&am=' + encodeURIComponent(price) + '&cu=INR';
            var qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(upiUri);
            var qrImg = document.getElementById('wa_upi_qr_img');
            if (qrImg) qrImg.src = qrUrl;
            var amtBadge = document.getElementById('wa_upi_amount_badge');
            if (amtBadge) amtBadge.innerHTML = 'Payable: &#8377;' + price;
        }

        function downloadWaModalQrCode() {
            var qrImg = document.getElementById('wa_upi_qr_img');
            if (qrImg && qrImg.src) {
                fetch(qrImg.src)
                    .then(function(res) { return res.blob(); })
                    .then(function(blob) {
                        var blobUrl = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = blobUrl;
                        a.download = 'whatsappbulk-upi-qr.png';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(blobUrl);
                        a.remove();
                    })
                    .catch(function() {
                        window.open(qrImg.src, '_blank');
                    });
            }
        }

        function copyWaUpiVpa(btn) {
            var text = dynamicUpiVpa;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    showWaCopyFeedback(btn);
                }).catch(function() {
                    fallbackWaCopy(text, btn);
                });
            } else {
                fallbackWaCopy(text, btn);
            }
        }

        function fallbackWaCopy(text, btn) {
            var temp = document.createElement('input');
            temp.value = text;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            showWaCopyFeedback(btn);
        }

        function showWaCopyFeedback(btn) {
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i> Copied!';
            btn.classList.add('btn-success', 'text-white');
            btn.classList.remove('btn-outline-primary');
            setTimeout(function() {
                btn.innerHTML = orig;
                btn.classList.remove('btn-success', 'text-white');
                btn.classList.add('btn-outline-primary');
            }, 2000);
        }

        // Process Renewal Submission
        function submitWaRenewProcess() {
            var gw = selectedWaGateway || 'razorpay';
            var planSelect = document.getElementById('wa_modal_plan_select');
            var selOpt = planSelect ? planSelect.options[planSelect.selectedIndex] : null;
            var planId = selOpt ? (selectedModalTerm === 'yearly' ? (selOpt.getAttribute('data-yearly-id') || selOpt.value) : (selOpt.getAttribute('data-monthly-id') || selOpt.value)) : selectedModalPlanId;
            var planName = selOpt ? selOpt.getAttribute('data-name') : selectedModalPlanName;
            var price = (selectedModalTerm === 'yearly') ? selectedModalYearlyPrice : selectedModalMonthlyPrice;

            // 1. RAZORPAY ONLINE: In-App On-Page Razorpay Order & Popup
            if (gw === 'razorpay') {
                var btn = document.getElementById('btnSubmitWaRenew');
                var originalHtml = btn.innerHTML;
                if (typeof Razorpay === 'undefined') {
                    alert('Razorpay Checkout script failed to load. Please refresh the page or use UPI.');
                    return;
                }
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Preparing Razorpay...';

                fetch('{{ route("app.profile.renew_plan") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        plan_id: planId,
                        term: selectedModalTerm,
                        price: price,
                        payment_method: 'razorpay'
                    })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.status === 1 && data.razorpay_popup && data.order_id) {
                        var rzp = new Razorpay({
                            key: data.key_id,
                            amount: data.amount,
                            currency: data.currency || 'INR',
                            name: data.name || 'WhatsApp Bulk',
                            description: data.description || planName,
                            order_id: data.order_id,
                            prefill: data.prefill || {},
                            theme: { color: '#4f46e5' },
                            handler: function(rzpResp) {
                                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Verifying Payment...';
                                fetch('{{ route("app.profile.renew_plan") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        plan_id: planId,
                                        term: selectedModalTerm,
                                        price: price,
                                        payment_method: 'razorpay',
                                        razorpay_payment_id: rzpResp.razorpay_payment_id || '',
                                        razorpay_order_id: rzpResp.razorpay_order_id || '',
                                        razorpay_signature: rzpResp.razorpay_signature || ''
                                    })
                                })
                                .then(function(r) { return r.json(); })
                                .then(function(verifyRes) {
                                    if (verifyRes && verifyRes.status === 1) {
                                        window.location.href = verifyRes.redirect || '{{ url("/app/profile/plan?renewed=1") }}';
                                    } else {
                                        alert((verifyRes && verifyRes.message) || 'Payment verification failed.');
                                        btn.disabled = false;
                                        btn.innerHTML = originalHtml;
                                    }
                                })
                                .catch(function() {
                                    alert('Error verifying payment. Please contact support.');
                                    btn.disabled = false;
                                    btn.innerHTML = originalHtml;
                                });
                            },
                            modal: {
                                ondismiss: function() {
                                    btn.disabled = false;
                                    btn.innerHTML = originalHtml;
                                }
                            }
                        });
                        rzp.open();
                    } else if (data && data.status === 1 && data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        alert((data && data.message) || 'Unable to start Razorpay checkout.');
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }
                })
                .catch(function(err) {
                    console.error(err);
                    alert('Error contacting server. Please try again.');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                });
                return;
            }

            // 2. UPI APP: On-Page Direct Verification
            if (gw === 'upi') {
                var btnUpi = document.getElementById('btnSubmitWaRenewUpi');
                var origUpiHtml = btnUpi.innerHTML;
                btnUpi.disabled = true;
                btnUpi.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Activating Plan...';

                var form = document.getElementById('waRenewPlanForm');
                var formData = new FormData(form);
                formData.set('payment_method', 'upi');
                formData.set('plan_id', planId);
                formData.set('term', selectedModalTerm);
                formData.set('price', price);

                fetch('{{ route("app.profile.renew_plan") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data && data.status === 1) {
                        if (data.is_pending) {
                            btnUpi.innerHTML = '<i class="fa-solid fa-clock-rotate-left me-2"></i> Payment Submitted!';
                            btnUpi.style.background = '#d97706';
                            var alertBox = document.getElementById('waRenewAlertMessage');
                            if (alertBox) {
                                alertBox.className = 'alert alert-warning mt-3';
                                alertBox.innerHTML = '<i class="fa-solid fa-clock-rotate-left me-2"></i> ' + (data.message || 'Your UPI payment has been submitted for admin verification.');
                                alertBox.classList.remove('d-none');
                            }
                            setTimeout(function() {
                                window.location.href = data.redirect || '{{ url("/app/dashboard") }}';
                            }, 1500);
                        } else {
                            btnUpi.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i> Plan Renewed!';
                            btnUpi.style.background = '#15803d';
                            var alertBox = document.getElementById('waRenewAlertMessage');
                            if (alertBox) {
                                alertBox.className = 'alert alert-success mt-3';
                                alertBox.innerHTML = '<i class="fa-solid fa-circle-check me-2"></i> ' + (data.message || 'Your plan has been renewed successfully!');
                                alertBox.classList.remove('d-none');
                            }
                            setTimeout(function() {
                                window.location.href = data.redirect || '{{ url("/app/profile/plan?renewed=1") }}';
                            }, 1200);
                        }
                    } else {
                        alert(data.message || 'Failed to renew plan. Please check the UTR.');
                        btnUpi.disabled = false;
                        btnUpi.innerHTML = origUpiHtml;
                    }
                })
                .catch(function(err) {
                    console.error(err);
                    alert('Network error while processing renewal.');
                    btnUpi.disabled = false;
                    btnUpi.innerHTML = origUpiHtml;
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            calculateWaModalDates('monthly');
        });
    </script>
@endsection
