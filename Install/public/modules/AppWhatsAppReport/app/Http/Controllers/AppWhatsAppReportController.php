<?php

namespace Modules\AppWhatsAppReport\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nwidart\Modules\Facades\Module;

class AppWhatsAppReportController extends Controller
{
    protected array $featureMap = [
        'auto_reply' => [
            'label' => 'Auto Reply',
            'table' => 'whatsapp_autoresponder',
            'active_callback' => 'activeAutoReply',
            'account_filter' => 'instance_id',
        ],
        'chatbot' => [
            'label' => 'Chatbot',
            'table' => 'whatsapp_chatbot',
            'active_callback' => 'activeChatbot',
            'account_filter' => 'instance_id',
        ],
        'ai_smart_reply' => [
            'label' => 'AI Smart Reply',
            'table' => 'whatsapp_ai_smart_reply',
            'active_callback' => 'activeDefault',
            'account_filter' => 'instance_id',
        ],
        'bulk' => [
            'label' => 'Bulk Campaign',
            'table' => 'whatsapp_schedules',
            'active_callback' => 'activeBulk',
            'account_filter' => 'accounts',
        ],
        'api' => [
            'label' => 'REST API',
            'table' => null,
            'active_callback' => 'activeApi',
            'account_filter' => null,
        ],
    ];

    protected array $moduleMap = [
        'profile_info' => 'AppWhatsAppProfileInfo',
        'reports' => 'AppWhatsAppReport',
        'bulk' => 'AppWhatsAppBulk',
        'ai_smart_reply' => 'AppWhatsAppAISmartReply',
        'auto_reply' => 'AppWhatsAppAutoReply',
        'chatbot' => 'AppWhatsAppChatbot',
        'contacts' => 'AppWhatsAppContact',
        'participants_export' => 'AppWhatsAppParticipantsExport',
        'api' => 'AppWhatsAppApi',
    ];

    protected array $permissionMap = [
        'profile_info' => 'appwhatsappprofileinfo',
        'reports' => 'appwhatsappreport',
        'bulk' => 'appwhatsappbulk',
        'ai_smart_reply' => 'appwhatsappaismartreply',
        'auto_reply' => 'appwhatsappautoreply',
        'chatbot' => 'appwhatsappchatbot',
        'contacts' => 'appwhatsappcontact',
        'participants_export' => 'appwhatsappparticipantsexport',
        'api' => 'appwhatsappapi',
    ];

