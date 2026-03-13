# Mini WMS – Cloud-Native

> PHP 8.4 · MySQL 8 · Redis 7 · Nginx · Docker Compose · Kubernetes · Prometheus · Grafana · GitHub Actions

A production-ready Warehouse Management System built to demonstrate cloud-native PHP engineering: containerisation, structured logging, health probes, Prometheus metrics, Grafana dashboards, Kubernetes HPA auto-scaling, resilience testing, and a full CI/CD pipeline.

---

## Design goal

Mini WMS is intentionally simple as an application, but complex as an engineering system.

The goal is not to build a full ERP, but to demonstrate how a PHP backend can be engineered with modern cloud-native practices: observable, resilient, scalable, and deployable from a single `docker compose up`.

---

## Why this project

Most PHP portfolios stop at "CRUD with a framework". This project goes further: the application is the vehicle, the engineering around it is the point. Every layer — from the Nginx config to the K8s readiness probe — is a deliberate design decision, justified and tested.

---

## What it demonstrates

| Layer | What |
|---|---|
| **Application** | Role-based auth, CSRF, audit log, stock transactions, PRG pattern |
| **Containerisation** | Multi-stage Dockerfile, minimal Alpine image (~28 MiB at rest) |
| **Observability** | `/health.php` (liveness + readiness), `/metrics.php` (Prometheus), structured JSON logs with UUIDv4 `request_id` |
| **Resilience** | Graceful degradation on DB/Redis failure, `< 10 s` auto-recovery |
| **Scaling** | 1 943 req/s on health endpoint (ApacheBench), Docker Compose `--scale`, K8s HPA 1→5 replicas |
| **CI/CD** | GitHub Actions: lint → build → Trivy scan → integration test → Docker push |
| **Security** | Trivy CRITICAL/HIGH scan, CSRF on every form, prepared statements, bcrypt cost-12 passwords |

---

## Architecture

```
                ┌──────────────────────────────────────────┐
                │           Client / Load Balancer          │
                └──────────────────┬───────────────────────┘
                                   │ :8080
                ┌──────────────────▼───────────────────────┐
                │       Nginx 1.25 (reverse proxy)          │
                │  static files · FastCGI proxy · gzip      │
                └──────────────────┬───────────────────────┘
                                   │ :9000 (FastCGI)
                ┌──────────────────▼───────────────────────┐
                │     PHP-FPM 8.4-alpine (app container)    │
                │  pdo_mysql · opcache · redis extension    │
                └─────────┬────────────────────┬───────────┘
                          │                    │
           ┌──────────────▼──────┐    ┌────────▼──────────┐
           │   MySQL 8 (db)      │    │  Redis 7 (cache)   │
           │   utf8mb4_unicode   │    │  AOF persistence   │
           └─────────────────────┘    └───────────────────┘

Observability stack (same Docker network):
  ┌──────────────────┐   scrape    ┌──────────────────┐   datasource   ┌──────────────┐
  │  cAdvisor :8081  │────────────►│ Prometheus :9090  │───────────────►│ Grafana :3000│
  │  /metrics.php    │────────────►│  15s scrape cycle │                └──────────────┘
  │  /health.php     │────────────►└──────────────────┘
  └──────────────────┘
```

---

## Why this architecture

- **Nginx** handles static assets and FastCGI proxying with low memory overhead — no PHP process tied up serving CSS files.
- **PHP-FPM** keeps the app runtime isolated from the web server; workers are pre-forked and reused, enabling horizontal scaling without state leakage.
- **MySQL 8** provides transactional consistency for inventory and order flows — stock decrements and order item inserts run in a single `BEGIN/COMMIT` block, preventing partial writes.
- **Redis 7** is wired for session storage and prepares the platform for shared caching and future queue workloads without architectural changes.
- **Prometheus + Grafana** provide first-class observability with a pull-based scrape model — no vendor lock-in, no agent overhead on the application side.
- **Kubernetes HPA** demonstrates horizontal scaling under real CPU load, tied to readiness probes that remove pods from the endpoint pool during dependency failures.
- **Alpine base image** reduces the attack surface and keeps the image at ~28 MiB at rest, which matters for pull time in CI and cold-start latency in K8s.

---

## Proof

- **Health checks**: `/health.php` returns `200` when all dependencies are healthy, `503` otherwise — used as both liveness and readiness probe in K8s.
- **Metrics**: Prometheus scrape endpoint at `/metrics.php` exposes DB/Redis status, uptime, and scrape duration; Grafana auto-provisions the datasource and dashboard.
- **Resilience**: DB outage detected in `< 1 s`, health endpoint returns `503`, application recovers automatically in `< 10 s` after restart — [full test log](docs/RESILIENCE_TEST.md).
- **Scaling**: `1 943 req/s` on health endpoint, `5 088 req/s` on login page, zero failures across all ApacheBench runs — [full results](docs/SCALING_TEST.md).
- **CI/CD**: lint → build → Trivy CRITICAL/HIGH scan → docker-compose integration test → Docker push on `main` — pipeline defined in [`.github/workflows/ci.yml`](.github/workflows/ci.yml).

