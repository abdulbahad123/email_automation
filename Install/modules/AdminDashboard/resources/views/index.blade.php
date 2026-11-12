@extends('layouts.app')

@section('content')
    <div class="border-bottom mb-1 py-4 bg-polygon">
        <div class="container">
            <div class="d-flex justify-content-between align-self-center">
                <div class="mb-0">
                    <div class="fw-7 fs-20 mx-auto mb-2 text-primary-700">{{ __('Welcome, Admin') }} 🚀</div>
                    <div class="fw-5 text-gray-700">{{ __('Overview of platform performance, user activity, and system health.') }}</div>
                </div>
            </div>
        </div>
    </div>

    @php
        $now = time();
        $alertDays = (int)(function_exists('get_option') ? get_option('expire_alert_days', 7) : 7);
        $window = $now + ($alertDays * 86400);

        $expiredCount = 0;
        $expiringCount = 0;
        try {
            $expiredCount = \DB::table('users')
                ->where('role', '!=', 2)
                ->where('expiration_date', '>', 0)
                ->where('expiration_date', '<=', $now)
                ->count();

            $expiringCount = \DB::table('users')
                ->where('role', '!=', 2)
                ->where('expiration_date', '>', $now)
                ->where('expiration_date', '<=', $window)
                ->count();
        } catch (\Throwable $e) {}
    @endphp

    <div class="container pt-4 pb-0">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-2" style="background: linear-gradient(135deg, #fffdf0 0%, #fffbeb 100%); border-left: 5px solid #f59e0b !important; border: 1px solid #fde68a;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="size-48 rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fs-22 shadow-sm">
                        <i class="fa-solid fa-bell-exclamation"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-16 text-dark d-flex align-items-center gap-2">
                            <span>{{ __('Subscription Expire Alert') }}</span>
                            @if($expiredCount > 0)
                                <span class="badge bg-danger rounded-pill px-2 py-1 fs-11">{{ $expiredCount }} {{ __('Expired') }}</span>
                            @endif
                            @if($expiringCount > 0)
                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1 fs-11">{{ $expiringCount }} {{ __('Expiring in') }} {{ $alertDays }} {{ __('days') }}</span>
                            @endif
                        </div>
                        <div class="text-muted small mt-1">
                            @if($expiredCount > 0 || $expiringCount > 0)
                                {{ __('There are active accounts requiring renewal attention. Automated WhatsApp reminders can be dispatched with their direct renewal links.') }}
                            @else
                                {{ __('All user accounts are currently active. Automated alerts are set to notify users') }} <b>{{ $alertDays }} {{ __('days') }}</b> {{ __('before plan expiry.') }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ url('admin/expire-alert') }}" class="btn btn-warning btn-sm rounded-pill px-4 py-2 fw-bold text-dark shadow-sm" style="font-size: 13px;">
                        <i class="fa-light fa-sliders me-1"></i> {{ __('Manage Expire Alerts') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">

        <div class="ajax-pages" data-url="{{ route('admin.dashboard.statistics') }}" data-resp=".ajax-pages">
            
            <div class="pb-30 mt-200 ajax-scroll-loading">
                <div class="app-loading mx-auto mt-10 pl-0 pr-0">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>

        </div>       
        
    </div>
@endsection
