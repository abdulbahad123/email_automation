<?php

namespace Modules\AppEmailSMTP\Services;

use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DynamicMailerService
{
    /**
     * Cache built mailers within the current worker process to reuse SMTP connections across batches.
     *
     * @var array<string, MailerContract>
     */
    protected static array $mailerCache = [];

    public static function clearCache(): void
    {
        self::$mailerCache = [];
    }

    public static function encryptPassword(?string $plain): ?string
    {
        $plain = (string) $plain;
        if ($plain === '') {
            return '';
        }

        return Crypt::encryptString($plain);
    }

    public static function decryptPassword(?string $stored): string
    {
        $stored = (string) $stored;
        if ($stored === '') {
            return '';
        }

        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable $e) {
            return $stored;
        }
    }

    /**
     * Resolve an SMTP account row or admin fallback config.
     */
    public static function resolveAccount(int|string $smtpId, ?int $teamId = null): ?object
    {
        if ((string) $smtpId === 'admin_default' || (int) $smtpId === 0) {
            $host = trim((string) get_option('smtp_server', env('MAIL_HOST', '')));
            $fromEmail = trim((string) get_option('mail_sender_email', env('MAIL_FROM_ADDRESS', '')));
            if ($host === '' || $fromEmail === '') {
                return null;
            }

            return (object) [
                'id' => 0,
                'id_secure' => 'admin_default',
                'team_id' => $teamId ?? 0,
                'name' => __('System Default SMTP'),
                'host' => $host,
                'port' => (int) get_option('smtp_port', env('MAIL_PORT', 465)),
                'encryption' => strtolower((string) get_option('smtp_encryption', env('MAIL_ENCRYPTION', 'ssl'))),
                'username' => (string) get_option('smtp_username', env('MAIL_USERNAME', '')),
                'password' => (string) get_option('smtp_password', env('MAIL_PASSWORD', '')),
                'from_email' => $fromEmail,
                'from_name' => (string) get_option('mail_sender_name', env('MAIL_FROM_NAME', config('app.name'))),
                'reply_to' => $fromEmail,
                'daily_limit' => 0,
                'sent_today' => 0,
                'last_reset_date' => date('Y-m-d'),
                'status' => 1,
                'is_encrypted' => false,
            ];
        }

        $query = DB::table('email_smtp_accounts')->where('id', (int) $smtpId);
        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        }

        $account = $query->first();
        if (!$account) {
            return null;
        }

        $account->is_encrypted = true;
        return $account;
    }

    /**
     * Reset daily counter if the date rolled over and check if this SMTP has remaining daily quota.
     */
    public static function canSendToday(object $account): bool
    {
        if ((int) ($account->id ?? 0) === 0) {
            return true;
        }

        $today = date('Y-m-d');
        if (($account->last_reset_date ?? null) !== $today) {
            DB::table('email_smtp_accounts')
                ->where('id', $account->id)
                ->update([
                    'sent_today' => 0,
                    'last_reset_date' => $today,
                ]);
            $account->sent_today = 0;
            $account->last_reset_date = $today;
        }

        $limit = (int) ($account->daily_limit ?? 0);
        if ($limit <= 0) {
            return true;
        }

        return (int) $account->sent_today < $limit;
    }

    /**
     * Build (or retrieve cached) Mailer instance for the given SMTP account.
     */
    public static function getMailer(object $account, bool $forceFresh = false): MailerContract
    {
        $cacheKey = 'smtp_' . ($account->id ?? 0) . '_' . ($account->changed ?? 0);

        if (!$forceFresh && isset(self::$mailerCache[$cacheKey])) {
            return self::$mailerCache[$cacheKey];
        }

        $encryption = strtolower(trim((string) ($account->encryption ?? 'tls')));
        $port = (int) ($account->port ?? 587);
        $scheme = ($encryption === 'ssl' || $port === 465) ? 'smtps' : 'smtp';

        $password = !empty($account->is_encrypted)
            ? self::decryptPassword($account->password ?? '')
            : (string) ($account->password ?? '');

        $config = [
            'name' => $cacheKey,
            'transport' => 'smtp',
            'scheme' => $scheme,
            'host' => (string) $account->host,
            'port' => $port,
            'username' => (string) ($account->username ?? ''),
            'password' => $password,
            'timeout' => 15,
            'local_domain' => parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost',
        ];

        if ($encryption === 'none') {
            $config['auto_tls'] = false;
        }

        $mailer = Mail::build($config);
        self::$mailerCache[$cacheKey] = $mailer;

        return $mailer;
    }

    /**
     * Send an email using a specific SMTP account object.
     *
     * @return array{ok: bool, error: ?string}
     */
    public static function sendWithAccount(
        object $account,
        string $toEmail,
        string $subject,
        string $htmlBody,
        array $options = []
    ): array {
        $toEmail = trim($toEmail);
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => __('Invalid recipient email address.')];
        }

        if (!self::canSendToday($account)) {
            return ['ok' => false, 'error' => __('Daily SMTP sending limit reached.')];
        }

        try {
            $mailer = self::getMailer($account, !empty($options['force_fresh']));
            $fromEmail = trim((string) ($account->from_email ?? ''));
            $fromName = trim((string) ($account->from_name ?: $account->name ?: config('app.name')));
            $replyTo = trim((string) ($account->reply_to ?? ''));
            $plainText = trim(html_entity_decode(strip_tags(preg_replace('/<(br|p|div|li|tr|h[1-6])[^>]*>/i', "\n", $htmlBody)), ENT_QUOTES, 'UTF-8'));

            $mailer->send([], [], function ($message) use ($toEmail, $subject, $htmlBody, $plainText, $fromEmail, $fromName, $replyTo, $options) {
                $message->to($toEmail)
                    ->from($fromEmail, $fromName)
                    ->subject($subject)
                    ->html($htmlBody);

                if ($plainText !== '') {
                    $message->text($plainText);
                }

                if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    $message->replyTo($replyTo, $fromName);
                }

                if (!empty($options['unsubscribe_url'])) {
                    $headers = $message->getHeaders();
                    $headers->addTextHeader('List-Unsubscribe', '<' . $options['unsubscribe_url'] . '>');
                    $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
                }

                if (!empty($options['attachment'])) {
                    $attachmentPath = self::resolveAttachmentPath((string) $options['attachment']);
                    if ($attachmentPath && is_file($attachmentPath)) {
                        $message->attach($attachmentPath);
                    }
                }
            });

            if ((int) ($account->id ?? 0) > 0) {
                DB::table('email_smtp_accounts')
                    ->where('id', $account->id)
                    ->update([
                        'sent_today' => DB::raw('sent_today + 1'),
                        'last_reset_date' => date('Y-m-d'),
                        'last_error' => null,
                    ]);
                $account->sent_today = ((int) ($account->sent_today ?? 0)) + 1;
            }

            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            $errorMsg = mb_substr($e->getMessage(), 0, 500);
            if ((int) ($account->id ?? 0) > 0) {
                DB::table('email_smtp_accounts')
                    ->where('id', $account->id)
                    ->update(['last_error' => $errorMsg]);
            }

            // Reset cached transport so the next attempt reconnects cleanly.
            self::clearCache();

            return ['ok' => false, 'error' => $errorMsg];
        }
    }

    public static function resolveAttachmentPath(string $path): ?string
    {
        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if (is_file($path)) {
            return $path;
        }

        $candidates = [
            storage_path('app/' . ltrim($path, '/\\')),
            storage_path('app/public/' . ltrim($path, '/\\')),
            public_path(ltrim($path, '/\\')),
            base_path(ltrim($path, '/\\')),
        ];

        $urlPath = parse_url($path, PHP_URL_PATH);
        if ($urlPath) {
            $candidates[] = public_path(ltrim($urlPath, '/\\'));
            $candidates[] = base_path(ltrim($urlPath, '/\\'));
            $candidates[] = storage_path('app/public/' . ltrim(preg_replace('#^/?storage/#', '', $urlPath), '/\\'));
        }

        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

