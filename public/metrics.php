<?php
/**
 * Prometheus metrics endpoint
 * Exposes app health metrics in Prometheus text exposition format.
 * Scraped by: prometheus.yml → job mini-wms-app
 */
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

header('Content-Type: text/plain; version=0.0.4; charset=utf-8');
header('Cache-Control: no-store');

$scrapeStart = microtime(true);

// ─── DB check ─────────────────────────────────────────────────────────────────
$dbUp = 0;
try {
    $dbUp = db_ping() ? 1 : 0;
} catch (\Throwable) {
    $dbUp = 0;
}

// ─── Redis check ──────────────────────────────────────────────────────────────
$redisUp = -1; // -1 = not configured
if (REDIS_HOST !== '') {
    $redisUp = 0;
    try {
        $redis = new Redis();
        if ($redis->connect(REDIS_HOST, REDIS_PORT, 2.0)) {
            $pong    = $redis->ping();
            $redisUp = ($pong === '+PONG' || $pong === true) ? 1 : 0;
            $redis->close();
        }
    } catch (\Throwable) {
        $redisUp = 0;
    }
}

// ─── Uptime ───────────────────────────────────────────────────────────────────
$startFile = sys_get_temp_dir() . '/mini_wms_start';
$uptime    = file_exists($startFile) ? (time() - (int) file_get_contents($startFile)) : 0;

$scrapeEnd      = microtime(true);
$scrapeDuration = round($scrapeEnd - $scrapeStart, 6);

// ─── Output ───────────────────────────────────────────────────────────────────
$lines = [];

$lines[] = '# HELP mini_wms_up Is the Mini WMS application up (1=up, 0=down)';
$lines[] = '# TYPE mini_wms_up gauge';
$lines[] = "mini_wms_up 1";

$lines[] = '';
$lines[] = '# HELP mini_wms_db_up Is the database reachable (1=yes, 0=no)';
$lines[] = '# TYPE mini_wms_db_up gauge';
$lines[] = "mini_wms_db_up {$dbUp}";

if ($redisUp >= 0) {
    $lines[] = '';
    $lines[] = '# HELP mini_wms_redis_up Is Redis reachable (1=yes, 0=no)';
    $lines[] = '# TYPE mini_wms_redis_up gauge';
    $lines[] = "mini_wms_redis_up {$redisUp}";
}

$lines[] = '';
$lines[] = '# HELP mini_wms_uptime_seconds Application uptime in seconds';
$lines[] = '# TYPE mini_wms_uptime_seconds counter';
$lines[] = "mini_wms_uptime_seconds {$uptime}";

$lines[] = '';
$lines[] = '# HELP mini_wms_scrape_duration_seconds Duration of this metrics scrape';
$lines[] = '# TYPE mini_wms_scrape_duration_seconds gauge';
$lines[] = "mini_wms_scrape_duration_seconds {$scrapeDuration}";

$lines[] = '';

echo implode("\n", $lines);
