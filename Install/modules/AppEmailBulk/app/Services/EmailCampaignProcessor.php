<?php

namespace Modules\AppEmailBulk\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\AppEmailBulk\Jobs\SendCampaignEmailJob;
use Modules\AppEmailSMTP\Services\DynamicMailerService;
use Modules\AppEmailSMTP\Services\EmailSchemaService;

class EmailCampaignProcessor
{
    /**
     * Process due running email campaigns in safe, scalable cursor-based batches.
     *
     * @return array{campaigns_checked: int, sent: int, failed: int, queued: int, messages: array<int, string>}
     */
    public static function processDueCampaigns(int $maxCampaigns = 10, ?string $onlyCampaignSecureId = null, ?int $teamIdFilter = null): array
    {
        EmailSchemaService::ensureTables();

        $now = time();

        // Release stale locks older than 5 minutes (e.g. crashed PHP process)
        DB::table('email_campaigns')
            ->where('is_processing', 1)
            ->where('processing_started_at', '<', $now - 300)
            ->update(['is_processing' => 0]);

        $query = DB::table('email_campaigns')
            ->where('status', 1)
            ->where('is_processing', 0)
            ->where('time_post', '<=', $now)
            ->orderBy('time_post', 'asc')
            ->limit($maxCampaigns);

        if ($onlyCampaignSecureId) {
            $query->where('id_secure', $onlyCampaignSecureId);
        }
        if ($teamIdFilter !== null) {
            $query->where('team_id', $teamIdFilter);
        }

        $campaigns = $query->get();

        $summary = [
            'campaigns_checked' => 0,
            'sent' => 0,
            'failed' => 0,
            'queued' => 0,
            'messages' => [],
        ];

        foreach ($campaigns as $campaign) {
            // Check allowed sending hours window in campaign timezone
            if (!self::isWithinScheduleHours($campaign)) {
                continue;
            }

            // Atomically acquire processing lock
            $locked = DB::table('email_campaigns')
                ->where('id', $campaign->id)
                ->where('is_processing', 0)
                ->where('status', 1)
                ->update([
                    'is_processing' => 1,
                    'processing_started_at' => time(),
                ]);

            if ($locked === 0) {
                continue;
            }

            $summary['campaigns_checked']++;

            try {
                $res = self::processSingleCampaign($campaign);
                $summary['sent'] += $res['sent'];
                $summary['failed'] += $res['failed'];
                $summary['queued'] += $res['queued'];
                if (!empty($res['message'])) {
                    $summary['messages'][] = $campaign->name . ': ' . $res['message'];
                }
            } catch (\Throwable $e) {
                \Log::error('EmailCampaignProcessor error for campaign #' . $campaign->id . ': ' . $e->getMessage());
                DB::table('email_campaigns')->where('id', $campaign->id)->update([
                    'result' => mb_substr($e->getMessage(), 0, 500),
                    'changed' => time(),
                ]);
                $summary['messages'][] = $campaign->name . ': ' . $e->getMessage();
            } finally {
                DB::table('email_campaigns')->where('id', $campaign->id)->update([
                    'is_processing' => 0,
                ]);
                DynamicMailerService::clearCache();
            }
        }

        return $summary;
    }

