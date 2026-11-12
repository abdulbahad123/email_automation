<?php

namespace Modules\AppEmailSMTP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AppEmailSMTP\Services\DynamicMailerService;
use Modules\AppEmailSMTP\Services\EmailSchemaService;

class AppEmailSMTPController extends Controller
{
    public array $Datatable;
    protected string $table = 'email_smtp_accounts';

    public function __construct()
    {
        EmailSchemaService::ensureTables();

        $this->Datatable = [
            'element' => 'DataTable',
            'order' => ['created', 'desc'],
            'lengthMenu' => [10, 25, 50, 100],
            'search_field' => ['name', 'host', 'from_email', 'username'],
            'columns' => [
                ['data' => 'id_secure', 'name' => 'id_secure', 'className' => 'w-40'],
                ['data' => 'name', 'name' => 'name', 'title' => __('SMTP Account')],
                ['data' => 'host', 'name' => 'host', 'title' => __('Server')],
                ['data' => 'port', 'name' => 'port', 'title' => __('Port')],
                ['data' => 'encryption', 'name' => 'encryption', 'title' => __('Encryption')],
                ['data' => 'from_email', 'name' => 'from_email', 'title' => __('From Email')],
                ['data' => 'daily_limit', 'name' => 'daily_limit', 'title' => __('Daily Quota')],
                ['data' => 'sent_today', 'name' => 'sent_today', 'title' => __('Sent Today')],
                ['data' => 'status', 'name' => 'status', 'title' => __('Status')],
                ['data' => 'last_error', 'name' => 'last_error', 'title' => __('Last Error')],
                ['title' => __('Action'), 'className' => 'text-end'],
            ],
            'status_filter' => [
                ['value' => '-1', 'label' => __('All')],
                ['value' => '1', 'name' => 'enable', 'icon' => 'fa-light fa-eye', 'color' => 'success', 'label' => __('Active')],
                ['value' => '0', 'name' => 'disable', 'icon' => 'fa-light fa-eye-slash', 'color' => 'warning', 'label' => __('Disabled')],
            ],
            'actions' => [
                [
                    'url' => route('app.emailsmtp.status', ['status' => 'enable']),
                    'icon' => 'fa-light fa-eye',
                    'label' => __('Enable'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                [
                    'url' => route('app.emailsmtp.status', ['status' => 'disable']),
                    'icon' => 'fa-light fa-eye-slash',
                    'label' => __('Disable'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                ['divider' => true],
                [
                    'url' => route('app.emailsmtp.delete'),
                    'icon' => 'fa-light fa-trash-can-list',
                    'label' => __('Delete'),
                    'confirm' => __('Are you sure you want to delete this SMTP account?'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
            ],
        ];
    }

    public function index(Request $request)
    {
        $teamId = (int) $request->team_id;

        $stats = [
            'total' => (int) DB::table($this->table)->where('team_id', $teamId)->count(),
            'active' => (int) DB::table($this->table)->where('team_id', $teamId)->where('status', 1)->count(),
            'disabled' => (int) DB::table($this->table)->where('team_id', $teamId)->where('status', 0)->count(),
            'sent_today' => (int) DB::table($this->table)
                ->where('team_id', $teamId)
                ->where('last_reset_date', date('Y-m-d'))
                ->sum('sent_today'),
        ];

        return view('appemailsmtp::index', [
            'stats' => $stats,
            'Datatable' => $this->Datatable,
        ]);
    }

    public function list(Request $request)
    {
        $whereConditions = [
            'team_id' => (int) $request->team_id,
        ];

        $dataTableService = \DataTable::make($this->table, $this->Datatable, $whereConditions, []);
        $data = $dataTableService->getData($request);

        return response()->json($data);
    }

    public function popupUpdate(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $result = null;

        if ($id_secure) {
            $result = DB::table($this->table)
                ->where('team_id', $teamId)
                ->where('id_secure', $id_secure)
                ->first();
        }

        return response()->json([
            'status' => 1,
            'data' => view('appemailsmtp::popup_update', [
                'result' => $result,
            ])->render(),
        ]);
    }

    public function save(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;

        $name = trim((string) $request->input('name', ''));
        $host = trim((string) $request->input('host', ''));
        $port = (int) $request->input('port', 587);
        $encryption = strtolower(trim((string) $request->input('encryption', 'tls')));
        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');
        $fromEmail = trim((string) $request->input('from_email', ''));
        $fromName = trim((string) $request->input('from_name', ''));
        $replyTo = trim((string) $request->input('reply_to', ''));
        $dailyLimit = max(0, (int) $request->input('daily_limit', 0));
        $status = (int) $request->input('status', 1) ? 1 : 0;

        if ($name === '') {
            return response()->json(['status' => 0, 'message' => __('Account name is required.')]);
        }
        if ($host === '') {
            return response()->json(['status' => 0, 'message' => __('SMTP host is required.')]);
        }
        if ($port <= 0 || $port > 65535) {
            return response()->json(['status' => 0, 'message' => __('Please enter a valid SMTP port.')]);
        }
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            $encryption = 'tls';
        }
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['status' => 0, 'message' => __('Please enter a valid From Email address.')]);
        }
        if ($replyTo !== '' && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['status' => 0, 'message' => __('Please enter a valid Reply-To email address.')]);
        }

        $existing = null;
        if ($id_secure) {
            $existing = DB::table($this->table)
                ->where('team_id', $teamId)
                ->where('id_secure', $id_secure)
                ->first();
        }

        $data = [
            'team_id' => $teamId,
            'name' => $name,
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'username' => $username,
            'from_email' => $fromEmail,
            'from_name' => $fromName !== '' ? $fromName : $name,
            'reply_to' => $replyTo !== '' ? $replyTo : $fromEmail,
            'daily_limit' => $dailyLimit,
            'status' => $status,
            'changed' => time(),
        ];

        if ($password !== '') {
            $data['password'] = DynamicMailerService::encryptPassword($password);
        } elseif (!$existing) {
            $data['password'] = '';
        }

        if ($existing) {
            DB::table($this->table)->where('id', $existing->id)->update($data);
        } else {
            $data['id_secure'] = rand_string(16);
            $data['sent_today'] = 0;
            $data['last_reset_date'] = date('Y-m-d');
            $data['created'] = time();
            DB::table($this->table)->insert($data);
        }

        return response()->json([
            'status' => 1,
            'message' => __('SMTP account saved successfully.'),
            'redirect' => route('app.emailsmtp.index'),
        ]);
    }

    public function status(Request $request, string $status = 'enable')
    {
        $teamId = (int) $request->team_id;
        $ids = (array) $request->input('id', []);
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            return response()->json(['status' => 0, 'message' => __('Please select at least one SMTP account.')]);
        }

        DB::table($this->table)
            ->where('team_id', $teamId)
            ->whereIn('id_secure', $ids)
            ->update([
                'status' => $status === 'enable' ? 1 : 0,
                'changed' => time(),
            ]);

        return response()->json(['status' => 1, 'message' => __('Success')]);
    }

    public function popupTest(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $result = DB::table($this->table)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        abort_if(!$result, 404);

        return response()->json([
            'status' => 1,
            'data' => view('appemailsmtp::popup_test', [
                'result' => $result,
            ])->render(),
        ]);
    }

    public function testSend(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $testEmail = trim((string) $request->input('test_email', ''));

        if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['status' => 0, 'message' => __('Please enter a valid recipient email address.')]);
        }

