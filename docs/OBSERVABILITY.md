# Observability Guide – Mini WMS

> Covers: Health checks · Readiness vs Liveness probes · Prometheus metrics · cAdvisor · Grafana · Alert examples

---

## Overview

Mini WMS implements a three-layer observability stack:

```
┌─────────────────────────────────────────────────────────┐
│  Layer 1 – Application signals                          │
│  /health.php  (JSON) · /metrics.php  (Prometheus text)  │
├─────────────────────────────────────────────────────────┤
│  Layer 2 – Container signals                            │
│  cAdvisor  (CPU, RAM, net, disk per container)          │
├─────────────────────────────────────────────────────────┤
│  Layer 3 – Aggregation + visualization                  │
│  Prometheus (scrape + TSDB) · Grafana (dashboards)      │
└─────────────────────────────────────────────────────────┘
```

---

## 1. Health Check Endpoint – `/health.php`

**URL:** `http://localhost:8080/health.php`

**Purpose:** Synchronous check of all application dependencies. Used by:
- Docker Compose health checks (nginx service)
- Kubernetes readiness and liveness probes
- Uptime monitoring tools (Pingdom, UptimeRobot)

**Response (healthy):**
```json
{
  "ok": true,
  "db": true,
  "redis": true,
  "disk": true,
  "uptime": 1477,
  "timestamp": "2026-03-03T06:34:34+00:00",
  "version": "2.0.0"
}
```

**Response (degraded – DB down):**
```json
{
  "ok": false,
  "db": false,
  "redis": true,
  "disk": true,
  "uptime": 62,
  "timestamp": "2026-03-03T06:44:46+00:00",
  "version": "2.0.0"
}
```

**HTTP status codes:**
| State | Code |
|-------|------|
| All checks pass | `200 OK` |
| Any check fails | `503 Service Unavailable` |

**Checks performed:**
1. **DB** – `db_ping()` via PDO (live query to MySQL)
2. **Redis** – `$redis->connect()` + `ping()` (2 s timeout)
3. **Disk** – `is_writable($logDir)` (log directory writable)
4. **Uptime** – seconds since `/tmp/mini_wms_start` timestamp

---

## 2. Readiness vs Liveness Probes

These are Kubernetes concepts implemented via `/health.php`. They serve distinct purposes:

### Readiness Probe

> "Is this pod ready to receive traffic?"

```yaml
# deploy/k8s/app-deployment.yaml
readinessProbe:
  httpGet:
    path: /health.php
    port: 80
  initialDelaySeconds: 10
  periodSeconds: 5
  failureThreshold: 2
```

**Behavior on failure:**
- Pod is **removed from the Service endpoints**
- No new requests are routed to it
- Pod continues running (not restarted)
- Kubernetes waits for it to recover

**Use case:** During DB outage, readiness fails → pod removed from load balancer rotation → 503 not reached by users.

### Liveness Probe

> "Is this pod alive and should it keep running?"

```yaml
livenessProbe:
  httpGet:
    path: /health.php
    port: 80
  initialDelaySeconds: 30
  periodSeconds: 10
  failureThreshold: 3
```

**Behavior on failure:**
- Pod is **killed and restarted** by kubelet
- Equivalent to Docker's `restart: unless-stopped`

**Use case:** php-fpm deadlock or OOM → liveness fails after 30 s → pod restarted automatically.

### Key Difference

| Probe | Failure action | Use case |
|-------|---------------|----------|
| Readiness | Stop routing traffic | Temporary overload, DB down |
| Liveness | Restart the pod | Hung process, memory leak |

---

## 3. Prometheus Metrics – `/metrics.php`

**URL:** `http://localhost:8080/metrics.php`