    public function index(Request $request)
    {
        $teamId = (int) $request->team_id;
        [$startDate, $endDate] = \Core::parseDateRange($request, 30);

        $startTs = $startDate->copy()->startOfDay()->timestamp;
        $endTs = $endDate->copy()->endOfDay()->timestamp;
        $selectedAccount = trim((string) $request->input('account', ''));
        $selectedFeature = trim((string) $request->input('feature', 'all'));

        $accounts = $this->getAccounts($teamId);
        $featureKeys = $this->resolveFeatureKeys($selectedFeature);
        $selectedAccountRow = $selectedAccount !== '' ? $accounts->firstWhere('id_secure', $selectedAccount) : null;
        $accountToken = $selectedAccountRow ? $selectedAccountRow->token : null;
        $accountId = $selectedAccountRow ? (int) $selectedAccountRow->id : null;
        $accountsForTable = $selectedAccount !== ''
            ? $accounts->where('id_secure', $selectedAccount)->values()
            : $accounts->values();

        $featureRows = collect($featureKeys)->map(function (string $feature) use ($teamId, $accountToken, $accountId, $startTs, $endTs) {
            $metrics = $this->featureMetrics($feature, $teamId, $accountToken, $accountId, $startTs, $endTs);

            return (object) array_merge([
                'key' => $feature,
                'label' => __($this->featureMap[$feature]['label']),
            ], $metrics);
        })->values();

        $summary = [
            'records' => (int) $featureRows->sum('records'),
            'active' => (int) $featureRows->sum('active'),
            'sent' => (int) $featureRows->sum('sent'),
            'failed' => (int) $featureRows->sum('failed'),
            'accounts' => (int) $accountsForTable->count(),
        ];

        $quota = $this->getQuotaSummary($teamId);
        $permissionsInfo = $this->getPermissionsSummary();
        $accountRows = $accountsForTable->map(function ($account) use ($teamId, $featureKeys, $startTs, $endTs) {
            $rows = collect($featureKeys)->mapWithKeys(function (string $feature) use ($teamId, $account, $startTs, $endTs) {
                return [$feature => $this->featureMetrics($feature, $teamId, $account->token, (int) $account->id, $startTs, $endTs)];
            });

            return (object) [
                'id_secure' => $account->id_secure,
                'name' => $account->name,
                'username' => $account->username,
                'avatar' => $account->avatar,
                'token' => $account->token,
                'records' => (int) $rows->sum('records'),
                'active' => (int) $rows->sum('active'),
                'sent' => (int) $rows->sum('sent'),
                'failed' => (int) $rows->sum('failed'),
                'features' => $rows,
            ];
        })->sortByDesc('sent')->values();

        $featureOptions = ['all' => __('All features')];
        foreach ($this->availableFeatureMap() as $feature => $config) {
            $featureOptions[$feature] = __($config['label']);
        }

        $chart = [
            'categories' => $featureRows->pluck('label')->values()->all(),
            'series' => [
                [
                    'name' => __('Sent'),
                    'color' => '#22c55e',
                    'data' => $featureRows->pluck('sent')->map(fn ($value) => (int) $value)->values()->all(),
                ],
                [
                    'name' => __('Failed'),
                    'color' => '#ef4444',
                    'data' => $featureRows->pluck('failed')->map(fn ($value) => (int) $value)->values()->all(),
                ],
            ],
        ];

        $trendChart = $this->buildTrendChart($featureKeys, $teamId, $accountToken, $accountId, $startDate, $endDate);

        return view('appwhatsappreport::index', compact(
            'accounts',
            'accountRows',
            'chart',
            'endDate',
            'featureOptions',
            'featureRows',
            'permissionsInfo',
            'quota',
            'selectedAccount',
            'selectedFeature',
            'startDate',
            'summary',
            'trendChart'
        ));
    }

    protected function getQuotaSummary(int $teamId): array
    {
        $stats = DB::table('whatsapp_stats')->where('team_id', $teamId)->first();
        $user = auth()->user();
        $isActivePlan = $user && (!empty($user->plan_id) || (!empty($user->expiration_date) && ($user->expiration_date == -1 || $user->expiration_date > time())));

        $rawWaLimit = \Access::permission('whatsapp_message_per_month');
        $rawCredit = \Access::permission('credits');

        if ($rawWaLimit === -1 || $rawWaLimit === '-1' || $rawCredit === -1 || $rawCredit === '-1') {
            $monthlyLimit = -1;
        } elseif ((int) $rawWaLimit > 0) {
            $monthlyLimit = (int) $rawWaLimit;
        } elseif ((int) $rawCredit > 0) {
            $monthlyLimit = (int) $rawCredit;
        } else {
            $monthlyLimit = $isActivePlan ? -1 : 0;
        }

        $sentByMonth = (int) ($stats->wa_total_sent_by_month ?? 0);

        return [
            'limit' => $monthlyLimit,
            'sent_by_month' => $sentByMonth,
            'remaining' => $monthlyLimit === -1 ? -1 : max(0, $monthlyLimit - $sentByMonth),
            'is_unlimited' => $monthlyLimit === -1,
        ];
    }

