<?php

namespace Modules\AppWhatsAppBulk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Media;

class AppWhatsAppBulkController extends Controller
{
    public array $Datatable;
    protected string $scheduleTable = 'whatsapp_schedules';
    protected string $contactTable = 'whatsapp_contacts';
    protected string $phoneTable = 'whatsapp_phone_numbers';

    public function __construct()
    {
        $phoneSubquery = '(SELECT COUNT(*) FROM whatsapp_phone_numbers WHERE whatsapp_phone_numbers.pid = whatsapp_schedules.contact_id AND whatsapp_phone_numbers.team_id = whatsapp_schedules.team_id)';
        $pendingSubquery = 'GREATEST((' . $phoneSubquery . ') - COALESCE(whatsapp_schedules.sent, 0) - COALESCE(whatsapp_schedules.failed, 0), 0)';

        $this->Datatable = [
            'element' => 'DataTable',
            'order' => ['created', 'desc'],
            'lengthMenu' => [10, 25, 50, 100, 150, 200],
            'search_field' => ['whatsapp_schedules.name', 'whatsapp_schedules.caption', 'whatsapp_contacts.name'],
            'columns' => [
                ['data' => 'id_secure', 'name' => 'id_secure', 'className' => 'w-40'],
                ['data' => 'name', 'name' => 'name', 'title' => __('Campaign')],
                ['data' => 'contact_name', 'name' => 'whatsapp_contacts.name', 'alias' => 'contact_name', 'title' => __('Contact group')],
                ['data' => 'accounts_count', 'name' => 'JSON_LENGTH(whatsapp_schedules.accounts)', 'alias' => 'accounts_count', 'title' => __('Profiles')],
                ['data' => 'sent', 'name' => 'sent', 'title' => __('Sent')],
                ['data' => 'failed', 'name' => 'failed', 'title' => __('Failed')],
                ['data' => 'pending', 'name' => $pendingSubquery, 'alias' => 'pending', 'title' => __('Pending')],
                ['data' => 'time_post', 'name' => 'time_post', 'type' => 'datetime', 'title' => __('Next action')],
                ['data' => 'status', 'name' => 'status', 'title' => __('Status')],
                ['data' => 'caption', 'name' => 'caption', 'title' => __('Caption')],
                ['data' => 'min_delay', 'name' => 'min_delay', 'title' => __('Min delay')],
                ['data' => 'max_delay', 'name' => 'max_delay', 'title' => __('Max delay')],
                ['data' => 'total_phone_number', 'name' => $phoneSubquery, 'alias' => 'total_phone_number', 'title' => __('Total contacts')],
                ['title' => __('Action'), 'className' => 'text-end'],
            ],
            'status_filter' => [
                ['value' => '-1', 'label' => __('All')],
                ['value' => '1', 'name' => 'run', 'icon' => 'fa-light fa-play', 'color' => 'success', 'label' => __('Running')],
                ['value' => '0', 'name' => 'pause', 'icon' => 'fa-light fa-pause', 'color' => 'warning', 'label' => __('Paused')],
                ['value' => '2', 'name' => 'completed', 'icon' => 'fa-light fa-circle-check', 'color' => 'info', 'label' => __('Completed')],
            ],
            'actions' => [
                [
                    'url' => route('app.whatsappbulk.actions', ['action' => 'run']),
                    'icon' => 'fa-light fa-play',
                    'label' => __('Run selected'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                [
                    'url' => route('app.whatsappbulk.actions', ['action' => 'pause']),
                    'icon' => 'fa-light fa-pause',
                    'label' => __('Pause selected'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                ['divider' => true],
                [
                    'url' => route('app.whatsappbulk.actions', ['action' => 'delete']),
                    'icon' => 'fa-light fa-trash-can-list',
                    'label' => __('Delete selected'),
                    'confirm' => __('Are you sure you want to delete the selected campaigns?'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                [
                    'url' => route('app.whatsappbulk.delete_all'),
                    'icon' => 'fa-light fa-trash-can-list',
                    'label' => __('Delete all campaigns'),
                    'confirm' => __('Are you sure you want to delete all bulk campaigns?'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
            ],
        ];
    }

    public function index(Request $request)
    {
        $teamId = (int) $request->team_id;

        $stats = [
            'total' => (int) DB::table($this->scheduleTable)->where('team_id', $teamId)->count(),
            'running' => (int) DB::table($this->scheduleTable)->where('team_id', $teamId)->where('status', 1)->count(),
            'paused' => (int) DB::table($this->scheduleTable)->where('team_id', $teamId)->where('status', 0)->count(),
            'completed' => (int) DB::table($this->scheduleTable)->where('team_id', $teamId)->where('status', 2)->count(),
        ];

        return view('appwhatsappbulk::index', [
            'stats' => $stats,
            'Datatable' => $this->Datatable,
        ]);
    }

    public function list(Request $request)
    {
        $whereConditions = [
            'whatsapp_schedules.team_id' => (int) $request->team_id,
        ];

        $joins = [
            [
                'table' => 'whatsapp_contacts',
                'first' => 'whatsapp_contacts.id',
                'second' => 'whatsapp_schedules.contact_id',
                'type' => 'left',
            ],
        ];

        $dataTableService = \DataTable::make($this->scheduleTable, $this->Datatable, $whereConditions, $joins);
        $data = $dataTableService->getData($request);

        return response()->json($data);
    }

    public function update(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $result = null;
        if ($id_secure) {
            $result = DB::table($this->scheduleTable)
                ->where('team_id', $teamId)
                ->where('id_secure', $id_secure)
                ->first();
        }

        $accounts = DB::table('accounts')
            ->where('team_id', $teamId)
            ->where('social_network', 'whatsapp_unofficial')
            ->where('category', 'profile')
            ->where('login_type', 2)
            ->where('status', 1)
            ->orderByDesc('created')
            ->get();

        $contacts = DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $selectedAccounts = $this->decodeAccounts($result->accounts ?? '[]');
        $selectedFiles = !empty($result?->media) ? [$result->media] : false;
        $scheduleHours = $this->decodeScheduleHours($result->schedule_time ?? '');

        return view('appwhatsappbulk::update', compact('result', 'accounts', 'contacts', 'selectedAccounts', 'selectedFiles', 'scheduleHours'));
    }

    public function save(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $name = trim((string) $request->input('name', ''));
        $caption = trim((string) $request->input('caption', ''));
        $accounts = (array) $request->input('accounts', []);
        $contactIdSecure = trim((string) $request->input('contact_group', ''));
        $timePostRaw = trim((string) $request->input('time_post', ''));
        $minDelay = max(1, (int) $request->input('min_interval_per_post', 1));
        $maxDelay = max(1, (int) $request->input('max_interval_per_post', 1));
        $scheduleHours = array_values(array_filter(array_map(function ($hour) {
            return is_numeric($hour) && (int) $hour >= 0 && (int) $hour <= 23 ? (string) (int) $hour : null;
        }, (array) $request->input('schedule_time', []))));
        $mediaList = (array) $request->input('medias', []);
        $media = !empty($mediaList[0]) ? (string) $mediaList[0] : null;
        $media = $this->normalizeMediaUrl($media);

        if ($name === '') {
            return response()->json(['status' => 0, 'message' => __('Campaign name is required.')]);
        }

        if (empty($accounts)) {
            return response()->json(['status' => 0, 'message' => __('Please select at least a profile')]);
        }

        if ($contactIdSecure === '') {
            return response()->json(['status' => 0, 'message' => __('Please select a contact group')]);
        }

        if ($caption === '' && !$media) {
            return response()->json(['status' => 0, 'message' => __('Please enter a caption or add a media')]);
        }

        if ($minDelay > $maxDelay) {
            return response()->json(['status' => 0, 'message' => __('Max interval must be greater than or equal to min interval')]);
        }

        $accountRows = DB::table('accounts')
            ->where('team_id', $teamId)
            ->where('social_network', 'whatsapp_unofficial')
            ->where('category', 'profile')
            ->where('login_type', 2)
            ->where('status', 1)
            ->whereIn('id_secure', $accounts)
            ->get(['id']);

        if ($accountRows->isEmpty()) {
            return response()->json(['status' => 0, 'message' => __('You need to log in again to access your selected WhatsApp accounts.')]);
        }

        $contact = DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->where('status', 1)
            ->where('id_secure', $contactIdSecure)
            ->first();

        if (!$contact) {
            return response()->json(['status' => 0, 'message' => __('Please select a contact group')]);
        }

        $timePost = $timePostRaw !== '' ? (int) timestamp_sql($timePostRaw) : 0;
        if ($timePost <= 0) {
            return response()->json(['status' => 0, 'message' => __('Time post is required.')]);
        }

        $existing = null;
        if ($id_secure) {
            $existing = DB::table($this->scheduleTable)->where('team_id', $teamId)->where('id_secure', $id_secure)->first();
        }

        $data = [
            'team_id' => $teamId,
            'type' => 1,
            'template' => 0,
            'accounts' => json_encode($accountRows->pluck('id')->values()->all()),
            'contact_id' => $contact->id,
            'time_post' => $timePost,
            'min_delay' => $minDelay,
            'max_delay' => $maxDelay,
            'schedule_time' => !empty($scheduleHours) ? json_encode($scheduleHours) : '',
            'timezone' => (auth()->user()->timezone ?? config('app.timezone')),
            'name' => $name,
            'caption' => $caption,
            'media' => $media,
            'run' => 0,
            'changed' => time(),
        ];

        if ($existing) {
            DB::table($this->scheduleTable)->where('id', $existing->id)->update($data);
        } else {
            $runningCount = (int) DB::table($this->scheduleTable)->where('team_id', $teamId)->where('status', 1)->count();
            $maxRun = (int) \Access::permission('whatsapp_bulk_max_run');
            $status = ($maxRun > 0 && $runningCount >= $maxRun) ? 0 : 1;

            $data = array_merge($data, [
                'id_secure' => rand_string(),
                'status' => $status,
                'sent' => 0,
                'failed' => 0,
                'next_account' => 0,
                'result' => '',
                'created' => time(),
            ]);

            DB::table($this->scheduleTable)->insert($data);
        }

        return response()->json([
            'status' => 1,
            'message' => __('Success'),
            'redirect' => route('app.whatsappbulk.index'),
        ]);
    }

    public function status(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $item = DB::table($this->scheduleTable)->where('team_id', $teamId)->where('id_secure', $id_secure)->first();

        if (!$item) {
            return response()->json(['status' => 0, 'message' => __('The bulk campaign was not found')]);
        }

        if ((int) $item->status === 2) {
            return response()->json(['status' => 0, 'message' => __('The campaign has been completed.')]);
        }

        if ((int) $item->status === 1) {
            DB::table($this->scheduleTable)->where('id', $item->id)->update(['status' => 0, 'run' => 0, 'changed' => time()]);
        } else {
            $runningCount = (int) DB::table($this->scheduleTable)->where('team_id', $teamId)->where('status', 1)->count();
            $maxRun = (int) \Access::permission('whatsapp_bulk_max_run');
            if ($maxRun > 0 && $runningCount >= $maxRun) {
                return response()->json(['status' => 0, 'message' => sprintf(__('You can only run a maximum of %s campaigns at the same time.'), $maxRun)]);
            }

            DB::table($this->scheduleTable)->where('id', $item->id)->update(['status' => 1, 'run' => 0, 'changed' => time()]);
        }

        return response()->json(['status' => 1, 'message' => __('Success')]);
    }

    public function delete(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $ids = $request->input('id', $id_secure);
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            return response()->json(['status' => 0, 'message' => __('Please select an item to delete')]);
        }

        DB::table($this->scheduleTable)->where('team_id', $teamId)->whereIn('id_secure', $ids)->delete();
        return response()->json(['status' => 1, 'message' => __('Success')]);
    }

    public function deleteAll(Request $request)
    {
        $teamId = (int) $request->team_id;

        DB::table($this->scheduleTable)
            ->where('team_id', $teamId)
            ->delete();

        return response()->json(['status' => 1, 'message' => __('Success')]);
    }

    public function actions(Request $request, string $action)
    {
        $teamId = (int) $request->team_id;
        $ids = (array) $request->input('id', []);
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            return response()->json(['status' => 0, 'message' => __('Please select at least one campaign.')]);
        }

        $items = DB::table($this->scheduleTable)
            ->where('team_id', $teamId)
            ->whereIn('id_secure', $ids)
            ->get(['id', 'id_secure', 'status']);

        if ($items->isEmpty()) {
            return response()->json(['status' => 0, 'message' => __('The selected campaigns were not found.')]);
        }

        switch ($action) {
            case 'pause':
                DB::table($this->scheduleTable)
                    ->where('team_id', $teamId)
                    ->whereIn('id_secure', $ids)
                    ->where('status', '!=', 2)
                    ->update([
                        'status' => 0,
                        'run' => 0,
                        'changed' => time(),
                    ]);

                return response()->json(['status' => 1, 'message' => __('Success')]);

            case 'run':
                $maxRun = (int) \Access::permission('whatsapp_bulk_max_run');
                $runningOutsideSelection = (int) DB::table($this->scheduleTable)
                    ->where('team_id', $teamId)
                    ->where('status', 1)
                    ->whereNotIn('id_secure', $ids)
                    ->count();

                $runCandidates = $items
                    ->filter(fn ($item) => (int) $item->status !== 2)
                    ->pluck('id_secure')
                    ->values();

                if ($runCandidates->isEmpty()) {
                    return response()->json(['status' => 0, 'message' => __('The selected campaigns have already been completed.')]);
                }

                if ($maxRun > 0) {
                    $availableSlots = max(0, $maxRun - $runningOutsideSelection);
                    if ($availableSlots <= 0) {
                        return response()->json(['status' => 0, 'message' => sprintf(__('You can only run a maximum of %s campaigns at the same time.'), $maxRun)]);
                    }

                    $runCandidates = $runCandidates->take($availableSlots);
                }

                DB::table($this->scheduleTable)
                    ->where('team_id', $teamId)
                    ->whereIn('id_secure', $runCandidates->all())
                    ->update([
                        'status' => 1,
                        'run' => 0,
                        'changed' => time(),
                    ]);

                $message = __('Success');
                if ($maxRun > 0 && $runCandidates->count() < $items->where('status', '!=', 2)->count()) {
                    $message = __('Some campaigns were left paused because the running limit was reached.');
                }

                return response()->json(['status' => 1, 'message' => $message]);

            case 'delete':
                DB::table($this->scheduleTable)
                    ->where('team_id', $teamId)
                    ->whereIn('id_secure', $ids)
                    ->delete();

                return response()->json(['status' => 1, 'message' => __('Success')]);
        }

        return response()->json(['status' => 0, 'message' => __('Invalid action.')]);
    }

    protected function decodeAccounts($accounts): array
    {
        if (is_array($accounts)) {
            return array_values(array_filter(array_map('intval', $accounts)));
        }

        $decoded = json_decode((string) $accounts, true);
        return is_array($decoded) ? array_values(array_filter(array_map('intval', $decoded))) : [];
    }

    protected function decodeScheduleHours($hours): array
    {
        $decoded = json_decode((string) $hours, true);
        return is_array($decoded) ? array_map('strval', array_values($decoded)) : [];
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
