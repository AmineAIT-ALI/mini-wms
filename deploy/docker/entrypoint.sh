#!/bin/sh
set -e

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-mini_wms}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-root123}"

MAX_RETRIES=30
RETRIES=0

echo "[entrypoint] ──────────────────────────────────────────"
echo "[entrypoint] Mini WMS – PHP-FPM starting"
echo "[entrypoint] Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."

until php -r "
    try {
        \$pdo = new PDO(
            'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME};charset=utf8mb4',
            '${DB_USER}',
            '${DB_PASS}',
            [PDO::ATTR_TIMEOUT => 3]
        );
        exit(0);
    } catch (\Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    RETRIES=$((RETRIES + 1))
    if [ "$RETRIES" -ge "$MAX_RETRIES" ]; then
        echo "[entrypoint] ERROR: MySQL not available after ${MAX_RETRIES} attempts. Aborting."
        exit 1
    fi
    echo "[entrypoint] MySQL not ready (attempt ${RETRIES}/${MAX_RETRIES}), retrying in 2s..."
    sleep 2
done

echo "[entrypoint] MySQL ready."

# Ensure logs directory exists and is writable
mkdir -p /var/www/html/logs
chmod 775 /var/www/html/logs 2>/dev/null || true

# Record startup time for uptime tracking
echo "$(date +%s)" > /tmp/mini_wms_start

echo "[entrypoint] Starting PHP-FPM..."
echo "[entrypoint] ──────────────────────────────────────────"

exec "$@"