    protected function getPermissionsSummary(): array
    {
        $hasProfileInfoModule = $this->featureAvailable('profile_info');
        $hasReportModule = $this->featureAvailable('reports');
        $hasBulkModule = $this->featureAvailable('bulk');
        $hasAiSmartReplyModule = $this->featureAvailable('ai_smart_reply');
        $hasAutoReplyModule = $this->featureAvailable('auto_reply');
        $hasChatbotModule = $this->featureAvailable('chatbot');
        $hasContactsModule = $this->featureAvailable('contacts');
        $hasParticipantsExportModule = $this->featureAvailable('participants_export');
        $hasApiModule = $this->featureAvailable('api');

        $user = auth()->user();
        $isActivePlan = $user && (!empty($user->plan_id) || (!empty($user->expiration_date) && ($user->expiration_date == -1 || $user->expiration_date > time())));

        $isFeatEnabled = fn(string $permKey) => (bool) \Access::permission($permKey) || $isActivePlan;

        $rawWaLimit = \Access::permission('whatsapp_message_per_month');
        $rawCredit = \Access::permission('credits');

        if ($rawWaLimit === -1 || $rawWaLimit === '-1' || $rawCredit === -1 || $rawCredit === '-1') {
            $msgLimit = -1;
        } elseif ((int) $rawWaLimit > 0) {
            $msgLimit = (int) $rawWaLimit;
        } elseif ((int) $rawCredit > 0) {
            $msgLimit = (int) $rawCredit;
        } else {
            $msgLimit = $isActivePlan ? -1 : 0;
        }

        $rawChatbot = \Access::permission('whatsapp_chatbot_item_limit');
        if ($rawChatbot === -1 || $rawChatbot === '-1') {
            $chatbotLimit = -1;
        } elseif ((int) $rawChatbot > 0) {
            $chatbotLimit = (int) $rawChatbot;
        } else {
            $chatbotLimit = $isActivePlan ? -1 : 0;
        }

        $rawContactGroup = \Access::permission('whatsapp_bulk_max_contact_group');
        if ($rawContactGroup === -1 || $rawContactGroup === '-1') {
            $contactGroupLimit = -1;
        } elseif ((int) $rawContactGroup > 0) {
            $contactGroupLimit = (int) $rawContactGroup;
        } else {
            $contactGroupLimit = $isActivePlan ? -1 : 0;
        }

        $rawPhones = \Access::permission('whatsapp_bulk_max_phone_numbers');
        if ($rawPhones === -1 || $rawPhones === '-1') {
            $phonesGroupLimit = -1;
        } elseif ((int) $rawPhones > 0) {
            $phonesGroupLimit = (int) $rawPhones;
        } else {
            $phonesGroupLimit = $isActivePlan ? -1 : 0;
        }

        return [
            'enabled' => $isFeatEnabled('appchannelwhatsappunofficial'),
            'features' => collect([
                $hasProfileInfoModule ? ['label' => __('Profile Info'), 'enabled' => $isFeatEnabled('appwhatsappprofileinfo')] : null,
                $hasReportModule ? ['label' => __('Reports'), 'enabled' => $isFeatEnabled('appwhatsappreport')] : null,
                $hasBulkModule ? ['label' => __('Bulk campaigns'), 'enabled' => $isFeatEnabled('appwhatsappbulk')] : null,
                $hasAiSmartReplyModule ? ['label' => __('AI Smart Reply'), 'enabled' => $isFeatEnabled('appwhatsappaismartreply')] : null,
                $hasAutoReplyModule ? ['label' => __('Auto Reply'), 'enabled' => $isFeatEnabled('appwhatsappautoreply')] : null,
                $hasChatbotModule ? ['label' => __('Chatbot'), 'enabled' => $isFeatEnabled('appwhatsappchatbot')] : null,
                $hasContactsModule ? ['label' => __('Contacts'), 'enabled' => $isFeatEnabled('appwhatsappcontact')] : null,
                $hasParticipantsExportModule ? ['label' => __('Export participants'), 'enabled' => $isFeatEnabled('appwhatsappparticipantsexport')] : null,
                $hasApiModule ? ['label' => __('REST API'), 'enabled' => $isFeatEnabled('appwhatsappapi')] : null,
            ])->filter()->values()->all(),
            'limits' => [
                ['label' => __('Monthly messages'), 'value' => $msgLimit],
                ['label' => __('Chatbot item limit'), 'value' => $chatbotLimit],
                ['label' => __('Maximum contact groups'), 'value' => $contactGroupLimit],
                ['label' => __('Max numbers per group'), 'value' => $phonesGroupLimit],
            ],
        ];
    }

    protected function getAccounts(int $teamId): Collection
    {
        return DB::table('accounts')
            ->where('team_id', $teamId)
            ->where('social_network', 'whatsapp_unofficial')
            ->where('category', 'profile')
            ->where('login_type', 2)
            ->where('status', 1)
            ->orderBy('name')
            ->get();
    }

