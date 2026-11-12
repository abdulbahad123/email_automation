<div class="modal fade" id="ContactGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 px-4 px-lg-5 pt-4 pb-3 bg-white">
                <div class="d-flex align-items-center gap-12">
                    <div class="size-48 d-flex align-items-center justify-content-center bg-primary-100 text-primary b-r-12 flex-shrink-0">
                        <i class="fa-light fa-address-book fs-20"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-22 fw-6 text-gray-900 mb-1">
                            {{ $result ? __('Update contact group') : __('Create contact group') }}
                        </h5>
                        <div class="fs-13 text-gray-500">
                            {{ __('Group your WhatsApp contacts clearly so imports and bulk actions stay organized.') }}
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 px-lg-5 pb-4 pt-0 bg-white">
                <form class="actionForm" action="{{ route('app.whatsappcontact.save', ['id_secure' => $result->id_secure ?? null]) }}" method="POST" data-redirect="{{ route('app.whatsappcontact.index') }}">
                    @csrf

                    <div class="rounded-4 border border-gray-200 bg-light p-4 mb-4">
                        <div class="fs-14 fw-6 text-gray-900 mb-2">{{ __('Visibility') }}</div>
                        <div class="fs-13 text-gray-500">{{ __('Choose whether this contact group should be available for WhatsApp actions immediately.') }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-6">{{ __('Status') }}</label>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <label class="d-flex align-items-center gap-12 px-3 py-3 rounded-3 border bg-white cursor-pointer hp-100">
                                    <input class="form-check-input mt-0" type="radio" name="status" value="1" {{ !isset($result->status) || (int) $result->status === 1 ? 'checked' : '' }}>
                                    <span class="size-36 d-flex align-items-center justify-content-center bg-success-100 text-success b-r-10 flex-shrink-0">
                                        <i class="fa-light fa-eye"></i>
                                    </span>
                                    <span>
                                        <span class="d-block fw-6 text-gray-900">{{ __('Enable') }}</span>
                                        <span class="d-block fs-12 text-gray-500">{{ __('Ready to use in workflows') }}</span>
                                    </span>
                                </label>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="d-flex align-items-center gap-12 px-3 py-3 rounded-3 border bg-white cursor-pointer hp-100">
                                    <input class="form-check-input mt-0" type="radio" name="status" value="0" {{ isset($result->status) && (int) $result->status === 0 ? 'checked' : '' }}>
                                    <span class="size-36 d-flex align-items-center justify-content-center bg-warning-100 text-warning b-r-10 flex-shrink-0">
                                        <i class="fa-light fa-eye-slash"></i>
                                    </span>
                                    <span>
                                        <span class="d-block fw-6 text-gray-900">{{ __('Disable') }}</span>
                                        <span class="d-block fs-12 text-gray-500">{{ __('Keep it saved but hidden') }}</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-6">{{ __('Group contact name') }}</label>
                        <div class="fs-12 text-gray-500 mb-2">{{ __('Use a short and specific name such as Customer leads, VIP buyers, or Team support.') }}</div>
                        <input type="text" class="form-control form-control-lg" name="name" value="{{ $result->name ?? '' }}" placeholder="{{ __('Example: Customer leads') }}" required>
                    </div>

                    <div class="d-flex justify-content-between gap-12 pt-4 mt-4 border-top border-gray-200">
                        <button type="button" class="btn btn-outline btn-dark px-4" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary px-4">
                            {{ $result ? __('Update group') : __('Create group') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

