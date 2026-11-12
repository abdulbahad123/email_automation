<div class="modal fade" id="ImportEmailContactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 px-4 px-lg-5 pt-4 pb-3 bg-white">
                <div class="d-flex align-items-center gap-12">
                    <div class="size-48 d-flex align-items-center justify-content-center bg-primary-100 text-primary b-r-12 flex-shrink-0">
                        <i class="fa-light fa-file-import fs-20"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-22 fw-6 text-gray-900 mb-1">{{ __('Import Email Contacts') }}</h5>
                        <div class="fs-13 text-gray-500">{{ __('Upload a CSV file (supports 10k+ contacts) or paste email addresses into this group.') }}</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 px-lg-5 pb-4 pt-0 bg-white">
                <form class="actionForm" action="{{ route('app.emailcontact.add_contact', ['id_secure' => $result->id_secure]) }}" method="POST" data-redirect="{{ route('app.emailcontact.emails', ['id_secure' => $result->id_secure]) }}">
                    @csrf

                    <ul class="nav nav-pills p-1 mb-4 rounded-3 bg-light border d-flex gap-0">
                        <li class="nav-item flex-fill text-center">
                            <button class="nav-link active w-100 rounded-3 fw-6" data-bs-toggle="pill" data-bs-target="#import_email_csv" type="button">
                                {{ __('Upload CSV') }}
                            </button>
                        </li>
                        <li class="nav-item flex-fill text-center">
                            <button class="nav-link w-100 rounded-3 fw-6" data-bs-toggle="pill" data-bs-target="#import_email_form" type="button">
                                {{ __('Paste Emails') }}
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="import_email_csv">
                            <div class="border border-dashed border-primary-300 rounded-4 p-4 p-lg-5 bg-primary-50 text-center">
                                <div class="size-64 mx-auto mb-3 d-flex align-items-center justify-content-center bg-white text-primary b-r-16 shadow-sm">
                                    <i class="fa-light fa-file-arrow-up fs-28"></i>
                                </div>
                                <div class="fs-20 fw-6 text-gray-900 mb-2">{{ __('Import from CSV') }}</div>
                                <div class="fs-14 text-gray-600 max-w-460 mx-auto mb-4">
                                    {{ __('Include Email, Name, and any extra columns (like Company or City) to personalize emails with [name], [company], etc.') }}
                                </div>
                                <div class="d-flex flex-column flex-sm-row justify-content-center gap-12">
                                    <a href="{{ route('app.emailcontact.download_example_upload_csv') }}" class="btn btn-outline btn-dark px-4">
                                        <i class="fa-light fa-file-lines me-2"></i>{{ __('Download Sample CSV') }}
                                    </a>
                                    <label class="btn btn-primary px-4 mb-0">
                                        <i class="fa-light fa-upload me-2"></i>{{ __('Choose CSV file') }}
                                        <input id="import_email_contact_file" type="file" name="files[]" accept=".csv,.txt" class="d-none" data-action="{{ route('app.emailcontact.do_import_contact', ['id_secure' => $result->id_secure]) }}">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="import_email_form">
                            <div class="rounded-4 border border-gray-200 bg-light p-3 mb-3">
                                <div class="fs-13 text-gray-600">
                                    {{ __('One contact per line. Supported formats:') }}
                                    <code>alice@example.com</code>, <code>alice@example.com, Alice</code>, {{ __('or') }} <code>Alice &lt;alice@example.com&gt;</code>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="emails_textarea" class="form-label fw-6">{{ __('Email Addresses') }}</label>
                                <textarea class="form-control" name="emails" id="emails_textarea" rows="10" placeholder="alice@example.com, Alice Johnson&#10;bob@example.com, Bob Smith&#10;charlie@example.com"></textarea>
                            </div>

                            <div class="d-flex justify-content-between gap-12 pt-3 border-top border-gray-200">
                                <button type="button" class="btn btn-outline btn-dark" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn btn-primary px-4">{{ __('Add Contacts') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).off('change.emailContactImport').on('change.emailContactImport', '#import_email_contact_file', function () {
    var url = $(this).data('action');
    var files = this.files;
    if (!files || !files.length) return false;

    var formData = new FormData();
    for (var i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }
    formData.append('_token', '{{ csrf_token() }}');

    Main.overplay();
    $(this).val('');

    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        contentType: false,
        processData: false,
        success: function (result) {
            Main.overplay(true);
            Main.showNotify('', result.message || '', result.status == 1 ? 1 : 0);
            if (result.status == 1) {
                window.location.reload();
            }
        },
        error: function (xhr) {
            Main.overplay(true);
            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __('Upload CSV file failed.') }}';
            Main.showNotify('', message, 0);
        }
    });

    return false;
});
</script>

