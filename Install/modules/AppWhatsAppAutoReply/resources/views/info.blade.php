@php
    $emojiClass = 'input-emoji-' . uniqid();
@endphp

@if($status === 'success')
    <form id="WhatsAppAutoReplyForm" action="{{ module_url('save') }}" method="POST">
        @csrf
        <input type="hidden" name="instance_id" value="{{ $account->token ?? '' }}">
        <input type="hidden" name="type" value="1">

        <div class="card shadow-none border-gray-300 overflow-hidden">
            <div class="card-header border-0 p-4 pb-0 bg-white">
                <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-16">
                    <div>
                        <div class="fs-22 fw-6 text-gray-900 mb-2">
                            @if(!empty($account))
                                {{ __('Auto reply for :name', ['name' => $account->name]) }}
                            @else
                                {{ __('Set auto reply for all accounts') }}
                            @endif
                        </div>
                        <div class="fs-14 text-gray-600 max-w-620">
                            {{ __('Create a polished reply experience with AI-assisted writing, media attachments, and reply throttling controls.') }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 mb-4">
                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-4 hp-100 bg-light">
                            <div class="fs-13 fw-6 text-uppercase text-gray-500 mb-3">{{ __('Automation') }}</div>
                            <label class="form-label fw-6">{{ __('Status') }}</label>
                            <div class="d-flex flex-wrap gap-12">
                                <label class="d-flex align-items-center gap-10 px-3 py-2 rounded-3 border bg-white cursor-pointer">
                                    <input class="form-check-input mt-0" type="radio" name="status" id="wa_ar_status_1" value="1" {{ !isset($result->status) || (int) $result->status === 1 ? 'checked' : '' }}>
                                    <span class="fw-5">{{ __('Enable') }}</span>
                                </label>
                                <label class="d-flex align-items-center gap-10 px-3 py-2 rounded-3 border bg-white cursor-pointer">
                                    <input class="form-check-input mt-0" type="radio" name="status" id="wa_ar_status_0" value="0" {{ isset($result->status) && (int) $result->status === 0 ? 'checked' : '' }}>
                                    <span class="fw-5">{{ __('Disable') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-4 hp-100 bg-light">
                            <div class="fs-13 fw-6 text-uppercase text-gray-500 mb-3">{{ __('Audience') }}</div>
                            <label class="form-label fw-6">{{ __('Send to') }}</label>
                            <div class="d-flex flex-wrap gap-12">
                                <label class="d-flex align-items-center gap-10 px-3 py-2 rounded-3 border bg-white cursor-pointer">
                                    <input class="form-check-input mt-0" type="radio" name="send_to" id="wa_ar_send_to_1" value="1" {{ !isset($result->send_to) || (int) $result->send_to === 1 ? 'checked' : '' }}>
                                    <span class="fw-5">{{ __('All') }}</span>
                                </label>
                                <label class="d-flex align-items-center gap-10 px-3 py-2 rounded-3 border bg-white cursor-pointer">
                                    <input class="form-check-input mt-0" type="radio" name="send_to" id="wa_ar_send_to_2" value="2" {{ isset($result->send_to) && (int) $result->send_to === 2 ? 'checked' : '' }}>
                                    <span class="fw-5">{{ __('Individual') }}</span>
                                </label>
                                <label class="d-flex align-items-center gap-10 px-3 py-2 rounded-3 border bg-white cursor-pointer">
                                    <input class="form-check-input mt-0" type="radio" name="send_to" id="wa_ar_send_to_3" value="3" {{ isset($result->send_to) && (int) $result->send_to === 3 ? 'checked' : '' }}>
                                    <span class="fw-5">{{ __('Group') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border rounded-3 overflow-hidden mb-4">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-12 px-4 py-3 bg-light border-bottom">
                        <div>
                            <div class="fw-6 text-gray-900">{{ __('Reply composer') }}</div>
                            <div class="fs-13 text-gray-600">{{ __('Write the message, attach media, or use AI to create a better reply from a short idea.') }}</div>
                        </div>
                        <div class="d-flex flex-wrap gap-8">
                            <button type="button" class="btn btn-sm btn-light border ai-quick-prompt" data-prompt="{{ __('Write a short and friendly WhatsApp auto reply for new customer inquiries.') }}">{{ __('Friendly') }}</button>
                            <button type="button" class="btn btn-sm btn-light border ai-quick-prompt" data-prompt="{{ __('Write a concise WhatsApp auto reply that asks the customer for their order number.') }}">{{ __('Order help') }}</button>
                            <button type="button" class="btn btn-sm btn-light border ai-quick-prompt" data-prompt="{{ __('Write a professional WhatsApp auto reply that says we will respond during business hours.') }}">{{ __('Business hours') }}</button>
                        </div>
                    </div>

                    <div class="p-4">
                        <label for="wa_ar_caption" class="form-label fw-6">{{ __('Caption') }}</label>
                        <div class="mb-3 wrap-input-emoji">
                            <textarea id="wa_ar_caption" class="form-control {{ $emojiClass }} post-caption fw-4 border min-h-150" name="caption" placeholder="{{ __('Example: Thanks for contacting us. Tell us what you need and we will reply shortly.') }}">{{ $result->caption ?? '' }}</textarea>
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
                </div>

                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-4 hp-100">
                            <label for="wa_ar_delay" class="form-label fw-6">{{ __('Resubmit message only after (minute)') }}</label>
                            <input id="wa_ar_delay" type="number" min="1" class="form-control form-control-lg" name="delay" value="{{ $result->delay ?? 1 }}">
                            <div class="fs-13 text-gray-500 mt-2">
                                {{ __('Prevents sending the same reply too often in the same conversation.') }}
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-4 hp-100">
                            <label for="wa_ar_except" class="form-label fw-6">{{ __('Except contacts') }}</label>
                            <input id="wa_ar_except" type="text" class="form-control form-control-lg input-tags" name="except" value="{{ $result->except ?? '' }}" placeholder="841234567890,840123456789">
                            <div class="fs-13 text-gray-500 mt-2">
                                {{ __('Add each phone number as a tag. Press comma or Enter to create a new item.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white border-0 px-4 px-lg-5 pb-4 pt-0">
                <div class="border-top pt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg px-5 flex-shrink-0">
                        {{ __('Save Auto Reply') }}
                    </button>
                </div>
            </div>
        </div>
    </form>
@else
    <div class="alert alert-danger">
        {{ $message ?? __('An unexpected error occurred.') }}
    </div>
@endif

<script type="text/javascript">
    Main.Emoji("{{ $emojiClass }}");
    Main.Tags();

    $(document).off('click.waAiQuickPrompt').on('click.waAiQuickPrompt', '.ai-quick-prompt', function () {
        var prompt = $(this).data('prompt') || '';
        var caption = $('.post-caption');
        var aiButton = $('.generalAIContent').first();

        if (!caption.length) return;

        if (window.Main && typeof Main.typeText === 'function') {
            Main.typeText('.post-caption', prompt, 0, true);
        } else if (caption[0] && caption[0].emojioneArea) {
            caption[0].emojioneArea.setText(prompt);
        } else {
            caption.val(prompt).trigger('input');
        }

        setTimeout(function () {
            if (aiButton.length) {
                aiButton.trigger('click');
            }
        }, 250);
    });
</script>