    protected static function processSingleCampaign(object $campaign): array
    {
        $batchSize = max(1, min(500, (int) ($campaign->batch_size ?: 50)));
        $minDelay = max(0, (int) ($campaign->min_delay ?? 1));
        $maxDelay = max($minDelay, (int) ($campaign->max_delay ?? $minDelay));

        $smtpAccounts = self::resolveCampaignSmtps($campaign);
        if (empty($smtpAccounts)) {
            $msg = __('No active SMTP account with remaining daily quota.');
            DB::table('email_campaigns')->where('id', $campaign->id)->update([
                'result' => $msg,
                'changed' => time(),
            ]);
            return ['sent' => 0, 'failed' => 0, 'queued' => 0, 'message' => $msg];
        }

        // Cursor-based pagination: constant O(1) seek time even at 10,000+ contacts
        $contacts = DB::table('email_contacts')
            ->where('group_id', $campaign->group_id)
            ->where('team_id', $campaign->team_id)
            ->where('status', 1)
            ->where('unsubscribed', 0)
            ->where('id', '>', (int) $campaign->last_contact_id)
            ->orderBy('id', 'asc')
            ->limit($batchSize)
            ->get();

        if ($contacts->isEmpty()) {
            $hasPendingLogs = DB::table('email_logs')
                ->where('campaign_id', $campaign->id)
                ->where('status', 0)
                ->exists();

            if (!$hasPendingLogs) {
                DB::table('email_campaigns')->where('id', $campaign->id)->update([
                    'status' => 2,
                    'result' => __('Campaign completed.'),
                    'changed' => time(),
                ]);
                return ['sent' => 0, 'failed' => 0, 'queued' => 0, 'message' => __('Completed')];
            }

            return ['sent' => 0, 'failed' => 0, 'queued' => 0, 'message' => __('Waiting for queued jobs to finish.')];
        }

        $useAsyncQueue = config('queue.default') !== 'sync';
        $smtpCount = count($smtpAccounts);
        $smtpIndex = (int) ($campaign->next_smtp_index ?? 0);
        $lastContactId = (int) $campaign->last_contact_id;
        $sentCount = 0;
        $failedCount = 0;
        $queuedCount = 0;
        $cumulativeDelay = 0;
        $totalInBatch = $contacts->count();

        foreach ($contacts as $idx => $contact) {
            $lastContactId = (int) $contact->id;

            // Idempotency guard: skip if already logged for this campaign
            $alreadyLogged = DB::table('email_logs')
                ->where('campaign_id', $campaign->id)
                ->where('contact_id', $contact->id)
                ->exists();

            if ($alreadyLogged) {
                continue;
            }

            // Pick next available SMTP with remaining daily quota
            $selectedSmtp = null;
            for ($attempt = 0; $attempt < $smtpCount; $attempt++) {
                $candidate = $smtpAccounts[($smtpIndex + $attempt) % $smtpCount];
                if (DynamicMailerService::canSendToday($candidate)) {
                    $selectedSmtp = $candidate;
                    $smtpIndex = ($smtpIndex + $attempt + 1) % $smtpCount;
                    break;
                }
            }

            if (!$selectedSmtp) {
                // Stop advancing cursor at the previous contact so this one retries when quota resets
                $lastContactId = (int) $contact->id - 1;
                DB::table('email_campaigns')->where('id', $campaign->id)->update([
                    'result' => __('Daily SMTP sending limit reached. Will resume automatically.'),
                ]);
                break;
            }

            $unsubscribeUrl = url('email/unsubscribe/' . $contact->id_secure);
            $personalizedSubject = self::personalizeText((string) $campaign->subject, $contact, $unsubscribeUrl);
            $personalizedBody = self::personalizeHtml((string) $campaign->body, $contact, $unsubscribeUrl);

            if ($useAsyncQueue) {
                $logId = DB::table('email_logs')->insertGetId([
                    'id_secure' => rand_string(16),
                    'team_id' => $campaign->team_id,
                    'campaign_id' => $campaign->id,
                    'smtp_id' => (int) ($selectedSmtp->id ?? 0),
                    'contact_id' => $contact->id,
                    'to_email' => $contact->email,
                    'subject' => mb_substr($personalizedSubject, 0, 255),
                    'status' => 0,
                    'error_message' => null,
                    'sent_at' => null,
                    'created' => time(),
                ]);

                $job = new SendCampaignEmailJob(
                    logId: $logId,
                    campaignId: (int) $campaign->id,
                    teamId: (int) $campaign->team_id,
                    smtpId: (int) ($selectedSmtp->id ?? 0) === 0 ? 'admin_default' : (int) $selectedSmtp->id,
                    toEmail: $contact->email,
                    subject: $personalizedSubject,
                    htmlBody: $personalizedBody,
                    attachment: $campaign->attachment,
                    unsubscribeUrl: $unsubscribeUrl
                );

                if ($cumulativeDelay > 0) {
                    dispatch($job->delay(now()->addSeconds($cumulativeDelay)));
                } else {
                    dispatch($job);
                }

                $queuedCount++;
                if ($maxDelay > 0) {
                    $cumulativeDelay += random_int($minDelay, $maxDelay);
                }
            } else {
                $res = DynamicMailerService::sendWithAccount(
                    $selectedSmtp,
                    $contact->email,
                    $personalizedSubject,
                    $personalizedBody,
                    [
                        'attachment' => $campaign->attachment,
                        'unsubscribe_url' => $unsubscribeUrl,
                    ]
                );

                DB::table('email_logs')->insert([
                    'id_secure' => rand_string(16),
                    'team_id' => $campaign->team_id,
                    'campaign_id' => $campaign->id,
                    'smtp_id' => (int) ($selectedSmtp->id ?? 0),
                    'contact_id' => $contact->id,
                    'to_email' => $contact->email,
                    'subject' => mb_substr($personalizedSubject, 0, 255),
                    'status' => $res['ok'] ? 1 : 2,
                    'error_message' => $res['ok'] ? null : mb_substr((string) $res['error'], 0, 500),
                    'sent_at' => time(),
                    'created' => time(),
                ]);

                if ($res['ok']) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }

                if ($idx < $totalInBatch - 1 && $maxDelay > 0) {
                    $sleepSec = random_int($minDelay, $maxDelay);
                    if ($sleepSec > 0) {
                        sleep(min($sleepSec, 15));
                    }
                }
            }
        }

