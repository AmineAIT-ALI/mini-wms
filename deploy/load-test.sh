#!/usr/bin/env bash
# ─── Mini WMS – Load Test Script ───────────────────────────────────────────
set -euo pipefail

URL="${1:-http://localhost:8080/health.php}"
REQUESTS="${2:-100}"
CONCURRENCY="${3:-10}"

echo "╔══════════════════════════════════════════════╗"
echo "║       Mini WMS – Load Test               ║"
echo "╚══════════════════════════════════════════════╝"
echo "  Target      : $URL"
echo "  Requests    : $REQUESTS"
echo "  Concurrency : $CONCURRENCY"
echo ""

if ! command -v curl &>/dev/null; then
    echo "ERROR: curl is required." && exit 1
fi

TMPDIR_RES=$(mktemp -d)
trap 'rm -rf "$TMPDIR_RES"' EXIT

# ─── Single request function ──────────────────────────────────────────────────
do_request() {
    local id="$1"
    local out
    out=$(curl -s -o /dev/null -w "%{http_code}:%{time_total}" \
        --connect-timeout 5 --max-time 10 "$URL" 2>/dev/null || echo "000:0")
    echo "$out" > "${TMPDIR_RES}/req_${id}"
}
export -f do_request
export URL TMPDIR_RES

# ─── Execute requests in batches ──────────────────────────────────────────────
start_ns=$(date +%s%N 2>/dev/null || python3 -c "import time; print(int(time.time()*1e9))")
completed=0
pids=()

while [ "$completed" -lt "$REQUESTS" ]; do
    batch=0
    pids=()
    while [ "$batch" -lt "$CONCURRENCY" ] && [ "$completed" -lt "$REQUESTS" ]; do
        do_request "$completed" &
        pids+=($!)
        completed=$((completed + 1))
        batch=$((batch + 1))
    done
    for pid in "${pids[@]}"; do
        wait "$pid" 2>/dev/null || true
    done
    printf "\r  Progress: %d/%d" "$completed" "$REQUESTS"
done
echo ""

end_ns=$(date +%s%N 2>/dev/null || python3 -c "import time; print(int(time.time()*1e9))")
elapsed_ms=$(( (end_ns - start_ns) / 1000000 ))

# ─── Collect results ──────────────────────────────────────────────────────────
success=0
fail=0
total_latency=0

for f in "${TMPDIR_RES}"/req_*; do
    [ -f "$f" ] || continue
    content=$(cat "$f")
    code="${content%%:*}"
    time_s="${content##*:}"
    time_ms=$(echo "$time_s * 1000" | bc 2>/dev/null || python3 -c "print(int(float('${time_s}') * 1000))")
    if [ "$code" = "200" ]; then
        success=$((success + 1))
        total_latency=$((total_latency + time_ms))
    else
        fail=$((fail + 1))
    fi
done

avg_latency=0
[ "$success" -gt 0 ] && avg_latency=$((total_latency / success))

rps=0
[ "$elapsed_ms" -gt 0 ] && rps=$((REQUESTS * 1000 / elapsed_ms))

# ─── Results ──────────────────────────────────────────────────────────────────
echo ""
echo "╔══════════════════════════════════════════════╗"
echo "║                  Results                    ║"
echo "╚══════════════════════════════════════════════╝"
printf "  Total time   : %s ms\n" "$elapsed_ms"
printf "  Success      : %d / %d\n" "$success" "$REQUESTS"
printf "  Failed       : %d\n" "$fail"
printf "  Avg latency  : %s ms\n" "$avg_latency"
printf "  Throughput   : ~%d req/s\n" "$rps"
echo ""

[ "$fail" -gt 0 ] && echo "  ⚠ Some requests failed. Check if the app is running." && exit 1
echo "  ✓ All requests succeeded."
