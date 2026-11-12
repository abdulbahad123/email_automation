@if($status === 'success')
    <form id="WhatsAppAISmartReplyForm" action="{{ route('app.whatsappaismartreply.save') }}" method="POST" class="actionForm">
        @csrf
        <input type="hidden" name="instance_id" value="{{ $account->token ?? '' }}">

        <div class="card shadow-none border-gray-300 overflow-hidden">
            <div class="card-header border-0 p-4 pb-0 bg-white">
                <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-16">
                    <div>
                        <div class="fs-22 fw-6 text-gray-900 mb-2">
                            {{ __('AI smart reply for :name', ['name' => $account->name ?? __('Selected account')]) }}
                        </div>
                        <div class="fs-14 text-gray-600 max-w-620">
                            {{ __('Describe how the AI should answer new messages for this profile. The worker will call Laravel AI services and send back one final WhatsApp-ready reply.') }}
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
                                    <input class="form-check-input mt-0" type="radio" name="status" value="1" {{ !isset($result->status) || (int) $result->status === 1 ? 'checked' : '' }}>
                                    <span class="fw-5">{{ __('Enable') }}</span>
                                </label>
                                <label class="d-flex align-items-center gap-10 px-3 py-2 rounded-3 border bg-white cursor-pointer">
                                    <input class="form-check-input mt-0" type="radio" name="status" value="0" {{ isset($result->status) && (int) $result->status === 0 ? 'checked' : '' }}>
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
                                    <input class="form-check-input mt-0" type="radio" name="send_to" value="1" {{ !isset($result->send_to) || (int) $result->send_to === 1 ? 'checked' : '' }}>
                                    <span class="fw-5">{{ __('All') }}</span>
                                </label>
                                <label class="d-flex align-items-center gap-10 px-3 py-2 rounded-3 border bg-white cursor-pointer">
                                    <input class="form-check-input mt-0" type="radio" name="send_to" value="2" {{ isset($result->send_to) && (int) $result->send_to === 2 ? 'checked' : '' }}>
                                    <span class="fw-5">{{ __('Individual') }}</span>
                                </label>
                                <label class="d-flex align-items-center gap-10 px-3 py-2 rounded-3 border bg-white cursor-pointer">
                                    <input class="form-check-input mt-0" type="radio" name="send_to" value="3" {{ isset($result->send_to) && (int) $result->send_to === 3 ? 'checked' : '' }}>
                                    <span class="fw-5">{{ __('Group') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border rounded-3 overflow-hidden mb-4">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-12 px-4 py-3 bg-light border-bottom">
                        <div>
                            <div class="fw-6 text-gray-900">{{ __('AI instruction') }}</div>
                            <div class="fs-13 text-gray-600">{{ __('Tell the AI how it should answer. Example: be warm, ask one clarifying question, and keep replies under 120 characters.') }}</div>
                        </div>
                        <div class="d-flex flex-wrap gap-8">
                            <button type="button" class="btn btn-sm btn-light border wa-ai-smart-prompt" data-prompt="{{ __('Reply in a friendly tone, answer briefly, and invite the customer to share more details if needed.') }}">{{ __('Friendly') }}</button>
                            <button type="button" class="btn btn-sm btn-light border wa-ai-smart-prompt" data-prompt="{{ __('Reply professionally, mention business hours if relevant, and never overpromise.') }}">{{ __('Business hours') }}</button>
                            <button type="button" class="btn btn-sm btn-light border wa-ai-smart-prompt" data-prompt="{{ __('Reply like a sales assistant: warm, concise, and ask one question to move the conversation forward.') }}">{{ __('Sales') }}</button>
                        </div>
                    </div>

                    <div class="p-4">
                        <label for="wa_ai_prompt" class="form-label fw-6">{{ __('Instruction prompt') }}</label>
                        <textarea id="wa_ai_prompt" class="form-control fw-4 border min-h-180" name="prompt" placeholder="{{ __('Example: You are our WhatsApp assistant. Reply in the same language as the customer, keep it concise, and offer pricing only if asked directly.') }}">{{ $result->prompt ?? '' }}</textarea>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-4 hp-100">
                            <label for="wa_ai_fallback" class="form-label fw-6">{{ __('Fallback reply') }}</label>
                            <textarea id="wa_ai_fallback" class="form-control border min-h-140" name="fallback_caption" placeholder="{{ __('Optional: use this reply if AI fails or returns nothing.') }}">{{ $result->fallback_caption ?? '' }}</textarea>
                            <div class="fs-13 text-gray-500 mt-2">{{ __('Used only when the AI service cannot return a valid reply.') }}</div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="border rounded-3 p-4 hp-100 d-flex flex-column gap-16">
                            <div>
                                <label for="wa_ai_delay" class="form-label fw-6">{{ __('Resubmit message only after (minute)') }}</label>
                                <input id="wa_ai_delay" type="number" min="1" class="form-control form-control-lg" name="delay" value="{{ $result->delay ?? 1 }}">
                            </div>
                            <div>
                                <label for="wa_ai_max_length" class="form-label fw-6">{{ __('Target max length (characters)') }}</label>
                                <input id="wa_ai_max_length" type="number" min="30" max="1000" class="form-control form-control-lg" name="max_length" value="{{ $result->max_length ?? 120 }}">
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="border rounded-3 p-4 hp-100">
                            <label for="wa_ai_except" class="form-label fw-6">{{ __('Except contacts') }}</label>
                            <input id="wa_ai_except" type="text" class="form-control form-control-lg input-tags" name="except" value="{{ $result->except ?? '' }}" placeholder="841234567890,840123456789">
                            <div class="fs-13 text-gray-500 mt-2">{{ __('Add each phone number as a tag. AI smart reply will skip those contacts.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-white border-0 px-4 px-lg-5 pb-4 pt-0">
                <div class="border-top pt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg px-5 flex-shrink-0">{{ __('Save AI Smart Reply') }}</button>
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
    $(document).off('click.waAISmartPrompt').on('click.waAISmartPrompt', '.wa-ai-smart-prompt', function () {
        var prompt = $(this).data('prompt') || '';
        $('#wa_ai_prompt').val(prompt).trigger('input');
    });
</script>