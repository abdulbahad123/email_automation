<?php

namespace Modules\AppWhatsAppParticipantsExport\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppWhatsAppParticipantsExportController extends Controller
{
    protected string $serverUrl = '';
    protected string $contactTable = 'whatsapp_contacts';
    protected string $phoneTable = 'whatsapp_phone_numbers';

    public function __construct()
    {
        $this->serverUrl = rtrim((string) get_option('whatsapp_server_url', ''), '/');
    }

    public function index(Request $request)
    {
        $accounts = $this->getAccounts((int) $request->team_id);

        return view(module('key') . '::index', [
            'accounts' => $accounts,
            'stats' => [
                'accounts' => $accounts->count(),
                'connected' => $accounts->where('status', 1)->count(),
            ],
        ]);
    }

    public function groups(Request $request)
    {
        $teamId = (int) $request->team_id;
        $accountKey = trim((string) $request->input('account', ''));
        $account = $this->findAccount($teamId, $accountKey);

        if (!$account) {
            return response()->json([
                'status' => 0,
                'data' => view(module('key') . '::groups', [
                    'status' => 'error',
                    'message' => __('WhatsApp account does not exist. Please reconnect the profile and try again.'),
                    'account' => null,
                    'groups' => collect(),
                ])->render(),
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
        }

        if (!$this->serverUrl) {
            return response()->json([
                'status' => 0,
                'data' => view(module('key') . '::groups', [
                    'status' => 'error',
                    'message' => __('WhatsApp server URL has not been configured yet.'),
                    'account' => $account,
                    'groups' => collect(),
                ])->render(),
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
        }

        $response = $this->waRequest('get_groups', [
            'instance_id' => $account->token,
            'access_token' => $request->team->id_secure ?? '',
        ]);

        if (!$response['ok']) {
            return response()->json([
                'status' => 0,
                'data' => view(module('key') . '::groups', [
                    'status' => 'error',
                    'message' => $this->cleanUtf8((string) $response['message']),
                    'account' => $account,
                    'groups' => collect(),
                ])->render(),
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
        }

        $groups = collect($this->extractGroups($response['data']))
            ->map(function ($group) {
                $participants = collect($group['participants'] ?? []);

                return [
                    'id' => $this->cleanUtf8((string) ($group['id'] ?? '')),
                    'name' => $this->cleanUtf8((string) ($group['name'] ?? __('Untitled group'))),
                    'size' => (int) ($group['size'] ?? $participants->count()),
                    'participants' => $participants->values()->all(),
                ];
            })
            ->filter(fn ($group) => $group['id'] !== '')
            ->values();

        return response()->json([
            'status' => 1,
            'data' => view(module('key') . '::groups', [
                'status' => 'success',
                'message' => $this->cleanUtf8((string) $response['message']),
                'account' => $account,
                'groups' => $groups,
            ])->render(),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function exportGroup(Request $request, string $account_id, string $group_id)
    {
        $teamId = (int) $request->team_id;
        $account = $this->findAccount($teamId, $account_id);

        if (!$account || !$this->serverUrl) {
            return redirect()->route('app.whatsappparticipantsexport.index');
        }

        $group = $this->findWhatsAppGroup($request, $account, $group_id);
        if (!$group) {
            return redirect()->route('app.whatsappparticipantsexport.index');
        }

        $participants = collect($group['participants'] ?? [])->map(function ($participant) {
            $jid = is_array($participant)
                ? $this->cleanUtf8((string) ($participant['jid'] ?? $participant['id'] ?? ''))
                : $this->cleanUtf8((string) data_get($participant, 'jid', data_get($participant, 'id', '')));

            $phone = strstr($jid, '@', true) ?: $jid;

            return [
                'phone' => preg_replace('/\D+/', '', $phone),
            ];
        })->filter(fn ($item) => $item['phone'] !== '')->values()->all();

        $filename = $this->slug($this->cleanUtf8((string) ($group['name'] ?? 'group'))) . '-participants-' . date('Y-m-d') . '.csv';

        return new StreamedResponse(function () use ($participants) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['phone']);
            foreach ($participants as $row) {
                fputcsv($handle, [$row['phone']]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function popupImport(Request $request, string $account_id, string $group_id)
    {
        $teamId = (int) $request->team_id;
        $account = $this->findAccount($teamId, $account_id);

        if (!$account) {
            return response()->json([
                'status' => 0,
                'message' => __('WhatsApp account does not exist.'),
            ]);
        }

        $group = $this->findWhatsAppGroup($request, $account, $group_id);
        if (!$group) {
            return response()->json([
                'status' => 0,
                'message' => __('WhatsApp group does not exist or could not be loaded.'),
            ]);
        }

        $contacts = DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->orderBy('name')
            ->get(['id_secure', 'name', 'status']);

        return response()->json([
            'status' => 1,
            'data' => view(module('key') . '::popup_import_contacts', [
                'account' => $account,
                'group' => $group,
                'contacts' => $contacts,
            ])->render(),
        ]);
    }

    public function importToContacts(Request $request, string $account_id, string $group_id)
    {
        $teamId = (int) $request->team_id;
        $account = $this->findAccount($teamId, $account_id);

        if (!$account) {
            return response()->json([
                'status' => 0,
                'message' => __('WhatsApp account does not exist.'),
            ]);
        }

        $contactIds = $request->input('contact_ids', []);
        $contactIds = is_array($contactIds) ? array_values(array_filter($contactIds)) : [];

        if (empty($contactIds)) {
            return response()->json([
                'status' => 0,
                'message' => __('Please select at least one contact group.'),
            ]);
        }

        $group = $this->findWhatsAppGroup($request, $account, $group_id);
        if (!$group) {
            return response()->json([
                'status' => 0,
                'message' => __('WhatsApp group does not exist or could not be loaded.'),
            ]);
        }

        $phones = collect($group['participants'] ?? [])
            ->map(function ($participant) {
                $jid = is_array($participant)
                    ? $this->cleanUtf8((string) ($participant['jid'] ?? $participant['id'] ?? ''))
                    : $this->cleanUtf8((string) data_get($participant, 'jid', data_get($participant, 'id', '')));

                $phone = strstr($jid, '@', true) ?: $jid;
                return preg_replace('/\D+/', '', $phone);
            })
            ->filter()
            ->unique()
            ->values();

        if ($phones->isEmpty()) {
            return response()->json([
                'status' => 0,
                'message' => __('No valid participants were found to import.'),
            ]);
        }

        $contacts = DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->whereIn('id_secure', $contactIds)
            ->get();

        if ($contacts->isEmpty()) {
            return response()->json([
                'status' => 0,
                'message' => __('Selected contact groups do not exist.'),
            ]);
        }

        $maxPhoneNumbers = (int) \Access::permission('whatsapp_bulk_max_phone_numbers');
        $inserted = 0;

        foreach ($contacts as $contact) {
            $existingPhones = DB::table($this->phoneTable)
                ->where('team_id', $teamId)
                ->where('pid', $contact->id)
                ->pluck('phone')
                ->map(fn ($phone) => $this->normalizePhone((string) $phone))
                ->filter()
                ->flip();

            $currentCount = $existingPhones->count();
            $rows = [];

            foreach ($phones as $phone) {
                $normalizedPhone = $this->normalizePhone((string) $phone);
                if ($normalizedPhone === '' || isset($existingPhones[$normalizedPhone])) {
                    continue;
                }

                if ($maxPhoneNumbers > 0 && ($currentCount + count($rows)) >= $maxPhoneNumbers) {
                    break;
                }

                $rows[] = [
                    'id_secure' => rand_string(),
                    'team_id' => $teamId,
                    'pid' => $contact->id,
                    'phone' => $normalizedPhone,
                    'params' => null,
                ];
                $existingPhones[$normalizedPhone] = true;
            }

            if (!empty($rows)) {
                DB::table($this->phoneTable)->insert($rows);
                $inserted += count($rows);
            }
        }

        return response()->json([
            'status' => 1,
            'message' => trans_choice(':count phone numbers imported successfully.', $inserted, ['count' => $inserted]),
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

    protected function findAccount(int $teamId, string $accountId)
    {
        return DB::table('accounts')
            ->where('team_id', $teamId)
            ->where('social_network', 'whatsapp_unofficial')
            ->where('category', 'profile')
            ->where('login_type', 2)
            ->where('id_secure', $accountId)
            ->first();
    }

    protected function findWhatsAppGroup(Request $request, object $account, string $groupId): ?array
    {
        if (!$this->serverUrl) {
            return null;
        }

        $response = $this->waRequest('get_groups', [
            'instance_id' => $account->token,
            'access_token' => $request->team->id_secure ?? '',
        ]);

        if (!$response['ok']) {
            return null;
        }

        $group = collect($this->extractGroups($response['data']))
            ->first(fn ($item) => (string) ($item['id'] ?? '') === $groupId);

        return is_array($group) ? $group : null;
    }

    protected function normalizePhone(string $phone): string
    {
        return trim(str_replace(['+', ' ', "'", '`', '"'], '', $phone));
    }

    protected function extractGroups($payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        return $payload;
    }

    protected function cleanUtf8(?string $value): string
    {
        $value = (string) ($value ?? '');

        if ($value === '') {
            return '';
        }

        $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        if ($converted === false) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: $value;
        }

        return $converted;
    }

    protected function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: 'group';
        return trim($value, '-');
    }

    protected function waRequest(string $endpoint, array $params = []): array
    {
        try {
            $result = $this->waGetCurlLike($endpoint, $params);

            if (!$result) {
                return [
                    'ok' => false,
                    'data' => null,
                    'message' => __('Cannot connect to WhatsApp server. Please make sure the WhatsApp server is running.'),
                ];
            }

            $data = json_decode(json_encode($result), true);
            $status = $data['status'] ?? null;

            if ($status === 'error' || $status === 0 || $status === '0' || $status === false) {
                return [
                    'ok' => false,
                    'data' => $data,
                    'message' => $data['message'] ?? __('Unknown WhatsApp server response.'),
                ];
            }

            return [
                'ok' => true,
                'data' => $data,
                'message' => $data['message'] ?? __('Succeeded'),
            ];
        } catch (\Throwable $e) {
            Log::warning('WhatsApp export participants request failed', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage(),
            ]);
        }

        return [
            'ok' => false,
            'data' => null,
            'message' => __('Cannot connect to WhatsApp server. Please make sure the WhatsApp server is running.'),
        ];
    }

    protected function waGetCurlLike(string $endpoint, array $params = [])
    {
        $url = $this->serverUrl . '/' . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error) {
            return false;
        }

        return json_decode($response, true);
    }
}