<?php

namespace Modules\AppChannelWhatsappUnofficial\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AppChannels\Models\Accounts;
use Modules\AppWhatsAppChat\Services\WhatsAppChatService;

class AppChannelWhatsappUnofficialController extends Controller
{
    protected string $serverUrl = '';

    public function __construct()
    {
        if (!request()->routeIs('app.channelwhatsappunofficial.webhook')) {
            \Access::check('appchannels.' . module('key'));
        }

        $this->serverUrl = rtrim((string) get_option('whatsapp_server_url', ''), '/');
    }

    public function index(Request $request)
    {
        return redirect(module_url('oauth'));
    }

    public function oauth(Request $request, $instance_id = null)
    {
        if (session()->has('channels')) {
            session()->forget('channels');
        }
        if (!$this->serverUrl) {
            return redirect()->route('app.channelwhatsappunofficial.settings');
        }

        $accounts = Accounts::where([
                'team_id' => $request->team_id,
                'category' => 'profile',
            ])
            ->whereIn('social_network', $this->socialNetworks())
            ->orderByDesc('id')
            ->get();

        $account = null;
        if ($instance_id) {
            $account = Accounts::where([
                'team_id' => $request->team_id,
                'category' => 'profile',
                'token' => $instance_id,
            ])->whereIn('social_network', $this->socialNetworks())->first();
        }

        if ($account) {
            DB::table('whatsapp_sessions')
                ->where('instance_id', $account->token)
                ->update(['status' => 0]);
        } else {
            $session = DB::table('whatsapp_sessions')
                ->where('team_id', $request->team_id)
                ->where('status', 0)
                ->first();

            if (!$session) {
                $instance_id = strtoupper(substr(md5(uniqid((string) $request->team_id, true)), 0, 12));
                DB::table('whatsapp_sessions')
                    ->where('team_id', $request->team_id)
                    ->where('status', 0)
                    ->delete();

                DB::table('whatsapp_sessions')->insert([
                    'id_secure' => rand_string(),
                    'team_id' => $request->team_id,
                    'instance_id' => $instance_id,
                    'data' => null,
                    'status' => 0,
                ]);
            } else {
                $instance_id = $session->instance_id;
            }
        }

        $request->session()->put('whatsapp_instance_id', $instance_id);

        return view('appchannelwhatsappunofficial::index', [
            'instance_id' => $instance_id,
            'accounts' => $accounts,
            'can_add_account' => $this->canAddAccount($request, $instance_id),
            'module' => $request->module,
        ]);
    }

        protected function getTeamAndAccessToken(Request $request): array
    {
        $teamId = (int) ($request->team_id ?? 0);
        if (!$teamId && auth()->check()) {
            $teamId = (int) (auth()->user()->team_id ?? 0);
        }

        $team = null;
        if ($teamId) {
            $team = DB::table('teams')->where('id', $teamId)->first();
        }

        if (!$team && auth()->check()) {
            $team = DB::table('teams')->where('owner', auth()->id())->first();
            if ($team) {
                $teamId = (int) $team->id;
            }
        }

        if (!$team) {
            $team = DB::table('teams')->first();
            if ($team) {
                $teamId = (int) $team->id;
            }
        }

        $accessToken = '';
        if ($team) {
            if (empty($team->id_secure)) {
                $accessToken = 'token_' . bin2hex(random_bytes(8));
                try {
                    DB::table('teams')->where('id', $team->id)->update(['id_secure' => $accessToken]);
                } catch (\Throwable $e) {}
            } else {
                $accessToken = (string) $team->id_secure;
            }
        }

        if (empty($accessToken)) {
            $accessToken = 'token_' . bin2hex(random_bytes(8));
        }

        return [$teamId, $team, $accessToken];
    }

