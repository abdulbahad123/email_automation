@if(Access::permission('appwhatsappreport'))
@php
    $teamId = (int) request('team_id');
    $moduleEnabled = function (string $module): bool {
        try {
            $modulePath = base_path("modules/{$module}");
            if (!is_dir($modulePath) || !is_file($modulePath . DIRECTORY_SEPARATOR . 'module.json')) {
                return false;
            }

            $moduleInstance = \Nwidart\Modules\Facades\Module::find($module);

            return $moduleInstance ? $moduleInstance->isEnabled() : false;
        } catch (\Throwable $e) {
            return false;
        }
    };

    $hasProfileInfoModule = $moduleEnabled('AppWhatsAppProfileInfo');
    $hasReportModule = $moduleEnabled('AppWhatsAppReport');
    $hasChatModule = $moduleEnabled('AppWhatsAppChat');
    $hasBulkModule = $moduleEnabled('AppWhatsAppBulk');
    $hasAiSmartReplyModule = $moduleEnabled('AppWhatsAppAISmartReply');
    $hasAutoReplyModule = $moduleEnabled('AppWhatsAppAutoReply');
    $hasChatbotModule = $moduleEnabled('AppWhatsAppChatbot');
    $hasContactsModule = $moduleEnabled('AppWhatsAppContact');
    $hasApiModule = $moduleEnabled('AppWhatsAppApi');

    $hasStatsTable = \Schema::hasTable('whatsapp_stats');
    $hasAccountsTable = \Schema::hasTable('accounts');
    $hasSchedulesTable = \Schema::hasTable('whatsapp_schedules');
    $hasChatbotTable = \Schema::hasTable('whatsapp_chatbot');
    $hasAutoReplyTable = \Schema::hasTable('whatsapp_autoresponder');
    $hasAiReplyTable = \Schema::hasTable('whatsapp_ai_smart_reply');
    $hasChatTable = \Schema::hasTable('whatsapp_chat_conversations');

    $stats = ($teamId && $hasStatsTable) ? DB::table('whatsapp_stats')->where('team_id', $teamId)->first() : null;

    $accountsQuery = ($teamId && $hasAccountsTable)
        ? DB::table('accounts')
            ->where('team_id', $teamId)
            ->where('social_network', 'whatsapp_unofficial')
            ->where('category', 'profile')
            ->where('login_type', 2)
            ->where('status', 1)
        : null;

    $accounts = $accountsQuery ? $accountsQuery->count() : 0;
    $accountRows = $accountsQuery ? $accountsQuery->orderBy('name')->limit(5)->get() : collect();
    $teamRow = ($teamId && \Schema::hasTable('teams')) ? DB::table('teams')->where('id', $teamId)->first() : null;
    $teamToken = $teamRow->id_secure ?? '';
    $allConnectedAccounts = ($teamId && $hasAccountsTable)
        ? DB::table('accounts')
            ->where('team_id', $teamId)
            ->where('social_network', 'whatsapp_unofficial')
            ->where('category', 'profile')
            ->where('login_type', 2)
            ->where('status', 1)
            ->get()
        : collect();
    $firstToken = $allConnectedAccounts->first()->token ?? '';

    $activeBulk = ($teamId && $hasSchedulesTable)
        ? DB::table('whatsapp_schedules')->where('team_id', $teamId)->where('status', 1)->count()
        : 0;
    $activeChatbot = ($teamId && $hasChatbotTable)
        ? DB::table('whatsapp_chatbot')->where('team_id', $teamId)->where('status', 1)->where('run', 1)->count()
        : 0;
    $activeAutoReply = ($teamId && $hasAutoReplyTable)
        ? DB::table('whatsapp_autoresponder')->where('team_id', $teamId)->where('status', 1)->count()
        : 0;
    $activeAi = ($teamId && $hasAiReplyTable)
        ? DB::table('whatsapp_ai_smart_reply')->where('team_id', $teamId)->where('status', 1)->count()
        : 0;

    $user = auth()->user();
    $isActivePlan = $user && (!empty($user->plan_id) || (!empty($user->expiration_date) && ($user->expiration_date == -1 || $user->expiration_date > time())));

    $rawWaLimit = Access::permission('whatsapp_message_per_month');
    $rawCredit = Access::permission('credits');

    if ($rawWaLimit === -1 || $rawWaLimit === '-1' || $rawCredit === -1 || $rawCredit === '-1') {
        $monthlyLimit = -1;
    } elseif ((int) $rawWaLimit > 0) {
        $monthlyLimit = (int) $rawWaLimit;
    } elseif ((int) $rawCredit > 0) {
        $monthlyLimit = (int) $rawCredit;
    } else {
        $monthlyLimit = $isActivePlan ? -1 : 0;
    }
    $monthlySent = (int) ($stats->wa_total_sent_by_month ?? 0);
    $monthlyRemaining = $monthlyLimit === -1 ? -1 : max(0, $monthlyLimit - $monthlySent);
    $apiCount = (int) ($stats->wa_api_count ?? 0);
    $bulkSent = (int) ($stats->wa_bulk_sent_count ?? 0);
    $bulkFailed = (int) ($stats->wa_bulk_failed_count ?? 0);
    $chatbotCount = (int) ($stats->wa_chatbot_count ?? 0);
    $autoReplyCount = ($teamId && $hasAutoReplyTable)
        ? (int) DB::table('whatsapp_autoresponder')->where('team_id', $teamId)->sum('sent')
        : 0;
    $totalSent = (int) ($stats->wa_total_sent ?? 0);
    $chatCount = ($teamId && $hasChatTable) ? DB::table('whatsapp_chat_conversations')->where('team_id', $teamId)->count() : 0;
    $activeAutomations = $activeBulk + $activeChatbot + $activeAutoReply + $activeAi;
    $quotaPercent = $monthlyLimit > 0 ? min(100, round(($monthlySent / max(1, $monthlyLimit)) * 100, 1)) : 0;

    $isFeatEnabled = fn(string $k) => (bool) Access::permission($k) || $isActivePlan;

    $featureChips = collect([
        ['label' => __('Profile Info'), 'enabled' => $isFeatEnabled('appwhatsappprofileinfo'), 'installed' => $hasProfileInfoModule],
        ['label' => __('Reports'), 'enabled' => $isFeatEnabled('appwhatsappreport'), 'installed' => $hasReportModule],
        ['label' => __('Live Chat'), 'enabled' => $isFeatEnabled('appwhatsappchat'), 'installed' => $hasChatModule],
        ['label' => __('Bulk'), 'enabled' => $isFeatEnabled('appwhatsappbulk'), 'installed' => $hasBulkModule],
        ['label' => __('AI Reply'), 'enabled' => $isFeatEnabled('appwhatsappaismartreply'), 'installed' => $hasAiSmartReplyModule],
        ['label' => __('Auto Reply'), 'enabled' => $isFeatEnabled('appwhatsappautoreply'), 'installed' => $hasAutoReplyModule],
        ['label' => __('Chatbot'), 'enabled' => $isFeatEnabled('appwhatsappchatbot'), 'installed' => $hasChatbotModule],
        ['label' => __('Contacts'), 'enabled' => $isFeatEnabled('appwhatsappcontact'), 'installed' => $hasContactsModule],
        ['label' => __('REST API'), 'enabled' => $isFeatEnabled('appwhatsappapi'), 'installed' => $hasApiModule],
    ])->where('installed', true)->values();
    $enabledFeatureCount = $featureChips->filter(fn ($item) => $item['enabled'])->count();

    $rawChatbot = Access::permission('whatsapp_chatbot_item_limit');
    if ($rawChatbot === -1 || $rawChatbot === '-1') {
        $chatbotLimit = -1;
    } elseif ((int) $rawChatbot > 0) {
        $chatbotLimit = (int) $rawChatbot;
    } else {
        $chatbotLimit = $isActivePlan ? -1 : 0;
    }

    $rawContactGroup = Access::permission('whatsapp_bulk_max_contact_group');
    if ($rawContactGroup === -1 || $rawContactGroup === '-1') {
        $contactGroupLimit = -1;
    } elseif ((int) $rawContactGroup > 0) {
        $contactGroupLimit = (int) $rawContactGroup;
    } else {
        $contactGroupLimit = $isActivePlan ? -1 : 0;
    }

    $rawPhones = Access::permission('whatsapp_bulk_max_phone_numbers');
    if ($rawPhones === -1 || $rawPhones === '-1') {
        $phonesGroupLimit = -1;
    } elseif ((int) $rawPhones > 0) {
        $phonesGroupLimit = (int) $rawPhones;
    } else {
        $phonesGroupLimit = $isActivePlan ? -1 : 0;
    }

    $limits = [
        ['label' => __('Monthly messages'), 'value' => $monthlyLimit],
        ['label' => __('Chatbot items'), 'value' => $chatbotLimit],
        ['label' => __('Contact groups'), 'value' => $contactGroupLimit],
        ['label' => __('Phones / group'), 'value' => $phonesGroupLimit],
    ];

    $activityRows = collect([
        ['label' => __('Bulk Campaign'), 'sent' => $bulkSent, 'meta' => __('Failed: :count', ['count' => number_format($bulkFailed)]), 'icon' => 'fa-paper-plane', 'class' => 'info', 'installed' => $hasBulkModule],
        ['label' => __('REST API'), 'sent' => $apiCount, 'meta' => __('Usage count'), 'icon' => 'fa-code', 'class' => 'primary', 'installed' => $hasApiModule],
        ['label' => __('Chatbot'), 'sent' => $chatbotCount, 'meta' => __('Sent replies'), 'icon' => 'fa-robot', 'class' => 'warning', 'installed' => $hasChatbotModule],
        ['label' => __('Auto Reply'), 'sent' => $autoReplyCount, 'meta' => __('Sent replies'), 'icon' => 'fa-reply', 'class' => 'success', 'installed' => $hasAutoReplyModule],
        ['label' => __('AI Smart Reply'), 'sent' => $activeAi, 'meta' => __('Active rules'), 'icon' => 'fa-sparkles', 'class' => 'danger', 'installed' => $hasAiSmartReplyModule],
    ])->where('installed', true)->values();

    $summaryCards = collect([
        ['label' => __('Connected profiles'), 'value' => number_format($accounts), 'meta' => __('Active WhatsApp Unofficial accounts'), 'icon' => 'fa-mobile-screen-button', 'class' => 'success'],
        ['label' => __('Messages left this month'), 'value' => $monthlyRemaining === -1 ? __('Unlimited') : number_format($monthlyRemaining), 'meta' => __('Used: :count', ['count' => number_format($monthlySent)]), 'icon' => 'fa-messages-dollar', 'class' => 'primary'],
        ['label' => __('Active automations'), 'value' => number_format($activeAutomations), 'meta' => __('AI: :ai | Chatbot: :chatbot | Auto: :auto | Bulk: :bulk', ['ai' => number_format($activeAi), 'chatbot' => number_format($activeChatbot), 'auto' => number_format($activeAutoReply), 'bulk' => number_format($activeBulk)]), 'icon' => 'fa-bolt', 'class' => 'warning'],
        ['label' => __('Delivery totals'), 'value' => number_format($bulkSent), 'meta' => __('Failed: :count | API: :api', ['count' => number_format($bulkFailed), 'api' => number_format($apiCount)]), 'icon' => 'fa-paper-plane', 'class' => 'info'],
        ['label' => __('Total messages sent'), 'value' => number_format($totalSent), 'meta' => __('Lifetime total'), 'icon' => 'fa-chart-line-up', 'class' => 'danger'],
        ['label' => __('Live conversations'), 'value' => number_format($chatCount), 'meta' => __('Tracked in Live Chat'), 'icon' => 'fa-comments', 'class' => 'success', 'installed' => $hasChatModule],
    ])->filter(fn ($item) => ($item['installed'] ?? true) === true)->values();