---

## Screenshots

### Landing page
![Landing](docs/screenshots/landing.png)

### App – Dashboard
![Dashboard](docs/screenshots/dashboard.png)

### Observability – Grafana
![Grafana](docs/screenshots/grafana.png)

### Health endpoint
![Health](docs/screenshots/health.png)

### CI/CD – GitHub Actions (green)
![CI](docs/screenshots/ci.png)

---

## Key Endpoints

| Endpoint | Method | Purpose |
|---|---|---|
| `/health.php` | GET | Liveness & readiness probe — returns `200` or `503` |
| `/metrics.php` | GET | Prometheus scrape endpoint |
| `/login.php` | GET / POST | Authentication |
| `/dashboard.php` | GET | Operational overview (KPIs, recent orders, low stock) |
| `/products.php` | GET | Product catalogue with search |
| `/orders.php` | GET | Orders list with status filtering |
| `/stock_moves.php` | GET | Inventory movement history |
| `/audit.php` | GET | Audit log — admin only |

---

## Local Development

The entire stack runs locally via Docker Compose — no local PHP or MySQL installation required.

Services started with `docker compose up`:

| Service | Role | Port |
|---|---|---|
| Nginx | Reverse proxy, static files | 8080 |
| PHP-FPM | Application runtime | 9000 (internal) |
| MySQL 8 | Relational store | 3306 (internal) |
| Redis 7 | Session cache | 6379 (internal) |
| Prometheus | Metrics collector | 9090 |
| Grafana | Dashboard UI | 3000 |
| cAdvisor | Container metrics | 8081 |

---

## Quick Start

### Prerequisites

- Docker Desktop ≥ 4.x (with Docker Compose v2)
- 2 GB RAM available for containers

### 1. Clone and start (Docker only)

```bash
git clone https://github.com/AmineAIT-ALI/mini-wms.git
cd mini-wms
docker compose -f deploy/docker/docker-compose.yml up -d
```

### 2. Wait for all containers to become healthy (~60 s)

```bash
docker compose -f deploy/docker/docker-compose.yml ps
```

Expected output:
```
mini-wms-app          Up X minutes (healthy)
mini-wms-cadvisor     Up X minutes (healthy)
mini-wms-db           Up X minutes (healthy)
mini-wms-grafana      Up X minutes
mini-wms-nginx        Up X minutes (healthy)
mini-wms-prometheus   Up X minutes
mini-wms-redis        Up X minutes (healthy)
```

### 3. Open the application

| Service | URL | Credentials |
|---------|-----|-------------|
| App | http://localhost:8080 | admin@local.test / Password123! |
| Prometheus | http://localhost:9090 | — |
| Grafana | http://localhost:3000 | admin / admin123 |
| cAdvisor | http://localhost:8081 | — |

---

## Observability

### Health endpoint

```bash
curl http://localhost:8080/health.php
```

```json
{
  "ok": true, "db": true, "redis": true, "disk": true,
  "uptime": 1477, "timestamp": "2026-03-03T06:34:34+00:00", "version": "2.0.0"
}
```

Returns **200** when all checks pass, **503** when any dependency is down.

### Prometheus metrics

```bash
curl http://localhost:8080/metrics.php
```

```
mini_wms_up 1
mini_wms_db_up 1
mini_wms_redis_up 1
mini_wms_uptime_seconds 1307
mini_wms_scrape_duration_seconds 0.002962
```

Prometheus scrapes every 15 seconds. Grafana auto-provisions the datasource and dashboard.

### Structured JSON logging

Every HTTP request produces a JSON log entry in `logs/app.log`:

```json
{
  "timestamp": "2026-03-03T06:57:33+00:00",
  "level": "INFO",
  "request_id": "8a866a24-c597-4f91-a3d1-527761a522f9",
  "message": "request completed",
  "status_code": 200,
  "response_time_ms": 0.08,
  "request": {"method": "GET", "uri": "/login.php", "ip": "185.85.0.29"},
  "app": "mini-wms",
  "version": "2.0.0"
}
```

Fields: `request_id` (UUIDv4 per request), `response_time_ms`, `status_code`, client IP.
Full schema and query examples: [docs/LOGGING.md](docs/LOGGING.md).

---

## Resilience

Tested and documented in [docs/RESILIENCE_TEST.md](docs/RESILIENCE_TEST.md):

| Scenario | Detection | App behavior | Recovery |
|----------|-----------|--------------|----------|
| App container crash | nginx 504 | Gateway timeout | Auto-restart (compose/K8s) |
| MySQL down | `db: false` in `/health.php` | HTTP 503 on health | Auto-reconnect in < 10 s |
| Redis down | `redis: false` in `/health.php` | HTTP 503 on health, pages → 200 | Auto-reconnect in < 15 s |

```bash
# Simulate DB failure
docker stop mini-wms-db
curl http://localhost:8080/health.php   # → {"ok":false,"db":false,...}  HTTP 503

# Restore
docker start mini-wms-db
curl http://localhost:8080/health.php   # → {"ok":true,...}  HTTP 200  (< 10s)
```

