<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantDatabaseMiddleware
{
    /**
     * Handle multi-tenant database switching for WhatsApp Bulk.
     * Main domain (e.g. whatsappbulk.saasreselling.com) -> nooryak_whatautomation
     * Agency domain (e.g. whatsappbulk.saasreselling.in, custom agency domains) -> unique agency DB (e.g. nooryak_ps_saasreselling_whatsapp)
     */
    public function handle(Request $request, Closure $next): Response
    {
        // In standalone mode, stay strictly on the configured single application database.
        if (filter_var(env('STANDALONE_MODE', true), FILTER_VALIDATE_BOOLEAN)) {
            $configuredDb = env('DB_DATABASE') ?: env('DB_DATABASE_MAIN') ?: config('database.connections.mysql.database');
            if (!empty($configuredDb)) {
                $this->switchDatabase($configuredDb);
                $this->ensureSchemaProvisioned($configuredDb);
            }
            return $next($request);
        }

        $host = $request->getHost();
        $normalizedHost = strtolower(preg_replace('/^www\./', '', $host));
        $normalizedHost = preg_replace('/:\d+$/', '', $normalizedHost);

        $cleanHost = preg_replace('/^(whatsappbulk|whatsapp-bulk|whatsapp|wa|app)\./i', '', $normalizedHost);

        // Explicit tenant override via query params (useful for testing or admin impersonation)
        $explicitTenant = $request->query('agency') ?? $request->query('tenant') ?? $request->query('agency_slug');
        $explicitDb = $request->query('tenant_db') ?? $request->query('db');

        // Main platform domain detection: strictly use nooryak_whatautomation
        $isMainPlatform = in_array($cleanHost, [
            'saasreselling.com',
            'whatsappbulk.saasreselling.com',
        ], true) || in_array($normalizedHost, [
            'saasreselling.com',
            'whatsappbulk.saasreselling.com',
        ], true);

        $isLocalHost = in_array($normalizedHost, ['localhost', '127.0.0.1'], true);

        if (($isMainPlatform || ($isLocalHost && empty($explicitTenant) && empty($explicitDb))) && empty($explicitTenant) && empty($explicitDb)) {
            $mainDb = env('DB_DATABASE_MAIN', 'nooryak_whatautomation');
            $this->switchDatabase($mainDb);
            return $next($request);
        }

        // It is an agency domain or explicit tenant
        $targetDb = null;

        if (!empty($explicitDb) && $this->databaseExists($explicitDb)) {
            $targetDb = $explicitDb;
        } else {
            // 1. Resolve agency from Sass Admin DB
            $agency = $this->resolveAgency($cleanHost, $normalizedHost, $explicitTenant);

            // 2. Build candidate database list in priority order
            $candidates = [];

            if ($agency) {
                if (!empty($agency->whatsapp_database) && !in_array($agency->whatsapp_database, ['nooryak_whatautomation', 'replix_automation'])) {
                    $candidates[] = $agency->whatsapp_database;
                }

                $productDb = $this->findAgencyProductDb((int)$agency->id, 'whatsappbulk');
                if ($productDb) {
                    $candidates[] = $productDb;
                }

                $agencySlug = str_replace(['-', ' '], '_', strtolower($agency->slug ?? $agency->name ?? ''));
                $shortSlug = substr($agencySlug, 0, 16);
                $cpanelUser = env('CPANEL_USER', 'nooryak');

                $candidates[] = "{$cpanelUser}_ps_{$agencySlug}_whatsapp";
                $candidates[] = "{$cpanelUser}_ps_{$shortSlug}_whatsapp";
                $candidates[] = "nooryak_ps_{$agencySlug}_whatsapp";
                $candidates[] = "nooryak_ps_{$shortSlug}_whatsapp";
                $candidates[] = "{$cpanelUser}_{$agencySlug}_whatsapp";
                $candidates[] = "{$agencySlug}_whatsapp";
            }

            // Fallback from cleanHost domain (e.g. saasreselling.in -> saasreselling)
            $subSlug = explode('.', $cleanHost)[0];
            $subSlug = str_replace(['-', ' '], '_', strtolower($subSlug));
            $shortSubSlug = substr($subSlug, 0, 16);
            $cpanelUser = env('CPANEL_USER', 'nooryak');

            $candidates[] = "{$cpanelUser}_ps_{$subSlug}_whatsapp";
            $candidates[] = "{$cpanelUser}_ps_{$shortSubSlug}_whatsapp";
            $candidates[] = "nooryak_ps_{$subSlug}_whatsapp";
            $candidates[] = "nooryak_ps_{$shortSubSlug}_whatsapp";
            $candidates[] = "{$subSlug}_whatsapp";

            $candidates = array_values(array_unique(array_filter($candidates)));

            // 3. Find the first valid database that actually exists
            foreach ($candidates as $cand) {
                if ($this->databaseExists($cand)) {
                    $targetDb = $cand;
                    break;
                }
            }
        }

        if ($targetDb) {
            $this->switchDatabase($targetDb);
            $this->ensureSchemaProvisioned($targetDb);
        } else {
            // Safe fallback to main DB
            $mainDb = env('DB_DATABASE_MAIN', 'nooryak_whatautomation');
            $this->switchDatabase($mainDb);
        }

        return $next($request);
    }

    /**
     * Switch active database connection dynamically.
     */
    protected function switchDatabase(string $dbName): bool
    {
        $currentDb = config('database.connections.mysql.database');
        if ($currentDb === $dbName) {
            return true;
        }

        try {
            DB::purge('mysql');
            config(['database.connections.mysql.database' => $dbName]);
            DB::reconnect('mysql');
            DB::connection('mysql')->getPdo();
            return true;
        } catch (\Throwable $e) {
            // If targeted main DB fails, fallback to replix_automation or current
            try {
                if ($dbName === 'nooryak_whatautomation') {
                    DB::purge('mysql');
                    config(['database.connections.mysql.database' => 'replix_automation']);
                    DB::reconnect('mysql');
                    DB::connection('mysql')->getPdo();
                    return true;
                }
            } catch (\Throwable $ex) {}

            try {
                DB::purge('mysql');
                config(['database.connections.mysql.database' => $currentDb]);
                DB::reconnect('mysql');
            } catch (\Throwable $restoreEx) {}

            return false;
        }
    }

    /**
     * Check if a database exists and is accessible.
     */
    protected function databaseExists(string $dbName): bool
    {
        if (empty($dbName)) {
            return false;
        }

        // Try using existing PDO / connection
        try {
            $rows = DB::select("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?", [$dbName]);
            if (!empty($rows)) {
                return true;
            }
        } catch (\Throwable $e) {}

        // Try direct PDO connection test
        $hosts = array_values(array_unique(array_filter([env('DB_HOST', '127.0.0.1'), '127.0.0.1'])));
        $port = env('DB_PORT', '3306');

        $userPairs = array_values(array_filter([
            ['user' => (string)config('database.connections.mysql.username'), 'pass' => (string)config('database.connections.mysql.password')],
            ['user' => env('DB_USERNAME'), 'pass' => env('DB_PASSWORD', '')],
        ], function ($item) {
            return !empty($item['user']);
        }));

        foreach ($hosts as $h) {
            foreach ($userPairs as $pair) {
                try {
                    $dsn = "mysql:host={$h};port={$port};dbname={$dbName};charset=utf8mb4";
                    $pdo = new \PDO($dsn, $pair['user'], $pair['pass'], [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_TIMEOUT => 1,
                    ]);
                    return true;
                } catch (\Throwable $e) {}
            }
        }

        return false;
    }

    /**
     * Resolve agency record from SaaS Admin database.
     */
    protected function resolveAgency(string $cleanHost, string $normalizedHost, ?string $explicitTenant = null): ?object
    {
        $pdo = $this->getSassAdminPdo();
        if (!$pdo) {
            return null;
        }

        $apexDomain = $cleanHost;
        $parts = explode('.', $cleanHost);
        if (count($parts) >= 2) {
            $apexDomain = implode('.', array_slice($parts, -2));
        }

        $slugCandidate = $explicitTenant ?: $parts[0];

        try {
            $stmt = $pdo->prepare("SELECT id, name, slug, custom_domain, whatsapp_database FROM agencies 
                WHERE custom_domain = ? 
                   OR custom_domain = ? 
                   OR custom_domain LIKE ? 
                   OR slug = ? 
                   OR slug = ? 
                ORDER BY (custom_domain = ? OR slug = ?) DESC, id DESC LIMIT 1");
            $stmt->execute([
                $cleanHost,
                $normalizedHost,
                "%{$apexDomain}%",
                $slugCandidate,
                $cleanHost,
                $cleanHost,
                $slugCandidate
            ]);
            $agency = $stmt->fetch(\PDO::FETCH_OBJ);
            return $agency ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Look up agency_products db_name for product 'whatsappbulk'.
     */
    protected function findAgencyProductDb(int $agencyId, string $productSlug = 'whatsappbulk'): ?string
    {
        $pdo = $this->getSassAdminPdo();
        if (!$pdo) {
            return null;
        }

        try {
            $stmt = $pdo->prepare("SELECT ap.db_name
                 FROM agency_products ap
                 JOIN products p ON p.id = ap.product_id
                 WHERE ap.agency_id = ?
                   AND (p.slug = ? OR p.slug = 'whatsapp' OR p.slug LIKE '%whatsapp%')
                   AND ap.db_name IS NOT NULL
                   AND ap.db_name != ''
                 LIMIT 1");
            $stmt->execute([$agencyId, $productSlug]);
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            return $row->db_name ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get PDO connection to Sass Admin database.
     */
    protected function getSassAdminPdo(): ?\PDO
    {
        if (filter_var(env('STANDALONE_MODE', true), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        static $sassPdo = null;
        if ($sassPdo !== null) {
            return $sassPdo;
        }

        $cpanelUser = env('CPANEL_USER', '');
        $dbNameCandidates = array_values(array_unique(array_filter([
            env('SASS_ADMIN_DB'),
            env('DB_DATABASE_admin'),
            $cpanelUser ? "{$cpanelUser}_sass_admindb" : null,
            $cpanelUser ? "{$cpanelUser}_Sass_admindb" : null,
            'sass_admin',
            'sass_admindb',
        ])));

        $userPairs = array_values(array_filter([
            ['user' => env('SASS_ADMIN_DB_USER', env('DB_USERNAME_admin', '')), 'pass' => env('SASS_ADMIN_DB_PASS', env('DB_PASSWORD_admin', ''))],
            ['user' => (string)config('database.connections.mysql.username'), 'pass' => (string)config('database.connections.mysql.password')],
            ['user' => env('DB_USERNAME', ''), 'pass' => env('DB_PASSWORD', '')],
        ], function ($item) {
            return !empty($item['user']);
        }));

        $hosts = array_values(array_unique(array_filter([env('DB_HOST', '127.0.0.1'), '127.0.0.1'])));
        $port = env('DB_PORT', '3306');

        foreach ($dbNameCandidates as $db) {
            foreach ($hosts as $h) {
                foreach ($userPairs as $pair) {
                    try {
                        $dsn = "mysql:host={$h};port={$port};dbname={$db};charset=utf8mb4";
                        $pdo = new \PDO($dsn, $pair['user'], $pair['pass'], [
                            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                            \PDO::ATTR_TIMEOUT => 1,
                        ]);
                        $sassPdo = $pdo;
                        return $sassPdo;
                    } catch (\Throwable $e) {}
                }
            }
        }

        return null;
    }

    /**
     * Ensure database schema is provisioned if the database is newly created or empty.
     */
    protected function ensureSchemaProvisioned(string $dbName): void
    {
        try {
            $hasAccounts = DB::select("SHOW TABLES LIKE 'accounts'");
            if (!empty($hasAccounts)) {
                return;
            }

            // Database is empty! Auto-import replix_automation.sql template
            $templatePaths = [
                base_path('database/replix_automation.sql'),
                base_path('../replix_automation.sql'),
                base_path('../../replix_automation.sql'),
            ];

            $sqlPath = null;
            foreach ($templatePaths as $p) {
                if (file_exists($p)) {
                    $sqlPath = $p;
                    break;
                }
            }

            if ($sqlPath && file_exists($sqlPath)) {
                $sqlContent = file_get_contents($sqlPath);
                if (!empty($sqlContent)) {
                    DB::unprepared($sqlContent);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("TenantDatabaseMiddleware: Auto-import schema error for '{$dbName}': " . $e->getMessage());
        }
    }
}

