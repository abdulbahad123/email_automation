<?php
require_once __DIR__ . '/../app/finfo_polyfill.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

define('LARAVEL_START', microtime(true));

// Auto-initialize directories
@mkdir(__DIR__ . '/../storage/framework/cache', 0777, true);
@mkdir(__DIR__ . '/../storage/framework/sessions', 0777, true);
@mkdir(__DIR__ . '/../storage/framework/views', 0777, true);
@mkdir(__DIR__ . '/../storage/logs', 0777, true);
@mkdir(__DIR__ . '/../bootstrap/cache', 0777, true);

// Auto-initialize .env if missing
if (!file_exists(__DIR__ . '/../.env') && file_exists(__DIR__ . '/../.env.example')) {
    @copy(__DIR__ . '/../.env.example', __DIR__ . '/../.env');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Check vendor autoloader exists
if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    http_response_code(503);
    echo '<!DOCTYPE html><html><head><title>WhatsApp Bulk Setup Required</title><style>body{font-family:sans-serif;background:#0f172a;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;} .card{background:#1e293b;padding:32px;border-radius:12px;max-width:540px;box-shadow:0 10px 25px rgba(0,0,0,0.5);} h2{color:#10b981;margin-top:0;} code{background:#334155;padding:4px 8px;border-radius:4px;color:#38bdf8;}</style></head><body><div class="card"><h2>WhatsApp Bulk Platform Initializing</h2><p>The vendor dependencies must be installed on the server.</p><p>Please run the following command in <code>whatsappautomationtool/Install</code> on cPanel terminal:</p><p><code>composer install --no-dev --optimize-autoloader</code></p></div></body></html>';
    exit;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

use Illuminate\Http\Request;

function loadEnv($path)
{
    if (!file_exists($path)) return [];

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // skip comment
        if (!strpos($line, '=')) continue;

        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
    return $env;
}

$env = loadEnv(__DIR__.'/../.env');
$appInstalled = $env['APP_INSTALLED'] ?? null;
if ((!$appInstalled && $appInstalled !== 'true') || strtolower($appInstalled) === 'false') {
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header('Location: ' . $basePath . '/installer/');
    exit;
}

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());