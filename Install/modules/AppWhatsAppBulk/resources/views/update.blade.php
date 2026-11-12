@extends('layouts.app')

@section('content')
<style>
@media (min-width: 992px) {
    .compose-media {
        display: flex !important;
        flex: 0 0 390px !important;
        width: 390px !important;
        min-width: 390px !important;
        max-width: 390px !important;
        overflow: hidden;
    }
}

.compose-media {
    flex-shrink: 0;
}
</style>
@php
    $emojiClass = 'input-emoji-' . uniqid();
@endphp
<div class="position-absolute l-0 t-0 wp-100 hp-100 bg-white zIndex-9">
    <div class="d-flex hp-100">
        @can('appfiles')
        <div class="compose-media d-flex flex-column bg-white d-none flex-shrink-0 overflow-hidden border-end" style="flex: 0 0 390px; width: 390px; min-width: 390px; max-width: 390px;">
            @include('appfiles::block_files')
        </div>
        @endcan

        <form id="WhatsAppBulkForm" class="compose-editor d-flex flex-column flex-fill hp-100 border-start border-end actionForm bg-white" action="{{ route('app.whatsappbulk.save', ['id_secure' => $result->id_secure ?? null]) }}" method="POST">
            @csrf
            <input type="hidden" name="type" value="1">

            <div class="container-fluid px-4 px-lg-5 py-4 d-flex flex-column hp-100 overflow-auto">
                <div class="d-flex flex-column flex-fill gap-24 min-h-100">
                    <div class="d-flex flex-column flex-xl-row align-items-xl-end justify-content-between gap-20">
                        <div class="d-flex flex-column gap-10">
                            <div class="d-inline-flex align-items-center gap-8 px-3 py-2 bg-primary-100 text-primary rounded-pill fs-12 fw-6">
                                <i class="fa-brands fa-whatsapp"></i>
                                <span>{{ __('WhatsApp Unofficial') }}</span>
                            </div>
                            <div>
                                <h1 class="fs-28 lh-sm fw-6 text-gray-900 mb-2">{{ $result ? __('Update bulk campaign') : __('Create bulk campaign') }}</h1>
                                <div class="fs-15 text-gray-600 max-w-760">{{ __('Schedule a controlled WhatsApp bulk send to a saved contact group, rotate across connected profiles, and attach media when needed.') }}</div>
                            </div>
                        </div>
                        <div class="d-flex gap-8 flex-wrap">
                            <a href="{{ route('app.whatsappbulk.index') }}" class="btn btn-dark btn-lg showCompose">{{ __('Back') }}</a>
                            <button type="submit" class="btn btn-primary btn-lg px-5">{{ $result ? __('Update campaign') : __('Schedule campaign') }}</button>
                        </div>
                    </div>

                    <div class="row g-4 flex-fill">
                        <div class="col-12 col-xxl-5 d-flex">
                            <div class="card shadow-none border-gray-300 overflow-hidden w-100">
                                <div class="card-body p-4 p-lg-5 d-flex flex-column gap-24">
                                    <div>
                                        <div class="fs-20 fw-6 text-gray-900 mb-2">{{ __('Campaign setup') }}</div>
                                        <div class="text-gray-600 fs-14">{{ __('Choose the WhatsApp profiles that will send, bind the campaign to a contact group, and define how fast the worker should move from one contact to the next.') }}</div>
                                    </div>

                                    <div>
                                        <label class="form-label fw-6 mb-3">{{ __('Select WhatsApp accounts') }}</label>
                                        @include('appchannels::block_channels', [
                                            'social_network' => 'whatsapp_unofficial',
                                            'accounts' => $selectedAccounts ?? []
                                        ])
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label fw-6">{{ __('Campaign name') }}</label>
                                            <input type="text" class="form-control" name="name" value="{{ $result->name ?? '' }}" placeholder="{{ __('Example: March promo blast') }}">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-6">{{ __('Contact group') }}</label>
                                            <select class="form-select h-35" name="contact_group" data-control="select2" data-hide-search="0" data-placeholder="{{ __('Search contact group') }}">
                                                <option value="">{{ __('Search contact group') }}</option>
                                                @foreach($contacts as $contact)
                                                    <option value="{{ $contact->id_secure }}" {{ isset($result->contact_id) && (int) $result->contact_id === (int) $contact->id ? 'selected' : '' }}>{{ $contact->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="fs-12 text-gray-500 mt-2">{{ __('Search by group name and choose the list that this campaign should send to.') }}</div>
                                        </div>
                                    </div>

                                    <div class="border rounded-3 p-4 bg-light">
                                        <div class="fs-13 fw-6 text-uppercase text-gray-500 mb-3">{{ __('Schedule') }}</div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-6">{{ __('Time post') }}</label>
                                                <input type="text" class="form-control datetime" autocomplete="off" name="time_post" value="{{ !empty($result?->time_post) ? datetime_show($result->time_post) : '' }}" placeholder="{{ __('Select date and time') }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-6">{{ __('Min interval (second)') }}</label>
                                                <input type="number" min="1" class="form-control" name="min_interval_per_post" value="{{ $result->min_delay ?? 30 }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-6">{{ __('Max interval (second)') }}</label>
                                                <input type="number" min="1" class="form-control" name="max_interval_per_post" value="{{ $result->max_delay ?? 60 }}">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-6">{{ __('Schedule hours') }}</label>
                                                <div class="d-flex flex-wrap gap-16 fs-14 mb-2 wa-bulk-schedule-shortcuts">
                                                    <a href="javascript:void(0);" class="text-primary text-hover-primary wa-bulk-schedule-preset" data-preset="daytime">{{ __('Daytime') }}</a>
                                                    <a href="javascript:void(0);" class="text-primary text-hover-primary wa-bulk-schedule-preset" data-preset="nighttime">{{ __('Nighttime') }}</a>
                                                    <a href="javascript:void(0);" class="text-primary text-hover-primary wa-bulk-schedule-preset" data-preset="odd">{{ __('Odd') }}</a>
                                                    <a href="javascript:void(0);" class="text-primary text-hover-primary wa-bulk-schedule-preset" data-preset="even">{{ __('Even') }}</a>
                                                    <a href="javascript:void(0);" class="text-danger text-hover-danger wa-bulk-schedule-clear ms-lg-2">{{ __('Clear') }}</a>
                                                </div>
                                                <select class="form-select min-h-45 h-auto wa-bulk-schedule-hours" data-control="select2" name="schedule_time[]" multiple="true" data-placeholder="{{ __("Add hours") }}">
                                                    @for($i = 0; $i <= 23; $i++)
                                                        <option value="{{ $i }}" {{ in_array((string) $i, $scheduleHours ?? [], true) ? 'selected' : '' }}>{{ $i }}</option>
                                                    @endfor
                                                </select>
                                                <div class="fs-12 text-gray-500 mt-2">{{ __('The schedule allows you to set a unique run window for this campaign.') }}</div>
                                                <div class="fs-12 text-danger mt-1">{{ __('Leave empty to let the campaign run anytime.') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xxl-7 d-flex">
                            <div class="card shadow-none border-gray-300 overflow-hidden w-100">
                                <div class="card-header border-0 p-4 p-lg-5 pb-0 bg-white d-flex flex-column flex-lg-row justify-content-between gap-12">
                                    <div>
                                        <div class="fw-6 text-gray-900 fs-20">{{ __('Message composer') }}</div>
                                        <div class="fs-13 text-gray-600">{{ __('Write the campaign message, attach one media item if needed, and optionally use AI to improve the caption before scheduling.') }}</div>
                                    </div>
                                </div>
                                <div class="card-body p-4 p-lg-5 d-flex flex-column gap-20">
                                    <div class="d-flex flex-wrap gap-8">
                                        <span class="badge badge-outline badge-light-primary text-primary">{{ __('Spintax supported') }}</span>
                                        <span class="badge badge-outline badge-light-warning text-warning">{{ __('Use one media file per campaign') }}</span>
                                    </div>

                                    <div>
                                        <label for="wa_ar_caption" class="form-label fw-6">{{ __('Caption') }}</label>
                                        <div class="mb-3 wrap-input-emoji">
                                            <textarea id="wa_ar_caption" class="form-control {{ $emojiClass }} post-caption fw-4 border min-h-150" name="caption" placeholder="{{ __('Example: {Hi|Hello|Hey} %name%, here is the latest update from our team.') }}">{{ $result->caption ?? '' }}</textarea>
                                            <div class="p-3 border-end border-start border-bottom compose-type-media bg-white">
                                                @can("appfiles")
                                                <div class="compose-type-media">
                                                    @include('appfiles::block_selected_files', [
                                                        'files' => $selectedFiles ?? false
                                                    ])
                                                </div>
                                                @endcan
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center overflow-x-auto border border-top-0 bbr-r-6 bbl-r-6 bg-white">
                                                <div class="d-flex align-items-center">
                                                    @if(get_option("ai_status", 1) && Gate::allows('appaicontents'))
                                                    <div class="border-start">
                                                        <a href="javascript:void(0);" class="px-3 py-2 d-block generalAIContent" data-url="{{ route('app.ai-contents.create_content') }}" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-title="{{ __('AI Content') }}" data-bs-html="true" data-bs-content="{!! __('Enter a prompt in the caption box and click this button. Our AI will generate the perfect content for you with just one click.<br/><br/><b>Example:</b> Create a warm auto reply for customers asking about pricing.') !!}"><i class="fa-light fa-wand-magic-sparkles p-0"></i></a>
                                                    </div>
                                                    @endif

                                                    @if(get_option("url_shorteners_platform", 0) && Gate::allows('appmediasearch'))
                                                    <div class="border-start">
                                                        <a href="{{ url_app('url-shorteners/shorten') }}" class="px-3 py-2 d-block text-gray-700 text-nowrap actionMultiItem" data-call-success="AppPubishing.shorten(result);" data-bs-title="{{ __('Shorten Links') }}" data-bs-toggle="tooltip" data-bs-placement="top"><i class="fa-light fa-link-simple"></i></a>
                                                    </div>
                                                    @endif

                                                    @if(Gate::allows('appcaptions'))
                                                    <div class="border-start">
                                                        <a href="{{ route('app.captions.get_cation') }}" class="px-3 py-2 d-block text-gray-700 actionItem" data-offcanvas="getCaptionOffCanvas" data-bs-title="{{ __('Get Caption') }}" data-bs-toggle="tooltip" data-bs-placement="top"><i class="fal fa-comment-alt-lines p-0"></i></a>
                                                    </div>
                                                    <div class="border-start">
                                                        <a href="{{ route('app.captions.save_cation') }}" class="px-3 py-2 d-block text-gray-700 actionItem" data-popup="saveCaptionModal" data-bs-title="{{ __('Save caption') }}" data-bs-toggle="tooltip" data-bs-placement="top"><i class="fal fa-save p-0"></i></a>
                                                    </div>
                                                    @endif

                                                    <div class="count-word px-3 d-flex align-items-center justify-content-center text-gray-700 gap-8 py-2 border-start">
                                                        <span>0</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="border rounded-3 p-4 bg-light">
                                        <div class="d-flex align-items-start gap-12">
                                            <div class="size-44 d-flex align-items-center justify-content-center rounded-circle bg-primary-100 text-primary flex-shrink-0">
                                                <i class="fa-light fa-bolt"></i>
                                            </div>
                                            <div>
                                                <div class="fw-6 text-gray-900 mb-1">{{ __('Delivery logic') }}</div>
                                                <div class="fs-13 text-gray-600">{{ __('The worker will rotate through the selected contact group and connected WhatsApp profiles using the delay window you configured on the left.') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-top p-4 p-lg-5 d-flex justify-content-between align-items-center gap-12 flex-wrap">
                                    <a href="{{ route('app.whatsappbulk.index') }}" class="btn btn-dark showCompose d-lg-none d-md-none d-sm-block">{{ __('Back to setup') }}</a>
                                    <div class="ms-auto d-flex gap-8 flex-wrap">
                                        <a href="{{ route('app.whatsappbulk.index') }}" class="btn btn-dark btn-lg d-none d-lg-inline-flex">{{ __('Back') }}</a>
                                        <button type="submit" class="btn btn-primary btn-lg px-5">{{ $result ? __('Update campaign') : __('Schedule campaign') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript">
(function () {
    if (window.Main && typeof Main.Emoji === 'function') {
        Main.Emoji('{{ $emojiClass }}');
    }

    if (window.Files && typeof Files.init === 'function') {
        try {
            Files.init(false);
        } catch (e) {
            console.warn('Files.init warning:', e);
        }
    }

    var isDesktopViewport = function () {
        return $(window).width() >= 992;
    };

    var toggleComposePanels = function (showMedia) {
        var mediaPanel = $('.compose-media');
        var editorPanel = $('.compose-editor');

        if (isDesktopViewport()) {
            mediaPanel.removeClass('d-none');
            editorPanel.removeClass('d-none');
            return;
        }

        if (showMedia) {
            mediaPanel.removeClass('d-none');
            editorPanel.addClass('d-none');
        } else {
            mediaPanel.addClass('d-none');
            editorPanel.removeClass('d-none');
        }
    };

    var syncComposeMediaResponsive = function () {
        if (isDesktopViewport()) {
            toggleComposePanels(true);
        } else {
            toggleComposePanels(false);
        }
    };

    var scheduleHoursByPreset = function (preset) {
        switch (preset) {
            case 'daytime':
                return ['7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18'];
            case 'nighttime':
                return ['19', '20', '21', '22', '23', '0', '1', '2', '3', '4', '5', '6'];
            case 'odd':
                return ['1', '3', '5', '7', '9', '11', '13', '15', '17', '19', '21', '23'];
            case 'even':
                return ['0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22'];
            default:
                return [];
        }
    };

    var bindMediaToComposer = function (fileVal, fileUrl, fileType, fileId) {
        var mediaContainer = $('#medias .items');
        if (!mediaContainer.length) {
            mediaContainer = $('.file-selected-media .items');
        }

        var isVideo = (fileType === 'video' || /\.(mp4|mov|avi|webm|mkv|3gp|m4v|wmv)$/i.test(fileUrl || fileVal));
        var previewHtml = '';

        if (isVideo) {
            previewHtml = '<div class="file-item w-100 ratio ratio-1x1 min-h-80 border b-r-6 rounded selected text-danger bg-danger-100 position-relative mb-2" data-id="' + fileId + '" data-file="' + fileUrl + '" data-type="video">' +
                '<label class="d-flex flex-column flex-fill mb-0 h-100">' +
                    '<input class="form-check-input" name="medias[]" type="hidden" value="' + fileVal + '" id="' + fileId + '">' +
                    '<div class="d-flex flex-fill align-items-center justify-content-center overflow-hidden position-relative btl-r-6 btr-r-6 file-item-media">' +
                        '<video class="position-absolute top-0 start-0 w-100 h-100 object-cover" src="' + fileUrl + '" preload="metadata" muted loop playsinline></video>' +
                    '</div>' +
                '</label>' +
                '<button type="button" class="remove bg-white border b-r-100 text-danger w-20 h-20 fs-12 position-absolute r-0 t-0"><i class="fal fa-times"></i></button>' +
            '</div>';
        } else {
            previewHtml = '<div class="file-item w-100 ratio ratio-1x1 min-h-80 border b-r-6 rounded selected text-primary bg-primary-100 position-relative mb-2" data-id="' + fileId + '" data-file="' + fileUrl + '" data-type="' + (fileType || 'image') + '">' +
                '<label class="d-flex flex-column flex-fill mb-0 h-100">' +
                    '<input class="form-check-input" name="medias[]" type="hidden" value="' + fileVal + '" id="' + fileId + '">' +
                    '<div class="d-flex flex-fill align-items-center justify-content-center overflow-y-auto bg-cover position-relative btl-r-6 btr-r-6 file-item-media" style="background-image: url(\'' + fileUrl + '\'); background-size: cover; background-position: center;"></div>' +
                '</label>' +
                '<button type="button" class="remove bg-white border b-r-100 text-danger w-20 h-20 fs-12 position-absolute r-0 t-0"><i class="fal fa-times"></i></button>' +
            '</div>';
        }

        mediaContainer.html(previewHtml);
        $('.file-selected-media .drophere, #medias .drophere').hide();
    };

    var unbindMediaFromComposer = function (fileId) {
        if (fileId) {
            $('.file-selected-media .items .file-item[data-id="' + fileId + '"], #medias .items .file-item[data-id="' + fileId + '"]').remove();
            $('.compose-media .file-widget .file-item[data-id="' + fileId + '"]').find('input:checkbox').prop('checked', false);
            $('.compose-media .file-widget .file-item[data-id="' + fileId + '"]').removeClass('selected active');
        } else {
            $('.file-selected-media .items, #medias .items').empty();
            $('.compose-media .file-widget .file-item').find('input:checkbox').prop('checked', false);
            $('.compose-media .file-widget .file-item').removeClass('selected active');
        }

        var totalItems = $('.file-selected-media .items .file-item, #medias .items .file-item').length;
        if (totalItems === 0) {
            $('.file-selected-media .drophere, #medias .drophere').show();
        }
    };

    var syncActiveGallerySelections = function () {
        var selectedItem = $('.file-selected-media .items .file-item, #medias .items .file-item').first();
        if (selectedItem.length) {
            var fileId = selectedItem.attr('data-id');
            var fileVal = selectedItem.find('input[name="medias[]"]').val();
            $('.compose-media .file-widget .file-item').each(function () {
                var item = $(this);
                var cb = item.find('input:checkbox');
                if ((fileId && item.attr('data-id') === fileId) || (fileVal && cb.val() === fileVal)) {
                    cb.prop('checked', true);
                    item.addClass('selected active');
                }
            });
            $('.file-selected-media .drophere, #medias .drophere').hide();
        }
    };

    $(document).off('change.waBulkMediaCheckbox', '.compose-media .file-widget .file-item input:checkbox')
               .on('change.waBulkMediaCheckbox', '.compose-media .file-widget .file-item input:checkbox', function (e) {
        var checkbox = $(this);
        var fileItem = checkbox.closest('.file-item');
        var isChecked = checkbox.is(':checked');
        var fileVal = checkbox.val();
        var fileUrl = fileItem.attr('data-file') || '';
        var fileType = fileItem.attr('data-type') || 'image';
        var fileId = fileItem.attr('data-id') || ('file_' + Math.random().toString(36).substr(2, 9));

        if (isChecked) {
            // Uncheck other items in the media widget (WhatsApp Bulk campaign uses 1 media file)
            $('.compose-media .file-widget .file-item').not(fileItem).find('input:checkbox').prop('checked', false);
            $('.compose-media .file-widget .file-item').not(fileItem).removeClass('selected active');
            fileItem.addClass('selected active');

            bindMediaToComposer(fileVal, fileUrl, fileType, fileId);

            if (!isDesktopViewport()) {
                toggleComposePanels(false);
            }
        } else {
            fileItem.removeClass('selected active');
            unbindMediaFromComposer(fileId);
        }
    });

    $(document).off('click.waBulkFileItemBox', '.compose-media .file-widget .file-item')
               .on('click.waBulkFileItemBox', '.compose-media .file-widget .file-item', function (e) {
        // Skip if clicked directly on checkbox, dropdown action, or link
        if ($(e.target).closest('input:checkbox, .dropdown, .dropdown-menu, .dropdown-toggle, a, button').length) {
            return;
        }

        var checkbox = $(this).find('input:checkbox');
        if (checkbox.length) {
            checkbox.prop('checked', !checkbox.is(':checked')).trigger('change');
        }
    });

    $(document).off('click.waBulkRemove', '.file-selected-media .file-item .remove, #medias .remove')
               .on('click.waBulkRemove', '.file-selected-media .file-item .remove, #medias .remove', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var fileItem = $(this).closest('.file-item');
        var fileId = fileItem.attr('data-id');
        unbindMediaFromComposer(fileId);
        return false;
    });

    $(document).off('click.waBulkShowMedia').on('click.waBulkShowMedia', '.showMedia', function (e) {
        e.preventDefault();
        if (!isDesktopViewport()) {
            toggleComposePanels(true);
        }
        return false;
    });

    $(document).off('click.waBulkShowCompose').on('click.waBulkShowCompose', '.showCompose', function (e) {
        if (!isDesktopViewport()) {
            e.preventDefault();
            toggleComposePanels(false);
            return false;
        }
    });

    $(document).off('click.waBulkSchedulePreset').on('click.waBulkSchedulePreset', '.wa-bulk-schedule-preset', function (e) {
        e.preventDefault();
        var preset = $(this).data('preset') || '';
        var values = scheduleHoursByPreset(preset);
        $('.wa-bulk-schedule-hours').val(values).trigger('change');
        return false;
    });

    $(document).off('click.waBulkScheduleClear').on('click.waBulkScheduleClear', '.wa-bulk-schedule-clear', function (e) {
        e.preventDefault();
        $('.wa-bulk-schedule-hours').val(null).trigger('change');
        return false;
    });

    $(window).off('resize.waBulkComposeMedia').on('resize.waBulkComposeMedia', function () {
        syncComposeMediaResponsive();
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        if (settings && settings.url && settings.url.indexOf('files/mini_list') !== -1) {
            syncActiveGallerySelections();
        }
    });

    syncComposeMediaResponsive();
    syncActiveGallerySelections();

    // Ensure Media gallery always loads even if initial ajaxScroll missed or container finished mounting later
    setTimeout(function waBulkEnsureMediaLoaded() {
        var scroller = $('.compose-media .ajax-scroll');
        var listBox = scroller.find('.file-list');
        if (scroller.length && listBox.length && $.trim(listBox.html()) === '' && !scroller.hasClass('disabled')) {
            scroller.attr('data-loading', 0).removeAttr('data-page');
            if (window.Main && typeof Main.ajaxScroll === 'function') {
                Main.ajaxScroll(true);
            }
        }
    }, 250);
})();
</script>
@endsection