---

## Scaling

Tested with ApacheBench — [docs/SCALING_TEST.md](docs/SCALING_TEST.md):

| Test | Concurrency | Throughput | Avg latency | Failures |
|------|-------------|------------|-------------|----------|
| Health endpoint | 20 | 1 943 req/s | 10.3 ms | 0 |
| Health endpoint (heavy) | 50 | 1 922 req/s | 26.0 ms | 0 |
| Login page | 10 | 5 088 req/s | 1.97 ms | 0 |

**Memory footprint:** App + Nginx = ~28 MiB at rest. Five replicas ≈ 90 MiB.

### Docker Compose scale

```bash
docker compose -f deploy/docker/docker-compose.yml up -d --scale app=3
```

### Kubernetes HPA

```bash
kubectl apply -f deploy/k8s/
kubectl get hpa -n mini-wms -w
# Scales from 1 → 5 replicas when CPU > 50%
```

**Key K8s features:**
- `readinessProbe` on `/health.php` → removes pod from endpoints during DB outage
- `livenessProbe` on `/health.php` → restarts pod on deadlock/OOM
- `HPA` scales 1 → 5 replicas at 50% CPU
- `PVC` for MySQL data persistence
- `ConfigMap` for env config, `Secret` for DB password

---

## CI/CD

GitHub Actions workflow: [`.github/workflows/ci.yml`](.github/workflows/ci.yml)

```
push / PR
    │
    ▼
[lint]        PHP parallel-lint (all .php files)
    │
    ▼
[build]       docker buildx (cached layers, saved as artifact)
    │
    ├──▶ [security]      Trivy image scan (CRITICAL/HIGH → fail)
    │                    Trivy filesystem SARIF → GitHub Security tab
    │
    └──▶ [integration]   docker compose up → health check → smoke test
    │
    ▼  (main branch only)
[push]        docker push to DockerHub (tags: latest + short SHA)
```

**Required GitHub secrets:**
- `DOCKERHUB_USERNAME`
- `DOCKERHUB_TOKEN`

---

## Project Structure

```
mini-wms/
├── app/
│   ├── config/          bootstrap.php · db.php · env.php
│   ├── lib/             auth · csrf · logger · validators · audit · flash
│   ├── models/          User · Product · Order · StockMove · AuditLog
│   └── views/           PHP templates (layout + partials)
├── public/              index.php · login.php · health.php · metrics.php · …
│   └── assets/          css · js · img
├── sql/                 schema.sql · seed.sql
├── logs/                app.log (volume-mounted)
├── deploy/
│   ├── docker/          Dockerfile · docker-compose.yml · nginx.conf · php.ini · entrypoint.sh
│   ├── k8s/             10 Kubernetes manifests (namespace → HPA)
│   ├── monitoring/      prometheus.yml · grafana-dashboard.json · provisioning/
│   └── load-test.sh
├── docs/
│   ├── screenshots/     dashboard.png · grafana.png · health.png · ci.png
│   ├── RESILIENCE_TEST.md
│   ├── SCALING_TEST.md
│   ├── OBSERVABILITY.md
│   ├── LOGGING.md
│   └── README_FR.md     French translation of this README
└── .github/
    └── workflows/ci.yml
```

---

## Roadmap

| Item | Priority | Notes |
|------|----------|-------|
| Redis-based session storage | Medium | Replace file sessions for multi-replica support |
| Request tracing headers (`X-Request-ID`) | Medium | Correlate nginx + app logs |
| Alertmanager integration | Medium | Route Prometheus alerts to Slack/PagerDuty |
| Read-replica for MySQL | Low | Scale read-heavy workloads |
| Helm chart | Low | Package K8s manifests for easy deployment |
| OpenTelemetry traces | Low | Distributed tracing across services |

---

## Documentation

| Doc | Contents |
|-----|----------|
| [docs/OBSERVABILITY.md](docs/OBSERVABILITY.md) | Health probes, readiness vs liveness, Prometheus metrics, cAdvisor, alert rules |
| [docs/LOGGING.md](docs/LOGGING.md) | JSON log schema, Logger API, query examples, request_id tracing |
| [docs/RESILIENCE_TEST.md](docs/RESILIENCE_TEST.md) | Failure scenarios, observed behavior, recovery times |
| [docs/SCALING_TEST.md](docs/SCALING_TEST.md) | ApacheBench results, Docker Compose scale, K8s HPA strategy |
| [docs/README_FR.md](docs/README_FR.md) | French translation of this README |

---

## Engineering focus

This project was built to demonstrate:
- cloud-native backend design with explicit justification for every technology choice
- observability-first thinking: health probes, structured logs, and metrics from day one
- resilience under dependency failure, verified with real failure injection
- scalable deployment patterns: stateless app container, external session/cache, HPA-ready
- production-oriented CI/CD: security scanning, integration testing, and automated publishing in a single pipeline

---

## License

MIT
