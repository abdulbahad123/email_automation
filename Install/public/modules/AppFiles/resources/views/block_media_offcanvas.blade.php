@can("appfiles")
@php
    $stats = UploadFile::getFileStorageStats();
    $offcanvasId = $offcanvasId ?? 'appFilesMediaOffcanvas';
    $offcanvasLabelId = $offcanvasLabelId ?? ($offcanvasId . 'Label');
    $uploadInputId = $uploadInputId ?? ($offcanvasId . 'Upload');
    $offcanvasFormId = $offcanvasFormId ?? ($offcanvasId . 'Form');
    $listClass = $listClass ?? 'app-files-offcanvas-list';
    $listSelector = '.' . ltrim($listClass, '.');
    $scrollSelector = $scrollSelector ?? ('#' . $offcanvasId . ' .ajax-scroll');
    $title = $title ?? __('Attach media');
    $description = $description ?? __('Pick one media from your library.');
    $libraryTitle = $libraryTitle ?? __('Media library');
    $libraryDescription = $libraryDescription ?? __('Selected files will appear directly in the reply box.');
    $multiSelect = isset($multiSelect) ? (int) $multiSelect : 1;
@endphp

<div class="offcanvas offcanvas-end border-start shadow-sm" tabindex="-1" id="{{ $offcanvasId }}" aria-labelledby="{{ $offcanvasLabelId }}" style="width: 420px;">
    <div class="offcanvas-header px-4 py-3 border-bottom bg-white flex-column align-items-stretch gap-12">
        <div class="d-flex align-items-start justify-content-between gap-12 zIndex-10">
            <div class="min-w-0 flex-fill">
                <div class="fs-18 fw-6 text-gray-900 mb-1" id="{{ $offcanvasLabelId }}">{{ $title }}</div>
                <div class="fs-12 text-gray-500">{{ $description }}</div>
            </div>
            <button type="button" class="btn btn-light btn-sm rounded-circle size-36 p-0 d-inline-flex align-items-center justify-content-center flex-shrink-0" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}">
                <i class="fa-light fa-xmark"></i>
            </button>
        </div>

        <div class="d-flex align-items-center flex-wrap gap-8 zIndex-6">
            <div>
                <label for="{{ $uploadInputId }}" class="btn btn-light btn-sm rounded-circle size-36 p-0 d-inline-flex align-items-center justify-content-center mb-0" title="{{ __('Upload files') }}">
                    <i class="fa-light fa-arrow-up-from-bracket"></i>
                </label>
                <input id="{{ $uploadInputId }}" class="d-none form-file-input" name="avatar" type="file" multiple="true" data-call-success="Main.ajaxScroll(true)">
            </div>

            @if(get_option('file_google_drive_status') && Gate::allows('appfiles.google_drive'))
                <a class="btn btn-light btn-sm rounded-circle size-36 p-0 d-inline-flex align-items-center justify-content-center text-gray-700 text-hover-primary"
                   id="google-drive-chooser"
                   href="javascript:void(0);"
                   data-close-offcanvas="1"
                   data-call-success="Main.ajaxScroll(true)"
                   title="{{ __('Google Drive') }}">
                    <i class="fa-brands fa-google-drive"></i>
                </a>
            @endif

            @if(get_option('file_dropbox_status') && Gate::allows('appfiles.dropbox'))
                <a class="btn btn-light btn-sm rounded-circle size-36 p-0 d-inline-flex align-items-center justify-content-center text-gray-700 text-hover-primary"
                   id="dropbox-chooser"
                   href="javascript:void(0);"
                   data-close-offcanvas="1"
                   data-call-success="Main.ajaxScroll(true)"
                   title="{{ __('Dropbox') }}">
                    <i class="fa-brands fa-dropbox"></i>
                </a>
            @endif

            @if(get_option('file_onedrive_status') && Gate::allows('appfiles.onedrive'))
                <a class="btn btn-light btn-sm rounded-circle size-36 p-0 d-inline-flex align-items-center justify-content-center text-gray-700 text-hover-primary"
                   id="onedrive-chooser"
                   href="javascript:void(0);"
                   data-close-offcanvas="1"
                   data-call-success="Main.ajaxScroll(true)"
                   title="{{ __('OneDrive') }}">
                    <i class="fa-regular fa-cloud"></i>
                </a>
            @endif

            @if(Gate::allows('appmediasearch') && !empty(SearchMedia::services()))
                <a class="btn btn-light btn-sm rounded-circle size-36 p-0 d-inline-flex align-items-center justify-content-center text-gray-700 text-hover-primary actionItem"
                   href="{{ route('app.search_media.popup_search') }}"
                   data-popup="searchMediaModel"
                   data-close-offcanvas="1"
                   title="{{ __('Search media') }}">
                    <i class="fa-light fa-magnifying-glass"></i>
                </a>
            @endif

            <div class="btn-group position-static d-flex align-items-center gap-8">
                <div class="btn btn-light btn-sm rounded-circle size-36 p-0 d-inline-flex align-items-center justify-content-center dropdown-toggle dropdown-arrow-hide text-gray-900 shadow-none"
                     data-bs-toggle="dropdown"
                     aria-expanded="false"
                     title="{{ __('More actions') }}">
                    <i class="fa-light fa-grid-2"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end border-1 border-gray-300 px-2 w-100 max-w-125">
                    <li>
                        <a class="dropdown-item p-2 rounded d-flex gap-8 fw-5 fs-14 actionMultiItem"
                           href="{{ url_app('files/update_folder') }}"
                           data-form="#{{ $offcanvasFormId }}"
                           data-popup="updateFolderModal"
                           data-close-offcanvas="1">
                            <span class="size-16 me-1 text-center"><i class="fa-light fa-folder-plus"></i></span>
                            <span>{{ __('New folder') }}</span>
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item p-2 rounded d-flex gap-8 fw-5 fs-14 actionMultiItem"
                           href="{{ url_app('files/destroy') }}"
                           data-form="#{{ $offcanvasFormId }}"
                           data-call-success="Main.ajaxScroll(true)">
                            <span class="size-16 me-1 text-center"><i class="fa-light fa-trash-can-list"></i></span>
                            <span>{{ __('Delete') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="offcanvas-body p-0 d-flex flex-column overflow-hidden bg-gray-50">
        <form id="{{ $offcanvasFormId }}" class="d-flex flex-column flex-fill overflow-hidden">
            <div class="d-flex flex-column flex-fill overflow-hidden ajax-scroll files file-widget position-relative"
                 data-select-multi="{{ $multiSelect }}"
                 data-url="{{ url_app('files/mini_list') }}"
                 data-resp="{{ $listSelector }}"
                 data-scroll="{{ $scrollSelector }}">
                <div class="flex-fill overflow-auto p-3">
                    <div class="row {{ ltrim($listClass, '.') }}"></div>
                </div>
            </div>

            <div class="px-4 py-3 border-top bg-white d-flex align-items-center justify-content-between gap-12">
                <div>
                    <div class="fs-11 text-uppercase text-gray-500 fw-6">{{ __('Total') }}</div>
                    <div class="fs-14 fw-6 text-gray-900">{{ sprintf('%d ' . __('files'), $stats['total_files']) }}</div>
                </div>
                <div class="text-end">
                    <div class="fs-11 text-uppercase text-gray-500 fw-6">{{ __('Used') }}</div>
                    <div class="fs-14 fw-6 text-gray-900">{{ $stats['used_friendly'] }}/{{ $stats['max_friendly'] }}</div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(function () {
        var offcanvasSelector = '#{{ $offcanvasId }}';
        var $offcanvas = $(offcanvasSelector);

        var hideCurrentOffcanvas = function () {
            var element = $offcanvas.get(0);
            if (!element) {
                return;
            }

            if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                var instance = bootstrap.Offcanvas.getInstance(element) || bootstrap.Offcanvas.getOrCreateInstance(element);
                instance.hide();
            }

            element.classList.remove('show');
            element.setAttribute('aria-hidden', 'true');
            element.removeAttribute('aria-modal');
            element.style.visibility = 'hidden';

            $('body').removeClass('offcanvas-backdrop-show offcanvas-open');
            $('.offcanvas-backdrop').remove();
            $('body').css({
                overflow: '',
                paddingRight: ''
            });
        };

        $(document)
            .off('click.mediaOffcanvasClose{{ $offcanvasId }}', offcanvasSelector + ' [data-close-offcanvas="1"]')
            .on('click.mediaOffcanvasClose{{ $offcanvasId }}', offcanvasSelector + ' [data-close-offcanvas="1"]', function () {
                hideCurrentOffcanvas();
            });
    });
</script>
@endcan