        $row = DB::table($this->table)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        if (!$row) {
            return response()->json(['status' => 0, 'message' => __('SMTP account not found.')]);
        }

        $account = DynamicMailerService::resolveAccount($row->id, $teamId);
        if (!$account) {
            return response()->json(['status' => 0, 'message' => __('Unable to load SMTP configuration.')]);
        }

        $subject = __('SMTP Connection Test - :name', ['name' => $account->name]);
        $html = '<div style="font-family: Arial, sans-serif; padding: 16px;">'
            . '<h3 style="margin:0 0 8px;">' . e(__('SMTP Connection Verified')) . '</h3>'
            . '<p style="color:#475569;">' . e(__('Your SMTP account ":name" (:host::port) is working properly.', [
                'name' => $account->name,
                'host' => $account->host,
                'port' => $account->port,
            ])) . '</p>'
            . '</div>';

        $res = DynamicMailerService::sendWithAccount($account, $testEmail, $subject, $html, ['force_fresh' => true]);

        DB::table($this->table)->where('id', $row->id)->update([
            'last_tested_at' => time(),
            'last_error' => $res['ok'] ? null : $res['error'],
        ]);

        if (!$res['ok']) {
            return response()->json([
                'status' => 0,
                'message' => __('SMTP Test Failed: ') . ($res['error'] ?: __('Unknown error')),
            ]);
        }

        return response()->json([
            'status' => 1,
            'message' => __('Test email sent successfully to ') . $testEmail,
        ]);
    }

    public function delete(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $ids = $request->input('id', $id_secure);
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            return response()->json(['status' => 0, 'message' => __('Please select an SMTP account to delete.')]);
        }

        DB::table($this->table)
            ->where('team_id', $teamId)
            ->whereIn('id_secure', $ids)
            ->delete();

        return response()->json(['status' => 1, 'message' => __('Success')]);
    }
}