        $updatePayload = [
            'last_contact_id' => $lastContactId,
            'next_smtp_index' => $smtpIndex,
            'changed' => time(),
        ];

        if (!$useAsyncQueue) {
            $updatePayload['sent'] = (int) $campaign->sent + $sentCount;
            $updatePayload['failed'] = (int) $campaign->failed + $failedCount;
            $updatePayload['result'] = __('Last batch: :sent sent, :failed failed.', [
                'sent' => $sentCount,
                'failed' => $failedCount,
            ]);
        } else {
            $updatePayload['result'] = __('Dispatched :queued emails to queue.', ['queued' => $queuedCount]);
        }

        // Check if there are any remaining contacts after this cursor
        $hasMore = DB::table('email_contacts')
            ->where('group_id', $campaign->group_id)
            ->where('team_id', $campaign->team_id)
            ->where('status', 1)
            ->where('unsubscribed', 0)
            ->where('id', '>', $lastContactId)
            ->exists();

        if (!$hasMore && !$useAsyncQueue) {
            $updatePayload['status'] = 2;
            $updatePayload['result'] = __('Campaign completed.');
        }

        DB::table('email_campaigns')->where('id', $campaign->id)->update($updatePayload);

        return [
            'sent' => $sentCount,
            'failed' => $failedCount,
            'queued' => $queuedCount,
            'message' => $updatePayload['result'],
        ];
    }

    protected static function resolveCampaignSmtps(object $campaign): array
    {
        $ids = json_decode((string) $campaign->smtp_ids, true);
        if (!is_array($ids) || empty($ids)) {
            return [];
        }

        $accounts = [];
        foreach ($ids as $id) {
            $account = DynamicMailerService::resolveAccount($id, (int) $campaign->team_id);
            if ($account && (int) ($account->status ?? 0) === 1 && DynamicMailerService::canSendToday($account)) {
                $accounts[] = $account;
            }
        }

        return $accounts;
    }

    protected static function isWithinScheduleHours(object $campaign): bool
    {
        $hours = json_decode((string) ($campaign->schedule_time ?? ''), true);
        if (!is_array($hours) || empty($hours)) {
            return true;
        }

        $tz = $campaign->timezone ?: config('app.timezone', 'UTC');
        try {
            $currentHour = (string) Carbon::now($tz)->hour;
        } catch (\Throwable $e) {
            $currentHour = (string) Carbon::now()->hour;
        }

        return in_array($currentHour, array_map('strval', $hours), true);
    }

    public static function personalizeText(string $text, object $contact, string $unsubscribeUrl): string
    {
        $vars = self::buildVariables($contact, $unsubscribeUrl);
        $text = self::replaceVariables($text, $vars);
        return self::spinText($text);
    }

    public static function personalizeHtml(string $html, object $contact, string $unsubscribeUrl): string
    {
        $hasExplicitUnsub = stripos($html, '[unsubscribe_url]') !== false;
        $vars = self::buildVariables($contact, $unsubscribeUrl);
        $rendered = self::replaceVariables($html, $vars);
        $rendered = self::spinText($rendered);

        if (!$hasExplicitUnsub) {
            $rendered .= '<div style="margin-top:24px;padding-top:12px;border-top:1px solid #e2e8f0;font-size:12px;color:#64748b;text-align:center;">'
                . e(__('If you no longer wish to receive these emails, you may')) . ' '
                . '<a href="' . e($unsubscribeUrl) . '" style="color:#475569;text-decoration:underline;">' . e(__('unsubscribe here')) . '</a>.'
                . '</div>';
        }

        return $rendered;
    }

    protected static function buildVariables(object $contact, string $unsubscribeUrl): array
    {
        $vars = [
            'email' => (string) ($contact->email ?? ''),
            'name' => (string) ($contact->name ?? ''),
            'fullname' => (string) ($contact->name ?? ''),
            'unsubscribe_url' => $unsubscribeUrl,
        ];

        if (!empty($contact->params)) {
            $extra = json_decode((string) $contact->params, true);
            if (is_array($extra)) {
                foreach ($extra as $k => $v) {
                    $vars[strtolower(trim((string) $k))] = (string) $v;
                }
            }
        }

        return $vars;
    }

    protected static function replaceVariables(string $content, array $vars): string
    {
        return (string) preg_replace_callback('/\[([a-zA-Z0-9_]+)\]/', function ($matches) use ($vars) {
            $key = strtolower($matches[1]);
            return array_key_exists($key, $vars) ? $vars[$key] : $matches[0];
        }, $content);
    }

    protected static function spinText(string $text): string
    {
        while (preg_match('/\{([^{}]+)\}/', $text, $match)) {
            $options = explode('|', $match[1]);
            $choice = $options[array_rand($options)];
            $text = preg_replace('/' . preg_quote($match[0], '/') . '/', $choice, $text, 1);
        }

        return $text;
    }
}