    public function getQrcode(Request $request, string $instance_id)
    {
        [$teamId, $team, $accessToken] = $this->getTeamAndAccessToken($request);
        
        if (!$this->serverUrl) {
            return response()->json([
                'status' => 0,
                'message' => __('WhatsApp server URL has not been configured yet.'),
            ]);
        }

        if (!$this->canAddAccount($request, $instance_id)) {
            return response()->json([
                'status' => 0,
                'message' => __('You have added the maximum number of allowed channels.'),
            ]);
        }

        $account = Accounts::where([
            'team_id' => $request->team_id,
            'category' => 'profile',
            'token' => $instance_id,
        ])->whereIn('social_network', $this->socialNetworks())->first();

        if ($account) {
            $session = DB::table('whatsapp_sessions')
                ->where('team_id', $request->team_id)
                ->where('status', 0)
                ->first();

            if ($session) {
                if ($session->instance_id !== $instance_id) {
                    DB::table('whatsapp_sessions')
                        ->where('id', $session->id)
                        ->update([
                            'instance_id' => $instance_id,
                            'status' => 0,
                            'data' => null,
                        ]);
                }
            } else {
                DB::table('whatsapp_sessions')->insert([
                    'id_secure' => rand_string(),
                    'team_id' => $request->team_id,
                    'instance_id' => $instance_id,
                    'data' => null,
                    'status' => 0,
                ]);
            }
        }

        $this->registerWebhook($request, $instance_id);

        $response = $this->waRequest('get_qrcode', [
            'instance_id' => $instance_id,
            'access_token' => $accessToken,
            'team_id' => $teamId,
        ]);

        if (!$response['ok']) {
            return response()->json([
                'status' => 0,
                'message' => $response['message'] ?? __('The system is initializing a new QR code. Please retry in 2 3 seconds.'),
            ]);
        }

        $payload = $response['data'];
        $base64 = $this->extractQrCode($payload);

        if (!$base64) {
            return response()->json([
                'status' => 0,
                'message' => __('The system is initializing a new QR code. Please retry in 2 3 seconds.'),
            ]);
        }

        return response()->json([
            'status' => 1,
            'base64' => $base64,
            'data' => $payload,
        ]);
    }

    public function checkLogin(Request $request, string $instance_id)
    {
        [$teamId, $team, $accessToken] = $this->getTeamAndAccessToken($request);
        $modulePayload = $request->module;

        $waSession = DB::table('whatsapp_sessions')
            ->where('team_id', $teamId)
            ->where('instance_id', $instance_id)
            ->first();

        $account = Accounts::where([
                'team_id' => $teamId,
                'category' => 'profile',
                'token' => $instance_id,
            ])
            ->whereIn('social_network', $this->socialNetworks())
            ->where('login_type', 2)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();

        if ($account) {
            session(['channels' => $this->buildChannelsSessionFromAccount($account, $modulePayload, $instance_id)]);

            return response()->json([
                'status' => 1,
                'message' => __('Succeeded'),
                'redirect' => url_app('channels/add'),
            ]);
        }

        $profile = [];
        $connected = false;

        if ($waSession && !empty($waSession->data)) {
            $profile = $this->normalizeProfile(json_decode((string) $waSession->data, true) ?: []);
            $connected = (int) ($waSession->status ?? 0) === 1 || !empty($profile['id']) || !empty($profile['name']);
        }

        $response = $this->waRequest('instance', [
            'instance_id' => $instance_id,
            'access_token' => $accessToken,
            'team_id' => $teamId,
        ]);

        if ($response['ok']) {
            $instancePayload = (array) ($response['data'] ?? []);
            $instanceProfile = $this->extractProfileFromWebhook($instancePayload);

            if (!empty($instanceProfile)) {
                $profile = $instanceProfile;
            }

            if ($this->isConnectedPayload($instancePayload) || !empty($profile['id']) || !empty($profile['name'])) {
                $connected = true;
            }

            if ($connected) {
                $sessionPayload = !empty($profile) ? json_encode($profile, JSON_UNESCAPED_UNICODE) : ($waSession->data ?? null);

                if ($waSession) {
                    DB::table('whatsapp_sessions')
                        ->where('id', $waSession->id)
                        ->update([
                            'data' => $sessionPayload,
                            'status' => 1,
                        ]);
                } else {
                    DB::table('whatsapp_sessions')->insert([
                        'id_secure' => rand_string(),
                        'team_id' => $teamId,
                        'instance_id' => $instance_id,
                        'data' => $sessionPayload,
                        'status' => 1,
                    ]);
                }
            }
        }

        if (!$connected) {
            return response()->json([
                'status' => 0,
                'message' => __('Waiting for WhatsApp login.'),
            ]);
        }

        $channels = $this->buildChannelsSessionFromProfile($profile, $modulePayload, $instance_id, $waSession?->data);
        session(['channels' => $channels]);

        return response()->json([
            'status' => 1,
            'message' => __('Succeeded'),
            'redirect' => url_app('channels/add'),
        ]);
    }