    protected function resolveFeatureKeys(string $selectedFeature): array
    {
        $availableFeatures = $this->availableFeatureMap();

        if ($selectedFeature !== '' && $selectedFeature !== 'all' && isset($availableFeatures[$selectedFeature])) {
            return [$selectedFeature];
        }

        return array_keys($availableFeatures);
    }

    protected function featureMetrics(string $feature, int $teamId, ?string $instanceId, ?int $accountId, int $startTs, int $endTs): array
    {
        if ($feature === 'api') {
            return $this->apiMetrics($teamId, $instanceId, $accountId);
        }

        $config = $this->featureMap[$feature];
        $query = DB::table($config['table'])->where('team_id', $teamId);

        $this->applyAccountFilter($query, $config['account_filter'], $instanceId, $accountId, $teamId);
        $this->applyDateFilter($query, $startTs, $endTs);

        $records = (int) (clone $query)->count();
        $sent = (int) ((clone $query)->sum('sent') ?? 0);
        $failed = (int) ((clone $query)->sum('failed') ?? 0);
        $active = (int) $this->{$config['active_callback']}((clone $query));

        return [
            'records' => $records,
            'active' => $active,
            'sent' => $sent,
            'failed' => $failed,
        ];
    }

    protected function applyDateFilter(Builder $query, int $startTs, int $endTs): void
    {
        $query->whereRaw('COALESCE(NULLIF(changed, 0), NULLIF(created, 0), 0) BETWEEN ? AND ?', [$startTs, $endTs]);
    }

    protected function activeDefault(Builder $query): int
    {
        return (int) $query->where('status', 1)->count();
    }

    protected function activeAutoReply(Builder $query): int
    {
        return (int) $query->where('status', 1)->count();
    }

    protected function activeChatbot(Builder $query): int
    {
        return (int) $query->where('status', 1)->where('run', 1)->count();
    }

    protected function activeBulk(Builder $query): int
    {
        return (int) $query->where('status', 1)->count();
    }

    protected function activeApi(): int
    {
        return $this->featureAvailable('api')
            ? (int) \Access::permission('appwhatsappapi')
            : 0;
    }

    protected function apiMetrics(int $teamId, ?string $instanceId, ?int $accountId): array
    {
        if (!$this->featureAvailable('api')) {
            return [
                'records' => 0,
                'active' => 0,
                'sent' => 0,
                'failed' => 0,
            ];
        }

        $stats = DB::table('whatsapp_stats')->where('team_id', $teamId)->first();
        $sent = ($instanceId !== null || $accountId !== null) ? 0 : (int) ($stats->wa_api_count ?? 0);

        return [
            'records' => $sent,
            'active' => $this->activeApi(),
            'sent' => $sent,
            'failed' => 0,
        ];
    }

    protected function availableFeatureMap(): array
    {
        return collect($this->featureMap)
            ->filter(fn ($config, string $feature) => $this->featureAvailable($feature))
            ->all();
    }

    protected function featureAvailable(string $feature): bool
    {
        $module = $this->moduleMap[$feature] ?? null;
        $permissionKey = $this->permissionMap[$feature] ?? null;

        if ($module !== null && !$this->moduleInstalled($module)) {
            return false;
        }

        if ($permissionKey !== null && !\Access::canAccess($permissionKey, false)) {
            $user = auth()->user();
            $isActivePlan = $user && (!empty($user->plan_id) || (!empty($user->expiration_date) && ($user->expiration_date == -1 || $user->expiration_date > time())));
            if (!$isActivePlan) {
                return false;
            }
        }

        return true;
    }