**Format:** [Prometheus text exposition format 0.0.4](https://prometheus.io/docs/instrumenting/exposition_formats/)

**Exposed metrics:**

| Metric | Type | Description |
|--------|------|-------------|
| `mini_wms_up` | gauge | App is running (always 1 if endpoint responds) |
| `mini_wms_db_up` | gauge | MySQL reachable (1=yes, 0=no) |
| `mini_wms_redis_up` | gauge | Redis reachable (1=yes, -1=not configured) |
| `mini_wms_uptime_seconds` | counter | Seconds since app start |
| `mini_wms_scrape_duration_seconds` | gauge | Time to generate this metrics response |

**Example output:**
```
# HELP mini_wms_up Is the Mini WMS application up (1=up, 0=down)
# TYPE mini_wms_up gauge
mini_wms_up 1

# HELP mini_wms_db_up Is the database reachable (1=yes, 0=no)
# TYPE mini_wms_db_up gauge
mini_wms_db_up 1

# HELP mini_wms_redis_up Is Redis reachable (1=yes, 0=no)
# TYPE mini_wms_redis_up gauge
mini_wms_redis_up 1

# HELP mini_wms_uptime_seconds Application uptime in seconds
# TYPE mini_wms_uptime_seconds counter
mini_wms_uptime_seconds 1307

# HELP mini_wms_scrape_duration_seconds Duration of this metrics scrape
# TYPE mini_wms_scrape_duration_seconds gauge
mini_wms_scrape_duration_seconds 0.002962
```

**Scrape config** (`deploy/monitoring/prometheus.yml`):
```yaml
- job_name: 'mini-wms-app'
  scrape_interval: 15s
  metrics_path: /metrics.php
  static_configs:
    - targets: ['nginx:80']
```

---

## 4. cAdvisor – Container-Level Metrics

**URL:** `http://localhost:8081`

cAdvisor (Container Advisor) runs as a sidecar and exposes infrastructure-level metrics for each container:

| Metric group | Examples |
|-------------|---------|
| CPU | `container_cpu_usage_seconds_total` |
| Memory | `container_memory_usage_bytes`, `container_memory_rss` |
| Network | `container_network_receive_bytes_total` |
| Disk I/O | `container_blkio_device_usage_total` |

**In Prometheus**, query per container:
```promql
# CPU usage rate for app container (last 5m)
rate(container_cpu_usage_seconds_total{name="mini-wms-app"}[5m]) * 100

# Memory usage in MiB
container_memory_usage_bytes{name="mini-wms-app"} / 1024 / 1024

# Network I/O
rate(container_network_receive_bytes_total{name="mini-wms-nginx"}[5m])
```

---

## 5. Grafana Dashboard

**URL:** `http://localhost:3000`
**Credentials:** `admin` / `admin123`

The dashboard at `deploy/monitoring/grafana-dashboard.json` is auto-provisioned via:
- `deploy/monitoring/grafana-provisioning/datasources/prometheus.yml` → Prometheus data source
- `deploy/monitoring/grafana-provisioning/dashboards/default.yml` → Dashboard folder

**Dashboard panels:**
- App up/down status (green/red)
- DB and Redis availability
- Uptime counter
- Scrape duration gauge
- Container CPU (from cAdvisor)
- Container memory (from cAdvisor)

---

## 6. Prometheus Alert Examples

These alert rules can be added to `prometheus.yml` under `rule_files` or in a separate `alerts.yml`:

```yaml
groups:
  - name: mini_wms_alerts
    rules:

      # App completely down
      - alert: AppDown
        expr: mini_wms_up == 0
        for: 1m
        labels:
          severity: critical
        annotations:
          summary: "Mini WMS application is down"
          description: "The /metrics.php endpoint is unreachable for more than 1 minute."

      # Database unreachable
      - alert: DatabaseDown
        expr: mini_wms_db_up == 0
        for: 2m
        labels:
          severity: critical
        annotations:
          summary: "MySQL database is unreachable"
          description: "mini_wms_db_up has been 0 for {{ $value }} seconds."

      # Redis unreachable
      - alert: RedisDown
        expr: mini_wms_redis_up == 0
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "Redis is unreachable"
          description: "Application is running in degraded mode without Redis."

      # High container CPU
      - alert: HighCPU
        expr: >
          rate(container_cpu_usage_seconds_total{name="mini-wms-app"}[5m]) * 100 > 80
        for: 3m
        labels:
          severity: warning
        annotations:
          summary: "App container CPU > 80%"
          description: "Consider scaling up (HPA in K8s or --scale in Compose)."

      # High memory
      - alert: HighMemory
        expr: >
          container_memory_usage_bytes{name="mini-wms-app"} / 1024 / 1024 > 200
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "App container memory > 200 MiB"
```

To enable alerts, uncomment the `alerting` block in `prometheus.yml` and deploy Alertmanager.

---

## 7. Observability Data Flow

```
Browser / Load balancer
        │
        ▼
   nginx (8080)
   ├── /health.php     ──────────────────► Docker healthcheck
   │                                       K8s readiness/liveness
   │
   ├── /metrics.php    ◄──── Prometheus scrape (every 15 s)
   │                         └─► Grafana dashboard (port 3000)
   │
   └── app pages (PHP-FPM)

cAdvisor (8081)  ◄──── Prometheus scrape (every 10 s)
                       └─► container_cpu, container_memory, ...
```

---

## 8. Quick Reference

```bash
# Health check
curl http://localhost:8080/health.php | python3 -m json.tool

# Prometheus metrics
curl http://localhost:8080/metrics.php

# cAdvisor UI
open http://localhost:8081

# Prometheus expression browser
open http://localhost:9090

# Grafana dashboard
open http://localhost:3000   # admin / admin123

# Watch health in real time
watch -n 2 "curl -s http://localhost:8080/health.php | python3 -m json.tool"
```
