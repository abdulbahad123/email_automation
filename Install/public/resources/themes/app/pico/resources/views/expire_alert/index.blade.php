@extends('layouts.app')

@section('content')
<div class="border-bottom mb-4 py-4 bg-polygon">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="fw-7 fs-22 mb-1 text-primary-700">
                    <i class="fa-light fa-bell-exclamation me-2 text-warning"></i>{{ __('Expire Alert Settings') }}
                </div>
                <div class="fw-5 text-gray-700">{{ __('Configure plan expiry alert days and subscription renewal notices.') }}</div>
            </div>
            <div>
                <a href="{{ url('admin/dashboard') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    <i class="fa-regular fa-arrow-left me-1"></i> {{ __('Back to Dashboard') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2" role="alert">
            <i class="fa-regular fa-circle-check fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-4 d-flex align-items-center gap-2" role="alert">
            <i class="fa-regular fa-triangle-exclamation fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Clean Settings Form --}}
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <div class="fw-bold fs-16 text-dark d-flex align-items-center gap-2">
                <i class="fa-light fa-sliders text-primary"></i> {{ __('Recurring Expiry Alert Window') }}
            </div>
        </div>
        <div class="card-body p-4">
            <form action="{{ url('admin/expire-alert/update') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-8 offset-md-2">
                        <label class="form-label fw-semibold fs-15">{{ __('Recurring Expiry Alert (Days Before Expiry)') }}</label>
                        <div class="input-group">
                            <input type="number" name="expire_alert_days" class="form-control form-control-lg rounded-start-3" value="{{ old('expire_alert_days', $alertDays ?? 7) }}" min="1" max="365" required>
                            <span class="input-group-text bg-light fw-medium">{{ __('Days Before') }}</span>
                        </div>
                        <small class="text-muted d-block mt-2">{{ __('Example: Enter 7 for 1 week before expiry for monthly or yearly plans.') }}</small>

                        <div class="text-end pt-4">
                            <button type="submit" class="btn btn-success btn-lg px-4 rounded-pill fw-bold" style="background: linear-gradient(135deg, #22c55e 0%, #15803d 100%); border:none;">
                                <i class="fa-solid fa-save me-1"></i> {{ __('Save Expire Alert Settings') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection