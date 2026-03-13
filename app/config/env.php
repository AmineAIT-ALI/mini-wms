<?php
/**
 * Environment loader
 * Reads .env file from project root and populates $_ENV / getenv()
 */
declare(strict_types=1);

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // Strip surrounding quotes
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Resolve .env from project root (two levels up from app/config/)
$envFile = dirname(__DIR__, 2) . '/.env';
load_env($envFile);

// ─── Sync getenv() → $_ENV (Docker/system env vars not in $_ENV yet) ──────────
// With variables_order=EGPCS in php.ini this is already handled,
// but we keep this as a safety net for all PHP-FPM configurations.
foreach (['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS',
          'APP_ENV','APP_SECRET','LOG_PATH',
          'REDIS_HOST','REDIS_PORT'] as $_envKey) {
    if (!isset($_ENV[$_envKey]) && ($v = getenv($_envKey)) !== false) {
        $_ENV[$_envKey] = $v;
    }
}

// ─── Derived constants ────────────────────────────────────────────────────────
define('APP_VERSION', '2.0.0');
define('APP_ENV',    $_ENV['APP_ENV']    ?? 'production');
define('APP_SECRET', $_ENV['APP_SECRET'] ?? '');
define('LOG_PATH',   $_ENV['LOG_PATH']   ?? dirname(__DIR__, 2) . '/logs/app.log');
define('REDIS_HOST', $_ENV['REDIS_HOST'] ?? '');
define('REDIS_PORT', (int) ($_ENV['REDIS_PORT'] ?? 6379));

// ─── Error handling based on environment ──────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    $msg = sprintf("[%s][%d] %s in %s:%d\n", date('Y-m-d H:i:s'), $errno, $errstr, $errfile, $errline);
    error_log($msg, 3, LOG_PATH);
    if (APP_ENV !== 'development' && in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR], true)) {
        // Show generic error page in production
        if (!headers_sent()) {
            http_response_code(500);
        }
        $page500 = dirname(__DIR__, 2) . '/public/500.php';
        if (is_file($page500)) {
            include $page500;
        }
        exit(1);
    }
    return false;
});
