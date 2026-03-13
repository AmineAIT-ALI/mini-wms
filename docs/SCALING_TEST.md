# Scaling Test Report – Mini WMS

> Environment: Docker Compose · Platform: Apple Silicon M-series (ARM64) · Tool: ApacheBench 2.3 · Date: 2026-03-03

---

## Objective

Measure throughput and latency of the Mini WMS stack under increasing load, and document the scaling strategy available via Docker Compose and Kubernetes HPA.

---

## Test Environment

| Component | Value |
|-----------|-------|
| App image | `mini-wms:v2` (PHP 8.4-fpm-alpine) |
| Nginx | `nginx:1.25-alpine` |
| MySQL | `mysql:8.0` |
| Redis | `redis:7-alpine` |
| Host RAM | 7.65 GiB (Docker Desktop limit) |
| Tool | `ab` (ApacheBench 2.3) |

---

## Resource Snapshot (at rest)

```
mini-wms-nginx        0.00%   11.7 MiB
mini-wms-app          0.01%   16.1 MiB
mini-wms-db           2.44%  364.1 MiB   ← MySQL buffer pool
mini-wms-redis        0.73%    9.8 MiB
mini-wms-prometheus   0.00%   48.5 MiB
mini-wms-grafana      0.06%  126.6 MiB
mini-wms-cadvisor     0.59%   38.3 MiB
─────────────────────────────────────────
Total                          ~615 MiB
```

App + nginx together use only **~28 MiB** at rest.

---

## Load Test Results (1 app replica)

### Test A – Health endpoint (JSON, DB + Redis check)

```bash
ab -n 200 -c 20 http://localhost:8080/health.php
```

| Metric | Value |
|--------|-------|
| Concurrency | 20 |
| Total requests | 200 |
| Failed requests | 0 |
| Requests per second | **1 943 req/s** |
| Mean latency (per req) | 10.3 ms |
| Mean latency (overall) | 0.5 ms |

### Test B – Heavy load (health endpoint)

```bash
ab -n 1000 -c 50 http://localhost:8080/health.php
```

| Metric | Value |
|--------|-------|
| Concurrency | 50 |
| Total requests | 1 000 |
| Failed requests | 0 |
| Requests per second | **1 922 req/s** |
| Mean latency (per req) | 26.0 ms |
| Mean latency (overall) | 0.5 ms |

> Health endpoint includes a live MySQL + Redis connection check on every request. Throughput stays consistent as load increases.

### Test C – Login page (full PHP page render)

```bash
ab -n 200 -c 10 http://localhost:8080/login.php
```

| Metric | Value |
|--------|-------|
| Concurrency | 10 |
| Total requests | 200 |
| Failed requests | 0 |
| Requests per second | **5 088 req/s** |
| Mean latency | 1.97 ms |

> OPcache accelerates PHP rendering — static/OPcache-served pages are significantly faster than health checks that hit the database.

---

## Scaling Strategy

### Docker Compose (horizontal scale)

Docker Compose does **not** natively load-balance multiple replicas of the `app` service through a single port. To scale php-fpm replicas, configure nginx to upstream to the compose service DNS name and let Docker's internal round-robin DNS handle balancing:

```bash
# Scale to 3 app replicas
docker compose -f deploy/docker/docker-compose.yml \
  up -d --scale app=3 --no-recreate

# Verify
docker compose -f deploy/docker/docker-compose.yml ps app
```

> Note: The nginx upstream `app:9000` resolves to all running replicas via Docker's built-in DNS round-robin.

### Kubernetes HPA (production approach)

The `deploy/k8s/hpa.yaml` defines automatic scaling:

```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: mini-wms-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: mini-wms-app
  minReplicas: 1
  maxReplicas: 5
  metrics:
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: 50
```

| Trigger | Action |
|---------|--------|
| CPU > 50% | Scale up (add 1 replica) |
| CPU < 50% (stable 5 min) | Scale down |
| Max replicas | 5 |
| Min replicas | 1 |

**Deploy HPA:**
```bash
kubectl apply -f deploy/k8s/namespace.yaml
kubectl apply -f deploy/k8s/
kubectl get hpa -n mini-wms -w
```

**Simulate load in K8s to trigger scale-up:**
```bash
kubectl run load-gen --image=busybox --rm -it --restart=Never -n mini-wms \
  -- sh -c "while true; do wget -q -O- http://mini-wms-nginx/health.php > /dev/null; done"
```

---

## Memory Efficiency

| Scenario | App container memory |
|----------|----------------------|
| 1 replica (idle) | ~16 MiB |
| 1 replica (under 50 c load) | ~18 MiB |
| 5 replicas (K8s HPA max) | ~80–90 MiB total |

The php-fpm alpine image is **exceptionally memory-efficient**. Five replicas fit comfortably within 512 MiB, leaving headroom for MySQL (364 MiB) and other services.

---

## Bottleneck Analysis

Based on load tests:

1. **PHP-FPM (app):** Not the bottleneck. Scales horizontally with HPA.
2. **MySQL:** At 2.4% CPU and 364 MiB RAM at idle. Connection pool is a shared resource — under 1 000+ concurrent users, MySQL becomes the primary bottleneck. Mitigation: read replicas, PgBouncer, or Redis caching of hot queries.
3. **Redis:** 0.7% CPU, ideal as a session cache layer.
4. **Nginx:** Not the bottleneck at these request volumes.

---

## Run It Yourself

```bash
# Install ab (macOS)
# ab is included with macOS at /usr/sbin/ab

# Light load
ab -n 200 -c 20 http://localhost:8080/health.php

# Heavy load
ab -n 1000 -c 50 http://localhost:8080/health.php

# Custom load test script
bash deploy/load-test.sh http://localhost:8080/health.php 500 25
```
