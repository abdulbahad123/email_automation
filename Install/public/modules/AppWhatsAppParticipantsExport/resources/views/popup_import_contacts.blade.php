<div class="modal fade" id="ParticipantsImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 overflow-hidden b-r-16">
            <div class="modal-header border-bottom px-4 py-3">
                <div class="d-flex align-items-center gap-12">
                    <div class="size-48 d-flex align-items-center justify-content-center rounded-circle bg-primary-100 text-primary">
                        <i class="fa-light fa-address-book"></i>
                    </div>
                    <div>
                        <div class="fs-22 fw-6 text-gray-900">{{ __('Import participants to contacts') }}</div>
                        <div class="fs-13 text-gray-600">{{ __('Choose one or more contact groups to receive members from this WhatsApp group.') }}</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form class="actionForm" action="{{ route('app.whatsappparticipantsexport.import', ['account_id' => $account->id_secure, 'group_id' => $group['id']]) }}" method="POST" data-call-success="Main.closeModal('ParticipantsImportModal')">
                <div class="modal-body p-4">
                    <div class="border rounded-3 bg-light px-4 py-3 mb-4">
                        <div class="fs-12 text-gray-500 text-uppercase fw-6 mb-2">{{ __('Source group') }}</div>
                        <div class="fs-18 fw-6 text-gray-900">{{ $group['name'] ?? __('Untitled group') }}</div>
                        <div class="fs-13 text-gray-600 mt-1">{{ trans_choice(':count participants ready to import', (int) ($group['size'] ?? 0), ['count' => (int) ($group['size'] ?? 0)]) }}</div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between gap-12 mb-2">
                            <label class="form-label fw-6 mb-0">{{ __('Contact groups') }}</label>
                            @if($contacts->isNotEmpty())
                                <label class="form-check form-check-sm form-check-custom form-check-solid m-0 d-inline-flex align-items-center gap-8 text-nowrap">
                                    <input class="form-check-input" type="checkbox" id="participants_import_check_all">
                                    <span class="form-check-label fs-12 text-gray-600 text-nowrap">{{ __('Select all') }}</span>
                                </label>
                            @endif
                        </div>

                        @if($contacts->isEmpty())
                            <div class="alert alert-warning mb-0">{{ __('No contact groups found. Please create a contact group first in WhatsApp Contacts.') }}</div>
                        @else
                            <div class="border rounded-3 overflow-auto p-2" style="max-height: 260px;">
                                <div class="d-flex flex-column gap-8">
                                    @foreach($contacts as $contact)
                                        <label class="d-flex align-items-center justify-content-between gap-12 px-3 py-2 border rounded-3 cursor-pointer bg-hover-light">
                                            <div class="d-flex align-items-center gap-10 min-w-0">
                                                <span class="form-check form-check-sm form-check-custom form-check-solid m-0">
                                                    <input class="form-check-input participants-import-checkbox" type="checkbox" name="contact_ids[]" value="{{ $contact->id_secure }}">
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="d-block fw-5 text-gray-900 text-truncate">{{ $contact->name }}</span>
                                                    <span class="d-block fs-12 text-gray-500">{{ __('WhatsApp contact group') }}</span>
                                                </span>
                                            </div>
                                            <span class="badge badge-outline badge-sm {{ (int) $contact->status === 1 ? 'badge-light-success text-success' : 'badge-light-warning text-warning' }}">
                                                {{ (int) $contact->status === 1 ? __('Enabled') : __('Disabled') }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="fs-12 text-gray-500 mt-2">{{ __('You can import into a single contact group or several groups at the same time.') }}</div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline btn-dark" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary" @if($contacts->isEmpty()) disabled @endif>
                        <i class="fa-light fa-download me-1"></i>{{ __('Import participants') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).off('change.participantsImportCheckAll').on('change.participantsImportCheckAll', '#participants_import_check_all', function () {
    $('.participants-import-checkbox').prop('checked', $(this).is(':checked'));
});

$(document).off('change.participantsImportItem').on('change.participantsImportItem', '.participants-import-checkbox', function () {
    var all = $('.participants-import-checkbox').length;
    var checked = $('.participants-import-checkbox:checked').length;
    $('#participants_import_check_all').prop('checked', all > 0 && all === checked);
});
</script>