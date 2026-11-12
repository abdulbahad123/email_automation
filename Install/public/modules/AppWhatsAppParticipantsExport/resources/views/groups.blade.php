@if($status === 'success')
    <div class="card shadow-none border-gray-300 overflow-hidden hp-100">
        <div class="card-header bg-white border-bottom px-4 py-3 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-12">
            <div>
                <div class="fs-18 fw-6 text-gray-900">{{ __('Available groups') }}</div>
                <div class="d-flex flex-wrap align-items-center gap-8 fs-13 text-gray-600 mt-1">
                    <span>{{ __('Account:') }} <span class="fw-6 text-gray-800">{{ $account->name ?? '' }}</span></span>
                    <span class="text-gray-400">/</span>
                    <span>{{ trans_choice(':count groups found', $groups->count(), ['count' => $groups->count()]) }}</span>
                </div>
            </div>
            <button type="button" class="btn btn-outline btn-dark btn-sm wa-export-participants-reload">
                <i class="fa-light fa-rotate-right me-1"></i>{{ __('Refresh groups') }}
            </button>
        </div>

        @if($groups->isEmpty())
            <div class="card-body p-5 text-center d-flex flex-column align-items-center justify-content-center min-h-300">
                <div class="d-inline-flex align-items-center justify-content-center w-72 h-72 rounded-circle bg-light mb-3">
                    <i class="fa-light fa-users text-gray-500 fs-24"></i>
                </div>
                <div class="fs-20 fw-6 text-gray-900 mb-2">{{ __('No WhatsApp groups found') }}</div>
                <div class="fs-14 text-gray-600 max-w-520 mx-auto">
                    {{ __('Make sure this profile has joined or sent a message in the target group, then click refresh to load the latest list from the WhatsApp server.') }}
                </div>
            </div>
        @else
            <div class="card-body p-4 d-flex flex-column gap-3">
                @foreach($groups as $group)
                    <div class="border rounded-3 px-4 py-3 d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-16">
                        <div class="d-flex gap-12 align-items-start min-w-0 flex-fill">
                            <div class="size-48 d-flex align-items-center justify-content-center rounded-circle bg-primary-100 text-primary flex-shrink-0">
                                <i class="fa-light fa-users"></i>
                            </div>
                            <div class="min-w-0 flex-fill">
                                <div class="fs-16 fw-6 text-gray-900 text-truncate mb-1">{{ $group['name'] }}</div>
                                <div class="fs-13 text-gray-500 text-break">{{ __('Group ID:') }} {{ $group['id'] }}</div>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-10 ms-xl-3">
                            <div class="d-inline-flex align-items-center justify-content-center gap-8 px-3 py-2 bg-success-100 text-success rounded-pill fs-12 fw-6 text-nowrap">
                                <i class="fa-light fa-users"></i>
                                <span>{{ trans_choice(':count participants', $group['size'], ['count' => $group['size']]) }}</span>
                            </div>
                            <a href="{{ route('app.whatsappparticipantsexport.popup_import', ['account_id' => $account->id_secure, 'group_id' => $group['id']]) }}" class="btn btn-outline btn-dark btn-sm text-nowrap actionItem" data-popup="ParticipantsImportModal">
                                <i class="fa-light fa-address-book me-1"></i>{{ __('Import to contacts') }}
                            </a>
                            <a href="{{ route('app.whatsappparticipantsexport.export', ['account_id' => $account->id_secure, 'group_id' => $group['id']]) }}" class="btn btn-primary btn-sm text-nowrap">
                                <i class="fa-light fa-file-csv me-1"></i>{{ __('Download CSV') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@else
    <div class="card shadow-none border-danger-subtle overflow-hidden hp-100">
        <div class="card-body p-5 text-center d-flex flex-column align-items-center justify-content-center min-h-300">
            <div class="d-inline-flex align-items-center justify-content-center w-72 h-72 rounded-circle bg-danger-100 text-danger mb-3">
                <i class="fa-light fa-circle-exclamation fs-24"></i>
            </div>
            <div class="fs-20 fw-6 text-gray-900 mb-2">{{ __('Could not load WhatsApp groups') }}</div>
            <div class="fs-14 text-gray-600 max-w-520 mx-auto mb-4">{{ $message ?? __('Please try again.') }}</div>
            <button type="button" class="btn btn-outline btn-dark btn-sm wa-export-participants-reload">
                <i class="fa-light fa-rotate-right me-1"></i>{{ __('Try again') }}
            </button>
        </div>
    </div>
@endif
