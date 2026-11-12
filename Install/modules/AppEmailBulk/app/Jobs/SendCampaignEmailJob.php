<?php

namespace Modules\AppEmailBulk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\AppEmailSMTP\Services\DynamicMailerService;
use Modules\AppEmailSMTP\Services\EmailSchemaService;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public int $logId,
        public int $campaignId,
        public int $teamId,
        public int|string $smtpId,
        public string $toEmail,
        public string $subject,
        public string $htmlBody,
        public ?string $attachment = null,
        public ?string $unsubscribeUrl = null
    ) {}

    public function handle(): void
    {
        EmailSchemaService::ensureTables();

        $log = DB::table('email_logs')->where('id', $this->logId)->first();
        if (!$log || (int) $log->status === 1) {
            return;
        }

        $campaign = DB::table('email_campaigns')->where('id', $this->campaignId)->first();
        if (!$campaign || (int) $campaign->status === 0) {
            // Campaign paused before job ran: mark skipped/failed softly or leave pending
            return;
        }

        $account = DynamicMailerService::resolveAccount($this->smtpId, $this->teamId);
        if (!$account || (int) ($account->status ?? 0) !== 1) {
            $this->markFailed(__('Selected SMTP account is unavailable or disabled.'));
            return;
        }

        $res = DynamicMailerService::sendWithAccount(
            $account,
            $this->toEmail,
            $this->subject,
            $this->htmlBody,
            [
                'attachment' => $this->attachment,
                'unsubscribe_url' => $this->unsubscribeUrl,
            ]
        );

        if ($res['ok']) {
            DB::table('email_logs')->where('id', $this->logId)->update([
                'status' => 1,
                'error_message' => null,
                'sent_at' => time(),
            ]);
            DB::table('email_campaigns')->where('id', $this->campaignId)->increment('sent');
        } else {
            $this->markFailed($res['error'] ?: __('Failed to send email.'));
        }

        $this->checkCompletion($this->campaignId);
    }

    protected function markFailed(string $error): void
    {
        DB::table('email_logs')->where('id', $this->logId)->update([
            'status' => 2,
            'error_message' => mb_substr($error, 0, 500),
            'sent_at' => time(),
        ]);
        DB::table('email_campaigns')->where('id', $this->campaignId)->increment('failed');
    }

    protected function checkCompletion(int $campaignId): void
    {
        $campaign = DB::table('email_campaigns')->where('id', $campaignId)->first();
        if (!$campaign || (int) $campaign->status !== 1) {
            return;
        }

        $hasMoreContacts = DB::table('email_contacts')
            ->where('group_id', $campaign->group_id)
            ->where('team_id', $campaign->team_id)
            ->where('status', 1)
            ->where('unsubscribed', 0)
            ->where('id', '>', (int) $campaign->last_contact_id)
            ->exists();

        if ($hasMoreContacts) {
            return;
        }

        $hasPendingLogs = DB::table('email_logs')
            ->where('campaign_id', $campaignId)
            ->where('status', 0)
            ->exists();

        if (!$hasPendingLogs) {
            DB::table('email_campaigns')->where('id', $campaignId)->update([
                'status' => 2,
                'is_processing' => 0,
                'result' => __('Campaign completed.'),
                'changed' => time(),
            ]);
        }
    }
}

