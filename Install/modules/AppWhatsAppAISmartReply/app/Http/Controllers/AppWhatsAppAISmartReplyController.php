<?php

namespace Modules\AppWhatsAppAISmartReply\Http\Controllers;

use AI;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppWhatsAppAISmartReplyController extends Controller
{
    protected string $table = 'whatsapp_ai_smart_reply';

    public function index(Request $request)
    {
        $accounts = $this->getAccounts((int) $request->team_id);

        return view(module('key') . '::index', [
            'accounts' => $accounts,
        ]);
    }

    public function info(Request $request)
    {
        $teamId = (int) $request->team_id;
        $accountKey = trim((string) $request->input('account', ''));
        $account = null;
        $result = null;

        if ($accountKey !== '') {
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
                    ])->render(),
                ]);
            }

            $result = DB::table($this->table)
                ->where('team_id', $teamId)
                ->where('instance_id', $account->token)
                ->orderByDesc('id')
                ->first();
        }

        return response()->json([
            'status' => 1,
            'data' => view(module('key') . '::info', [
                'status' => 'success',
                'account' => $account,
                'result' => $result,
            ])->render(),
        ]);
    }

    public function save(Request $request)
    {
        $teamId = (int) $request->team_id;
        $status = (int) $request->input('status', 1);
        $prompt = trim((string) $request->input('prompt', ''));
        $fallbackCaption = trim((string) $request->input('fallback_caption', ''));
        $delay = (int) $request->input('delay', 1);
        $instanceId = trim((string) $request->input('instance_id', ''));
        $except = $this->sanitizeExcept((string) $request->input('except', ''));
        $sendTo = (int) $request->input('send_to', 1);
        $maxLength = (int) $request->input('max_length', 120);

        if ($prompt === '') {
            return response()->json([
                'status' => 0,
                'message' => __('AI instructions are required.'),
            ]);
        }

        if ($delay < 1) {
            return response()->json([
                'status' => 0,
                'message' => __('Delay is required.'),
            ]);
        }

        $maxLength = max(30, min($maxLength, 1000));

        $account = DB::table('accounts')
            ->where('team_id', $teamId)
            ->where('social_network', 'whatsapp_unofficial')
            ->where('category', 'profile')
            ->where('login_type', 2)
            ->where('token', $instanceId)
            ->first();

        if (!$account) {
            return response()->json([
                'status' => 0,
                'message' => __('Please select a WhatsApp profile.'),
            ]);
        }

        $existing = DB::table($this->table)
            ->where('team_id', $teamId)
            ->where('instance_id', $instanceId)
            ->first();

        $data = [
            'team_id' => $teamId,
            'instance_id' => $instanceId,
            'prompt' => $prompt,
            'fallback_caption' => $fallbackCaption !== '' ? $fallbackCaption : null,
            'except' => $except,
            'delay' => $delay,
            'send_to' => in_array($sendTo, [1, 2, 3], true) ? $sendTo : 1,
            'max_length' => $maxLength,
            'status' => $status ? 1 : 0,
            'changed' => time(),
        ];

        if ($existing) {
            DB::table($this->table)->where('id', $existing->id)->update($data);
        } else {
            $data['id_secure'] = rand_string();
            $data['created'] = time();
            DB::table($this->table)->insert($data);
        }

        return response()->json([
            'status' => 1,
            'message' => __('Succeeded'),
        ]);
    }

    public function generate(Request $request)
    {
        $accessToken = trim((string) $request->query('access_token', ''));
        $instanceId = trim((string) $request->query('instance_id', ''));
        $message = trim((string) $request->query('message', ''));
        $chatId = trim((string) $request->query('chat_id', ''));

        if ($accessToken === '' || $instanceId === '' || $message === '') {
            return response()->json([
                'status' => 0,
                'message' => __('Missing required AI smart reply data.'),
            ]);
        }

        $team = DB::table('teams')->where('id_secure', $accessToken)->first();
        if (!$team) {
            return response()->json([
                'status' => 0,
                'message' => __('Authentication failed.'),
            ]);
        }

        $account = DB::table('accounts')
            ->where('team_id', $team->id)
            ->where('social_network', 'whatsapp_unofficial')
            ->where('category', 'profile')
            ->where('login_type', 2)
            ->where('token', $instanceId)
            ->first();

        if (!$account) {
            return response()->json([
                'status' => 0,
                'message' => __('Profile does not exist.'),
            ]);
        }

        $rule = DB::table($this->table)
            ->where('team_id', $team->id)
            ->where('instance_id', $instanceId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->first();

        if (!$rule) {
            return response()->json([
                'status' => 0,
                'message' => __('AI smart reply is not enabled for this profile.'),
            ]);
        }

        $prompt = $this->buildPrompt((string) $rule->prompt, $message, (int) ($rule->max_length ?? 120), $chatId);

        try {
            $result = AI::process($prompt, 'text', [
                'maxResult' => 1,
            ], (int) $team->id);

            $reply = trim((string) ($result['data'][0] ?? ''));
            $reply = preg_replace('/^(assistant|reply)\s*[:\-]\s*/i', '', $reply);
            $reply = preg_replace('/\s+/', ' ', $reply ?? '');
            $reply = trim((string) $reply);

            if ($reply === '') {
                $reply = trim((string) ($rule->fallback_caption ?? ''));
            }

            if ($reply === '') {
                return response()->json([
                    'status' => 0,
                    'message' => __('AI did not return a usable reply.'),
                ]);
            }

            return response()->json([
                'status' => 1,
                'message' => __('Succeeded'),
                'data' => $reply,
            ]);
        } catch (\Throwable $e) {
            $fallback = trim((string) ($rule->fallback_caption ?? ''));
            if ($fallback !== '') {
                return response()->json([
                    'status' => 1,
                    'message' => __('Fallback reply returned.'),
                    'data' => $fallback,
                ]);
            }

            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function buildPrompt(string $instruction, string $incomingMessage, int $maxLength, string $chatId = ''): string
    {
        $chatHint = $chatId !== '' ? "Chat ID: {$chatId}\n" : '';

        return trim("You are an AI assistant that writes one WhatsApp reply only.\n" .
            "Follow the operator instruction below exactly.\n" .
            "Keep the reply concise, natural, and ready to send.\n" .
            "Reply in the same language as the customer unless the operator instruction explicitly says otherwise.\n" .
            "Do not include labels, markdown, explanations, or multiple options.\n" .
            "Target maximum length: {$maxLength} characters.\n\n" .
            "Operator instruction:\n{$instruction}\n\n" .
            $chatHint .
            "Customer message:\n{$incomingMessage}\n\n" .
            "Return only the final WhatsApp reply text.");
    }

    protected function sanitizeExcept(string $except): string
    {
        $except = preg_replace('/[^0-9,]/', '', $except);
        $except = preg_replace('/,+/', ',', $except);
        return trim((string) $except, ',');
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
}