<?php

namespace Modules\AppWhatsAppChatbot\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Media;

class AppWhatsAppChatbotController extends Controller
{
    public function index(Request $request)
    {
        $accounts = $this->getAccounts($request->team_id);
        $stats = [
            'accounts' => $accounts->count(),
            'items' => (int) DB::table('whatsapp_chatbot')->where('team_id', $request->team_id)->count(),
        ];

        return view(module('key') . '::index', compact('accounts', 'stats'));
    }

    public function info(Request $request)
    {
        $teamId = (int) $request->team_id;
        $accountKey = trim((string) $request->input('account', ''));
        $itemKey = trim((string) $request->input('item', ''));

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
                    'message' => __('WhatsApp account does not exist. Please reconnect the profile and try again.'),
                    'account' => null,
                    'result' => null,
                    'items' => collect(),
                    'selectedFiles' => false,
                    'run' => 0,
                ])->render(),
            ]);
        }

        $items = DB::table('whatsapp_chatbot')
            ->where('team_id', $teamId)
            ->where('instance_id', $account->token)
            ->orderByDesc('created')
            ->get();

        $run = (int) DB::table('whatsapp_chatbot')
            ->where('team_id', $teamId)
            ->where('instance_id', $account->token)
            ->max('run');

        $result = null;
        if ($itemKey !== '') {
            $result = DB::table('whatsapp_chatbot')
                ->where('team_id', $teamId)
                ->where('instance_id', $account->token)
                ->where('id_secure', $itemKey)
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
                'items' => $items,
                'selectedFiles' => $selectedFiles,
                'run' => $run,
            ])->render(),
        ]);
    }

    public function save(Request $request)
    {
        $teamId = (int) $request->team_id;
        $itemKey = trim((string) $request->input('id_secure', ''));
        $instanceId = trim((string) $request->input('instance_id', ''));
        $name = trim((string) $request->input('name', ''));
        $keywords = $this->sanitizeKeywords((string) $request->input('keywords', ''));
        $caption = trim((string) $request->input('caption', ''));
        $typeSearch = (int) $request->input('type_search', 1);
        $sendTo = (int) $request->input('send_to', 1);
        $status = (int) $request->input('status', 1);
        $selectedMedias = array_values(array_filter(array_map('trim', (array) $request->input('medias', []))));
        $media = $selectedMedias[0] ?? trim((string) $request->input('media', ''));
        $media = $this->normalizeMediaUrl($media);

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

        if ((int) $account->status === 0) {
            return response()->json([
                'status' => 0,
                'message' => __('Relogin is required.'),
            ]);
        }

        if ($name === '') {
            return response()->json([
                'status' => 0,
                'message' => __('Bot name is required.'),
            ]);
        }

        if (mb_strlen($name) > 100) {
            return response()->json([
                'status' => 0,
                'message' => __('Bot name may not be greater than 100 characters.'),
            ]);
        }

        if ($keywords === '') {
            return response()->json([
                'status' => 0,
                'message' => __('Keywords are required.'),
            ]);
        }

        if ($caption === '' && $media === null) {
            return response()->json([
                'status' => 0,
                'message' => __('Please enter a caption or add media.'),
            ]);
        }

        $existing = null;
        if ($itemKey !== '') {
            $existing = DB::table('whatsapp_chatbot')
                ->where('team_id', $teamId)
                ->where('instance_id', $instanceId)
                ->where('id_secure', $itemKey)
                ->first();
        }

        if (!$existing) {
            $limit = (int) \Access::permission('whatsapp_chatbot_item_limit', 0);
            $count = (int) DB::table('whatsapp_chatbot')
                ->where('team_id', $teamId)
                ->where('instance_id', $instanceId)
                ->count();

            if ($limit > 0 && $count >= $limit) {
                return response()->json([
                    'status' => 0,
                    'message' => sprintf(__('You can only add a maximum of %s chatbot items.'), $limit),
                ]);
            }
        }

        $run = (int) DB::table('whatsapp_chatbot')
            ->where('team_id', $teamId)
            ->where('instance_id', $instanceId)
            ->max('run');

        $data = [
            'team_id' => $teamId,
            'instance_id' => $instanceId,
            'name' => $name,
            'type' => 1,
            'type_search' => in_array($typeSearch, [1, 2], true) ? $typeSearch : 1,
            'template' => 0,
            'keywords' => $keywords,
            'caption' => $caption !== '' ? $caption : null,
            'media' => $media,
            'run' => $run,
            'send_to' => in_array($sendTo, [1, 2, 3], true) ? $sendTo : 1,
            'status' => $status ? 1 : 0,
            'changed' => time(),
        ];

        if ($existing) {
            DB::table('whatsapp_chatbot')->where('id', $existing->id)->update($data);
            $itemKey = $existing->id_secure;
        } else {
            $data['id_secure'] = rand_string();
            $data['created'] = time();
            DB::table('whatsapp_chatbot')->insert($data);
            $itemKey = $data['id_secure'];
        }

        return response()->json([
            'status' => 1,
            'message' => __('Succeeded'),
            'item_id' => $itemKey,
        ]);
    }

    public function status(Request $request, string $instance_id)
    {
        $teamId = (int) $request->team_id;
        $items = DB::table('whatsapp_chatbot')
            ->where('team_id', $teamId)
            ->where('instance_id', $instance_id)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 0,
                'message' => __('Please add at least a chatbot item to start.'),
            ]);
        }

        $run = (int) $items->max('run') ? 0 : 1;

        DB::table('whatsapp_chatbot')
            ->where('team_id', $teamId)
            ->where('instance_id', $instance_id)
            ->update([
                'run' => $run,
                'changed' => time(),
            ]);

        return response()->json([
            'status' => 1,
            'message' => __('Succeeded'),
            'run' => $run,
        ]);
    }

    public function delete(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $ids = $request->input('id', $id_secure);

        if (empty($ids)) {
            return response()->json([
                'status' => 0,
                'message' => __('Please select an item to delete.'),
            ]);
        }

        $ids = is_array($ids) ? $ids : [$ids];

        DB::table('whatsapp_chatbot')
            ->where('team_id', $teamId)
            ->whereIn('id_secure', array_filter($ids))
            ->delete();

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

    protected function sanitizeKeywords(string $keywords): string
    {
        $items = collect(explode(',', $keywords))
            ->map(fn ($item) => trim(mb_strtolower($item)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return implode(',', $items);
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