@endphp

<div class="card border-gray-300 shadow-none overflow-hidden mb-4">
    <div class="card-header border-0 p-4 pb-0 d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-12">
        <div class="d-flex flex-column gap-4">
            <div class="fw-6 fs-18 text-gray-900">{{ __('WhatsApp Report') }}</div>
            <div class="fs-13 text-gray-600">{{ __('Monitor unofficial WhatsApp profiles, message quota, automation activity, current limits, and top account usage from one dashboard card.') }}</div>
        </div>
        <a href="{{ route('app.whatsappreport.index', ['team_id' => $teamId ?: null]) }}" class="btn btn-outline btn-dark btn-sm">
            <i class="fa-light fa-chart-column me-1"></i>{{ __('Open report') }}
        </a>
    </div>

    <div class="card-body p-4">
        <div class="row g-3 mb-4">
            @foreach($summaryCards as $card)
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="border rounded-3 p-3 h-100 d-flex align-items-center justify-content-between gap-12">
                        <div class="min-w-0">
                            <div class="fs-12 text-gray-500">{{ $card['label'] }}</div>
                            <div class="fw-7 fs-22 text-gray-900">{{ $card['value'] }}</div>
                            <div class="fs-11 text-gray-500">{{ $card['meta'] }}</div>
                        </div>
                        <div class="size-42 rounded-3 bg-{{ $card['class'] }}-100 text-{{ $card['class'] }} d-flex align-items-center justify-content-center fs-20 flex-shrink-0">
                            <i class="fa-light {{ $card['icon'] }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-12 col-xxl-5">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between gap-12 mb-3">
                        <div class="fw-6 text-gray-900">{{ __('Feature activity') }}</div>
                        <span class="badge badge-light-primary text-primary">{{ __('This month') }}</span>
                    </div>
                    <div class="row g-2">
                        @foreach($activityRows as $item)
                            <div class="col-12 col-md-6">
                                <div class="border rounded-3 p-3 h-100 d-flex align-items-center justify-content-between gap-12">
                                    <div class="min-w-0">
                                        <div class="fw-5 text-gray-900">{{ $item['label'] }}</div>
                                        <div class="fs-11 text-gray-500">{{ $item['meta'] }}</div>
                                    </div>
                                    <div class="text-end flex-shrink-0">
                                        <div class="fw-7 fs-18 text-gray-900">{{ number_format($item['sent']) }}</div>
                                        <div class="size-34 rounded-3 bg-{{ $item['class'] }}-100 text-{{ $item['class'] }} d-inline-flex align-items-center justify-content-center fs-16 mt-2">
                                            <i class="fa-light {{ $item['icon'] }}"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="fw-6 text-gray-900 mb-3">{{ __('Enabled features') }}</div>
                    <div class="d-flex flex-wrap gap-8 mb-4">
                        @foreach($featureChips as $item)
                            <span class="badge {{ $item['enabled'] ? 'badge-light-success text-success' : 'badge-light-danger text-danger' }}">{{ $item['label'] }}</span>
                        @endforeach
                    </div>

                    <div class="d-flex align-items-center justify-content-between gap-12 mb-3">
                        <div class="fw-6 text-gray-900">{{ __('Current limits') }}</div>
                        <span class="badge badge-light-primary text-primary">{{ number_format($enabledFeatureCount) }} {{ __('features enabled') }}</span>
                    </div>

                    <div class="row g-2 mb-3">
                        @foreach($limits as $item)
                            <div class="col-6">
                                <div class="bg-light rounded-3 px-3 py-2 h-100">
                                    <div class="fs-11 text-gray-500 mb-1">{{ $item['label'] }}</div>
                                    <div class="fw-6 text-gray-900">{{ (int) $item['value'] === -1 ? __('Unlimited') : number_format((int) $item['value']) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border rounded-3 p-3">
                        <div class="d-flex align-items-center justify-content-between gap-12 mb-2">
                            <div class="fw-5 text-gray-900">{{ __('Monthly quota usage') }}</div>
                            <div class="fw-6 text-gray-900">{{ $monthlyLimit === -1 ? __('Unlimited') : ($quotaPercent . '%') }}</div>
                        </div>
                        <div class="progress h-8 bg-light-primary mb-2">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $monthlyLimit === -1 ? 0 : $quotaPercent }}%"></div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between fs-11 text-gray-500">
                            <span>{{ __('Used: :count', ['count' => number_format($monthlySent)]) }}</span>
                            <span>{{ $monthlyRemaining === -1 ? __('Unlimited left') : __(':count left', ['count' => number_format($monthlyRemaining)]) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-3">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between gap-12 mb-3">
                        <div class="fw-6 text-gray-900">{{ __('Top accounts') }}</div>
                        <span class="fs-11 text-gray-500">{{ __('Connected profiles') }}</span>
                    </div>
                    <div class="d-flex flex-column gap-10">
                        @forelse($accountRows as $account)
                            <div class="d-flex align-items-center gap-10 border rounded-3 p-2">
                                <div class="size-42 rounded-circle overflow-hidden border flex-shrink-0 bg-light d-flex align-items-center justify-content-center">
                                    @if(!empty($account->avatar))
                                        <img src="{{ Media::url($account->avatar) }}" class="wp-100 hp-100 object-fit-cover" alt="{{ $account->name }}">
                                    @else
                                        <span class="fw-6 text-primary">{{ mb_substr($account->name ?: 'WA', 0, 2) }}</span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-fill">
                                    <div class="fw-6 text-gray-900 text-truncate">{{ $account->name }}</div>
                                    <div class="fs-11 text-gray-500 text-truncate">{{ $account->username ?: __('WhatsApp profile') }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="text-gray-500 fs-12">{{ __('No WhatsApp profiles found.') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========================================================
     1. LIVE WHATSAPP TEST CONSOLE
     ======================================================== --}}
<div class="card border-0 shadow-sm rounded-4 mb-4 mt-4">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-12">
            <span class="size-40 rounded-circle bg-light-success text-success d-flex align-items-center justify-content-center fs-18">
                <i class="fab fa-whatsapp"></i>
            </span>
            <div>
                <h5 class="fw-bold mb-0 text-gray-900">{{ __('Live WhatsApp Test Console') }}</h5>
                <div class="fs-12 text-gray-500">{{ __('Send test messages directly from this dashboard in any format (Text, Image, Document, Video, Audio).') }}</div>
            </div>
        </div>
        <span class="badge badge-light-success text-success fw-bold px-3 py-2">
            <i class="fas fa-circle-check me-1"></i> {{ count($allConnectedAccounts) }} {{ __('Connected Profile(s)') }}
        </span>
    </div>
    <div class="card-body p-4">
        @if($allConnectedAccounts->isEmpty())
            <div class="alert alert-warning d-flex align-items-center gap-12 mb-0">
                <i class="fas fa-triangle-exclamation fs-20"></i>
                <div>
                    <strong>{{ __('No WhatsApp Profile Connected') }}</strong>
                    <div>{{ __('Please scan the QR code and link a WhatsApp profile first in') }} <a href="{{ url('app/whatsapp_unofficial/profile/oauth') }}" class="fw-bold text-decoration-underline">{{ __('Channels') }}</a>.</div>
                </div>
            </div>
        @else
            <form id="wa-live-test-form" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    {{-- Sender Profile --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold fs-13 text-gray-700">{{ __('Send From (Connected Profile)') }}</label>
                        <select name="instance_id" id="wa-test-instance-id" class="form-select border-gray-300">
                            @foreach($allConnectedAccounts as $acc)
                                <option value="{{ $acc->token }}">
                                    📱 {{ $acc->name }} (Instance: {{ $acc->token }})
                                </option>
                            @endforeach
                        </select>
                        <div class="fs-11 text-gray-500 mt-1">{{ __('The connected WhatsApp account that will dispatch the message.') }}</div>
                    </div>

                    {{-- Recipient Number --}}
                    <div class="col-md-6">
                        <label class="form-label fw-bold fs-13 text-gray-700">{{ __('Recipient Mobile Number') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-gray-600"><i class="fas fa-phone"></i></span>
                            <input type="text" name="number" id="wa-test-number" class="form-control border-gray-300" placeholder="e.g. 918667702473 or 8667702473" required>
                        </div>
                        <div class="fs-11 text-gray-500 mt-1">{{ __('Include country code (e.g. 91 for India). 10-digit numbers automatically formatted.') }}</div>
                    </div>

                    {{-- Format Selection Tabs --}}
                    <div class="col-12">
                        <label class="form-label fw-bold fs-13 text-gray-700 mb-2">{{ __('Select Message Format') }}</label>
                        <div class="d-flex flex-wrap gap-8" id="wa-format-buttons">
                            <button type="button" class="btn btn-sm btn-outline-primary active wa-format-btn" data-type="text">
                                <i class="fas fa-comment-dots me-1"></i> {{ __('Text Message') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary wa-format-btn" data-type="image">
                                <i class="fas fa-image me-1"></i> {{ __('Image (JPG/PNG)') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary wa-format-btn" data-type="document">
                                <i class="fas fa-file-pdf me-1"></i> {{ __('Document (PDF/DOC)') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary wa-format-btn" data-type="video">
                                <i class="fas fa-video me-1"></i> {{ __('Video (MP4)') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary wa-format-btn" data-type="audio">
                                <i class="fas fa-music me-1"></i> {{ __('Audio (MP3)') }}
                            </button>
                        </div>
                    </div>

                    {{-- Message / Caption --}}
                    <div class="col-12">
                        <label class="form-label fw-bold fs-13 text-gray-700">
                            <span id="wa-label-message">{{ __('Message Content') }}</span>
                        </label>
                        <textarea name="message" id="wa-test-message" rows="3" class="form-control border-gray-300" placeholder="Type your WhatsApp message text here..."></textarea>
                    </div>

                    {{-- Media URL & File Upload Section --}}
                    <div class="col-12 d-none" id="wa-media-section">
                        <div class="card bg-light border p-3 rounded-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold fs-12 text-gray-700">{{ __('Media Direct URL (Recommended)') }}</label>
                                    <input type="url" name="media_url" id="wa-test-media-url" class="form-control bg-white" placeholder="https://... or http://localhost:8080/ai_sample.jpg">
                                    <div class="fs-11 text-gray-500 mt-1">{{ __('Direct web link to image, PDF document, video, or audio.') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold fs-12 text-gray-700">{{ __('OR Upload Local File') }}</label>
                                    <input type="file" name="media_file" id="wa-test-media-file" class="form-control bg-white">
                                    <div class="fs-11 text-gray-500 mt-1">{{ __('Upload any file from your computer.') }}</div>
                                </div>
                                <div class="col-md-6" id="wa-filename-group">
                                    <label class="form-label fw-bold fs-12 text-gray-700">{{ __('Document Filename (Optional)') }}</label>
                                    <input type="text" name="filename" id="wa-test-filename" class="form-control bg-white" placeholder="e.g. Invoice_OCT2026.pdf">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Fill Sample Presets --}}
                    <div class="col-12">
                        <div class="d-flex flex-wrap align-items-center gap-8">
                            <span class="fs-12 text-gray-500 me-1">{{ __('Quick Presets:') }}</span>
                            <button type="button" class="btn btn-xs btn-light border text-gray-700" onclick="fillWaSample('otp')">
                                <i class="fas fa-key text-warning me-1"></i> {{ __('OTP Verification Code') }}
                            </button>
                            <button type="button" class="btn btn-xs btn-light border text-gray-700" onclick="fillWaSample('text')">
                                <i class="fas fa-comment text-primary me-1"></i> {{ __('Simple Greeting Text') }}
                            </button>
                            <button type="button" class="btn btn-xs btn-light border text-gray-700" onclick="fillWaSample('ai_image')">
                                <i class="fas fa-robot text-success me-1"></i> {{ __('AI Mascot Image') }}
                            </button>
                            <button type="button" class="btn btn-xs btn-light border text-gray-700" onclick="fillWaSample('pdf')">
                                <i class="fas fa-file-pdf text-danger me-1"></i> {{ __('Sample PDF Document') }}
                            </button>
                        </div>
                    </div>

                    {{-- Submit Button & Status Indicator --}}
                    <div class="col-12 d-flex align-items-center gap-12 mt-3">
                        <button type="submit" id="wa-test-submit-btn" class="btn btn-success fw-bold px-4 py-2">
                            <i class="fas fa-paper-plane me-2"></i> {{ __('Send Test WhatsApp Message') }}
                        </button>
                        <span id="wa-test-spinner" class="d-none text-gray-600 fs-13">
                            <span class="spinner-border spinner-border-sm text-success me-2" role="status"></span>
                            {{ __('Transmitting via WhatsApp Gateway...') }}
                        </span>
                    </div>

                    {{-- Result Alert Container --}}
                    <div class="col-12 d-none" id="wa-test-result-container">
                        <div id="wa-test-result-alert" class="alert mb-0 p-3 rounded-3"></div>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>

{{-- ========================================================
     2. API CREDENTIALS & DEVELOPER INTEGRATION HUB
     ======================================================== --}}
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-12">
            <span class="size-40 rounded-circle bg-light-primary text-primary d-flex align-items-center justify-content-center fs-18">
                <i class="fas fa-code"></i>
            </span>
            <div>
                <h5 class="fw-bold mb-0 text-gray-900">{{ __('API Credentials & Developer Integration') }}</h5>
                <div class="fs-12 text-gray-500">{{ __('Use these credentials to trigger WhatsApp messages and OTPs from external apps or scripts.') }}</div>
            </div>
        </div>
        <span class="badge badge-light-primary text-primary fw-bold px-3 py-2">
            <i class="fas fa-terminal me-1"></i> {{ __('REST API Ready') }}
        </span>
    </div>
    <div class="card-body p-4">
        <div class="row g-4 mb-4">
            {{-- API Key (Team Token) --}}
            <div class="col-md-4">
                <div class="p-3 border rounded-3 bg-light h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold fs-13 text-gray-800"><i class="fas fa-key text-warning me-1"></i> {{ __('API Key (Team Token)') }}</span>
                        <button type="button" class="btn btn-xs btn-outline-dark" onclick="copyText('{{ $teamToken }}', this)">
                            <i class="fas fa-copy me-1"></i> {{ __('Copy') }}
                        </button>
                    </div>
                    <input type="text" class="form-control form-control-sm font-monospace bg-white text-dark fw-bold border-gray-300" value="{{ $teamToken }}" readonly>
                    <div class="fs-11 text-gray-500 mt-2">{{ __('Your unique secret team authentication token (access_token).') }}</div>
                </div>
            </div>

            {{-- Instance ID --}}
            <div class="col-md-4">
                <div class="p-3 border rounded-3 bg-light h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold fs-13 text-gray-800"><i class="fas fa-mobile-screen-button text-success me-1"></i> {{ __('Active Instance ID') }}</span>
                        <button type="button" class="btn btn-xs btn-outline-dark" onclick="copyText('{{ $firstToken }}', this)">
                            <i class="fas fa-copy me-1"></i> {{ __('Copy') }}
                        </button>
                    </div>
                    <input type="text" class="form-control form-control-sm font-monospace bg-white text-dark fw-bold border-gray-300" value="{{ $firstToken }}" readonly>
                    <div class="fs-11 text-gray-500 mt-2">{{ __('The identifier for your linked WhatsApp profile.') }}</div>
                </div>
            </div>

            {{-- Engine Endpoint --}}
            <div class="col-md-4">
                <div class="p-3 border rounded-3 bg-light h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold fs-13 text-gray-800"><i class="fas fa-network-wired text-primary me-1"></i> {{ __('Send Message Endpoint') }}</span>
                        <button type="button" class="btn btn-xs btn-outline-dark" onclick="copyText('http://localhost:8000/send_message', this)">
                            <i class="fas fa-copy me-1"></i> {{ __('Copy') }}
                        </button>
                    </div>
                    <input type="text" class="form-control form-control-sm font-monospace bg-white text-dark fw-bold border-gray-300" value="http://localhost:8000/send_message" readonly>
                    <div class="fs-11 text-gray-500 mt-2">{{ __('HTTP POST endpoint provided by the WAZIPER Node engine.') }}</div>
                </div>
            </div>
        </div>

        {{-- Integration Code Tabs --}}
        <div class="border rounded-3 p-3">
            <ul class="nav nav-tabs nav-line-tabs mb-3 fs-13 fw-bold" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#wa-tab-curl">cURL (Terminal / Bash)</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#wa-tab-php">PHP / Laravel</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#wa-tab-js">Node.js / JavaScript</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#wa-tab-payload">API Parameter Schema</a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- cURL --}}
                <div class="tab-pane fade show active" id="wa-tab-curl">
                    <div class="position-relative">
                        <button type="button" class="btn btn-xs btn-secondary position-absolute top-0 end-0 m-2" onclick="copySnippet('wa-code-curl', this)">
                            <i class="fas fa-copy me-1"></i> {{ __('Copy Code') }}
                        </button>
                        <pre class="bg-dark text-white p-3 rounded-3 font-monospace fs-12 mb-0 overflow-auto" id="wa-code-curl">curl -X POST "http://localhost:8000/send_message?access_token={{ $teamToken }}&instance_id={{ $firstToken }}" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "number=918667702473" \
  -d "message=Your verification OTP code is 948210. Valid for 5 minutes."</pre>
                    </div>
                </div>

                {{-- PHP / Laravel --}}
                <div class="tab-pane fade" id="wa-tab-php">
                    <div class="position-relative">
                        <button type="button" class="btn btn-xs btn-secondary position-absolute top-0 end-0 m-2" onclick="copySnippet('wa-code-php', this)">
                            <i class="fas fa-copy me-1"></i> {{ __('Copy Code') }}
                        </button>
                        <pre class="bg-dark text-white p-3 rounded-3 font-monospace fs-12 mb-0 overflow-auto" id="wa-code-php">&lt;?php

use Illuminate\Support\Facades\Http;

// In your external PHP / Laravel controller:
$response = Http::asForm()->post("http://localhost:8000/send_message", [
    'access_token' => '{{ $teamToken }}',
    'instance_id'  => '{{ $firstToken }}',
    'number'       => '918667702473',
    'message'      => 'Your verification OTP code is 948210.',
    // Optional for media attachments:
    // 'media_url' => 'http://localhost:8080/ai_sample.jpg',
    // 'filename'  => 'invoice.pdf'
]);

$result = $response->json();
if (($result['status'] ?? '') === 'success') {
    // OTP / message delivered successfully!
}</pre>
                    </div>
                </div>

                {{-- Node.js --}}
                <div class="tab-pane fade" id="wa-tab-js">
                    <div class="position-relative">
                        <button type="button" class="btn btn-xs btn-secondary position-absolute top-0 end-0 m-2" onclick="copySnippet('wa-code-js', this)">
                            <i class="fas fa-copy me-1"></i> {{ __('Copy Code') }}
                        </button>
                        <pre class="bg-dark text-white p-3 rounded-3 font-monospace fs-12 mb-0 overflow-auto" id="wa-code-js">const response = await fetch("http://localhost:8000/send_message?access_token={{ $teamToken }}&instance_id={{ $firstToken }}", {
  method: "POST",
  headers: { "Content-Type": "application/x-www-form-urlencoded" },
  body: new URLSearchParams({
    number: "918667702473",
    message: "Your verification OTP code is 948210."
  })
});

const data = await response.json();
console.log(data); // { status: "success", message: "Message sent successfully" }</pre>
                    </div>
                </div>

                {{-- Payload Schema --}}
                <div class="tab-pane fade" id="wa-tab-payload">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm fs-12 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>access_token</code></td>
                                    <td>string</td>
                                    <td><span class="badge bg-danger">Yes</span></td>
                                    <td>Team API Token ({{ $teamToken }}) passed in query or body.</td>
                                </tr>
                                <tr>
                                    <td><code>instance_id</code></td>
                                    <td>string</td>
                                    <td><span class="badge bg-danger">Yes</span></td>
                                    <td>Connected WhatsApp Instance token ({{ $firstToken }}).</td>
                                </tr>
                                <tr>
                                    <td><code>number</code></td>
                                    <td>string</td>
                                    <td><span class="badge bg-danger">Yes</span></td>
                                    <td>Recipient number with country code (e.g. <code>918667702473</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>message</code></td>
                                    <td>string</td>
                                    <td>Optional</td>
                                    <td>Text body or media caption.</td>
                                </tr>
                                <tr>
                                    <td><code>media_url</code></td>
                                    <td>string (URL)</td>
                                    <td>Optional</td>
                                    <td>URL to image (<code>.jpg/.png</code>), document (<code>.pdf/.docx</code>), video (<code>.mp4</code>), or audio (<code>.mp3</code>).</td>
                                </tr>
                                <tr>
                                    <td><code>filename</code></td>
                                    <td>string</td>
                                    <td>Optional</td>
                                    <td>Custom filename for document attachments (e.g. <code>Order_Receipt.pdf</code>).</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        initWaTestConsole();
    });

    // Also re-init if loaded via AJAX
    setTimeout(initWaTestConsole, 500);

    function initWaTestConsole() {
        // Format switcher
        var btns = document.querySelectorAll('.wa-format-btn');
        if (!btns.length) return;

        btns.forEach(function(btn) {
            btn.onclick = function() {
                document.querySelectorAll('.wa-format-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                var type = this.dataset.type;
                var mediaSection = document.getElementById('wa-media-section');
                var messageLabel = document.getElementById('wa-label-message');
                var filenameGroup = document.getElementById('wa-filename-group');

                if (type === 'text') {
                    mediaSection.classList.add('d-none');
                    messageLabel.textContent = "{{ __('Message Content') }}";
                } else {
                    mediaSection.classList.remove('d-none');
                    messageLabel.textContent = "{{ __('Caption (Optional)') }}";
                    if (type === 'document') {
                        filenameGroup.classList.remove('d-none');
                    } else {
                        filenameGroup.classList.add('d-none');
                    }
                }
            };
        });

        // Live AJAX Form Submit
        var testForm = document.getElementById('wa-live-test-form');
        if (testForm && !testForm.dataset.initialized) {
            testForm.dataset.initialized = 'true';
            testForm.onsubmit = function(e) {
                e.preventDefault();
                var submitBtn = document.getElementById('wa-test-submit-btn');
                var spinner = document.getElementById('wa-test-spinner');
                var resultBox = document.getElementById('wa-test-result-container');
                var alertDiv = document.getElementById('wa-test-result-alert');

                submitBtn.disabled = true;
                spinner.classList.remove('d-none');
                resultBox.classList.add('d-none');

                var formData = new FormData(this);

                fetch("{{ route('app.whatsappreport.send_test_message') }}", {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                    resultBox.classList.remove('d-none');

                    if (data.status === 1) {
                        alertDiv.className = 'alert alert-success d-flex flex-column gap-2 mb-0';
                        alertDiv.innerHTML = `
                            <div class="d-flex align-items-center gap-10">
                                <i class="fas fa-circle-check fs-20 text-success"></i>
                                <div class="fw-bold fs-14">` + data.message + `</div>
                            </div>
                            <div class="fs-12 text-gray-700">
                                <span><strong>Target Chat ID:</strong> <code>` + (data.chat_id || '') + `</code></span>
                                ` + (data.media_url ? `<span class="ms-3"><strong>Media Attached:</strong> <a href="` + data.media_url + `" target="_blank" class="text-success text-decoration-underline">` + data.media_url + `</a></span>` : '') + `
                            </div>
                        `;
                    } else {
                        alertDiv.className = 'alert alert-danger d-flex align-items-center gap-10 mb-0';
                        alertDiv.innerHTML = `
                            <i class="fas fa-circle-exclamation fs-20 text-danger"></i>
                            <div>
                                <div class="fw-bold fs-14">Delivery Failed</div>
                                <div class="fs-12">` + (data.message || 'Unknown gateway error') + `</div>
                            </div>
                        `;
                    }
                })
                .catch(function(err) {
                    submitBtn.disabled = false;
                    spinner.classList.add('d-none');
                    resultBox.classList.remove('d-none');
                    alertDiv.className = 'alert alert-danger d-flex align-items-center gap-10 mb-0';
                    alertDiv.innerHTML = `
                        <i class="fas fa-circle-exclamation fs-20 text-danger"></i>
                        <div>
                            <div class="fw-bold fs-14">Network / Server Error</div>
                            <div class="fs-12">` + err.message + `</div>
                        </div>
                    `;
                });
            };
        }
    }

    // Quick Fill Presets
    window.fillWaSample = function(preset) {
        var numInput = document.getElementById('wa-test-number');
        var msgInput = document.getElementById('wa-test-message');
        var mediaInput = document.getElementById('wa-test-media-url');
        var filenameInput = document.getElementById('wa-test-filename');

        if (!numInput.value) {
            numInput.value = '918667702473';
        }

        if (preset === 'otp') {
            document.querySelector('.wa-format-btn[data-type="text"]')?.click();
            var otp = Math.floor(100000 + Math.random() * 900000);
            msgInput.value = '🔐 Your verification OTP is ' + otp + '. Valid for 5 minutes. Do not share this code with anyone.';
            if (mediaInput) mediaInput.value = '';
        } else if (preset === 'text') {
            document.querySelector('.wa-format-btn[data-type="text"]')?.click();
            msgInput.value = "hai these is test message don't replay";
            if (mediaInput) mediaInput.value = '';
        } else if (preset === 'ai_image') {
            document.querySelector('.wa-format-btn[data-type="image"]')?.click();
            msgInput.value = "🎨 Here is your AI Generated Mascot image!";
            if (mediaInput) mediaInput.value = "http://localhost:8080/ai_sample.jpg";
        } else if (preset === 'pdf') {
            document.querySelector('.wa-format-btn[data-type="document"]')?.click();
            msgInput.value = "📄 Here is your sample invoice document.";
            if (mediaInput) mediaInput.value = "https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf";
            if (filenameInput) filenameInput.value = "Sample_Invoice.pdf";
        }
    };

    // Copy helper
    window.copyText = function(val, btn) {
        navigator.clipboard.writeText(val).then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check text-success"></i> Copied!';
            setTimeout(function() { btn.innerHTML = orig; }, 2000);
        });
    };

    window.copySnippet = function(elementId, btn) {
        var code = document.getElementById(elementId).innerText;
        window.copyText(code, btn);
    };
</script>

@endif