    protected function moduleInstalled(string $module): bool
    {
        try {
            $modulePath = base_path("modules/{$module}");
            if (!is_dir($modulePath) || !is_file($modulePath . DIRECTORY_SEPARATOR . 'module.json')) {
                return false;
            }

            $moduleInstance = Module::find($module);

            return $moduleInstance ? $moduleInstance->isEnabled() : false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function buildTrendChart(array $featureKeys, int $teamId, ?string $instanceId, ?int $accountId, $startDate, $endDate): array
    {
        $categories = collect(CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay()))
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->values();

        $sentMap = $categories->mapWithKeys(fn ($date) => [$date => 0])->all();
        $failedMap = $categories->mapWithKeys(fn ($date) => [$date => 0])->all();

        foreach ($featureKeys as $feature) {
            $table = $this->featureMap[$feature]['table'];
            if ($table === null) {
                continue;
            }


            $query = DB::table($table)
                ->selectRaw("DATE(FROM_UNIXTIME(COALESCE(NULLIF(changed, 0), NULLIF(created, 0), 0))) as report_date")
                ->selectRaw("SUM(COALESCE(sent, 0)) as total_sent")
                ->selectRaw("SUM(COALESCE(failed, 0)) as total_failed")
                ->where('team_id', $teamId);

            $this->applyAccountFilter($query, $this->featureMap[$feature]['account_filter'], $instanceId, $accountId, $teamId);
            $this->applyDateFilter($query, $startDate->copy()->startOfDay()->timestamp, $endDate->copy()->endOfDay()->timestamp);

            $rows = $query
                ->groupBy('report_date')
                ->orderBy('report_date')
                ->get();

            foreach ($rows as $row) {
                $date = (string) $row->report_date;
                if (!array_key_exists($date, $sentMap)) {
                    continue;
                }

                $sentMap[$date] += (int) ($row->total_sent ?? 0);
                $failedMap[$date] += (int) ($row->total_failed ?? 0);
            }
        }

        return [
            'categories' => $categories->map(fn ($date) => \Carbon\Carbon::parse($date)->format('M d'))->all(),
            'series' => [
                [
                    'name' => __('Sent'),
                    'type' => 'areaspline',
                    'data' => array_values($sentMap),
                ],
                [
                    'name' => __('Failed'),
                    'color' => '#ef4444',
                    'type' => 'line',
                    'data' => array_values($failedMap),
                ],
            ],
        ];
    }

    protected function applyAccountFilter(Builder $query, ?string $accountFilter, ?string $instanceId, ?int $accountId, int $teamId = 0): void
    {
        if ($accountFilter === 'instance_id' && $instanceId !== null) {
            $query->where('instance_id', $instanceId);
            return;
        }

        if ($accountFilter === 'accounts' && $accountId !== null) {
            $accountCount = ($teamId > 0) ? DB::table('accounts')
                ->where('team_id', $teamId)
                ->where('social_network', 'whatsapp_unofficial')
                ->where('category', 'profile')
                ->count() : 0;

            $query->where(function ($sub) use ($accountId, $accountCount) {
                // If only 1 account exists for this team, attribute all team bulk campaigns to this single connected profile
                if ($accountCount <= 1) {
                    $sub->whereNotNull('team_id');
                    return;
                }

                $sub->whereRaw('JSON_CONTAINS(COALESCE(NULLIF(accounts, ""), "[]"), ?)', ['[' . $accountId . ']'])
                    ->orWhereRaw('JSON_CONTAINS(COALESCE(NULLIF(accounts, ""), "[]"), ?)', ['["' . $accountId . '"]'])
                    ->orWhere('accounts', 'like', '%"' . $accountId . '"%')
                    ->orWhere('accounts', 'like', '%[' . $accountId . ']%')
                    ->orWhere('accounts', 'like', '%[' . $accountId . ',%')
                    ->orWhere('accounts', 'like', '%,' . $accountId . ',%')
                    ->orWhere('accounts', 'like', '%,' . $accountId . ']%')
                    ->orWhere('accounts', 'like', '%' . $accountId . '%')
                    ->orWhere('accounts', '=', (string) $accountId)
                    ->orWhere('accounts', '=', '[' . $accountId . ']');
            });
        }
    }

    public function sendTestMessage(Request $request)
    {
        $teamId = (int) $request->team_id;
        $team = DB::table('teams')->where('id', $teamId)->first();
        if (!$team) {
            return response()->json(['status' => 0, 'message' => __('Team not found.')], 404);
        }

        $accessToken = $team->id_secure ?? '';
        $instanceId = trim((string) $request->input('instance_id', ''));
        $number = trim((string) $request->input('number', ''));
        $message = trim((string) $request->input('message', ''));
        $mediaUrl = trim((string) $request->input('media_url', ''));
        $filename = trim((string) $request->input('filename', ''));

        if (empty($number)) {
            return response()->json(['status' => 0, 'message' => __('Recipient phone number is required.')]);
        }

        $cleanNumber = preg_replace('/\D+/', '', $number);
        if (strlen($cleanNumber) === 10 && in_array(substr($cleanNumber, 0, 1), ['6', '7', '8', '9'])) {
            $cleanNumber = '91' . $cleanNumber;
        }

        if (empty($instanceId)) {
            $account = DB::table('accounts')
                ->where('team_id', $teamId)
                ->where('social_network', 'whatsapp_unofficial')
                ->where('category', 'profile')
                ->where('status', 1)
                ->first();
            $instanceId = $account->token ?? '';
        }

        if (empty($instanceId)) {
            return response()->json(['status' => 0, 'message' => __('No active WhatsApp profile found. Please link a WhatsApp channel first.')]);
        }

        // Check if PHP dropped the upload because it exceeded upload_max_filesize in php.ini
        if (isset($_FILES['media_file']) && !empty($_FILES['media_file']['error'])) {
            $errCode = $_FILES['media_file']['error'];
            if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
                return response()->json([
                    'status' => 0,
                    'message' => __('Uploaded file exceeds server upload_max_filesize limit. Please increase upload_max_filesize and post_max_size in cPanel MultiPHP INI Editor, or select a smaller file.')
                ]);
            }
        }

        $localFilePath = '';
        $detectedMime = null;
        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');
            if ($file->isValid()) {
                $ext = strtolower($file->getClientOriginalExtension());
                $originalName = $file->getClientOriginalName();
                $safeName = 'test_' . time() . '_' . rand(1000, 9999) . ($ext ? '.' . $ext : '');
                $destinationPath = public_path('uploads/whatsapp_tests');
                if (!file_exists($destinationPath)) {
                    @mkdir($destinationPath, 0777, true);
                }
                $file->move($destinationPath, $safeName);
                $localFilePath = $destinationPath . DIRECTORY_SEPARATOR . $safeName;
                @chmod($localFilePath, 0777);
                $mediaUrl = url('uploads/whatsapp_tests/' . $safeName);
                if (empty($filename)) {
                    $filename = $originalName;
                }
                try {
                    $detectedMime = $file->getClientMimeType();
                } catch (\Throwable $e) {}
            }
        }