    public function webhook(Request $request, string $instance_id)
    {
        $teamId = (int) $request->query('team_id', 0);

        if ($teamId < 1 || !$this->isValidWebhookKey($request, $instance_id, $teamId)) {
            return response()->json(['status' => 0, 'message' => 'Invalid key'], 403);
        }

        $payload = $request->all();
        if (empty($payload)) {
            $payload = json_decode($request->getContent(), true) ?: [];
        }

        $profile = $this->extractProfileFromWebhook($payload);
        $connected = $this->isConnectedPayload($payload);

        $sessionData = !empty($profile) ? json_encode($profile, JSON_UNESCAPED_UNICODE) : null;
        $session = DB::table('whatsapp_sessions')
            ->where('team_id', $teamId)
            ->where('instance_id', $instance_id)
            ->first();

        $updates = [];
        if ($sessionData !== null) {
            $updates['data'] = $sessionData;
        }
        if ($connected) {
            $updates['status'] = 1;
        } elseif ($this->isDisconnectedPayload($payload)) {
            $updates['status'] = 0;
        }

        if ($session) {
            if (!empty($updates)) {
                DB::table('whatsapp_sessions')
                    ->where('id', $session->id)
                    ->update($updates);
            }
        } else {
            DB::table('whatsapp_sessions')->insert([
                'id_secure' => rand_string(),
                'team_id' => $teamId,
                'instance_id' => $instance_id,
                'data' => $sessionData,
                'status' => $connected ? 1 : 0,
            ]);
        }

        try {
            app(WhatsAppChatService::class)->ingestFromWebhook($payload, $teamId, $instance_id);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp unofficial chat ingest skipped', [
                'instance_id' => $instance_id,
                'team_id' => $teamId,
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json(['status' => 1]);
    }

    public function settings()
    {
        return view('appchannelwhatsappunofficial::settings');
    }

    protected function buildChannelsSessionFromAccount(object $account, array $modulePayload, string $instance_id): array
    {
        return [
            'status' => 1,
            'message' => __('Succeeded'),
            'channels' => [[
                'id' => $account->pid ?: $instance_id,
                'name' => $account->name,
                'avatar' => $account->avatar ?: text2img($account->name ?: 'WhatsApp', 'rand'),
                'desc' => $account->username ?: __('Profile'),
                'link' => $account->username ? 'https://wa.me/' . preg_replace('/\D+/', '', (string) $account->username) : '',
                'oauth' => $instance_id,
                'module' => $modulePayload['module_name'],
                'reconnect_url' => $modulePayload['uri'] . '/oauth/' . $instance_id,
                'social_network' => 'whatsapp_unofficial',
                'category' => 'profile',
                'login_type' => 2,
                'can_post' => 0,
                'data' => $account->tmp,
                'proxy' => 0,
            ]],
            'module' => $modulePayload,
            'save_url' => url_app('channels/save'),
            'reconnect_url' => module_url('oauth/' . $instance_id),
            'oauth' => $instance_id,
        ];
    }

    protected function buildChannelsSessionFromProfile(array $profile, array $modulePayload, string $instance_id, ?string $rawSessionData = null): array
    {
        $channelId = $profile['id'] ?? $instance_id;
        $name = $profile['name'] ?? ('WhatsApp ' . $channelId);
        $phone = preg_replace('/\D+/', '', $channelId);
        $avatar = $profile['avatar'] ?? text2img($name, 'rand');

        return [
            'status' => 1,
            'message' => __('Succeeded'),
            'channels' => [[
                'id' => $channelId,
                'name' => $name,
                'avatar' => $avatar,
                'desc' => $phone ?: __('Profile'),
                'link' => $phone ? 'https://wa.me/' . $phone : '',
                'oauth' => $instance_id,
                'module' => $modulePayload['module_name'],
                'reconnect_url' => $modulePayload['uri'] . '/oauth/' . $instance_id,
                'social_network' => 'whatsapp_unofficial',
                'category' => 'profile',
                'login_type' => 2,
                'can_post' => 0,
                'data' => json_encode([
                    'instance_id' => $instance_id,
                    'profile' => !empty($rawSessionData) ? (json_decode($rawSessionData, true) ?: $profile) : $profile,
                ], JSON_UNESCAPED_UNICODE),
                'proxy' => 0,
            ]],
            'module' => $modulePayload,
            'save_url' => url_app('channels/save'),
            'reconnect_url' => module_url('oauth/' . $instance_id),
            'oauth' => $instance_id,
        ];
    }

    protected function canAddAccount(Request $request, string $instance_id): bool
    {
        $exists = Accounts::where([
            'team_id' => $request->team_id,
            'category' => 'profile',
        ])->whereIn('social_network', $this->socialNetworks())->where(function ($query) use ($instance_id) {
            $query->where('token', $instance_id)
                ->orWhere('pid', $instance_id);
        })->exists();

        return $exists || \Channels::checkCanAddAccounts(module('module_name'));
    }

    protected function registerWebhook(Request $request, string $instance_id): void
    {
        [$teamId, $team, $accessToken] = $this->getTeamAndAccessToken($request);
        
        $webhookUrl = module_url('webhook/' . $instance_id)
            . '?team_id=' . $teamId
            . '&key=' . $this->webhookKey($teamId, $instance_id);

        $webhook = DB::table('whatsapp_webhook')
            ->where('team_id', $teamId)
            ->where('instance_id', $instance_id)
            ->first();

        if ($webhook) {
            DB::table('whatsapp_webhook')
                ->where('id', $webhook->id)
                ->update([
                    'webhook_url' => $webhookUrl,
                    'status' => 1,
                ]);
        } else {
            DB::table('whatsapp_webhook')->insert([
                'id_secure' => rand_string(),
                'team_id' => $teamId,
                'instance_id' => $instance_id,
                'webhook_url' => $webhookUrl,
                'status' => 1,
            ]);
        }

        $this->waRequest('set_webhook', [
            'instance_id' => $instance_id,
            'access_token' => $accessToken,
            'team_id' => $teamId,
            'webhook_url' => $webhookUrl,
            'enable' => 1,
        ]);
    }

    protected function waRequest(string $endpoint, array $params = []): array
    {
        if (!isset($params['db'])) {
            $params['db'] = config('database.connections.mysql.database');
        }

        try {
            $result = $this->waGetCurlLike($endpoint, $params);
            if (!$result) {
                return ['ok' => false, 'data' => null, 'message' => __('Cannot connect to WhatsApp server. Please make sure the WhatsApp server is running.')];
            }

            $data = json_decode(json_encode($result), true);
            $status = $data['status'] ?? null;
            if ($status === 'error' || $status === 0 || $status === '0' || $status === false) {
                return ['ok' => false, 'data' => $data, 'message' => $data['message'] ?? __('Unknown WhatsApp server response.')];
            }

            return ['ok' => true, 'data' => $data, 'message' => $data['message'] ?? __('Succeeded')];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp unofficial request failed', ['endpoint' => $endpoint, 'message' => $e->getMessage()]);
        }

        return ['ok' => false, 'data' => null, 'message' => __('Cannot connect to WhatsApp server. Please make sure the WhatsApp server is running.')];
    }

    protected function waGetCurlLike(string $endpoint, array $params = [])
    {
        if (!$this->serverUrl) {
            return null;
        }

        $url = $this->serverUrl . '/' . ltrim($endpoint, '/') . '?' . http_build_query($params);
        $headers = [
            'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,fr;q=0.8;q=0.6,en;q=0.4,ar;q=0.2',
            'Accept-Encoding: gzip,deflate',
            'Accept-Charset: utf-8;q=0.7,*;q=0.7',
            'cookie:datr=; locale=en_US; sb=; pl=n; lu=gA; c_user=; xs=; act=; presence=',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_REFERER, url(''));
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }

    protected function extractQrCode($payload): ?string
    {
        if (is_string($payload) && $payload !== '') {
            return str_starts_with($payload, 'data:image') ? $payload : 'data:image/png;base64,' . $payload;
        }

        if (!is_array($payload)) {
            return null;
        }

        foreach (['base64', 'qrcode', 'qr', 'message'] as $key) {
            if (!empty($payload[$key]) && is_string($payload[$key])) {
                return str_starts_with($payload[$key], 'data:image') ? $payload[$key] : 'data:image/png;base64,' . $payload[$key];
            }
        }

        if (!empty($payload['data']) && is_array($payload['data'])) {
            return $this->extractQrCode($payload['data']);
        }

        return null;
    }

    protected function webhookKey(int $teamId, string $instance_id): string
    {
        return hash_hmac('sha256', $teamId . '|' . $instance_id, (string) config('app.key'));
    }

    protected function isValidWebhookKey(Request $request, string $instance_id, int $teamId): bool
    {
        return hash_equals($this->webhookKey($teamId, $instance_id), (string) $request->query('key', ''));
    }

    protected function isConnectedPayload(array $payload): bool
    {
        // Check the connected field explicitly first
        $connected = data_get($payload, 'data.connected');
        if ($connected === false) {
            return false;
        }
        if ($connected === true) {
            return true;
        }

        $haystacks = array_filter([
            strtolower((string) ($payload['event'] ?? '')),
            strtolower((string) data_get($payload, 'data.status', '')),
            strtolower((string) data_get($payload, 'data.connection', '')),
            strtolower((string) data_get($payload, 'data.state', '')),
            strtolower((string) data_get($payload, 'connection', '')),
            strtolower((string) data_get($payload, 'state', '')),
        ]);

        foreach ($haystacks as $value) {
            if (str_contains($value, 'open') || str_contains($value, 'connected') || str_contains($value, 'ready')) {
                return true;
            }
        }

        return false;
    }

    protected function isDisconnectedPayload(array $payload): bool
    {
        $haystacks = array_filter([
            strtolower((string) ($payload['event'] ?? '')),
            strtolower((string) ($payload['status'] ?? '')),
            strtolower((string) data_get($payload, 'data.status', '')),
            strtolower((string) data_get($payload, 'data.connection', '')),
            strtolower((string) data_get($payload, 'data.state', '')),
            strtolower((string) data_get($payload, 'connection', '')),
            strtolower((string) data_get($payload, 'state', '')),
            strtolower((string) data_get($payload, 'data.lastDisconnect.error.message', '')),
        ]);

        foreach ($haystacks as $value) {
            if (
                str_contains($value, 'close') ||
                str_contains($value, 'closed') ||
                str_contains($value, 'disconnect') ||
                str_contains($value, 'logged out') ||
                str_contains($value, 'logout')
            ) {
                return true;
            }
        }

        return false;
    }

    protected function extractProfileFromWebhook(array $payload): array
    {
        foreach ([
            data_get($payload, 'data.profile'),
            data_get($payload, 'data.user'),
            data_get($payload, 'data'),
            $payload,
        ] as $candidate) {
            if (is_array($candidate)) {
                $profile = $this->normalizeProfile($candidate);
                if ($profile['id'] || $profile['name'] || $profile['avatar']) {
                    return $profile;
                }
            }
        }

        return [];
    }

    protected function normalizeProfile(array $profile): array
    {
        $id = $this->normalizeProfileId((string) (
            $profile['id'] ??
            $profile['wid'] ??
            $profile['user'] ??
            $profile['phone'] ??
            $profile['number'] ??
            ''
        ));

        return [
            'id' => $id,
            'name' => (string) (
                $profile['name'] ??
                $profile['pushName'] ??
                $profile['notify'] ??
                $profile['profile_name'] ??
                ''
            ),
            'avatar' => (string) (
                $profile['avatar'] ??
                $profile['profilePicUrl'] ??
                $profile['profile_picture'] ??
                $profile['picture'] ??
                ''
            ),
        ];
    }

    protected function normalizeProfileId(string $id): string
    {
        if (str_contains($id, '@')) {
            $id = explode('@', $id)[0];
        }

        if (str_contains($id, ':')) {
            $id = explode(':', $id)[0];
        }

        return trim($id);
    }

    protected function socialNetworks(): array
    {
        return ['whatsapp_unofficial'];
    }
}
