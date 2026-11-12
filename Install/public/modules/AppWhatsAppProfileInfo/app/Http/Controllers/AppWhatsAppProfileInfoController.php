<?php

namespace Modules\AppWhatsAppProfileInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppWhatsAppProfileInfoController extends Controller
{
    protected string $serverUrl = '';

    public function __construct()
    {
        $this->serverUrl = rtrim((string) get_option('whatsapp_server_url', ''), '/');
    }

    public function index(Request $request)
    {
        return view(module('key') . '::index', [
            'accounts' => $this->getAccounts((int) $request->team_id),
        ]);
    }

    public function info(Request $request)
    {
        $account = $this->findAccount((int) $request->team_id, trim((string) $request->input('account', '')));

        if (!$account) {
            return response()->json([
                'status' => 0,
                'data' => view(module('key') . '::info', [
                    'status' => 'error',
                    'message' => __('WhatsApp account does not exist. Please reconnect the profile and try again.'),
                    'account' => null,
                    'webhook' => null,
                    'session' => null,
                    'liveInfo' => null,
                    'accessToken' => $request->team->id_secure ?? '',
                ])->render(),
            ]);
        }

        $webhook = DB::table('whatsapp_webhook')->where('team_id', $request->team_id)->where('instance_id', $account->token)->first();
        $session = DB::table('whatsapp_sessions')->where('team_id', $request->team_id)->where('instance_id', $account->token)->first();
        $liveResponse = $this->waRequest('instance', [
            'instance_id' => $account->token,
            'access_token' => $request->team->id_secure ?? '',
        ]);

        return response()->json([
            'status' => 1,
            'data' => view(module('key') . '::info', [
                'status' => 'success',
                'account' => $account,
                'webhook' => $webhook,
                'session' => $session,
                'liveInfo' => $liveResponse['ok'] ? ($liveResponse['data']['data'] ?? null) : null,
                'accessToken' => $request->team->id_secure ?? '',
            ])->render(),
        ]);
    }

    public function logout(Request $request)
    {
        $account = $this->findAccount((int) $request->team_id, trim((string) $request->input('account', '')));
        if (!$account) {
            return response()->json(['status' => 0, 'message' => __('WhatsApp account does not exist.')]);
        }

        $this->waRequest('logout', [
            'instance_id' => $account->token,
            'access_token' => $request->team->id_secure ?? '',
        ]);

        DB::table('accounts')->where('id', $account->id)->update([
            'status' => 0,
            'changed' => time(),
        ]);

        DB::table('whatsapp_sessions')->where('team_id', $request->team_id)->where('instance_id', $account->token)->update([
            'status' => 0,
            'data' => null,
        ]);

        return response()->json(['status' => 1, 'message' => __('Profile logged out successfully.')]);
    }

    public function reset(Request $request)
    {
        $account = $this->findAccount((int) $request->team_id, trim((string) $request->input('account', '')));
        if (!$account) {
            return response()->json(['status' => 0, 'message' => __('WhatsApp account does not exist.')]);
        }

        $this->waRequest('logout', [
            'instance_id' => $account->token,
            'access_token' => $request->team->id_secure ?? '',
        ]);

        DB::table('accounts')->where('id', $account->id)->delete();
        DB::table('whatsapp_autoresponder')->where('instance_id', $account->token)->delete();
        DB::table('whatsapp_chatbot')->where('instance_id', $account->token)->delete();
        DB::table('whatsapp_ai_smart_reply')->where('instance_id', $account->token)->delete();
        DB::table('whatsapp_sessions')->where('instance_id', $account->token)->delete();
        DB::table('whatsapp_webhook')->where('instance_id', $account->token)->delete();
        DB::table('whatsapp_schedules')->where(function ($query) use ($account) {
            $id = (int) $account->id;
            $query->where('accounts', 'like', '%[' . $id . ']%')
                ->orWhere('accounts', 'like', '%[' . $id . ',%')
                ->orWhere('accounts', 'like', '%,' . $id . ',%')
                ->orWhere('accounts', 'like', '%,' . $id . ']%');
        })->delete();

        return response()->json(['status' => 1, 'message' => __('Instance reset was successful.')]);
    }

    protected function getAccounts(int $teamId)
    {
        return DB::table('accounts')
            ->where('team_id', $teamId)
            ->where('social_network', 'whatsapp_unofficial')
            ->where('category', 'profile')
            ->where('login_type', 2)
            ->orderBy('created')
            ->get();
    }

    protected function findAccount(int $teamId, string $accountKey)
    {
        return DB::table('accounts')
            ->where('team_id', $teamId)
            ->where('social_network', 'whatsapp_unofficial')
            ->where('category', 'profile')
            ->where('login_type', 2)
            ->where('id_secure', $accountKey)
            ->first();
    }

    protected function waRequest(string $endpoint, array $params = []): array
    {
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
            Log::warning('WhatsApp profile info request failed', ['endpoint' => $endpoint, 'message' => $e->getMessage()]);
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

        return json_decode($result);
    }
}

