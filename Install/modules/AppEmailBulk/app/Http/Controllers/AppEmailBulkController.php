<?php

namespace Modules\AppEmailBulk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\AppEmailBulk\Services\EmailCampaignProcessor;
use Modules\AppEmailBulk\Services\EmailTemplateService;
use Modules\AppEmailSMTP\Services\DynamicMailerService;
use Modules\AppEmailSMTP\Services\EmailSchemaService;

class AppEmailBulkController extends Controller
{
    public array $Datatable;
    public array $LogDatatable;
    protected string $campaignTable = 'email_campaigns';
    protected string $groupTable = 'email_contact_groups';
    protected string $contactTable = 'email_contacts';
    protected string $smtpTable = 'email_smtp_accounts';
    protected string $logTable = 'email_logs';

    public function __construct()
    {
        EmailSchemaService::ensureTables();

        $contactSubquery = '(SELECT COUNT(*) FROM email_contacts WHERE email_contacts.group_id = email_campaigns.group_id AND email_contacts.team_id = email_campaigns.team_id AND email_contacts.unsubscribed = 0 AND email_contacts.status = 1)';
        $pendingSubquery = 'GREATEST((' . $contactSubquery . ') - COALESCE(email_campaigns.sent, 0) - COALESCE(email_campaigns.failed, 0), 0)';

        $this->Datatable = [
            'element' => 'DataTable',
            'order' => ['created', 'desc'],
            'lengthMenu' => [10, 25, 50, 100],
            'search_field' => ['email_campaigns.name', 'email_campaigns.subject', 'email_contact_groups.name'],
            'columns' => [
                ['data' => 'id_secure', 'name' => 'id_secure', 'className' => 'w-40'],
                ['data' => 'name', 'name' => 'name', 'title' => __('Campaign')],
                ['data' => 'group_name', 'name' => 'email_contact_groups.name', 'alias' => 'group_name', 'title' => __('Contact group')],
                ['data' => 'smtp_count', 'name' => 'JSON_LENGTH(email_campaigns.smtp_ids)', 'alias' => 'smtp_count', 'title' => __('SMTPs')],
                ['data' => 'sent', 'name' => 'sent', 'title' => __('Sent')],
                ['data' => 'failed', 'name' => 'failed', 'title' => __('Failed')],
                ['data' => 'pending', 'name' => $pendingSubquery, 'alias' => 'pending', 'title' => __('Pending')],
                ['data' => 'time_post', 'name' => 'time_post', 'type' => 'datetime', 'title' => __('Scheduled')],
                ['data' => 'status', 'name' => 'status', 'title' => __('Status')],
                ['data' => 'subject', 'name' => 'subject', 'title' => __('Subject')],
                ['data' => 'batch_size', 'name' => 'batch_size', 'title' => __('Batch')],
                ['data' => 'min_delay', 'name' => 'min_delay', 'title' => __('Min delay')],
                ['data' => 'max_delay', 'name' => 'max_delay', 'title' => __('Max delay')],
                ['data' => 'total_group_contacts', 'name' => $contactSubquery, 'alias' => 'total_group_contacts', 'title' => __('Total contacts')],
                ['data' => 'result', 'name' => 'result', 'title' => __('Result')],
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
                    'url' => route('app.emailbulk.actions', ['action' => 'run']),
                    'icon' => 'fa-light fa-play',
                    'label' => __('Run selected'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                [
                    'url' => route('app.emailbulk.actions', ['action' => 'pause']),
                    'icon' => 'fa-light fa-pause',
                    'label' => __('Pause selected'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                ['divider' => true],
                [
                    'url' => route('app.emailbulk.actions', ['action' => 'delete']),
                    'icon' => 'fa-light fa-trash-can-list',
                    'label' => __('Delete selected'),
                    'confirm' => __('Are you sure you want to delete the selected campaigns?'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
                [
                    'url' => route('app.emailbulk.delete_all'),
                    'icon' => 'fa-light fa-trash-can-list',
                    'label' => __('Delete all campaigns'),
                    'confirm' => __('Are you sure you want to delete all email campaigns?'),
                    'call_success' => "Main.DataTable_Reload('#DataTable')"
                ],
            ],
        ];

        $this->LogDatatable = [
            'element' => 'DataTableLogs',
            'order' => ['id', 'desc'],
            'lengthMenu' => [10, 25, 50, 100, 200],
            'search_field' => ['to_email', 'subject', 'error_message'],
            'columns' => [
                ['data' => 'id_secure', 'name' => 'id_secure', 'className' => 'w-40'],
                ['data' => 'to_email', 'name' => 'to_email', 'title' => __('Recipient')],
                ['data' => 'subject', 'name' => 'subject', 'title' => __('Subject')],
                ['data' => 'status', 'name' => 'status', 'title' => __('Status')],
                ['data' => 'error_message', 'name' => 'error_message', 'title' => __('Details')],
                ['data' => 'sent_at', 'name' => 'sent_at', 'title' => __('Sent At')],
            ],
            'status_filter' => [
                ['value' => '-1', 'label' => __('All')],
                ['value' => '1', 'label' => __('Sent')],
                ['value' => '0', 'label' => __('Queued')],
                ['value' => '2', 'label' => __('Failed')],
            ],
        ];
    }

    public function index(Request $request)
    {
        $teamId = (int) $request->team_id;

        $stats = [
            'total' => (int) DB::table($this->campaignTable)->where('team_id', $teamId)->count(),
            'running' => (int) DB::table($this->campaignTable)->where('team_id', $teamId)->where('status', 1)->count(),
            'paused' => (int) DB::table($this->campaignTable)->where('team_id', $teamId)->where('status', 0)->count(),
            'completed' => (int) DB::table($this->campaignTable)->where('team_id', $teamId)->where('status', 2)->count(),
        ];

        return view('appemailbulk::index', [
            'stats' => $stats,
            'Datatable' => $this->Datatable,
        ]);
    }

    public function list(Request $request)
    {
        $whereConditions = [
            'email_campaigns.team_id' => (int) $request->team_id,
        ];

        $joins = [
            [
                'table' => 'email_contact_groups',
                'first' => 'email_contact_groups.id',
                'second' => 'email_campaigns.group_id',
                'type' => 'left',
            ],
        ];

        $dataTableService = \DataTable::make($this->campaignTable, $this->Datatable, $whereConditions, $joins);
        $data = $dataTableService->getData($request);

        return response()->json($data);
    }

    public function update(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $result = null;

        if ($id_secure) {
            $result = DB::table($this->campaignTable)
                ->where('team_id', $teamId)
                ->where('id_secure', $id_secure)
                ->first();
        }

        $smtps = DB::table($this->smtpTable)
            ->where('team_id', $teamId)
            ->where('status', 1)
            ->orderByDesc('created')
            ->get();

        $hasAdminDefault = DynamicMailerService::resolveAccount('admin_default') !== null;

        $groups = DB::table($this->groupTable)
            ->where('team_id', $teamId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $selectedSmtps = $this->decodeSmtpIds($result->smtp_ids ?? '[]');
        $scheduleHours = $this->decodeScheduleHours($result->schedule_time ?? '');
        $templates = EmailTemplateService::getTemplates();

        return view('appemailbulk::update', compact(
            'result',
            'smtps',
            'hasAdminDefault',
            'groups',
            'selectedSmtps',
            'scheduleHours',
            'templates'
        ));
    }

    public function save(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $name = trim((string) $request->input('name', ''));
        $subject = trim((string) $request->input('subject', ''));
        $body = trim((string) $request->input('body', ''));
        $smtpSecureIds = (array) $request->input('smtp_ids', []);
        $groupSecureId = trim((string) $request->input('contact_group', ''));
        $timePostRaw = trim((string) $request->input('time_post', ''));
        $batchSize = max(1, min(500, (int) $request->input('batch_size', 50)));
        $minDelay = max(0, (int) $request->input('min_delay', 1));
        $maxDelay = max($minDelay, (int) $request->input('max_delay', 2));
        $scheduleHours = array_values(array_filter(array_map(function ($hour) {
            return is_numeric($hour) && (int) $hour >= 0 && (int) $hour <= 23 ? (string) (int) $hour : null;
        }, (array) $request->input('schedule_time', []))));

        if ($name === '') {
            return response()->json(['status' => 0, 'message' => __('Campaign name is required.')]);
        }
        if (empty($smtpSecureIds)) {
            return response()->json(['status' => 0, 'message' => __('Please select at least one SMTP account.')]);
        }
        if ($groupSecureId === '') {
            return response()->json(['status' => 0, 'message' => __('Please select an email contact group.')]);
        }
        if ($subject === '') {
            return response()->json(['status' => 0, 'message' => __('Email subject is required.')]);
        }
        if ($body === '') {
            return response()->json(['status' => 0, 'message' => __('Email message content is required.')]);
        }

        // Resolve SMTP IDs
        $resolvedSmtpIds = [];
        if (in_array('admin_default', $smtpSecureIds, true) && DynamicMailerService::resolveAccount('admin_default')) {
            $resolvedSmtpIds[] = 'admin_default';
        }
        $teamSmtpIds = DB::table($this->smtpTable)
            ->where('team_id', $teamId)
            ->where('status', 1)
            ->whereIn('id_secure', $smtpSecureIds)
            ->pluck('id')
            ->all();
        foreach ($teamSmtpIds as $sid) {
            $resolvedSmtpIds[] = (int) $sid;
        }

        if (empty($resolvedSmtpIds)) {
            return response()->json(['status' => 0, 'message' => __('Selected SMTP account(s) are not active or not found.')]);
        }

        $group = DB::table($this->groupTable)
            ->where('team_id', $teamId)
            ->where('status', 1)
            ->where('id_secure', $groupSecureId)
            ->first();

        if (!$group) {
            return response()->json(['status' => 0, 'message' => __('Selected contact group was not found or is disabled.')]);
        }

        $totalContacts = (int) DB::table($this->contactTable)
            ->where('team_id', $teamId)
            ->where('group_id', $group->id)
            ->where('status', 1)
            ->where('unsubscribed', 0)
            ->count();

        if ($totalContacts === 0) {
            return response()->json(['status' => 0, 'message' => __('The selected contact group has no active email contacts.')]);
        }

        $timePost = $timePostRaw !== '' ? (int) timestamp_sql($timePostRaw) : time();
        if ($timePost <= 0) {
            $timePost = time();
        }

        $existing = null;
        if ($id_secure) {
            $existing = DB::table($this->campaignTable)
                ->where('team_id', $teamId)
                ->where('id_secure', $id_secure)
                ->first();
        }

        $attachmentPath = $existing->attachment ?? null;
        if ($request->hasFile('attachment')) {
            $storedAttachment = $this->storeAttachmentFile($request, $teamId);
            if ($storedAttachment !== null) {
                $attachmentPath = $storedAttachment;
            }
        } elseif ($request->boolean('remove_attachment')) {
            $attachmentPath = null;
        }

        $body = EmailTemplateService::normalizeBodyHtml($body);
        $inlineImage = $this->storeInlineImageFile($request, $teamId);
        if ($inlineImage !== null) {
            $body = EmailTemplateService::injectInlineImage($body, $inlineImage['url']);
        }

        $data = [
            'team_id' => $teamId,
            'name' => $name,
            'subject' => $subject,
            'body' => $body,
            'smtp_ids' => json_encode(array_values($resolvedSmtpIds)),
            'group_id' => $group->id,
            'attachment' => $attachmentPath,
            'time_post' => $timePost,
            'batch_size' => $batchSize,
            'min_delay' => $minDelay,
            'max_delay' => $maxDelay,
            'schedule_time' => !empty($scheduleHours) ? json_encode($scheduleHours) : '',
            'timezone' => auth()->user()->timezone ?? config('app.timezone'),
            'total_contacts' => $totalContacts,
            'changed' => time(),
        ];

        if ($existing) {
            DB::table($this->campaignTable)->where('id', $existing->id)->update($data);
        } else {
            $data = array_merge($data, [
                'id_secure' => rand_string(16),
                'sent' => 0,
                'failed' => 0,
                'next_smtp_index' => 0,
                'last_contact_id' => 0,
                'status' => 1,
                'is_processing' => 0,
                'result' => __('Scheduled and waiting for worker.'),
                'created' => time(),
            ]);
            DB::table($this->campaignTable)->insert($data);
        }

        return response()->json([
            'status' => 1,
            'message' => __('Campaign saved successfully.'),
            'redirect' => route('app.emailbulk.index'),
        ]);
    }

    public function status(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $item = DB::table($this->campaignTable)->where('team_id', $teamId)->where('id_secure', $id_secure)->first();

        if (!$item) {
            return response()->json(['status' => 0, 'message' => __('Campaign not found.')]);
        }

        if ((int) $item->status === 2) {
            return response()->json(['status' => 0, 'message' => __('Campaign is already completed.')]);
        }

        $newStatus = (int) $item->status === 1 ? 0 : 1;
        DB::table($this->campaignTable)->where('id', $item->id)->update([
            'status' => $newStatus,
            'is_processing' => 0,
            'changed' => time(),
        ]);

        return response()->json(['status' => 1, 'message' => __('Success')]);
    }

    public function actions(Request $request, string $action)
    {
        $teamId = (int) $request->team_id;
        $ids = array_values(array_filter((array) $request->input('id', [])));

        if (empty($ids)) {
            return response()->json(['status' => 0, 'message' => __('Please select at least one campaign.')]);
        }

        switch ($action) {
            case 'pause':
                DB::table($this->campaignTable)
                    ->where('team_id', $teamId)
                    ->whereIn('id_secure', $ids)
                    ->where('status', '!=', 2)
                    ->update(['status' => 0, 'is_processing' => 0, 'changed' => time()]);
                return response()->json(['status' => 1, 'message' => __('Success')]);

            case 'run':
                DB::table($this->campaignTable)
                    ->where('team_id', $teamId)
                    ->whereIn('id_secure', $ids)
                    ->where('status', '!=', 2)
                    ->update(['status' => 1, 'is_processing' => 0, 'changed' => time()]);
                return response()->json(['status' => 1, 'message' => __('Success')]);

            case 'delete':
                $campaignIds = DB::table($this->campaignTable)
                    ->where('team_id', $teamId)
                    ->whereIn('id_secure', $ids)
                    ->pluck('id')
                    ->all();
                if (!empty($campaignIds)) {
                    DB::table($this->logTable)->where('team_id', $teamId)->whereIn('campaign_id', $campaignIds)->delete();
                    DB::table($this->campaignTable)->where('team_id', $teamId)->whereIn('id', $campaignIds)->delete();
                }
                return response()->json(['status' => 1, 'message' => __('Success')]);
        }

        return response()->json(['status' => 0, 'message' => __('Invalid action.')]);
    }

    public function delete(Request $request, ?string $id_secure = null)
    {
        $teamId = (int) $request->team_id;
        $ids = $request->input('id', $id_secure);
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_filter($ids));

        if (empty($ids)) {
            return response()->json(['status' => 0, 'message' => __('Please select a campaign to delete.')]);
        }

        $campaignIds = DB::table($this->campaignTable)
            ->where('team_id', $teamId)
            ->whereIn('id_secure', $ids)
            ->pluck('id')
            ->all();

        if (!empty($campaignIds)) {
            DB::table($this->logTable)->where('team_id', $teamId)->whereIn('campaign_id', $campaignIds)->delete();
            DB::table($this->campaignTable)->where('team_id', $teamId)->whereIn('id', $campaignIds)->delete();
        }

        return response()->json(['status' => 1, 'message' => __('Success')]);
    }

    public function deleteAll(Request $request)
    {
        $teamId = (int) $request->team_id;
        $campaignIds = DB::table($this->campaignTable)->where('team_id', $teamId)->pluck('id')->all();

        if (!empty($campaignIds)) {
            DB::table($this->logTable)->where('team_id', $teamId)->whereIn('campaign_id', $campaignIds)->delete();
            DB::table($this->campaignTable)->where('team_id', $teamId)->whereIn('id', $campaignIds)->delete();
        }

        return response()->json(['status' => 1, 'message' => __('Success')]);
    }

    public function logs(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $campaign = DB::table($this->campaignTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        abort_if(!$campaign, 404);

        return view('appemailbulk::logs', [
            'campaign' => $campaign,
            'Datatable' => $this->LogDatatable,
        ]);
    }

    public function logsList(Request $request, string $id_secure)
    {
        $teamId = (int) $request->team_id;
        $campaign = DB::table($this->campaignTable)
            ->where('team_id', $teamId)
            ->where('id_secure', $id_secure)
            ->first();

        abort_if(!$campaign, 404);

        $whereConditions = [
            'team_id' => $teamId,
            'campaign_id' => $campaign->id,
        ];

        $dataTableService = \DataTable::make($this->logTable, $this->LogDatatable, $whereConditions, []);
        $data = $dataTableService->getData($request);

        return response()->json($data);
    }

    public function processNow(Request $request, ?string $id_secure = null)
    {
        @set_time_limit(180);
        $teamId = (int) $request->team_id;

        $summary = EmailCampaignProcessor::processDueCampaigns(5, $id_secure, $teamId);

        return response()->json([
            'status' => 1,
            'message' => __('Processed :campaigns campaign(s): :sent sent, :queued queued, :failed failed.', [
                'campaigns' => $summary['campaigns_checked'],
                'sent' => $summary['sent'],
                'queued' => $summary['queued'],
                'failed' => $summary['failed'],
            ]),
            'summary' => $summary,
        ]);
    }

    public function singleSendForm(Request $request)
    {
        $teamId = (int) $request->team_id;

        $smtps = DB::table($this->smtpTable)
            ->where('team_id', $teamId)
            ->where('status', 1)
            ->orderByDesc('created')
            ->get();

        $hasAdminDefault = DynamicMailerService::resolveAccount('admin_default') !== null;

        $recentLogs = DB::table($this->logTable)
            ->where('team_id', $teamId)
            ->whereNull('campaign_id')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $templates = EmailTemplateService::getTemplates();

        return view('appemailbulk::single_send', compact('smtps', 'hasAdminDefault', 'recentLogs', 'templates'));
    }

    public function singleSendSubmit(Request $request)
    {
        $teamId = (int) $request->team_id;
        $smtpSecureId = trim((string) $request->input('smtp_id', ''));
        $toEmail = trim((string) $request->input('to_email', ''));
        $subject = trim((string) $request->input('subject', ''));
        $body = trim((string) $request->input('body', ''));

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['status' => 0, 'message' => __('Please enter a valid recipient email address.')]);
        }
        if ($subject === '') {
            return response()->json(['status' => 0, 'message' => __('Email subject is required.')]);
        }
        if ($body === '') {
            return response()->json(['status' => 0, 'message' => __('Email message is required.')]);
        }

        $account = null;
        if ($smtpSecureId === 'admin_default') {
            $account = DynamicMailerService::resolveAccount('admin_default');
        } elseif ($smtpSecureId !== '') {
            $row = DB::table($this->smtpTable)
                ->where('team_id', $teamId)
                ->where('id_secure', $smtpSecureId)
                ->where('status', 1)
                ->first();
            if ($row) {
                $account = DynamicMailerService::resolveAccount($row->id, $teamId);
            }
        } else {
            // Auto-select first active team SMTP, or fallback to admin default
            $row = DB::table($this->smtpTable)
                ->where('team_id', $teamId)
                ->where('status', 1)
                ->orderBy('id')
                ->first();
            $account = $row
                ? DynamicMailerService::resolveAccount($row->id, $teamId)
                : DynamicMailerService::resolveAccount('admin_default');
        }

        if (!$account) {
            return response()->json(['status' => 0, 'message' => __('No active SMTP account found. Please add an SMTP account first.')]);
        }

        $attachmentPath = $this->storeAttachmentFile($request, $teamId);
        $inlineImage = $this->storeInlineImageFile($request, $teamId);

        $htmlBody = EmailTemplateService::normalizeBodyHtml($body);
        if ($inlineImage !== null) {
            $htmlBody = EmailTemplateService::injectInlineImage($htmlBody, $inlineImage['url']);
        }

        $res = DynamicMailerService::sendWithAccount($account, $toEmail, $subject, $htmlBody, [
            'attachment' => $attachmentPath,
            'force_fresh' => true,
        ]);

        if ($attachmentPath) {
            Storage::disk('local')->delete($attachmentPath);
        }
        if ($inlineImage && !empty($inlineImage['path']) && is_file($inlineImage['path'])) {
            @unlink($inlineImage['path']);
        }

        DB::table($this->logTable)->insert([
            'id_secure' => rand_string(16),
            'team_id' => $teamId,
            'campaign_id' => null,
            'smtp_id' => (int) ($account->id ?? 0),
            'contact_id' => null,
            'to_email' => $toEmail,
            'subject' => mb_substr($subject, 0, 255),
            'status' => $res['ok'] ? 1 : 2,
            'error_message' => $res['ok'] ? null : mb_substr((string) $res['error'], 0, 500),
            'sent_at' => time(),
            'created' => time(),
        ]);

        if (!$res['ok']) {
            return response()->json([
                'status' => 0,
                'message' => __('Failed to send email: ') . ($res['error'] ?: __('Unknown SMTP error')),
            ]);
        }

        return response()->json([
            'status' => 1,
            'message' => __('Email sent successfully to :email!', ['email' => $toEmail]),
        ]);
    }

    public function uploadImage(Request $request)
    {
        $teamId = (int) $request->team_id;
        $file = $request->file('file') ?: $request->file('image');

        if (!$file || !$file->isValid()) {
            return response()->json(['status' => 0, 'message' => __('Please choose a valid image file.')], 422);
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return response()->json(['status' => 0, 'message' => __('Only JPG, PNG, GIF, and WEBP images are supported.')], 422);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return response()->json(['status' => 0, 'message' => __('Image size must not exceed 5 MB.')], 422);
        }

        $stored = $this->persistPublicImage($file, $teamId);
        if (!$stored) {
            return response()->json(['status' => 0, 'message' => __('Could not save uploaded image.')], 500);
        }

        return response()->json([
            'status' => 1,
            'location' => $stored['url'],
            'url' => $stored['url'],
        ]);
    }

    protected function storeAttachmentFile(Request $request, int $teamId): ?string
    {
        if (!$request->hasFile('attachment')) {
            return null;
        }

        $file = $request->file('attachment');
        if (!$file || !$file->isValid()) {
            return null;
        }

        $originalName = trim((string) $file->getClientOriginalName());
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        if ($safeName === '' || $safeName === '.' || $safeName === '..') {
            $ext = strtolower((string) $file->getClientOriginalExtension());
            $safeName = 'attachment_' . rand_string(6) . ($ext !== '' ? '.' . $ext : '');
        }

        return $file->storeAs('email_attachments/' . $teamId . '/' . rand_string(8), $safeName, 'local');
    }

    /**
     * @return array{url: string, path: string}|null
     */
    protected function storeInlineImageFile(Request $request, int $teamId): ?array
    {
        if (!$request->hasFile('inline_image')) {
            return null;
        }

        $file = $request->file('inline_image');
        if (!$file || !$file->isValid()) {
            return null;
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return null;
        }

        return $this->persistPublicImage($file, $teamId);
    }

    /**
     * @return array{url: string, path: string}|null
     */
    protected function persistPublicImage($file, int $teamId): ?array
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $baseName = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName) ?: 'image';
        $filename = rand_string(8) . '_' . mb_substr($safeBase, 0, 40) . '.' . $ext;

        $relativeDir = 'uploads/email_images/' . $teamId;
        $targetDir = public_path($relativeDir);

        try {
            File::ensureDirectoryExists($targetDir);
            $file->move($targetDir, $filename);

            return [
                'url' => asset($relativeDir . '/' . $filename),
                'path' => $targetDir . DIRECTORY_SEPARATOR . $filename,
            ];
        } catch (\Throwable $e) {
            try {
                $path = $file->storeAs('email_images/' . $teamId, $filename, 'public');
                return [
                    'url' => asset('storage/' . $path),
                    'path' => Storage::disk('public')->path($path),
                ];
            } catch (\Throwable $inner) {
                \Log::error('Failed to persist email image: ' . $inner->getMessage());
                return null;
            }
        }
    }

    public function unsubscribe(string $id_secure)
    {
        EmailSchemaService::ensureTables();

        $contact = DB::table($this->contactTable)->where('id_secure', $id_secure)->first();
        if ($contact && (int) $contact->unsubscribed !== 1) {
            DB::table($this->contactTable)->where('id', $contact->id)->update([
                'unsubscribed' => 1,
            ]);
        }

        return view('appemailbulk::unsubscribed', [
            'email' => $contact->email ?? null,
        ]);
    }

    public function cronRun(Request $request)
    {
        $configuredKey = (string) get_option('cron_key', '');
        $providedKey = (string) $request->query('key', '');

        if ($configuredKey !== '' && !hash_equals($configuredKey, $providedKey)) {
            return response()->json(['status' => 0, 'message' => 'Unauthorized cron key'], 403);
        }

        @set_time_limit(240);
        $summary = EmailCampaignProcessor::processDueCampaigns(10);

        return response()->json([
            'status' => 1,
            'summary' => $summary,
        ]);
    }

    protected function decodeSmtpIds($raw): array
    {
        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_map('strval', $decoded);
    }

    protected function decodeScheduleHours($hours): array
    {
        $decoded = json_decode((string) $hours, true);
        return is_array($decoded) ? array_map('strval', array_values($decoded)) : [];
    }
}

