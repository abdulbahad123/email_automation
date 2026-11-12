<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\User;

class WhatsAppOtpService
{
    const DEFAULT_OTP_TEMPLATE = "Your verification code for {product_name} is: {otp}. This code is valid for {validity_minutes} minutes. Please do not share this code with anyone.";
    const DEFAULT_ENDPOINT = "http://127.0.0.1:8000";

    /**
     * Resolve database PDO connection for Sass_admin
     */
    public static function getSassAdminPdo()
    {
        if (filter_var(env('STANDALONE_MODE', true), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $dbHost = env('SASS_ADMIN_DB_HOST', env('DB_HOST', '127.0.0.1'));
        $dbPort = env('SASS_ADMIN_DB_PORT', env('DB_PORT', '3306'));
        $cpanelUser = env('CPANEL_USER', '');

        $userPairs = array_values(array_filter([
            ['user' => env('DB_USERNAME_admin') ?: env('SASS_ADMIN_DB_USER'), 'pass' => env('DB_PASSWORD_admin', '') ?: env('SASS_ADMIN_DB_PASS', '')],
            ['user' => config('database.connections.mysql.username'), 'pass' => config('database.connections.mysql.password')],
            ['user' => env('DB_USERNAME'), 'pass' => env('DB_PASSWORD', '')],
        ], function ($item) {
            return !empty($item['user']);
        }));

        $dbCandidates = array_values(array_unique(array_filter([
            $cpanelUser ? "{$cpanelUser}_Sass_admindb" : null,
            $cpanelUser ? "{$cpanelUser}_sass_admindb" : null,
            env('SASS_ADMIN_DB'),
            env('DB_DATABASE_admin'),
            'sass_admin',
        ])));

        foreach ($dbCandidates as $dbName) {
            foreach ($userPairs as $pair) {
                try {
                    $pdo = new \PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $pair['user'], $pair['pass'], [
                        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_SILENT,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
                        \PDO::ATTR_TIMEOUT            => 2,
                    ]);
                    return $pdo;
                } catch (\Throwable $e) {}
            }
        }

        return null;
    }

    /**
     * Resolve Multi-Tenant WhatsApp Gateway Configuration:
     * - If domain is a White-Label Agency domain: use that Agency's credentials.
     * - If domain is SuperAdmin product domain: use SuperAdmin Master gateway credentials.
     */
    public static function resolveConfig(?string $host = null): array
    {
        if (empty($host)) {
            $host = request()->getHost() ?: ($_SERVER['HTTP_HOST'] ?? '');
        }

        $rawHost = strtolower(trim((string)$host));
        $rawHost = preg_replace('/:\d+$/', '', $rawHost);
        $rawHost = preg_replace('/^www\./i', '', $rawHost);
        $cleanHost = preg_replace('/^(launchshop|checkout|app|www|websitebuilder|website-builder|whatsappbulk|whatsapp-bulk|whatsapp|wa)\./i', '', $rawHost);

        $platformBaseHosts = [
            'whatsappbulk.saasreselling.com',
            'saasreselling.com',
            'localhost',
            '127.0.0.1',
        ];

        $cacheKey = 'wa_otp_config_' . md5($cleanHost);
        $cached = Cache::get($cacheKey);
        if ($cached && is_array($cached)) {
            return $cached;
        }

        $pdo = self::getSassAdminPdo();
        $agency = null;

        // 1. Check if request matches an Agency record in Sass_admin
        if ($pdo) {
            try {
                $subSlug = explode('.', $cleanHost)[0];
                $stmt = $pdo->prepare("SELECT * FROM agencies WHERE custom_domain = ? OR custom_domain = ? OR custom_domain LIKE ? OR slug = ? LIMIT 1");
                $stmt->execute([$rawHost, $cleanHost, "%{$cleanHost}%", $subSlug]);
                $agency = $stmt->fetch();
            } catch (\Throwable $e) {}
        }

        // 2. Fetch SuperAdmin global settings as baseline
        $globalSettings = [];
        if ($pdo) {
            try {
                $sStmt = $pdo->query("SELECT `key`, `value` FROM settings WHERE `key` LIKE 'whatsapp_%'");
                if ($sStmt) {
                    $rows = $sStmt->fetchAll();
                    foreach ($rows as $r) {
                        $globalSettings[$r->key] = $r->value;
                    }
                }
            } catch (\Throwable $e) {}
        }

        $localAppName = (function_exists('get_option') ? trim((string) get_option('website_title', '')) : '') ?: env('APP_NAME', 'WhatsApp Bulk');
        $localGatewayUrl = (function_exists('get_option') ? trim((string) get_option('whatsapp_server_url', '')) : '') ?: env('WHATSAPP_SERVER_URL', self::DEFAULT_ENDPOINT);

        $config = [
            'is_agency'    => false,
            'agency_id'    => null,
            'product_name' => $localAppName,
            'endpoint'     => !empty($globalSettings['whatsapp_gateway_url']) ? $globalSettings['whatsapp_gateway_url'] : $localGatewayUrl,
            'access_token' => !empty($globalSettings['whatsapp_access_token']) ? $globalSettings['whatsapp_access_token'] : null,
            'instance_id'  => !empty($globalSettings['whatsapp_instance_id']) ? $globalSettings['whatsapp_instance_id'] : null,
            'db'           => !empty($globalSettings['whatsapp_database']) ? $globalSettings['whatsapp_database'] : config('database.connections.mysql.database', ''),
            'otp_template' => !empty($globalSettings['whatsapp_otp_template']) ? $globalSettings['whatsapp_otp_template'] : self::DEFAULT_OTP_TEMPLATE,
            'is_active'    => isset($globalSettings['whatsapp_is_active']) ? (int)$globalSettings['whatsapp_is_active'] : 1,
        ];

        // 3. Overlay Agency credentials if White-Label product
        if ($agency) {
            $config['is_agency'] = true;
            $config['agency_id'] = $agency->id ?? null;
            if (!empty($agency->name)) {
                $config['product_name'] = $agency->name;
            }

            if (!empty($agency->whatsapp_gateway_url) && $agency->whatsapp_gateway_url !== 'http://localhost:8000') {
                $config['endpoint'] = $agency->whatsapp_gateway_url;
            }

            $tokenCandidate = $agency->whatsapp_access_token ?: ($agency->whatsapp_api_key ?? null);
            if (!empty($tokenCandidate)) {
                $config['access_token'] = trim((string)$tokenCandidate);
            }
            if (!empty($agency->whatsapp_instance_id)) {
                $config['instance_id'] = trim((string)$agency->whatsapp_instance_id);
            }
            if (!empty($agency->whatsapp_database)) {
                $config['db'] = trim((string)$agency->whatsapp_database);
            }
            if (!empty($agency->whatsapp_otp_template)) {
                $config['otp_template'] = $agency->whatsapp_otp_template;
            }
            if (isset($agency->is_whatsapp_active)) {
                $config['is_active'] = (int)$agency->is_whatsapp_active;
            }
        }

        // 4. If credentials still missing, resolve dynamically from local accounts/options
        if (empty($config['instance_id']) || empty($config['access_token'])) {
            $dynamic = self::resolveLocalCredentials($config['db'], $config['endpoint']);
            if (empty($config['instance_id']) && !empty($dynamic['instance_id'])) {
                $config['instance_id'] = $dynamic['instance_id'];
            }
            if (empty($config['access_token']) && !empty($dynamic['access_token'])) {
                $config['access_token'] = $dynamic['access_token'];
            }
        }

        try {
            Cache::put($cacheKey, $config, 30);
        } catch (\Throwable $e) {}

        return $config;
    }

    /**
     * Resolve active instance and access token from local DB or Node active_instance API
     */
    public static function resolveLocalCredentials(?string $db = null, ?string $endpoint = null): array
    {
        $resolvedDb = $db ?: config('database.connections.mysql.database', 'replix_automation');
        $endpointBase = rtrim($endpoint ?: self::DEFAULT_ENDPOINT, '/');

        // Check node server
        try {
            $res = Http::timeout(2)->withoutVerifying()->get($endpointBase . '/active_instance?db=' . urlencode($resolvedDb));
            if ($res->successful()) {
                $json = $res->json();
                if (($json['status'] ?? '') === 'success' && !empty($json['instance_id'])) {
                    return [
                        'instance_id'  => $json['instance_id'],
                        'access_token' => $json['access_token'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {}

        // Query accounts table directly in local database
        try {
            $account = \DB::table('accounts')
                ->where('status', 1)
                ->where('social_network', 'whatsapp_unofficial')
                ->orderBy('id', 'desc')
                ->first();

            if ($account && !empty($account->token)) {
                $token = null;
                if (!empty($account->team_id)) {
                    $team = \DB::table('teams')->where('id', $account->team_id)->first();
                    $token = $team->id_secure ?? null;
                }
                return [
                    'instance_id'  => $account->token,
                    'access_token' => $token,
                ];
            }
        } catch (\Throwable $e) {}

        // Fallback to options
        return [
            'instance_id'  => function_exists('get_option') ? get_option('whatsapp_instance_id', '') : '',
            'access_token' => function_exists('get_option') ? get_option('whatsapp_access_token', '') : '',
        ];
    }

    /**
     * Clean and format phone number into E.164 international format without plus sign
     */
    public static function formatNumber($phone, string $countryCode = '91'): string
    {
        if (empty($phone)) {
            return '';
        }

        $digits = preg_replace('/[^0-9]/', '', (string)$phone);
        if ($digits === '') {
            return '';
        }

        $cleanCode = preg_replace('/[^0-9]/', '', (string)$countryCode) ?: '91';

        // Strip leading zeros
        $digits = ltrim($digits, '0');

        // If phone already starts with the country code
        if (strpos($digits, $cleanCode) === 0 && strlen($digits) >= (strlen($cleanCode) + 7)) {
            return $digits;
        }

        // Standard national number format without country code
        return $cleanCode . $digits;
    }

    /**
     * Generate a 6-digit cryptographic random OTP code
     */
    public static function generateOtp(int $length = 6): string
    {
        return str_pad((string)random_int(100000, 999999), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Send OTP via WhatsApp using multi-tenant routing
     */
    public static function sendWhatsappOtp(string $phone, string $countryCode = '91', ?string $customOtp = null): array
    {
        $cleanNumber = self::formatNumber($phone, $countryCode);
        if (empty($cleanNumber) || strlen($cleanNumber) < 8) {
            return [
                'status'  => 0,
                'message' => __('Please enter a valid mobile number.'),
            ];
        }

        $config = self::resolveConfig();
        $otp = $customOtp ?: self::generateOtp();
        $validityMinutes = 10;

        $template = !empty($config['otp_template']) ? $config['otp_template'] : self::DEFAULT_OTP_TEMPLATE;
        $message = str_replace(
            ['{otp}', '{product_name}', '{validity_minutes}*', '{validity_minutes}'],
            [$otp, $config['product_name'], (string)$validityMinutes, (string)$validityMinutes],
            $template
        );

        $endpoint = rtrim($config['endpoint'] ?: self::DEFAULT_ENDPOINT, '/') . '/send_message';
        $accessToken = $config['access_token'];
        $instanceId = $config['instance_id'];
        $dbName = $config['db'];

        $payload = [
            'access_token' => $accessToken,
            'instance_id'  => $instanceId,
            'number'       => $cleanNumber,
            'message'      => $message,
            'caption'      => $message,
            'db'           => $dbName,
        ];

        $queryUrl = $endpoint . '?' . http_build_query([
            'access_token' => $accessToken,
            'instance_id'  => $instanceId,
            'db'           => $dbName,
        ]);

        $delivered = false;
        $responseBody = '';

        try {
            $ch = curl_init($queryUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $decoded = json_decode($responseBody, true);
                if (isset($decoded['status']) && ($decoded['status'] === 1 || $decoded['status'] === 'success' || $decoded['status'] === true)) {
                    $delivered = true;
                } else if (!empty($decoded['message_id']) || !empty($decoded['id'])) {
                    $delivered = true;
                } else {
                    // Gateway accepted the request
                    $delivered = true;
                }
            }
        } catch (\Throwable $e) {
            $responseBody = $e->getMessage();
        }

        // Cache OTP for 10 minutes (keyed by clean international number)
        $cacheKey = 'wa_otp_' . md5($cleanNumber);
        Cache::put($cacheKey, [
            'otp'        => $otp,
            'expires_at' => time() + ($validityMinutes * 60),
            'attempts'   => 0,
        ], $validityMinutes * 60);

        // Session backup
        session([
            'wa_signup_otp'   => $otp,
            'wa_signup_phone' => $cleanNumber,
            'wa_signup_exp'   => time() + ($validityMinutes * 60),
        ]);

        // Audit log
        $logEntry = "[" . date('Y-m-d H:i:s') . "] [WhatsAppOTP] Phone: {$cleanNumber} | Tenant: " . ($config['is_agency'] ? "Agency (#{$config['agency_id']})" : "SuperAdmin") . " | Status: " . ($delivered ? "Delivered" : "Queued/Sent") . " | Response: {$responseBody}\n";
        @file_put_contents(storage_path('logs/whatsapp_otp.log'), $logEntry, FILE_APPEND);

        return [
            'status'     => 1,
            'message'    => __('Verification code has been sent to your WhatsApp number +:number', ['number' => $cleanNumber]),
            'phone'      => $cleanNumber,
            'delivered'  => $delivered,
        ];
    }

    /**
     * Send WhatsApp Plan Renewal Congratulations Notification
     */
    public static function sendPlanRenewedNotification(
        ?string $phone,
        string $customerName,
        string $planName,
        string $expireDate,
        string $productName = 'WhatsApp Automation Tool'
    ): bool {
        if (empty($phone)) return false;

        $cleanNumber = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanNumber) == 10) {
            $cleanNumber = '91' . $cleanNumber;
        }

        $config = self::resolveConfig();
        if (empty($config['access_token']) || empty($config['instance_id'])) {
            return false;
        }

        $message = "🎉 *Congratulations {$customerName}! Your plan has been renewed!*\n\nYour *{$productName}* subscription (*{$planName}*) has been renewed successfully.\n\n📅 *New Expiry Date:* {$expireDate}\n✨ *Status:* Fully Active\n🌐 All dashboard tools, campaigns, and features are now fully unlocked.\n\nThank you for choosing *{$productName}*! 🚀";

        $endpoint = rtrim($config['endpoint'] ?: self::DEFAULT_ENDPOINT, '/') . '/send_message';
        $accessToken = $config['access_token'];
        $instanceId = $config['instance_id'];
        $dbName = $config['db'];

        $payload = [
            'access_token' => $accessToken,
            'instance_id'  => $instanceId,
            'number'       => $cleanNumber,
            'message'      => $message,
            'caption'      => $message,
            'db'           => $dbName,
        ];

        $queryUrl = $endpoint . '?' . http_build_query([
            'access_token' => $accessToken,
            'instance_id'  => $instanceId,
            'db'           => $dbName,
        ]);

        try {
            $ch = curl_init($queryUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_exec($ch);
            curl_close($ch);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Send WhatsApp Password Reset Notification
     */
    public static function sendPasswordResetNotification(
        ?string $phone,
        string $customerName,
        string $resetUrl,
        string $productName = 'WhatsApp Bulk'
    ): bool {
        if (empty($phone)) return false;

        $cleanNumber = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($cleanNumber) == 10) {
            $cleanNumber = '91' . $cleanNumber;
        }

        $config = self::resolveConfig();
        if (empty($config['access_token']) || empty($config['instance_id'])) {
            return false;
        }

        $message = "🔐 *Password Reset Request - {$productName}*\n\n" .
                   "Hello *{$customerName}*,\n\n" .
                   "We received a request to reset your password. Click the secure link below to set your new password:\n\n" .
                   "🔗 {$resetUrl}\n\n" .
                   "⏱️ This reset link is valid for 60 minutes.\n" .
                   "If you did not request a password reset, you can safely ignore this message.\n\n" .
                   "– Team {$productName} 🚀";

        $endpoint = rtrim($config['endpoint'] ?: self::DEFAULT_ENDPOINT, '/') . '/send_message';
        $accessToken = $config['access_token'];
        $instanceId = $config['instance_id'];
        $dbName = $config['db'];

        $payload = [
            'access_token' => $accessToken,
            'instance_id'  => $instanceId,
            'number'       => $cleanNumber,
            'message'      => $message,
            'caption'      => $message,
            'db'           => $dbName,
        ];

        $queryUrl = $endpoint . '?' . http_build_query([
            'access_token' => $accessToken,
            'instance_id'  => $instanceId,
            'db'           => $dbName,
        ]);

        try {
            $ch = curl_init($queryUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return ($httpCode >= 200 && $httpCode < 300);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Verify WhatsApp OTP code
     */
    public static function verifyWhatsappOtp(string $phone, string $countryCode, string $enteredOtp): array
    {
        $cleanNumber = self::formatNumber($phone, $countryCode);
        $enteredOtp = trim($enteredOtp);

        if (empty($enteredOtp)) {
            return [
                'status'  => 0,
                'message' => __('Please enter the 6-digit OTP code.'),
            ];
        }

        $cacheKey = 'wa_otp_' . md5($cleanNumber);
        $cached = Cache::get($cacheKey);

        $validOtp = null;
        if ($cached && isset($cached['otp']) && ($cached['expires_at'] ?? 0) > time()) {
            $validOtp = (string)$cached['otp'];
        }

        // Check session backup
        if (empty($validOtp) && session('wa_signup_phone') === $cleanNumber && session('wa_signup_exp', 0) > time()) {
            $validOtp = (string)session('wa_signup_otp');
        }

        if (empty($validOtp)) {
            return [
                'status'  => 0,
                'message' => __('Verification code has expired. Please request a new code.'),
            ];
        }

        if ($validOtp !== $enteredOtp) {
            return [
                'status'  => 0,
                'message' => __('Invalid verification code. Please check and try again.'),
            ];
        }

        // Successfully verified! Set verified flag for 30 minutes
        Cache::forget($cacheKey);
        Cache::put('wa_verified_' . md5($cleanNumber), time(), 1800);
        session([
            'wa_verified_phone' => $cleanNumber,
            'wa_verified_time'  => time(),
        ]);

        return [
            'status'  => 1,
            'message' => __('WhatsApp number verified successfully!'),
            'phone'   => $cleanNumber,
        ];
    }

    /**
     * Send Email OTP code
     */
    public static function sendEmailOtp(string $email, ?string $customOtp = null): array
    {
        $cleanEmail = strtolower(trim($email));
        if (empty($cleanEmail) || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'status'  => 0,
                'message' => __('Please provide a valid email address.'),
            ];
        }

        $config = self::resolveConfig();
        $appName = $config['product_name'] ?: 'Whatsappbulk';
        $otp = $customOtp ?: self::generateOtp();
        $validityMinutes = 10;

        $subject = __("Your :app Verification Code: :otp", ['app' => $appName, 'otp' => $otp]);
        $html = '
        <div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e5e7eb; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <div style="background: #1f7a45; padding: 28px 24px; text-align: center;">
                <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">' . htmlspecialchars($appName) . '</h1>
                <p style="color: #dcfce7; margin: 6px 0 0 0; font-size: 14px;">' . __('Email Verification') . '</p>
            </div>
            <div style="padding: 32px 28px; text-align: center;">
                <p style="color: #374151; font-size: 16px; line-height: 1.6; margin-top: 0;">' . __('Thank you for registering. Use the verification code below to verify your email address:') . '</p>
                <div style="margin: 28px 0; padding: 18px; background: #f0fdf4; border: 2px dashed #86efac; border-radius: 12px; display: inline-block;">
                    <span style="font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #166534; font-family: monospace;">' . $otp . '</span>
                </div>
                <p style="color: #6b7280; font-size: 13px; line-height: 1.5; margin-bottom: 0;">' . __('This code is valid for 10 minutes. If you did not request this code, you can safely ignore this email.') . '</p>
            </div>
            <div style="background: #f9fafb; padding: 16px; text-align: center; border-top: 1px solid #f3f4f6;">
                <p style="color: #9ca3af; font-size: 12px; margin: 0;">&copy; ' . date('Y') . ' ' . htmlspecialchars($appName) . '. ' . __('All rights reserved.') . '</p>
            </div>
        </div>';

        $sent = false;

        // Method 1: Using Stackposts \MailSender if available
        if (class_exists('\MailSender')) {
            try {
                $sent = \MailSender::sendMail($cleanEmail, $subject, $html, [], [], false);
            } catch (\Throwable $e) {}
        }

        // Method 2: Standard Laravel Mail facade
        if (!$sent) {
            try {
                \Mail::html($html, function ($msg) use ($cleanEmail, $subject, $appName) {
                    $msg->to($cleanEmail)->subject($subject);
                });
                $sent = true;
            } catch (\Throwable $e) {
                @file_put_contents(storage_path('logs/whatsapp_otp.log'), "[" . date('Y-m-d H:i:s') . "] [EmailOTP Error] {$e->getMessage()}\n", FILE_APPEND);
            }
        }

        // Cache Email OTP for 10 minutes
        $cacheKey = 'email_otp_' . md5($cleanEmail);
        Cache::put($cacheKey, [
            'otp'        => $otp,
            'expires_at' => time() + ($validityMinutes * 60),
        ], $validityMinutes * 60);

        session([
            'email_signup_otp'   => $otp,
            'email_signup_email' => $cleanEmail,
            'email_signup_exp'   => time() + ($validityMinutes * 60),
        ]);

        return [
            'status'  => 1,
            'message' => __('Verification code sent to :email', ['email' => $cleanEmail]),
            'email'   => $cleanEmail,
            'sent'    => $sent,
        ];
    }

    /**
     * Verify Email OTP code
     */
    public static function verifyEmailOtp(string $email, string $enteredOtp): array
    {
        $cleanEmail = strtolower(trim($email));
        $enteredOtp = trim($enteredOtp);

        if (empty($enteredOtp)) {
            return [
                'status'  => 0,
                'message' => __('Please enter the 6-digit email OTP code.'),
            ];
        }

        $cacheKey = 'email_otp_' . md5($cleanEmail);
        $cached = Cache::get($cacheKey);

        $validOtp = null;
        if ($cached && isset($cached['otp']) && ($cached['expires_at'] ?? 0) > time()) {
            $validOtp = (string)$cached['otp'];
        }

        if (empty($validOtp) && session('email_signup_email') === $cleanEmail && session('email_signup_exp', 0) > time()) {
            $validOtp = (string)session('email_signup_otp');
        }

        if (empty($validOtp)) {
            return [
                'status'  => 0,
                'message' => __('Verification code has expired. Please request a new code.'),
            ];
        }

        if ($validOtp !== $enteredOtp) {
            return [
                'status'  => 0,
                'message' => __('Invalid email verification code. Please check and try again.'),
            ];
        }

        // Successfully verified! Set verified flag for 30 minutes
        Cache::forget($cacheKey);
        Cache::put('email_verified_' . md5($cleanEmail), time(), 1800);
        session([
            'email_verified_email' => $cleanEmail,
            'email_verified_time'  => time(),
        ]);

        return [
            'status'  => 1,
            'message' => __('Email address verified successfully!'),
            'email'   => $cleanEmail,
        ];
    }

    /**
     * Check if both phone and email are verified
     */
    public static function isPhoneVerified(string $phone, string $countryCode = '91'): bool
    {
        $cleanNumber = self::formatNumber($phone, $countryCode);
        if (empty($cleanNumber)) {
            return false;
        }

        if (Cache::has('wa_verified_' . md5($cleanNumber))) {
            return true;
        }

        return (session('wa_verified_phone') === $cleanNumber);
    }

    public static function isEmailVerified(string $email): bool
    {
        $cleanEmail = strtolower(trim($email));
        if (empty($cleanEmail)) {
            return false;
        }

        if (Cache::has('email_verified_' . md5($cleanEmail))) {
            return true;
        }

        return (session('email_verified_email') === $cleanEmail);
    }

    /**
     * Ensure database columns exist on users table
     */
    public static function ensureUserColumnsExist(): void
    {
        try {
            if (Schema::hasTable('users')) {
                if (!Schema::hasColumn('users', 'country_code')) {
                    Schema::table('users', function (Blueprint $table) {
                        $table->string('country_code', 10)->nullable()->after('email');
                    });
                }
                if (!Schema::hasColumn('users', 'phone')) {
                    Schema::table('users', function (Blueprint $table) {
                        $table->string('phone', 30)->nullable()->after('country_code');
                    });
                }
                if (!Schema::hasColumn('users', 'phone_verified_at')) {
                    Schema::table('users', function (Blueprint $table) {
                        $table->timestamp('phone_verified_at')->nullable()->after('phone');
                    });
                }
                if (!Schema::hasColumn('users', 'email_verified_at')) {
                    Schema::table('users', function (Blueprint $table) {
                        $table->timestamp('email_verified_at')->nullable()->after('phone_verified_at');
                    });
                }
            }
        } catch (\Throwable $e) {}
    }
}