        if (empty($message) && empty($mediaUrl) && empty($localFilePath)) {
            return response()->json(['status' => 0, 'message' => __('Please enter a message or select a media file/URL.')]);
        }

        $serverUrl = rtrim((string) get_option('whatsapp_server_url', 'http://localhost:8000'), '/');
        $endpoint = $serverUrl . '/send_message?access_token=' . urlencode($accessToken) . '&instance_id=' . urlencode($instanceId) . '&db=' . urlencode(config('database.connections.mysql.database'));

        $payload = [
            'number' => $cleanNumber,
            'message' => $message,
            'caption' => $message,
            'media_url' => $mediaUrl,
            'file_path' => $localFilePath,
            'filename' => $filename,
            'mimetype' => !empty($file) && $file->isValid() ? $file->getMimeType() : null,
        ];

        try {
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                return response()->json(['status' => 0, 'message' => __('WhatsApp server error: ') . $curlError]);
            }

            $resData = json_decode($response, true);
            if (!empty($resData) && ($resData['status'] === 'success' || $resData['status'] === 1)) {
                try {
                    $stats = DB::table('whatsapp_stats')->where('team_id', $teamId)->first();
                    if ($stats) {
                        DB::table('whatsapp_stats')->where('team_id', $teamId)->update([
                            'wa_total_sent' => DB::raw('wa_total_sent + 1'),
                            'wa_total_sent_by_month' => DB::raw('wa_total_sent_by_month + 1'),
                            'wa_api_count' => DB::raw('wa_api_count + 1'),
                        ]);
                    }
                } catch (\Throwable $e) {}

                return response()->json([
                    'status' => 1,
                    'message' => __('Message sent successfully!'),
                    'chat_id' => $resData['chat_id'] ?? ($cleanNumber . '@s.whatsapp.net'),
                    'media_url' => $mediaUrl,
                    'data' => $resData,
                ]);
            }

            return response()->json([
                'status' => 0,
                'message' => $resData['message'] ?? __('Failed to send message via WhatsApp gateway.'),
                'data' => $resData,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }
}
