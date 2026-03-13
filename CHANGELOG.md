# Changelog

All notable changes to Mini WMS are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [2.0.0] – 2026-03-02

### Added – Cloud-Native Infrastructure
- **Docker**: Dockerfile (php:8.4-fpm-alpine, non-root user, Redis ext, OPcache)
- **Docker Compose**: 7 services (app, nginx, db, redis, prometheus, grafana, cadvisor)
- **Nginx**: reverse proxy with security headers (CSP, X-Frame, nosniff), gzip, static caching
- **Redis**: cache service with health check
- **Prometheus**: scrapes `/metrics.php` + cAdvisor container metrics
- **Grafana**: auto-provisioned dashboard (CPU, RAM, network, app status panels)
- **cAdvisor**: container-level metrics (CPU, memory, network, disk I/O)

### Added – Kubernetes
- `namespace.yaml` – isolated `mini-wms` namespace
- `configmap.yaml` – non-sensitive env vars + nginx.conf ConfigMap
- `secret.yaml` – DB password + APP_SECRET (base64)
- `mysql-deployment.yaml` – MySQL 8 with PVC (5Gi)
- `app-deployment.yaml` – PHP-FPM with readiness/liveness probes
- `nginx-deployment.yaml` – Nginx with initContainer to copy app files
- `redis-deployment.yaml` – Redis 7 with health checks
- `service.yaml` – ClusterIP services for all 4 components
- `ingress.yaml` – Ingress for `mini-wms.local`
- `hpa.yaml` – HPA CPU 50% trigger, 1–5 replicas, scale-down stabilization

### Added – Application
- `public/health.php` – enriched JSON: db + redis + disk + uptime + version
- `public/metrics.php` – Prometheus exposition format endpoint
- `app/lib/logger.php` – structured JSON logger (INFO/WARNING/ERROR/DEBUG)

### Changed
- `app/config/env.php` – Docker env vars take precedence over .env file; added REDIS_HOST/PORT constants
- `app/config/bootstrap.php` – loads Logger
- `deploy/docker/php.ini` – `variables_order=EGPCS`, expose_php=Off, OPcache tuned

### Security
- Non-root container user (UID 1000)
- Nginx security headers (CSP, X-Frame-Options, X-Content-Type-Options)
- `expose_php = Off`, `allow_url_fopen = Off`
- Secrets via env vars, never in code or image layers

---

## [1.0.0] – 2024-03-01

### Added
- Initial release of Mini WMS – Inventory & Orders

#### Authentication
- Login / logout with bcrypt password hashing
- Session-based auth with ID regeneration on login
- Role-based access control (admin / user)
- CSRF protection on all POST forms

#### Products
- Full CRUD for admin (create, edit, delete with FK check)
- List with search (name / SKU) and pagination (20/page)
- Low-stock badge when `stock <= threshold`

#### Orders
- Create orders with multiple items (atomic transaction)
- Status workflow: `pending → picked → shipped / cancelled`
- `pending → picked`: stock check + atomic decrement
- `picked → cancelled`: atomic stock re-increment
- Order detail page with contextual action buttons

#### Stock Moves
- Manual in / manual out entries
- Guard: `manual_out` blocked if stock would go negative
- Filterable history (product, reason, date range) + pagination
- Automatic moves for `order_pick` and `order_cancel`

#### Audit Log
- Logs: login_success, login_fail, logout, product_create,
  product_update, product_delete, order_create,
  order_status_change, stock_move
- JSON metadata per event
- Admin-only audit page with filters and pagination

#### Dashboard
- KPI cards: total products, low-stock count, orders by status (4 counters)
- 5 most recent orders
- 5 most recent stock moves

#### Infrastructure
- PHP 8.4, MySQL 8, vanilla CSS/JS
- No framework, no Composer, no build tool
- `.env` file for secrets
- `health.php` endpoint (JSON, 503 on DB failure)
- Custom 404 / 500 error pages
- Apache `.htaccess` security headers

---

## Unreleased

- [ ] Export CSV for products / orders / stock moves
- [ ] User management page (admin)
- [ ] Bulk product import via CSV
- [ ] API endpoints (JSON REST)
