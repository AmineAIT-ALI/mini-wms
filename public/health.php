<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache');
header('X-Content-Type-Options: nosniff');

// ─── DB check ─────────────────────────────────────────────────────────────────
$dbOk = false;
try {
    $dbOk = db_ping();
} catch (\Throwable) {
    $dbOk = false;
}

// ─── Redis check ──────────────────────────────────────────────────────────────
$redisOk = null; // null = not configured
if (REDIS_HOST !== '') {
    $redisOk = false;
    try {
        $redis = new Redis();
        if ($redis->connect(REDIS_HOST, REDIS_PORT, 2.0)) {
            $pong    = $redis->ping();
            $redisOk = ($pong === '+PONG' || $pong === true);
            $redis->close();
        }
    } catch (\Throwable) {
        $redisOk = false;
    }
}

// ─── Disk write check ─────────────────────────────────────────────────────────
$diskOk = false;
$logDir = dirname(LOG_PATH);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
$diskOk = is_writable($logDir);

// ─── Uptime ───────────────────────────────────────────────────────────────────
$startFile = sys_get_temp_dir() . '/mini_wms_start';
if (!file_exists($startFile)) {
    file_put_contents($startFile, (string) time());
}
$uptime = time() - (int) file_get_contents($startFile);

// ─── Overall status ───────────────────────────────────────────────────────────
$allOk = $dbOk && $diskOk && ($redisOk !== false);

$response = [
    'ok'        => $allOk,
    'db'        => $dbOk,
    'redis'     => $redisOk,    // null = not configured, true/false = check result
    'disk'      => $diskOk,
    'uptime'    => $uptime,
    'timestamp' => date('c'),
    'version'   => APP_VERSION,
];

if (!$allOk) {
    http_response_code(503);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
