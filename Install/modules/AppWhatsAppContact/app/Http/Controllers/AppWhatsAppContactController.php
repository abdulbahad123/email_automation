<?php

namespace Modules\AppWhatsAppContact\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class AppWhatsAppContactController extends Controller
{
    public $Datatable;
    public $PhoneDatatable;
    protected string $contactTable = 'whatsapp_contacts';
    protected string $phoneTable = 'whatsapp_phone_numbers';

    public function __construct()
    {
        $this->Datatable = [
            'element' => 'DataTable',
            'order' => ['created', 'desc'],
            'lengthMenu' => [10, 25, 50, 100, 150, 200],
            'search_field' => ['name'],
            'columns' => [
                ['data' => 'id_secure', 'name' => 'id_secure', 'className' => 'w-40'],
                ['data' => 'name', 'name' => 'name', 'title' => __('Group Info')],
                [
                    'data' => 'contacts_count',
                    'name' => '(SELECT COUNT(*) FROM whatsapp_phone_numbers WHERE whatsapp_phone_numbers.pid = whatsapp_contacts.id AND whatsapp_phone_numbers.team_id = whatsapp_contacts.team_id)',
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
                    'url' => route('app.whatsappcontact.status', ['status' => 'enable']),
                    'icon' => 'fa-eye',
                    'label' => __('Enable'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                [
                    'url' => route('app.whatsappcontact.status', ['status' => 'disable']),
                    'icon' => 'fa-eye-slash',
                    'label' => __('Disable'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                ['divider' => true],
                [
                    'url' => route('app.whatsappcontact.delete'),
                    'icon' => 'fa-trash-can-list',
                    'label' => __('Delete'),
                    'confirm' => __('Are you sure you want to delete this item?'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
            ],
        ];

        $this->PhoneDatatable = [
            'element' => 'DataTablePhones',
            'order' => ['id', 'desc'],
            'lengthMenu' => [10, 25, 50, 100, 150, 200],
            'search_field' => ['phone', 'params'],
            'columns' => [
                ['data' => 'id_secure', 'name' => 'id_secure', 'className' => 'w-40'],
                ['data' => 'phone', 'name' => 'phone', 'title' => __('Phone number')],
                ['data' => 'params', 'name' => 'params', 'title' => __('Params')],
                ['title' => __('Action'), 'className' => 'text-end'],
            ],
            'actions' => [
                [
                    'url' => route('app.whatsappcontact.delete_phone'),
                    'icon' => 'fa-trash-can-list',
                    'label' => __('Delete selected'),
                    'confirm' => __('Are you sure you want to delete this item?'),
                    'call_success' => "Main.DataTable_Reload('#DataTablePhones')"
                ],
            ],
        ];
    }

    public function index(Request $request)
    {
        $teamId = (int) $request->team_id;

        $stats = [
            'groups' => (int) DB::table($this->contactTable)->where('team_id', $teamId)->count(),
            'enabled_groups' => (int) DB::table($this->contactTable)->where('team_id', $teamId)->where('status', 1)->count(),
            'disabled_groups' => (int) DB::table($this->contactTable)->where('team_id', $teamId)->where('status', 0)->count(),
            'numbers' => (int) DB::table($this->phoneTable)->where('team_id', $teamId)->count(),
        ];

        return view('appwhatsappcontact::index', [
            'stats' => $stats,
            'Datatable' => $this->Datatable,
        ]);
    }

    public function list(Request $request)
    {
        $whereConditions = [
            'team_id' => (int) $request->team_id,
        ];

        $dataTableService = \DataTable::make($this->contactTable, $this->Datatable, $whereConditions, []);
        $data = $dataTableService->getData($request);

        return response()->json($data);
    }

    public function update(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $result = null;

        if ($id_secure) {
            $result = DB::table($this->contactTable)
                ->where('team_id', $teamId)
                ->where('id_secure', $id_secure)
                ->first();
        }

        return view('appwhatsappcontact::update', [
            'result' => $result,
        ]);
    }

    public function popupUpdate(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $result = null;

        if ($id_secure) {
            $result = DB::table($this->contactTable)
                ->where('team_id', $teamId)
                ->where('id_secure', $id_secure)
                ->first();
        }

        return response()->json([
            'status' => 1,
            'data' => view('appwhatsappcontact::popup_update', [
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
                'message' => __('Group contact name is required.'),
            ]);
        }

        $existing = null;
        if ($id_secure) {
            $existing = DB::table($this->contactTable)
                ->where('team_id', $teamId)
                ->where('id_secure', $id_secure)
                ->first();
        }

        $duplicate = DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->where('name', $name)
            ->when($existing, function ($query) use ($existing) {
                $query->where('id', '!=', $existing->id);
            })
            ->first();

        if ($duplicate) {
            return response()->json([
                'status' => 0,
                'message' => __('This group contact name already exists.'),
            ]);
        }

        if (!$existing) {
            $maxGroups = (int) \Access::permission('whatsapp_bulk_max_contact_group');
            $totalGroups = (int) DB::table($this->contactTable)->where('team_id', $teamId)->count();
            if ($maxGroups > 0 && $totalGroups >= $maxGroups) {
                return response()->json([
                    'status' => 0,
                    'message' => sprintf(__('You can only create a maximum of %s contact groups'), $maxGroups),
                ]);
            }
        }

        $data = [
            'team_id' => $teamId,
            'name' => $name,
            'status' => $status ? 1 : 0,
            'changed' => time(),
        ];

        if ($existing) {
            DB::table($this->contactTable)->where('id', $existing->id)->update($data);
        } else {
            $data['id_secure'] = rand_string();
            $data['created'] = time();
            DB::table($this->contactTable)->insert($data);
        }

        return response()->json([
            'status' => 1,
            'message' => __('Success'),
            'redirect' => route('app.whatsappcontact.index'),
        ]);
    }

    public function status(Request $request, string $status = 'enable')
    {
        $teamId = (int) $request->team_id;
        $ids = $request->input('id', []);
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            return response()->json([
                'status' => 0,
                'message' => __('Please select at least one item'),
            ]);
        }

        $statusValue = $status === 'enable' ? 1 : 0;

        DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->whereIn('id_secure', $ids)
            ->update([
                'status' => $statusValue,
                'changed' => time(),
            ]);

        return response()->json([
            'status' => 1,
            'message' => __('Success'),
        ]);
    }

    public function delete(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $ids = $request->input('id', $id_secure);
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            return response()->json([
                'status' => 0,
                'message' => __('Please select an item to delete'),
            ]);
        }

        $contacts = DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->whereIn('id_secure', $ids)
            ->get();

        foreach ($contacts as $contact) {
            DB::table($this->contactTable)->where('id', $contact->id)->delete();
            DB::table($this->phoneTable)->where('team_id', $teamId)->where('pid', $contact->id)->delete();
        }

        return response()->json([
            'status' => 1,
            'message' => __('Success'),
        ]);
    }

    public function phoneNumbers(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;

        $contact = DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        abort_if(!$contact, 404);

        $stats = [
            'numbers' => (int) DB::table($this->phoneTable)->where('team_id', $teamId)->where('pid', $contact->id)->count(),
            'current_page' => 0,
        ];

        return view('appwhatsappcontact::phone_numbers', [
            'contact' => $contact,
            'stats' => $stats,
            'Datatable' => $this->PhoneDatatable,
        ]);
    }

    public function phoneNumbersList(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $contact = DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        abort_if(!$contact, 404);

        $whereConditions = [
            'team_id' => $teamId,
            'pid' => $contact->id,
        ];

        $dataTableService = \DataTable::make($this->phoneTable, $this->PhoneDatatable, $whereConditions, []);
        $data = $dataTableService->getData($request);

        return response()->json($data);
    }

    public function popupImportContact(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $result = DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        abort_if(!$result, 404);

        return response()->json([
            'status' => 1,
            'data' => view('appwhatsappcontact::popup_import_contact', [
                'result' => $result,
            ])->render(),
        ]);
    }

    public function downloadExampleUploadCsv()
    {
        $path = module_path('AppWhatsAppContact', 'resources/assets/csv_template.csv');
        return Response::download($path, 'csv_template.csv');
    }

    public function addContact(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $phoneNumbers = trim((string) $request->input('phone_numbers', ''));

        if ($phoneNumbers === '') {
            return response()->json([
                'status' => 0,
                'message' => __('Phone numbers are required.'),
            ]);
        }

        $contact = DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        if (!$contact) {
            return response()->json([
                'status' => 0,
                'message' => __('Contact group does not exist.'),
            ]);
        }

        $rows = preg_split('/\r\n|\r|\n/', $phoneNumbers);
        return $this->insertPhoneNumbers($teamId, $contact, $rows, false);
    }

    public function doImportContact(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $contact = DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        if (!$contact) {
            return response()->json([
                'status' => 0,
                'message' => __('Contact group does not exist.'),
            ]);
        }

        if (!$request->hasFile('files')) {
            return response()->json([
                'status' => 0,
                'message' => __('Cannot found files csv to upload'),
            ]);
        }

        $file = $request->file('files')[0] ?? $request->file('files');
        if (!$file || strtolower($file->getClientOriginalExtension()) !== 'csv') {
            return response()->json([
                'status' => 0,
                'message' => __('The filetype you are attempting to upload is not allowed'),
            ]);
        }

        $rows = array_map('str_getcsv', file($file->getRealPath()));
        if (empty($rows)) {
            return response()->json([
                'status' => 0,
                'message' => __('Upload csv file failed.'),
            ]);
        }

        $headers = [];
        $phoneRows = [];
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                $headers = array_slice($row, 1);
                continue;
            }

            $phone = $row[0] ?? '';
            $params = [];
            foreach (array_slice($row, 1) as $paramIndex => $value) {
                if ($value !== '' && isset($headers[$paramIndex])) {
                    $params[$headers[$paramIndex]] = $value;
                }
            }

            $phoneRows[] = [
                'phone' => $phone,
                'params' => !empty($params) ? json_encode($params) : null,
            ];
        }

        return $this->insertPhoneNumbers($teamId, $contact, $phoneRows, true);
    }

    public function deletePhone(Request $request)
    {
        $teamId = (int) $request->team_id;
        $ids = $request->input('id', []);
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            return response()->json([
                'status' => 0,
                'message' => __('Please select an item to delete'),
            ]);
        }

        DB::table($this->phoneTable)
            ->where('team_id', $teamId)
            ->whereIn('id_secure', $ids)
            ->delete();

        return response()->json([
            'status' => 1,
            'message' => __('Success'),
        ]);
    }

    protected function insertPhoneNumbers(int $teamId, object $contact, array $rows, bool $withParams)
    {
        $maxPhoneNumbers = (int) \Access::permission('whatsapp_bulk_max_phone_numbers');
        $currentCount = (int) DB::table($this->phoneTable)
            ->where('team_id', $teamId)
            ->where('pid', $contact->id)
            ->count();

        if ($maxPhoneNumbers > 0 && ($currentCount + count($rows)) > $maxPhoneNumbers) {
            return response()->json([
                'status' => 0,
                'message' => sprintf(__('You can only add up to %s phone numbers per contact group'), $maxPhoneNumbers),
            ]);
        }

        $data = [];
        foreach ($rows as $row) {
            $phone = $withParams ? ($row['phone'] ?? '') : $row;
            $params = $withParams ? ($row['params'] ?? null) : null;
            $phone = $this->normalizePhone((string) $phone);

            if ($phone === '' || (!is_numeric($phone) && stripos($phone, '@g.us') === false)) {
                continue;
            }

            $data[] = [
                'id_secure' => rand_string(),
                'team_id' => $teamId,
                'pid' => $contact->id,
                'phone' => $phone,
                'params' => $params,
            ];
        }

        if (!empty($data)) {
            DB::table($this->phoneTable)->insert($data);
        }

        return response()->json([
            'status' => 1,
            'message' => __('Success'),
            'redirect' => route('app.whatsappcontact.phone_numbers', ['id_secure' => $contact->id_secure]),
        ]);
    }

    protected function normalizePhone(string $phone): string
    {
        return trim(str_replace(['+', ' ', "'", '`', '"'], '', $phone));
    }
}
