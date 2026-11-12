<div class="modal fade" id="ImportContactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 px-4 px-lg-5 pt-4 pb-3 bg-white">
                <div class="d-flex align-items-center gap-12">
                    <div class="size-48 d-flex align-items-center justify-content-center bg-primary-100 text-primary b-r-12 flex-shrink-0">
                        <i class="fa-light fa-file-import fs-20"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fs-22 fw-6 text-gray-900 mb-1">{{ __('Import contact') }}</h5>
                        <div class="fs-13 text-gray-500">{{ __('Upload a CSV file or paste multiple phone numbers into this contact group.') }}</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body px-4 px-lg-5 pb-4 pt-0 bg-white">
                <form class="actionForm" action="{{ route('app.whatsappcontact.add_contact', ['id_secure' => $result->id_secure]) }}" method="POST" data-redirect="{{ route('app.whatsappcontact.phone_numbers', ['id_secure' => $result->id_secure]) }}">
                    @csrf

                    <ul class="nav nav-pills p-1 mb-4 rounded-3 bg-light border d-flex gap-0">
                        <li class="nav-item flex-fill text-center">
                            <button class="nav-link active w-100 rounded-3 fw-6" data-bs-toggle="pill" data-bs-target="#import_csv" type="button">
                                {{ __('Upload CSV') }}
                            </button>
                        </li>
                        <li class="nav-item flex-fill text-center">
                            <button class="nav-link w-100 rounded-3 fw-6" data-bs-toggle="pill" data-bs-target="#import_form" type="button">
                                {{ __('Via Form') }}
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="import_csv">
                            <div class="border border-dashed border-primary-300 rounded-4 p-4 p-lg-5 bg-primary-50 text-center">
                                <div class="size-64 mx-auto mb-3 d-flex align-items-center justify-content-center bg-white text-primary b-r-16 shadow-sm">
                                    <i class="fa-light fa-file-arrow-up fs-28"></i>
                                </div>
                                <div class="fs-20 fw-6 text-gray-900 mb-2">{{ __('Import from CSV') }}</div>
                                <div class="fs-14 text-gray-600 max-w-420 mx-auto mb-4">
                                    {{ __('Use the sample template to structure your phone numbers and optional parameters correctly before uploading.') }}
                                </div>
                                <div class="d-flex flex-column flex-sm-row justify-content-center gap-12">
                                    <a href="{{ route('app.whatsappcontact.download_example_upload_csv') }}" class="btn btn-outline btn-dark px-4">
                                        <i class="fa-light fa-file-lines me-2"></i>{{ __('Example template') }}
                                    </a>
                                    <label class="btn btn-primary px-4 mb-0">
                                        <i class="fa-light fa-upload me-2"></i>{{ __('Choose CSV file') }}
                                        <input id="import_whatsapp_contact" type="file" name="files[]" class="d-none" data-action="{{ route('app.whatsappcontact.do_import_contact', ['id_secure' => $result->id_secure]) }}">
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="import_form">
                            <div class="rounded-4 border border-gray-200 bg-light p-4 mb-4">
                                <div class="fs-14 fw-6 text-gray-900 mb-2">{{ __('Paste multiple phone numbers') }}</div>
                                <div class="fs-13 text-gray-500">{{ __('One phone number per line. Group IDs such as @g.us are also supported.') }}</div>
                            </div>

                            <div class="mb-4">
                                <label for="phone_numbers" class="form-label fw-6">{{ __('Phone numbers') }}</label>
                                <textarea class="form-control form-control-lg" name="phone_numbers" id="phone_numbers" rows="12" placeholder="841234567890&#10;840123456789&#10;+840123456798&#10;84123456789-1618177713@g.us"></textarea>
                            </div>

                            <div class="d-flex justify-content-between gap-12 pt-3 border-top border-gray-200">
                                <button type="button" class="btn btn-outline btn-dark" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn btn-primary px-4">{{ __('Add numbers') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).off('change.whatsappContactImport').on('change.whatsappContactImport', '#import_whatsapp_contact', function () {
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
            var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __('Upload csv file failed.') }}';
            Main.showNotify('', message, 0);
        }
    });

    return false;
});
</script>

