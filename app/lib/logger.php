<?php
/**
 * Structured JSON logger – V2
 * All log entries are written as JSON lines to LOG_PATH.
 *
 * Each entry includes:
 *   request_id        – UUIDv4 per HTTP request (shared across all logs in one request)
 *   response_time_ms  – ms since request start (populated via Logger::finish())
 *   status_code       – HTTP status code (set via Logger::setStatus())
 */
declare(strict_types=1);

class Logger
{
    private static string $requestId  = '';
    private static float  $requestStart = 0.0;
    private static int    $statusCode  = 200;

    // ─── Request lifecycle ────────────────────────────────────────────────────

    /**
     * Call once at bootstrap (session_ensure or top of page) to initialise
     * a per-request ID and start the response timer.
     */
    public static function startRequest(): void
    {
        self::$requestStart = microtime(true);
        self::$requestId    = self::generateRequestId();
        self::$statusCode   = 200;
    }

    /**
     * Set the HTTP status code that will be included in log entries.
     * Call this just before http_response_code() / header().
     */
    public static function setStatus(int $code): void
    {
        self::$statusCode = $code;
    }

    /**
     * Emit a final INFO log entry with response_time_ms for the request.
     * Call at the very end of page execution (or register via register_shutdown_function).
     */
    public static function finish(): void
    {
        $elapsed = self::$requestStart > 0.0
            ? round((microtime(true) - self::$requestStart) * 1000, 2)
            : null;

        self::write('INFO', 'request completed', [
            'status_code'      => self::$statusCode,
            'response_time_ms' => $elapsed,
        ]);
    }

    // ─── Log levels ───────────────────────────────────────────────────────────

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        if ((defined('APP_ENV') ? APP_ENV : 'production') === 'development') {
            self::write('DEBUG', $message, $context);
        }
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    private static function write(string $level, string $message, array $context): void
    {
        $elapsed = self::$requestStart > 0.0
            ? round((microtime(true) - self::$requestStart) * 1000, 2)
            : null;

        $entry = [
            'timestamp'        => date('c'),
            'level'            => $level,
            'request_id'       => self::$requestId ?: null,
            'message'          => $message,
            'context'          => $context,
            'status_code'      => self::$statusCode,
            'response_time_ms' => $elapsed,
            'request'          => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'uri'    => $_SERVER['REQUEST_URI']    ?? '',
                'ip'     => $_SERVER['HTTP_X_FORWARDED_FOR']
                            ?? $_SERVER['REMOTE_ADDR']
                            ?? '',
            ],
            'app'     => 'mini-wms',
            'version' => defined('APP_VERSION') ? APP_VERSION : '2.0.0',
        ];

        $line    = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $logPath = defined('LOG_PATH') ? LOG_PATH : '/tmp/mini_wms.log';

        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        error_log($line, 3, $logPath);
    }

    private static function generateRequestId(): string
    {
        // UUIDv4 (random)
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
