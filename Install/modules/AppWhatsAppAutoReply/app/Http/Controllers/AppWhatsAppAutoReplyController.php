<?php

namespace Modules\AppWhatsAppAutoReply\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Media;

class AppWhatsAppAutoReplyController extends Controller
{
    public function index(Request $request)
    {
        $accounts = $this->getAccounts($request->team_id);

        return view(module('key') . '::index', [
            'accounts' => $accounts,
        ]);
    }

    public function info(Request $request)
    {
        $teamId = $request->team_id;
        $accountKey = $request->input('account');
        $account = null;
        $result = null;

        if ($accountKey !== 'all') {
            $account = DB::table('accounts')
                ->where('team_id', $teamId)
                ->where('social_network', 'whatsapp_unofficial')
                ->where('category', 'profile')
                ->where('login_type', 2)
                ->where('id_secure', $accountKey)
                ->first();

            if (!$account) {
                return response()->json([
                    'status' => 0,
                    'data' => view(module('key') . '::info', [
                        'status' => 'error',
                        'message' => __('WhatsApp account does not exist. Please try again or reconnect your WhatsApp account.'),
                        'account' => null,
                        'result' => null,
                        'selectedFiles' => false,
                    ])->render(),
                ]);
            }

            $result = DB::table('whatsapp_autoresponder')
                ->where('team_id', $teamId)
                ->where(function ($query) use ($account) {
                    $query->where('id_secure', $account->id_secure)
                        ->orWhere('instance_id', $account->token);
                })
                ->orderByDesc('id')
                ->first();
        }

        $selectedFiles = false;
        if (!empty($result?->media)) {
            $selectedFiles = [$result->media];
        }

        return response()->json([
            'status' => 1,
            'data' => view(module('key') . '::info', [
                'status' => 'success',
                'account' => $account,
                'result' => $result,
                'selectedFiles' => $selectedFiles,
            ])->render(),
        ]);
    }

    public function save(Request $request)
    {
        $teamId = $request->team_id;
        $status = (int) $request->input('status', 1);
        $caption = trim((string) $request->input('caption', ''));
        $delay = (int) $request->input('delay', 1);
        $instanceId = trim((string) $request->input('instance_id', ''));
        $except = trim((string) $request->input('except', ''));
        $except = preg_replace('/[^0-9,]/', '', $except);
        $except = preg_replace('/,+/', ',', $except);
        $except = trim($except, ',');
        $sendTo = (int) $request->input('send_to', 1);
        $selectedMedias = (array) $request->input('medias', []);
        $selectedMedias = array_values(array_filter(array_map('trim', $selectedMedias)));
        $media = trim((string) $request->input('media', ''));

        if (!empty($selectedMedias)) {
            $media = $selectedMedias[0];
        }

        $media = $this->normalizeMediaUrl($media);

        if ($delay < 1) {
            return response()->json([
                'status' => 0,
                'message' => __('Delay is required.'),
            ]);
        }

        if ($caption === '' && $media === null) {
            return response()->json([
                'status' => 0,
                'message' => __('Please enter a caption or media URL.'),
            ]);
        }

        $accounts = $instanceId !== ''
            ? DB::table('accounts')
                ->where('team_id', $teamId)
                ->where('social_network', 'whatsapp_unofficial')
                ->where('category', 'profile')
                ->where('login_type', 2)
                ->where('token', $instanceId)
                ->get()
            : $this->getAccounts($teamId);

        if ($accounts->isEmpty()) {
            return response()->json([
                'status' => 0,
                'message' => __('Profile does not exist.'),
            ]);
        }

        foreach ($accounts as $account) {
            $existing = DB::table('whatsapp_autoresponder')
                ->where('team_id', $teamId)
                ->where('id_secure', $account->id_secure)
                ->first();

            $data = [
                'team_id' => $teamId,
                'id_secure' => $account->id_secure,
                'type' => 1,
                'template' => 0,
                'instance_id' => $account->token,
                'caption' => $caption !== '' ? $caption : null,
                'media' => $media,
                'except' => $except,
                'delay' => $delay,
                'send_to' => $sendTo,
                'status' => $status,
                'changed' => time(),
            ];

            if ($existing) {
                DB::table('whatsapp_autoresponder')
                    ->where('id', $existing->id)
                    ->update($data);
            } else {
                $data['created'] = time();
                DB::table('whatsapp_autoresponder')->insert($data);
            }
        }

        return response()->json([
            'status' => 1,
            'message' => __('Succeeded'),
        ]);
    }

    protected function getAccounts(int $teamId)
    {
        return DB::table('accounts')
            ->where('team_id', $teamId)
            ->where('social_network', 'whatsapp_unofficial')
            ->where('category', 'profile')
            ->where('login_type', 2)
            ->where('status', 1)
            ->orderBy('created')
            ->get();
    }

    protected function normalizeMediaUrl(?string $media): ?string
    {
        $media = trim((string) $media);
        if ($media === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $media)) {
            return $media;
        }

        return Media::url($media);
    }
}
