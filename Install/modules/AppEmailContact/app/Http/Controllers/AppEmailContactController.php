<?php

namespace Modules\AppEmailContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Modules\AppEmailSMTP\Services\EmailSchemaService;

class AppEmailContactController extends Controller
{
    public array $Datatable;
    public array $EmailDatatable;
    protected string $groupTable = 'email_contact_groups';
    protected string $contactTable = 'email_contacts';

    public function __construct()
    {
        EmailSchemaService::ensureTables();

        $this->Datatable = [
            'element' => 'DataTable',
            'order' => ['created', 'desc'],
            'lengthMenu' => [10, 25, 50, 100],
            'search_field' => ['name'],
            'columns' => [
                ['data' => 'id_secure', 'name' => 'id_secure', 'className' => 'w-40'],
                ['data' => 'name', 'name' => 'name', 'title' => __('Group Info')],
                [
                    'data' => 'contacts_count',
                    'name' => '(SELECT COUNT(*) FROM email_contacts WHERE email_contacts.group_id = email_contact_groups.id AND email_contacts.team_id = email_contact_groups.team_id)',
                    'alias' => 'contacts_count',
                    'title' => __('Contacts'),
                ],
                ['data' => 'status', 'name' => 'status', 'title' => __('Status')],
                ['data' => 'changed', 'name' => 'changed', 'title' => __('Updated')],
                ['title' => __('Action'), 'className' => 'text-end'],
            ],
            'status_filter' => [
                ['value' => '-1', 'label' => __('All')],
                ['value' => '1', 'name' => 'enable', 'icon' => 'fa-light fa-eye', 'color' => 'success', 'label' => __('Enabled')],
                ['value' => '0', 'name' => 'disable', 'icon' => 'fa-light fa-eye-slash', 'color' => 'warning', 'label' => __('Disabled')],
            ],
            'actions' => [
                [
                    'url' => route('app.emailcontact.status', ['status' => 'enable']),
                    'icon' => 'fa-light fa-eye',
                    'label' => __('Enable'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                [
                    'url' => route('app.emailcontact.status', ['status' => 'disable']),
                    'icon' => 'fa-light fa-eye-slash',
                    'label' => __('Disable'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                ['divider' => true],
                [
                    'url' => route('app.emailcontact.delete'),
                    'icon' => 'fa-light fa-trash-can-list',
                    'label' => __('Delete'),
                    'confirm' => __('Are you sure you want to delete this contact group?'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
            ],
        ];

        $this->EmailDatatable = [
            'element' => 'DataTableEmails',
            'order' => ['id', 'desc'],
            'lengthMenu' => [10, 25, 50, 100, 200],
            'search_field' => ['email', 'name', 'params'],
            'columns' => [
                ['data' => 'id_secure', 'name' => 'id_secure', 'className' => 'w-40'],
                ['data' => 'email', 'name' => 'email', 'title' => __('Email')],
                ['data' => 'name', 'name' => 'name', 'title' => __('Name')],
                ['data' => 'params', 'name' => 'params', 'title' => __('Variables')],
                ['data' => 'unsubscribed', 'name' => 'unsubscribed', 'title' => __('Subscription')],
                ['title' => __('Action'), 'className' => 'text-end'],
            ],
            'actions' => [
                [
                    'url' => route('app.emailcontact.delete_email'),
                    'icon' => 'fa-light fa-trash-can-list',
                    'label' => __('Delete selected'),
                    'confirm' => __('Are you sure you want to delete the selected email contacts?'),
                    'call_success' => "Main.DataTable_Reload('#DataTableEmails')"
                ],
            ],
        ];
    }

    public function index(Request $request)
    {
        $teamId = (int) $request->team_id;

        $stats = [
            'groups' => (int) DB::table($this->groupTable)->where('team_id', $teamId)->count(),
            'enabled_groups' => (int) DB::table($this->groupTable)->where('team_id', $teamId)->where('status', 1)->count(),
            'disabled_groups' => (int) DB::table($this->groupTable)->where('team_id', $teamId)->where('status', 0)->count(),
            'contacts' => (int) DB::table($this->contactTable)->where('team_id', $teamId)->count(),
        ];

        return view('appemailcontact::index', [
            'stats' => $stats,
            'Datatable' => $this->Datatable,
        ]);
    }

    public function list(Request $request)
    {
        $whereConditions = [
            'team_id' => (int) $request->team_id,
        ];

        $dataTableService = \DataTable::make($this->groupTable, $this->Datatable, $whereConditions, []);
        $data = $dataTableService->getData($request);

        return response()->json($data);
    }

    public function popupUpdate(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $result = null;

        if ($id_secure) {
            $result = DB::table($this->groupTable)
                ->where('team_id', $teamId)
                ->where('id_secure', $id_secure)
                ->first();
        }

        return response()->json([
            'status' => 1,
            'data' => view('appemailcontact::popup_update', [
                'result' => $result,
            ])->render(),
        ]);
    }

    public function save(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $status = (int) $request->input('status', 1);
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            return response()->json([
                'status' => 0,
                'message' => __('Contact group name is required.'),
            ]);
        }

        $existing = null;
        if ($id_secure) {
            $existing = DB::table($this->groupTable)
                ->where('team_id', $teamId)
                ->where('id_secure', $id_secure)
                ->first();
        }

        $duplicate = DB::table($this->groupTable)
            ->where('team_id', $teamId)
            ->where('name', $name)
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->first();

        if ($duplicate) {
            return response()->json([
                'status' => 0,
                'message' => __('This contact group name already exists.'),
            ]);
        }

        $data = [
            'team_id' => $teamId,
            'name' => $name,
            'status' => $status ? 1 : 0,
            'changed' => time(),
        ];

        if ($existing) {
            DB::table($this->groupTable)->where('id', $existing->id)->update($data);
        } else {
            $data['id_secure'] = rand_string(16);
            $data['created'] = time();
            DB::table($this->groupTable)->insert($data);
        }

        return response()->json([
            'status' => 1,
            'message' => __('Success'),
            'redirect' => route('app.emailcontact.index'),
        ]);
    }

    public function status(Request $request, string $status = 'enable')
    {
        $teamId = (int) $request->team_id;
        $ids = (array) $request->input('id', []);
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            return response()->json(['status' => 0, 'message' => __('Please select at least one item')]);
        }

        DB::table($this->groupTable)
            ->where('team_id', $teamId)
            ->whereIn('id_secure', $ids)
            ->update([
                'status' => $status === 'enable' ? 1 : 0,
                'changed' => time(),
            ]);

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

        $groups = DB::table($this->groupTable)
            ->where('team_id', $teamId)
            ->whereIn('id_secure', $ids)
            ->get();

        foreach ($groups as $group) {
            DB::table($this->contactTable)->where('team_id', $teamId)->where('group_id', $group->id)->delete();
            DB::table($this->groupTable)->where('id', $group->id)->delete();
        }

        return response()->json(['status' => 1, 'message' => __('Success')]);
    }

    public function emails(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $group = DB::table($this->groupTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        abort_if(!$group, 404);

        $stats = [
            'total' => (int) DB::table($this->contactTable)->where('team_id', $teamId)->where('group_id', $group->id)->count(),
            'subscribed' => (int) DB::table($this->contactTable)->where('team_id', $teamId)->where('group_id', $group->id)->where('unsubscribed', 0)->count(),
            'unsubscribed' => (int) DB::table($this->contactTable)->where('team_id', $teamId)->where('group_id', $group->id)->where('unsubscribed', 1)->count(),
        ];

        return view('appemailcontact::emails', [
            'group' => $group,
            'stats' => $stats,
            'Datatable' => $this->EmailDatatable,
        ]);
    }

    public function emailsList(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $group = DB::table($this->groupTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        abort_if(!$group, 404);

        $whereConditions = [
            'team_id' => $teamId,
            'group_id' => $group->id,
        ];

        $dataTableService = \DataTable::make($this->contactTable, $this->EmailDatatable, $whereConditions, []);
        $data = $dataTableService->getData($request);

        return response()->json($data);
    }

    public function popupImportContact(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $result = DB::table($this->groupTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        abort_if(!$result, 404);

        return response()->json([
            'status' => 1,
            'data' => view('appemailcontact::popup_import_contact', [
                'result' => $result,
            ])->render(),
        ]);
    }

    public function downloadExampleUploadCsv()
    {
        $path = module_path('AppEmailContact', 'resources/assets/csv_template.csv');
        return Response::download($path, 'email_contacts_template.csv');
    }

    public function addContact(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $raw = trim((string) $request->input('emails', ''));

        if ($raw === '') {
            return response()->json(['status' => 0, 'message' => __('Please enter at least one email address.')]);
        }

        $group = DB::table($this->groupTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        if (!$group) {
            return response()->json(['status' => 0, 'message' => __('Contact group does not exist.')]);
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $records = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $email = '';
            $name = '';

            // Support "Name <email@example.com>" or "email@example.com,Name" or "email@example.com"
            if (preg_match('/^(.*?)<([^>]+)>$/', $line, $m)) {
                $name = trim($m[1], " \t\"'");
                $email = strtolower(trim($m[2]));
            } elseif (str_contains($line, ',')) {
                [$first, $second] = array_map('trim', explode(',', $line, 2));
                if (filter_var($first, FILTER_VALIDATE_EMAIL)) {
                    $email = strtolower($first);
                    $name = $second;
                } elseif (filter_var($second, FILTER_VALIDATE_EMAIL)) {
                    $email = strtolower($second);
                    $name = $first;
                }
            } else {
                $email = strtolower($line);
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $records[] = [
                    'email' => $email,
                    'name' => $name,
                    'params' => null,
                ];
            }
        }

        return $this->insertEmailsChunked($teamId, $group, $records);
    }

    public function doImportContact(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $group = DB::table($this->groupTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        if (!$group) {
            return response()->json(['status' => 0, 'message' => __('Contact group does not exist.')]);
        }

        if (!$request->hasFile('files')) {
            return response()->json(['status' => 0, 'message' => __('Please select a CSV file to upload.')]);
        }

        $file = $request->file('files')[0] ?? $request->file('files');
        $ext = $file ? strtolower($file->getClientOriginalExtension()) : '';
        if (!$file || !in_array($ext, ['csv', 'txt'], true)) {
            return response()->json(['status' => 0, 'message' => __('Only CSV or TXT files are allowed.')]);
        }

        $handle = fopen($file->getRealPath(), 'rb');
        if (!$handle) {
            return response()->json(['status' => 0, 'message' => __('Failed to read uploaded CSV file.')]);
        }

        $headers = [];
        $emailColIndex = 0;
        $nameColIndex = null;
        $records = [];
        $rowNumber = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($rowNumber === 0) {
                $rowNumber++;
                $normalizedHeaders = array_map(fn ($h) => strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', $h))), $row);
                $looksLikeHeader = false;
                foreach ($normalizedHeaders as $idx => $hdr) {
                    if (in_array($hdr, ['email', 'email_address', 'e-mail', 'mail'], true)) {
                        $emailColIndex = $idx;
                        $looksLikeHeader = true;
                    }
                    if (in_array($hdr, ['name', 'full_name', 'fullname', 'first_name'], true)) {
                        $nameColIndex = $idx;
                        $looksLikeHeader = true;
                    }
                }
                if ($looksLikeHeader) {
                    $headers = $normalizedHeaders;
                    continue;
                }
                // First row was actually data without a header row
            }

            $rowNumber++;
            $email = strtolower(trim((string) ($row[$emailColIndex] ?? '')));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $name = $nameColIndex !== null ? trim((string) ($row[$nameColIndex] ?? '')) : '';
            $params = [];
            foreach ($row as $idx => $value) {
                if ($idx === $emailColIndex || $idx === $nameColIndex) {
                    continue;
                }
                $value = trim((string) $value);
                if ($value !== '') {
                    $key = $headers[$idx] ?? ('param' . ($idx + 1));
                    $params[$key] = $value;
                }
            }

            $records[] = [
                'email' => $email,
                'name' => $name,
                'params' => !empty($params) ? json_encode($params, JSON_UNESCAPED_UNICODE) : null,
            ];
        }
        fclose($handle);

        return $this->insertEmailsChunked($teamId, $group, $records);
    }

    public function deleteEmail(Request $request)
    {
        $teamId = (int) $request->team_id;
        $ids = (array) $request->input('id', []);
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            return response()->json(['status' => 0, 'message' => __('Please select at least one contact.')]);
        }

        DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->whereIn('id_secure', $ids)
            ->delete();

        return response()->json(['status' => 1, 'message' => __('Success')]);
    }

    protected function insertEmailsChunked(int $teamId, object $group, array $records)
    {
        if (empty($records)) {
            return response()->json(['status' => 0, 'message' => __('No valid email addresses were found.')]);
        }

        // Deduplicate within the incoming payload
        $unique = [];
        foreach ($records as $rec) {
            $unique[$rec['email']] = $rec;
        }

        $now = time();
        $inserted = 0;
        $chunks = array_chunk(array_values($unique), 500);

        foreach ($chunks as $chunk) {
            $rows = [];
            foreach ($chunk as $item) {
                $rows[] = [
                    'id_secure' => rand_string(16),
                    'team_id' => $teamId,
                    'group_id' => $group->id,
                    'email' => $item['email'],
                    'name' => $item['name'] !== '' ? mb_substr($item['name'], 0, 191) : null,
                    'params' => $item['params'],
                    'unsubscribed' => 0,
                    'status' => 1,
                    'created' => $now,
                ];
            }

            $inserted += DB::table($this->contactTable)->insertOrIgnore($rows);
        }

        return response()->json([
            'status' => 1,
            'message' => __('Imported :count email contact(s) successfully (duplicates skipped).', ['count' => $inserted]),
            'redirect' => route('app.emailcontact.emails', ['id_secure' => $group->id_secure]),
        ]);
    }
}

