# Structured Logging Guide – Mini WMS

> File: `app/lib/logger.php` · Log format: JSON lines (one JSON object per line) · Output: `LOG_PATH` env var

---

## Overview

Every HTTP request automatically produces a structured JSON log entry. The logger is initialized in `app/config/bootstrap.php` and fires a completion record at request teardown via `register_shutdown_function`.

---

## Log Entry Schema

```json
{
  "timestamp":        "2026-03-03T06:57:33+00:00",
  "level":            "INFO",
  "request_id":       "8a866a24-c597-4f91-a3d1-527761a522f9",
  "message":          "request completed",
  "context":          { "status_code": 200, "response_time_ms": 0.08 },
  "status_code":      200,
  "response_time_ms": 0.08,
  "request": {
    "method": "GET",
    "uri":    "/login.php",
    "ip":     "185.85.0.29"
  },
  "app":     "mini-wms",
  "version": "2.0.0"
}
```

| Field | Type | Description |
|-------|------|-------------|
| `timestamp` | ISO 8601 | UTC time of log entry |
| `level` | string | `INFO`, `WARNING`, `ERROR`, `DEBUG` |
| `request_id` | UUIDv4 | Unique per HTTP request; shared across all log entries in the same request lifecycle |
| `message` | string | Human-readable description |
| `context` | object | Arbitrary key-value pairs from the call site |
| `status_code` | int | HTTP response code (set via `Logger::setStatus()`) |
| `response_time_ms` | float | Milliseconds since `Logger::startRequest()` |
| `request.method` | string | HTTP verb (`GET`, `POST`, …) or `CLI` |
| `request.uri` | string | Request path + query string |
| `request.ip` | string | Client IP (prefers `X-Forwarded-For`) |
| `app` | string | Always `mini-wms` |
| `version` | string | App version |

---

## Real Log Samples

### Successful request (login page, 0.08 ms)

```json
{"timestamp":"2026-03-03T06:57:33+00:00","level":"INFO","request_id":"8a866a24-c597-4f91-a3d1-527761a522f9","message":"request completed","context":{"status_code":200,"response_time_ms":0.08},"status_code":200,"response_time_ms":0.08,"request":{"method":"GET","uri":"/login.php","ip":"185.85.0.29"},"app":"mini-wms","version":"2.0.0"}
```

### Health check from Prometheus (2.19 ms with DB+Redis checks)

```json
{"timestamp":"2026-03-03T06:57:33+00:00","level":"INFO","request_id":"c71e16e1-790e-4348-9784-75faeba7bf76","message":"request completed","context":{"status_code":200,"response_time_ms":2.19},"status_code":200,"response_time_ms":2.2,"request":{"method":"GET","uri":"/health.php","ip":"172.18.0.3"},"app":"mini-wms","version":"2.0.0"}
```

### Prometheus scrape of metrics endpoint (3.64 ms)

```json
{"timestamp":"2026-03-03T06:57:31+00:00","level":"INFO","request_id":"7eaab47a-3d0f-41f2-bded-9f50a2bf353b","message":"request completed","context":{"status_code":200,"response_time_ms":3.64},"status_code":200,"response_time_ms":3.65,"request":{"method":"GET","uri":"/health.php?format=prometheus","ip":"172.18.0.3"},"app":"mini-wms","version":"2.0.0"}
```

---

## Logger API

### Bootstrap integration (automatic)

```php
// app/config/bootstrap.php
Logger::startRequest();                          // Set request ID + start timer
register_shutdown_function(static function (): void {
    Logger::finish();                            // Write completion log entry
});
```

### Manual logging in page handlers

```php
// Log an info message with context
Logger::info('User logged in', ['user_id' => 42, 'email' => 'user@example.com']);

// Log a warning
Logger::warning('Product stock low', ['product_id' => 7, 'stock' => 2]);

// Log an error
Logger::error('Order creation failed', ['order_id' => null, 'reason' => $e->getMessage()]);

// Log debug (only in APP_ENV=development)
Logger::debug('SQL query executed', ['query' => $sql, 'bindings' => $params]);

// Set HTTP status code (call before http_response_code())
Logger::setStatus(403);
http_response_code(403);
```

### Writing a completion log from a handler

```php
// At the end of a POST handler before redirect
Logger::setStatus(302);
redirect('/orders', 'success', 'Order created');
```

---

## Log File Location

| Environment | Path |
|-------------|------|
| Docker Compose | `/var/www/html/logs/app.log` (volume-mounted to `./logs/app.log` on host) |
| Kubernetes | `/var/www/html/logs/app.log` (PVC-mounted) |
| Local PHP server | Configured via `LOG_PATH` env var |

---

## Querying Logs

### View live logs (Docker)

```bash
# Follow log file
tail -f logs/app.log

# Pretty-print last 10 entries
tail -10 logs/app.log | while IFS= read -r line; do echo "$line" | python3 -m json.tool; echo "---"; done
```

### Filter by request_id

```bash
# Trace all entries from a single request
grep "8a866a24-c597-4f91-a3d1-527761a522f9" logs/app.log
```

### Filter errors only

```bash
grep '"level":"ERROR"' logs/app.log | python3 -c "
import sys, json
for line in sys.stdin:
    d = json.loads(line)
    print(f'{d[\"timestamp\"]} [{d[\"request_id\"]}] {d[\"message\"]} — {d[\"context\"]}')
"
```

### Show slow requests (> 100 ms)

```bash
python3 -c "
import json
with open('logs/app.log') as f:
    for line in f:
        try:
            d = json.loads(line)
            if (d.get('response_time_ms') or 0) > 100:
                print(f'{d[\"timestamp\"]} {d[\"request\"][\"uri\"]} → {d[\"response_time_ms\"]} ms [{d[\"request_id\"]}]')
        except: pass
"
```

### Average response time

```bash
python3 -c "
import json
times = []
with open('logs/app.log') as f:
    for line in f:
        try:
            d = json.loads(line)
            rt = d.get('response_time_ms')
            if rt is not None:
                times.append(rt)
        except: pass
if times:
    print(f'Avg: {sum(times)/len(times):.2f} ms | Min: {min(times):.2f} ms | Max: {max(times):.2f} ms | Count: {len(times)}')
"
```

---

## Log Levels

| Level | Used for |
|-------|----------|
| `INFO` | Normal operation (request completed, user actions) |
| `WARNING` | Degraded state (low stock, failed retries) |
| `ERROR` | Failures requiring attention (DB query failed, auth error) |
| `DEBUG` | Development diagnostics (only when `APP_ENV=development`) |

---

## request_id Tracing

The `request_id` is a UUIDv4 generated by `Logger::startRequest()` at the top of every request. Every log entry within the same PHP execution shares the same `request_id`, making it trivial to reconstruct the full execution trace for a single HTTP request:

```bash
# Find all log entries for a problematic request
grep "REQUEST_ID_HERE" logs/app.log
```

In a distributed system (multiple app replicas), correlate logs across containers by passing the `request_id` as an `X-Request-ID` response header and logging it in nginx:

```nginx
# nginx.conf addition
add_header X-Request-ID $upstream_http_x_request_id;
log_format json_combined escape=json '{"nginx_time":"$time_iso8601","request_id":"$upstream_http_x_request_id","status":$status,"uri":"$request_uri","upstream_time":$upstream_response_time}';
```
