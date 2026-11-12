@extends('layouts.app')

@section('content')
<form class="actionForm" action="{{ route('app.whatsappcontact.save', ['id_secure' => $result->id_secure ?? null]) }}" method="POST" data-redirect="{{ route('app.whatsappcontact.index') }}">
    @csrf
    <div class="container py-4 mw-800">
        <div class="d-flex align-items-center justify-content-between gap-16 mb-4">
            <div>
                <h1 class="fs-28 fw-6 text-gray-900 mb-2">{{ $result ? __('Update contact group') : __('Create contact group') }}</h1>
                <div class="fs-14 text-gray-600">{{ __('Create, edit, and organize contact groups for WhatsApp bulk actions.') }}</div>
            </div>
        </div>

        <div class="card shadow-none border-gray-300 overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                <div class="mb-4">
                    <label class="form-label fw-6">{{ __('Status') }}</label>
                    <div class="d-flex flex-wrap gap-12">
                        <label class="d-flex align-items-center gap-10 px-3 py-2 rounded-3 border bg-white cursor-pointer">
                            <input class="form-check-input mt-0" type="radio" name="status" value="1" {{ !isset($result->status) || (int) $result->status === 1 ? 'checked' : '' }}>
                            <span>{{ __('Enable') }}</span>
                        </label>
                        <label class="d-flex align-items-center gap-10 px-3 py-2 rounded-3 border bg-white cursor-pointer">
                            <input class="form-check-input mt-0" type="radio" name="status" value="0" {{ isset($result->status) && (int) $result->status === 0 ? 'checked' : '' }}>
                            <span>{{ __('Disable') }}</span>
                        </label>
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-6">{{ __('Group contact name') }}</label>
                    <input type="text" class="form-control form-control-lg" name="name" value="{{ $result->name ?? '' }}" required>
                </div>
            </div>
            <div class="card-footer bg-white border-0 px-4 px-lg-5 pb-4 pt-0">
                <div class="border-top pt-4 d-flex justify-content-between">
                    <a href="{{ route('app.whatsappcontact.index') }}" class="btn btn-outline btn-dark btn-lg">{{ __('Back') }}</a>
                    <button type="submit" class="btn btn-primary btn-lg">{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

