@php
    $emojiClass = 'input-emoji-' . uniqid();
@endphp

@if($status === 'success')
    <div class="d-flex flex-column gap-20 hp-100">
        <div class="card shadow-none border-gray-300 overflow-hidden">
            <div class="card-body p-4 p-xl-5">
                <div class="rounded-3 p-4 p-xl-5 bg-light border mb-4">
                    <div class="d-flex flex-column flex-xxl-row align-items-xxl-start justify-content-between gap-20">
                        <div class="d-flex align-items-start gap-16 min-w-0 flex-grow-1">
                            <div class="w-56 h-56 rounded-circle bg-success-100 text-success d-flex align-items-center justify-content-center flex-shrink-0 fs-22">
                                <i class="fa-light fa-user-robot"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-10 mb-2">
                                    <span class="px-3 py-1 rounded-pill bg-white border fs-12 fw-6 text-success">{{ __('Chatbot control center') }}</span>
                                    <span class="px-3 py-1 rounded-pill {{ $run ? 'bg-success-100 text-success' : 'bg-dark text-white' }} fs-12 fw-6">
                                        {{ $run ? __('Running') : __('Stopped') }}
                                    </span>
                                </div>

                                <div class="fs-15 text-gray-600 max-w-700">
                                    {{ __('Build tighter keyword rules, review existing chatbot items, and keep the reply composer in one place.') }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-12 flex-shrink-0 align-self-start">
                            <button type="button" class="btn {{ $run ? 'btn-success' : 'btn-dark' }} btn-lg wa-chatbot-status" data-url="{{ route('app.whatsappchatbot.status', ['instance_id' => $account->token]) }}">
                                @if($run)
                                    <i class="fa-solid fa-circle-notch fa-spin me-2"></i>{{ __('Running') }}
                                @else
                                    <i class="fa-light fa-stop-circle me-2"></i>{{ __('Stopped') }}
                                @endif
                            </button>
                            <button type="button" class="btn btn-primary btn-lg showCompose wa-chatbot-new">
                                <i class="fa-light fa-plus me-2"></i>{{ __('New item') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 g-xl-4">
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 p-4 hp-100 bg-white">
                            <div class="fs-11 text-uppercase fw-6 text-gray-500 mb-2">{{ __('Profile') }}</div>
                            <div class="fs-24 fw-6 text-gray-900 mb-1 text-truncate">{{ $account->name }}</div>
                            <div class="fs-13 text-gray-500 text-truncate">{{ $account->username ?? $account->pid ?? $account->token }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="border rounded-3 p-4 hp-100 bg-white">
                            <div class="fs-11 text-uppercase fw-6 text-gray-500 mb-2">{{ __('Items') }}</div>
                            <div class="fs-24 fw-6 text-gray-900 mb-1">{{ $items->count() }}</div>
                            <div class="fs-13 text-gray-500">{{ __('Active keyword rules') }}</div>
                        </div>
                    </div>

                </div>

                <div class="border-top mt-4 pt-4">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-12 mb-3">
                        <div>
                            <div class="fs-20 fw-6 text-gray-900">{{ __('Chatbot items') }}</div>
                            <div class="fs-13 text-gray-500">{{ __('Pick an existing rule to edit it, or start a clean item from the button above.') }}</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        @forelse($items as $item)
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="border rounded-3 p-3 p-lg-4 hp-100 {{ !empty($result?->id_secure) && $result->id_secure === $item->id_secure ? 'border-primary bg-primary-100' : 'bg-white' }}">
                                    <div class="d-flex justify-content-between align-items-start gap-12 mb-3">
                                        <div class="min-w-0">
                                            <div class="fw-6 text-gray-900 text-truncate mb-1">{{ $item->name }}</div>
                                            <div class="d-flex flex-wrap gap-8 fs-12">
                                                <span class="px-2 py-1 rounded-pill bg-light text-gray-700">{{ sprintf(__('%d keywords'), count(array_filter(explode(',', (string) $item->keywords)))) }}</span>
                                                <span class="px-2 py-1 rounded-pill {{ (int) $item->status ? 'bg-success-100 text-success' : 'bg-gray-200 text-gray-700' }}">{{ (int) $item->status ? __('Enabled') : __('Disabled') }}</span>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-8 flex-shrink-0">
                                            <button type="button" class="btn btn-light-dark btn-sm wa-chatbot-edit" data-id="{{ $item->id_secure }}">
                                                <i class="fa-light fa-pen"></i>
                                            </button>
                                            <button type="button" class="btn btn-light-dark btn-sm wa-chatbot-delete" data-url="{{ route('app.whatsappchatbot.delete', ['id_secure' => $item->id_secure]) }}">
                                                <i class="fa-light fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="fs-13 text-gray-600 lh-base text-break">{{ $item->keywords }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="border rounded-3 p-4 p-lg-5 text-center text-gray-500 bg-light">
                                    {{ __('No chatbot items yet. Create the first one below.') }}
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <form id="WhatsAppChatbotForm" action="{{ route('app.whatsappchatbot.save') }}" method="POST" class="card shadow-none border-gray-300 overflow-hidden">
            @csrf
            <input type="hidden" name="instance_id" value="{{ $account->token }}">
            <input type="hidden" name="id_secure" value="{{ $result->id_secure ?? '' }}">

            <div class="card-header border-0 p-4 p-xl-5 pb-0 bg-white">
                <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-between gap-16">
                    <div>
                        <div class="fs-28 fw-6 text-gray-900 mb-2">{{ $result ? __('Edit chatbot item') : __('Create chatbot item') }}</div>
                        <div class="fs-15 text-gray-600">{{ __('Define the trigger on the left and craft the reply experience on the right.') }}</div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 p-xl-5">
                <div class="row g-4 g-xxl-5 align-items-start">
                    <div class="col-12 col-xl-4">
                        <div class="d-flex flex-column gap-4">
                            <div class="row g-4">
                                <div class="col-12">
                                    <div class="border rounded-3 p-4 bg-light hp-100">
                                        <div class="fs-13 fw-6 text-uppercase text-gray-500 mb-3">{{ __('Automation') }}</div>
                                        <label class="form-label fw-6">{{ __('Status') }}</label>
                                        <div class="row g-2 mb-4">
                                            <div class="col-6">
                                                <label class="d-flex align-items-center justify-content-center gap-10 px-3 py-3 rounded-3 border bg-white cursor-pointer hp-100">
                                                    <input class="form-check-input mt-0" type="radio" name="status" value="1" {{ !isset($result->status) || (int) $result->status === 1 ? 'checked' : '' }}>
                                                    <span class="fw-5">{{ __('Enable') }}</span>
                                                </label>
                                            </div>
                                            <div class="col-6">
                                                <label class="d-flex align-items-center justify-content-center gap-10 px-3 py-3 rounded-3 border bg-white cursor-pointer hp-100">
                                                    <input class="form-check-input mt-0" type="radio" name="status" value="0" {{ isset($result->status) && (int) $result->status === 0 ? 'checked' : '' }}>
                                                    <span class="fw-5">{{ __('Disable') }}</span>
                                                </label>
                                            </div>
                                        </div>

                                        <label class="form-label fw-6">{{ __('Send to') }}</label>
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <label class="d-flex align-items-center justify-content-center gap-10 px-2 py-3 rounded-3 border bg-white cursor-pointer hp-100">
                                                    <input class="form-check-input mt-0" type="radio" name="send_to" value="1" {{ !isset($result->send_to) || (int) $result->send_to === 1 ? 'checked' : '' }}>
                                                    <span class="fw-5">{{ __('All') }}</span>
                                                </label>
                                            </div>
                                            <div class="col-4">
                                                <label class="d-flex align-items-center justify-content-center gap-10 px-2 py-3 rounded-3 border bg-white cursor-pointer hp-100">
                                                    <input class="form-check-input mt-0" type="radio" name="send_to" value="2" {{ isset($result->send_to) && (int) $result->send_to === 2 ? 'checked' : '' }}>
                                                    <span class="fw-5">{{ __('Individual') }}</span>
                                                </label>
                                            </div>
                                            <div class="col-4">
                                                <label class="d-flex align-items-center justify-content-center gap-10 px-2 py-3 rounded-3 border bg-white cursor-pointer hp-100">
                                                    <input class="form-check-input mt-0" type="radio" name="send_to" value="3" {{ isset($result->send_to) && (int) $result->send_to === 3 ? 'checked' : '' }}>
                                                    <span class="fw-5">{{ __('Group') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 mb-3">
                                    <div class="border rounded-3 p-4 hp-100">
                                        <label class="form-label fw-6">{{ __('Bot name') }}</label>
                                        <input type="text" class="form-control form-control-lg mb-4" name="name" value="{{ $result->name ?? '' }}" maxlength="100" placeholder="{{ __('Customer inquiry bot') }}">

                                        <label class="form-label fw-6">{{ __('Keyword match type') }}</label>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="d-flex align-items-center justify-content-center gap-10 px-3 py-3 rounded-3 border bg-white cursor-pointer hp-100">
                                                    <input class="form-check-input mt-0" type="radio" name="type_search" value="1" {{ !isset($result->type_search) || (int) $result->type_search === 1 ? 'checked' : '' }}>
                                                    <span class="fw-5">{{ __('Contains keyword') }}</span>
                                                </label>
                                            </div>
                                            <div class="col-6">
                                                <label class="d-flex align-items-center justify-content-center gap-10 px-3 py-3 rounded-3 border bg-white cursor-pointer hp-100">
                                                    <input class="form-check-input mt-0" type="radio" name="type_search" value="2" {{ isset($result->type_search) && (int) $result->type_search === 2 ? 'checked' : '' }}>
                                                    <span class="fw-5">{{ __('Exact keyword') }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="border rounded-3 p-4">
                                <div class="d-flex align-items-start justify-content-between gap-12 mb-3">
                                    <div>
                                        <div class="fw-6 text-gray-900">{{ __('Keywords') }}</div>
                                        <div class="fs-13 text-gray-500">{{ __('Add trigger words as tags. The bot will match against these phrases.') }}</div>
                                    </div>
                                    <div class="px-2 py-1 rounded-pill bg-light fs-12 fw-6 text-gray-600">{{ __('Rule input') }}</div>
                                </div>
                                <input type="text" class="form-control form-control-lg input-tags" name="keywords" value="{{ $result->keywords ?? '' }}" placeholder="pricing,price,quote">
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-8">
                        <div class="border rounded-3 overflow-hidden bg-white">
                            <div class="d-flex flex-column flex-xxl-row align-items-xxl-start justify-content-between gap-16 px-4 py-4 bg-light border-bottom">
                                <div>
                                    <div class="fs-22 fw-6 text-gray-900 mb-1">{{ __('Reply composer') }}</div>
                                    <div class="fs-14 text-gray-600 max-w-520">{{ __('Write the final answer, attach media, and use AI to turn a rough intent into a polished reply.') }}</div>
                                </div>
                                <div class="d-flex flex-wrap gap-8">
                                    <button type="button" class="btn btn-sm btn-light border ai-quick-prompt" data-prompt="{{ __('Write a short and friendly WhatsApp chatbot reply for customers asking about pricing.') }}">{{ __('Pricing') }}</button>
                                    <button type="button" class="btn btn-sm btn-light border ai-quick-prompt" data-prompt="{{ __('Write a concise WhatsApp chatbot reply that asks the customer for their order number.') }}">{{ __('Order help') }}</button>
                                    <button type="button" class="btn btn-sm btn-light border ai-quick-prompt" data-prompt="{{ __('Write a professional WhatsApp chatbot reply that explains business hours and expected response time.') }}">{{ __('Business hours') }}</button>
                                </div>
                            </div>

                            <div class="p-4 p-xl-5">
                                <div class="d-flex align-items-center gap-12 px-3 px-lg-4 py-3 rounded-3 bg-primary-100 text-gray-700 mb-4">
                                    <div class="w-36 h-36 rounded-circle bg-white text-primary d-flex align-items-center justify-content-center flex-shrink-0">
                                        <i class="fa-light fa-wand-magic-sparkles"></i>
                                    </div>
                                    <div class="fs-13 lh-base mb-0 flex-fill">
                                        {{ __('Tip: start with the customer intent, then use AI and media to shape a clearer, more useful chatbot answer.') }}
                                    </div>
                                </div>

                                <label for="wa_chatbot_caption" class="form-label fw-6">{{ __('Caption') }}</label>
                                <div class="mb-3 wrap-input-emoji">
                                    <textarea id="wa_chatbot_caption" class="form-control {{ $emojiClass }} post-caption fw-4 border min-h-180" name="caption" placeholder="{{ __('Example: Thanks for messaging us. Tell us what you need and we will help right away.') }}">{{ $result->caption ?? '' }}</textarea>
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
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white border-0 px-4 px-xl-5 pb-4 pt-0">
                <div class="border-top pt-4 d-flex justify-content-end w-100">
                    <button type="submit" class="btn btn-primary btn-lg px-5 flex-shrink-0">
                        {{ __('Save Chatbot Item') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@else
    <div class="alert alert-danger">
        {{ $message ?? __('An unexpected error occurred.') }}
    </div>
@endif

<script type="text/javascript">
    Main.Emoji("{{ $emojiClass }}");

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
            if (aiButton.length) aiButton.trigger('click');
        }, 250);
    });
</script>
