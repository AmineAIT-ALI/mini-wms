<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini WMS – Cloud-Native Warehouse Management System</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <meta name="description" content="A production-ready WMS built to demonstrate modern cloud-native backend engineering: containers, Kubernetes, observability, resilience, and CI/CD.">
    <style>
        /* ── Reset & Base ─────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; font-size: 16px; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: #060610;
            color: #e2e8f0;
            line-height: 1.6;
            overflow-x: hidden;
        }
        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        code, pre { font-family: ui-monospace, 'Cascadia Code', 'Consolas', monospace; }

        /* ── Tokens ───────────────────────────────────────────────────────────── */
        :root {
            --indigo:      #6366f1;
            --indigo-lt:   #818cf8;
            --indigo-dim:  rgba(99,102,241,.12);
            --indigo-brd:  rgba(99,102,241,.25);
            --cyan:        #06b6d4;
            --cyan-dim:    rgba(6,182,212,.12);
            --cyan-brd:    rgba(6,182,212,.25);
            --green:       #10b981;
            --amber:       #f59e0b;
            --red:         #ef4444;
            --white04:     rgba(255,255,255,.04);
            --white07:     rgba(255,255,255,.07);
            --white10:     rgba(255,255,255,.10);
            --slate400:    #94a3b8;
            --slate500:    #64748b;
            --slate600:    #475569;
            --max-w:       1100px;
            --r:           12px;
            --r-lg:        18px;
        }

        /* ── Utilities ────────────────────────────────────────────────────────── */
        .container { max-width: var(--max-w); margin: 0 auto; padding: 0 1.5rem; }
        .text-gradient {
            background: linear-gradient(135deg, #fff 0%, #c7d2fe 45%, var(--indigo-lt) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .text-gradient-cyan {
            background: linear-gradient(135deg, #67e8f9 0%, var(--indigo-lt) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .text-gradient-green {
            background: linear-gradient(135deg, #6ee7b7 0%, #67e8f9 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .mono { font-family: ui-monospace, 'Cascadia Code', 'Consolas', monospace !important; }
        .tag {
            display: inline-flex; align-items: center; gap: .4rem;
            background: var(--indigo-dim); border: 1px solid var(--indigo-brd);
            color: #a5b4fc; border-radius: 999px;
            padding: .28rem .85rem; font-size: .72rem; font-weight: 700;
            letter-spacing: .06em; text-transform: uppercase; margin-bottom: 1.25rem;
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; }

        /* ── Animations ───────────────────────────────────────────────────────── */
        @keyframes fadeUp   { from { opacity:0; transform:translateY(18px); } to { opacity:1; transform:translateY(0); } }
        @keyframes pulseSoft{ 0%,100%{ opacity:.12; } 50%{ opacity:.2; } }
        .fade-up    { animation: fadeUp .7s ease forwards; }
        .fade-up-d1 { animation: fadeUp .7s .1s ease both; }
        .fade-up-d2 { animation: fadeUp .7s .2s ease both; }
        .fade-up-d3 { animation: fadeUp .7s .3s ease both; }

        /* ── NAV ──────────────────────────────────────────────────────────────── */
        .nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 50;
            background: rgba(6,6,16,.88); backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255,255,255,.05);
        }
        .nav-inner {
            max-width: var(--max-w); margin: 0 auto; padding: 0 1.5rem;
            height: 56px; display: flex; align-items: center; justify-content: space-between;
        }
        .nav-brand { display: flex; align-items: center; gap: .625rem; }
        .nav-logo {
            width: 28px; height: 28px; border-radius: 8px;
            background: var(--indigo); display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .nav-name { font-weight: 700; font-size: .9rem; }
        .nav-ver  { color: var(--indigo-lt); font-size: .75rem; font-family: ui-monospace,monospace; }
        .nav-right { display: flex; align-items: center; gap: 1rem; }
        .nav-link-muted { font-size: .875rem; color: var(--slate400); transition: color .15s; }
        .nav-link-muted:hover { color: #fff; }
        .btn-nav {
            background: var(--indigo); color: #fff; font-size: .8rem; font-weight: 600;
            padding: .45rem 1rem; border-radius: 8px; transition: background .15s;
        }
        .btn-nav:hover { background: #4f46e5; }

        /* ── BUTTONS ──────────────────────────────────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; gap: .4rem;
            font-weight: 600; font-size: .9375rem; border-radius: var(--r);
            padding: .875rem 1.75rem; transition: all .15s; cursor: pointer; border: none;
            font-family: inherit;
        }
        .btn-primary {
            background: var(--indigo); color: #fff;
            box-shadow: 0 4px 20px rgba(99,102,241,.3);
        }
        .btn-primary:hover { background: #4f46e5; box-shadow: 0 8px 28px rgba(99,102,241,.4); }
        .btn-outline {
            background: transparent; color: var(--slate400);
            border: 1px solid rgba(255,255,255,.12);
        }
        .btn-outline:hover { color: #fff; border-color: rgba(255,255,255,.25); }

        /* ── CARDS ────────────────────────────────────────────────────────────── */
        .card {
            background: var(--white04); border: 1px solid var(--white07);
            border-radius: var(--r-lg);
        }
        .card-hover { transition: all .2s ease; }
        .card-hover:hover {
            background: rgba(255,255,255,.06); border-color: rgba(99,102,241,.35);
            transform: translateY(-3px); box-shadow: 0 16px 40px rgba(99,102,241,.1);
        }

        /* ── CODE BLOCKS ──────────────────────────────────────────────────────── */
        .code-block {
            background: #0d1117; border: 1px solid rgba(255,255,255,.08);
            border-radius: var(--r); overflow: hidden;
        }
        .code-header {
            background: rgba(255,255,255,.03); border-bottom: 1px solid rgba(255,255,255,.06);
            padding: .625rem 1.1rem; display: flex; align-items: center; gap: .45rem;
        }
        .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
        .code-label { font-size: .72rem; color: var(--slate500); margin-left: .25rem; }
        pre { padding: 1.25rem 1.5rem; font-size: .78rem; line-height: 1.8; overflow-x: auto; }
        .c-gray  { color: var(--slate500); }
        .c-blue  { color: #a5b4fc; }
        .c-green { color: #6ee7b7; }
        .c-amber { color: #fcd34d; }
        .c-cyan  { color: #67e8f9; }
        .c-red   { color: #fca5a5; }
        .c-white { color: #f1f5f9; }

        /* ── GLOWS ────────────────────────────────────────────────────────────── */
        .glow {
            position: absolute; border-radius: 50%;
            pointer-events: none; filter: blur(80px); animation: pulseSoft 5s ease-in-out infinite;
        }
        .glow-purple { background: radial-gradient(circle, rgba(99,102,241,.18) 0%, transparent 70%); }
        .glow-cyan   { background: radial-gradient(circle, rgba(6,182,212,.12) 0%, transparent 70%); }

        /* ── HERO ─────────────────────────────────────────────────────────────── */
        .hero {
            min-height: 100vh; display: flex; align-items: center; padding-top: 56px;
            position: relative; overflow: hidden;
            background-image:
                linear-gradient(rgba(99,102,241,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99,102,241,.05) 1px, transparent 1px);
            background-size: 56px 56px;
        }
        .hero-content { max-width: 780px; padding: 5rem 0; position: relative; z-index: 1; }
        .hero h1 { font-size: clamp(3.5rem, 8vw, 6rem); font-weight: 900; line-height: 1; letter-spacing: -.03em; margin-bottom: 1.5rem; }
        .hero-sub { font-size: 1.25rem; color: var(--slate400); line-height: 1.7; max-width: 560px; margin-bottom: 2.5rem; }
        .hero-sub strong { color: #fff; font-weight: 500; }
        .hero-btns { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 3rem; }
        .badges { display: flex; flex-wrap: wrap; gap: .5rem; }
        .badge-tech {
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
            color: var(--slate400); font-size: .72rem;
            font-family: ui-monospace,monospace; padding: .25rem .75rem; border-radius: 999px;
        }

        /* ── STATS ────────────────────────────────────────────────────────────── */
        .stats-bar {
            border-top: 1px solid rgba(255,255,255,.05);
            border-bottom: 1px solid rgba(255,255,255,.05);
            background: rgba(255,255,255,.015);
        }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); }
        .stat-item { padding: 2rem 1.5rem; border-left: 2px solid rgba(99,102,241,.35); }
        .stat-item:first-child { border-left: none; }
        .stat-num  { font-size: 2rem; font-weight: 900; color: #fff; letter-spacing: -.04em; line-height: 1; }
        .stat-name { font-size: .8125rem; font-weight: 600; color: var(--indigo-lt); margin-top: .25rem; margin-bottom: .2rem; }
        .stat-desc { font-size: .72rem; color: var(--slate500); }

        /* ── SECTIONS ─────────────────────────────────────────────────────────── */
        section { padding: 6rem 0; }
        .section-alt { background: rgba(255,255,255,.015); border-top: 1px solid rgba(255,255,255,.05); border-bottom: 1px solid rgba(255,255,255,.05); }
        h2.section-title { font-size: clamp(2rem, 4vw, 3rem); font-weight: 900; line-height: 1.15; margin-bottom: 1rem; }
        .section-sub { font-size: 1.1rem; color: var(--slate400); line-height: 1.7; max-width: 560px; }
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: start; }
        .two-col-center { align-items: center; }

        /* ── ARCH ─────────────────────────────────────────────────────────────── */
        .arch-flow { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .5rem; }
        .arch-node {
            background: var(--indigo-dim); border: 1px solid var(--indigo-brd);
            border-radius: 10px; padding: .625rem 1.1rem; text-align: center;
        }
        .arch-node-cyan {
            background: var(--cyan-dim); border: 1px solid var(--cyan-brd);
            border-radius: 10px; padding: .625rem 1.1rem; text-align: center;
        }
        .arch-node-strong { background: rgba(99,102,241,.2); border-color: rgba(99,102,241,.45); }
        .arch-label { font-size: .68rem; color: var(--slate400); margin-bottom: .2rem; }
        .arch-name  { font-size: .875rem; font-weight: 700; color: #fff; }
        .arch-desc  { font-size: .68rem; color: var(--slate500); margin-top: .2rem; }
        .arch-arrow { color: rgba(99,102,241,.45); font-size: 1.1rem; padding: 0 .1rem; display: flex; align-items: center; }
        .arch-arrow-cyan { color: rgba(6,182,212,.45); }
        .arch-col   { display: flex; flex-direction: column; gap: .5rem; }

        /* ── FEATURE LIST ─────────────────────────────────────────────────────── */
        .feat-list { display: flex; flex-direction: column; gap: 1.125rem; }
        .feat-item { display: flex; gap: .875rem; }
        .feat-dot  { width: 6px; height: 6px; border-radius: 50%; background: var(--cyan); flex-shrink: 0; margin-top: .45rem; }
        .feat-dot-green { background: var(--green); }
        .feat-title { font-size: .875rem; font-weight: 600; color: #fff; margin-bottom: .2rem; }
        .feat-desc  { font-size: .8125rem; color: var(--slate400); line-height: 1.6; }

        /* ── PROBLEM CARDS ────────────────────────────────────────────────────── */
        .prob-grid { display: flex; flex-direction: column; gap: .875rem; }
        .prob-card { padding: 1.125rem 1.25rem; }
        .prob-header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: .5rem; }
        .prob-bad  { font-size: .72rem; font-family: monospace; color: #fca5a5; }
        .prob-good { font-size: .72rem; font-family: monospace; color: #6ee7b7; }
        .prob-text { font-size: .8125rem; color: var(--slate400); line-height: 1.6; }

        /* ── RESILIENCE TABLE ─────────────────────────────────────────────────── */
        .res-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .res-table th {
            background: rgba(255,255,255,.03); color: var(--slate500);
            font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em;
            padding: .75rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,.06); text-align: left;
        }
        .res-table td { padding: .875rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,.04); vertical-align: top; }
        .res-table tr:last-child td { border-bottom: none; }
        .res-table td:last-child { color: #6ee7b7; font-weight: 500; font-size: .8rem; }
        .res-table td:first-child { color: #fff; font-weight: 500; }
        .res-table td:not(:first-child):not(:last-child) { color: var(--slate400); font-size: .8125rem; }

        /* ── PIPELINE ─────────────────────────────────────────────────────────── */
        .pipeline { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .5rem; padding: 2rem; }
        .pipe-step {
            border-radius: 8px; padding: .6rem 1rem; text-align: center;
            font-size: .8rem; font-weight: 700; white-space: nowrap;
        }
        .pipe-arrow { color: rgba(255,255,255,.2); font-size: 1.2rem; display: flex; align-items: center; }

        /* ── HIGHLIGHTS GRID ──────────────────────────────────────────────────── */
        .hl-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1rem; flex-shrink: 0;
        }
        .hl-dot { width: 8px; height: 8px; border-radius: 50%; }
        .hl-card { padding: 1.5rem; }
        .hl-title { font-size: .875rem; font-weight: 700; color: #fff; margin-bottom: .4rem; }
        .hl-desc  { font-size: .78rem; color: var(--slate400); line-height: 1.65; }

        /* ── TREE ─────────────────────────────────────────────────────────────── */
        .tree { padding: 1.5rem; font-size: .78rem; line-height: 2; }
        .tree-line { color: rgba(99,102,241,.35); }
        .tree-dir  { color: #a5b4fc; font-weight: 600; }
        .tree-file { color: var(--slate400); }
        .tree-cmt  { color: rgba(255,255,255,.22); }

        /* ── TRY IT ───────────────────────────────────────────────────────────── */
        .try-section { text-align: center; position: relative; overflow: hidden; }
        .try-urls { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; margin: 2.5rem 0; }
        .url-pill {
            display: flex; align-items: center; gap: .625rem;
            background: var(--white04); border: 1px solid var(--white07);
            border-radius: var(--r); padding: .625rem 1.1rem;
        }
        .url-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .url-name { font-size: .875rem; font-weight: 600; }
        .url-addr { font-size: .75rem; color: var(--slate500); font-family: monospace; }
        .try-btns { display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; }

        /* ── FOOTER ───────────────────────────────────────────────────────────── */
        .footer {
            border-top: 1px solid rgba(255,255,255,.05);
            padding: 2.5rem 0;
        }
        .footer-inner {
            display: flex; flex-wrap: wrap; align-items: center;
            justify-content: space-between; gap: 1.5rem;
        }
        .footer-brand { display: flex; align-items: center; gap: .625rem; }
        .footer-logo {
            width: 28px; height: 28px; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
        }
        .footer-logo img { display: block; border-radius: 6px; }
        .footer-name  { font-weight: 700; font-size: .875rem; }
        .footer-meta  { font-size: .75rem; color: var(--slate600); margin-left: .25rem; }
        .footer-links { display: flex; align-items: center; gap: 1.5rem; }
        .footer-link  { font-size: .875rem; color: var(--slate500); transition: color .15s; }
        .footer-link:hover { color: #fff; }

        /* ── RESPONSIVE ───────────────────────────────────────────────────────── */
        @media (max-width: 900px) {
            .two-col   { grid-template-columns: 1fr; gap: 3rem; }
            .grid-3    { grid-template-columns: 1fr 1fr; }
            .stats-grid{ grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .grid-2    { grid-template-columns: 1fr; }
            .grid-3    { grid-template-columns: 1fr; }
            .stats-grid{ grid-template-columns: 1fr 1fr; }
            .hero h1   { font-size: 3rem; }
            .nav-link-muted { display: none; }
            pre { font-size: .72rem; padding: 1rem; }
            .pipeline  { gap: .35rem; }
            .pipe-step { padding: .5rem .7rem; font-size: .72rem; }
        }
    </style>
</head>
<body>

<!-- ─── NAV ──────────────────────────────────────────────────────────────────── -->
<nav class="nav">
    <div class="nav-inner">
        <div class="nav-brand">
            <div class="nav-logo" style="background:none;padding:0;width:auto;height:auto;">
                <img src="assets/img/logo.png" width="36" height="36" alt="Mini WMS logo" style="display:block;border-radius:6px;">
            </div>
            <span class="nav-name">Mini WMS</span>
        </div>
        <div class="nav-right">
            <a href="#architecture" class="nav-link-muted">Architecture</a>
            <a href="#observability" class="nav-link-muted">Observability</a>
            <a href="#pipeline" class="nav-link-muted">CI/CD</a>
            <a href="https://github.com/AmineAIT-ALI/mini-wms" target="_blank" class="nav-link-muted">GitHub</a>
            <a href="login.php" class="btn-nav">View App →</a>
        </div>
    </div>
</nav>

<!-- ─── HERO ──────────────────────────────────────────────────────────────────── -->
<section class="hero">
    <div class="glow glow-purple" style="width:700px;height:700px;top:-200px;left:-220px;"></div>
    <div class="glow glow-cyan"   style="width:500px;height:500px;bottom:-150px;right:-180px;animation-delay:2.5s;"></div>
    <div class="container">
        <div class="hero-content">
            <div class="tag fade-up">
                <svg width="8" height="8" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="6"/></svg>
                Cloud-Native · Open Source
            </div>
            <h1 class="fade-up-d1"><span class="text-gradient">Mini WMS</span></h1>
            <p class="hero-sub fade-up-d2">
                A production-ready Warehouse Management System built to demonstrate
                <strong>modern cloud-native backend engineering</strong> —
                containers, Kubernetes, observability, resilience, and CI/CD pipelines.
            </p>
            <div class="hero-btns fade-up-d3">
                <a href="#architecture" class="btn btn-primary">Explore Architecture</a>
                <a href="login.php" class="btn btn-outline">
                    Launch App
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                </a>
            </div>
            <div class="badges fade-up-d3">
                <?php foreach(['PHP 8.4','MySQL 8','Redis 7','Docker','Kubernetes','Prometheus','Grafana','Nginx','GitHub Actions'] as $t): ?>
                <span class="badge-tech"><?= $t ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ─── STATS ─────────────────────────────────────────────────────────────────── -->
<div class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            <?php foreach([
                ['1 943', 'req/s throughput',       'ApacheBench · 20 concurrent'],
                ['7',     'containerised services', 'Full stack · Docker Compose'],
                ['0',     'failures under load',    '500 requests · 0 errors'],
                ['< 10s', 'DB failure recovery',    'Health probe auto-reconnect'],
            ] as $s): ?>
            <div class="stat-item">
                <div class="stat-num"><?= $s[0] ?></div>
                <div class="stat-name"><?= $s[1] ?></div>
                <div class="stat-desc"><?= $s[2] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ─── PROBLEM ───────────────────────────────────────────────────────────────── -->
<section>
    <div class="container">
        <div class="two-col two-col-center">
            <div>
                <div class="tag">The Problem</div>
                <h2 class="section-title">Traditional software wasn't built to <span class="text-gradient">scale or fail safely.</span></h2>
                <p class="section-sub" style="margin-bottom:1.5rem;">
                    Most warehouse systems are monolithic — one process, one failure domain. When the database slows, every user suffers. When traffic spikes, there's no mechanism to adapt. When something breaks, engineers are blind.
                </p>
                <p class="section-sub">
                    Mini WMS was engineered to demonstrate that these are <span style="color:#fff;">solved engineering problems</span> — if you architect with containers, observability, health probes, and horizontal scaling from day one.
                </p>
            </div>
            <div class="prob-grid">
                <?php foreach([
                    ['Monolithic',     'Containerised services', 'Each component is isolated, independently scalable, with its own failure domain and restart policy.'],
                    ['Opaque failures','Observability-first',    'Prometheus metrics, Grafana dashboards, and structured JSON logs on every request.'],
                    ['Manual scaling', 'Kubernetes HPA',         'Horizontal Pod Autoscaler scales 1→5 replicas automatically when CPU exceeds 50%.'],
                    ['No recovery plan','Health probes + tests',  'Liveness and readiness probes. Failure scenarios deliberately induced, observed, documented.'],
                ] as $p): ?>
                <div class="card prob-card card-hover">
                    <div class="prob-header">
                        <span class="prob-bad"><?= $p[0] ?></span>
                        <span class="prob-good"><?= $p[1] ?></span>
                    </div>
                    <p class="prob-text"><?= $p[2] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ─── ARCHITECTURE ──────────────────────────────────────────────────────────── -->
<section class="section-alt" id="architecture">
    <div class="container">
        <div style="text-align:center;margin-bottom:3.5rem;">
            <div class="tag" style="margin:0 auto 1.25rem;">Architecture</div>
            <h2 class="section-title"><span class="text-gradient">Cloud-Native by design.</span></h2>
            <p class="section-sub" style="margin:0 auto;">Seven services, each with a single responsibility, communicating over a private Docker network. Stateless application layer for horizontal scaling.</p>
        </div>

        <!-- Request path -->
        <div class="card" style="padding:2rem;margin-bottom:1rem;">
            <p style="font-size:.68rem;font-weight:700;color:var(--slate600);text-transform:uppercase;letter-spacing:.08em;margin-bottom:1.5rem;text-align:center;">Request Path</p>
            <div class="arch-flow">
                <div class="arch-node">
                    <div class="arch-label">Client</div>
                    <div class="arch-name">Browser</div>
                </div>
                <div class="arch-arrow">→</div>
                <div class="arch-node">
                    <div class="arch-label">:8080</div>
                    <div class="arch-name">Nginx 1.25</div>
                    <div class="arch-desc">Proxy · gzip · static</div>
                </div>
                <div class="arch-arrow">→</div>
                <div class="arch-node arch-node-strong">
                    <div class="arch-label">:9000 FastCGI</div>
                    <div class="arch-name">PHP-FPM 8.4</div>
                    <div class="arch-desc">OPcache · PDO · Redis ext</div>
                </div>
                <div class="arch-arrow">→</div>
                <div class="arch-col">
                    <div class="arch-node">
                        <div class="arch-label">:3306</div>
                        <div class="arch-name">MySQL 8</div>
                        <div class="arch-desc">utf8mb4 · relational</div>
                    </div>
                    <div class="arch-node-cyan">
                        <div class="arch-label" style="color:var(--cyan);">:6379</div>
                        <div class="arch-name">Redis 7</div>
                        <div class="arch-desc">Sessions · AOF</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Observability pipeline -->
        <div class="card" style="padding:2rem;margin-bottom:2rem;">
            <p style="font-size:.68rem;font-weight:700;color:var(--slate600);text-transform:uppercase;letter-spacing:.08em;margin-bottom:1.5rem;text-align:center;">Observability Pipeline</p>
            <div class="arch-flow">
                <div class="arch-node-cyan">
                    <div class="arch-label" style="color:var(--cyan);">:8081</div>
                    <div class="arch-name">cAdvisor</div>
                    <div class="arch-desc">Container metrics</div>
                </div>
                <div class="arch-arrow arch-arrow-cyan">→</div>
                <div class="arch-node">
                    <div class="arch-label">/metrics.php</div>
                    <div class="arch-name">App Metrics</div>
                    <div class="arch-desc">Custom endpoint</div>
                </div>
                <div class="arch-arrow arch-arrow-cyan">→</div>
                <div class="arch-node-cyan">
                    <div class="arch-label" style="color:var(--cyan);">:9090 · 15s</div>
                    <div class="arch-name">Prometheus</div>
                    <div class="arch-desc">TSDB · alert rules</div>
                </div>
                <div class="arch-arrow arch-arrow-cyan">→</div>
                <div class="arch-node-cyan" style="background:rgba(6,182,212,.2);border-color:rgba(6,182,212,.45);">
                    <div class="arch-label" style="color:var(--cyan);">:3000</div>
                    <div class="arch-name">Grafana</div>
                    <div class="arch-desc">Auto-provisioned</div>
                </div>
            </div>
        </div>

        <!-- Component cards -->
        <div class="grid-3">
            <?php foreach([
                ['Nginx 1.25',  '#6366f1', 'Reverse proxy and static file server. Handles gzip, FastCGI proxying to PHP-FPM, connection limits. The only public entry point.'],
                ['PHP-FPM 8.4', '#6366f1', 'Application runtime on Alpine Linux. OPcache enabled. Redis extension for session storage. Stateless — every request is independent.'],
                ['MySQL 8',     '#6366f1', 'Relational datastore with utf8mb4. Products, orders, stock moves, users, audit logs. Volume-mounted for data persistence.'],
                ['Redis 7',     '#06b6d4', 'Session cache with AOF persistence. Externalising sessions from the app container is the prerequisite for horizontal scaling.'],
                ['Prometheus',  '#06b6d4', 'Time-series metrics DB. Scrapes /metrics.php every 15 seconds. Alert rules for uptime, DB health, and response time included.'],
                ['Grafana',     '#06b6d4', 'Dashboards auto-provisioned via ConfigMap. Datasource and panels configured on first container boot — zero manual setup.'],
            ] as $c): ?>
            <div class="card card-hover hl-card">
                <div class="hl-icon" style="background:<?= $c[1] ?>18;border:1px solid <?= $c[1] ?>30;">
                    <div class="hl-dot" style="background:<?= $c[1] ?>;"></div>
                </div>
                <div class="hl-title"><?= $c[0] ?></div>
                <div class="hl-desc"><?= $c[2] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ─── OBSERVABILITY ─────────────────────────────────────────────────────────── -->
<section id="observability">
    <div class="container">
        <div class="two-col">
            <div>
                <div class="tag">Observability</div>
                <h2 class="section-title">If you can't measure it, you <span class="text-gradient-cyan">can't operate it.</span></h2>
                <p class="section-sub" style="margin-bottom:2rem;">Every component exposes structured signals. Health probes distinguish readiness from liveness. Every log entry carries a UUIDv4 request ID for full tracing.</p>
                <div class="feat-list">
                    <?php foreach([
                        ['Health probes',       'Readiness on /health.php. Returns 503 during DB/Redis outage — removing the pod from load balancer rotation before traffic is affected.'],
                        ['Prometheus metrics',  'Custom /metrics.php in Prometheus exposition format. Tracks uptime, DB/Redis health, scrape duration. Alert rules ship with the project.'],
                        ['Grafana dashboards',  'Auto-provisioned on container start via ConfigMap. Container CPU/memory from cAdvisor. App metrics from /metrics.php. No manual wiring.'],
                        ['Structured JSON logs', 'Every HTTP request writes a JSON log with timestamp, request_id (UUIDv4), status_code, response_time_ms, method, and URI. Log-shipping ready.'],
                    ] as $o): ?>
                    <div class="feat-item">
                        <div class="feat-dot"></div>
                        <div>
                            <div class="feat-title"><?= $o[0] ?></div>
                            <div class="feat-desc"><?= $o[1] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:1rem;">
                <div class="code-block">
                    <div class="code-header">
                        <span class="dot" style="background:#ff5f57"></span>
                        <span class="dot" style="background:#febc2e"></span>
                        <span class="dot" style="background:#28c840"></span>
                        <span class="code-label mono">GET /health.php → 200 OK</span>
                    </div>
<pre><code><span class="c-gray">{</span>
  <span class="c-blue">"ok"</span>      <span class="c-gray">:</span> <span class="c-green">true</span><span class="c-gray">,</span>
  <span class="c-blue">"db"</span>      <span class="c-gray">:</span> <span class="c-green">true</span><span class="c-gray">,</span>
  <span class="c-blue">"redis"</span>   <span class="c-gray">:</span> <span class="c-green">true</span><span class="c-gray">,</span>
  <span class="c-blue">"uptime"</span>  <span class="c-gray">:</span> <span class="c-amber">2534</span><span class="c-gray">,</span>
  <span class="c-blue">"version"</span> <span class="c-gray">:</span> <span class="c-cyan">"2.0.0"</span>
<span class="c-gray">}</span></code></pre>
                </div>

                <div class="code-block">
                    <div class="code-header">
                        <span class="dot" style="background:#ff5f57"></span>
                        <span class="dot" style="background:#febc2e"></span>
                        <span class="dot" style="background:#28c840"></span>
                        <span class="code-label mono">GET /metrics.php → Prometheus</span>
                    </div>
<pre><code><span class="c-gray"># HELP mini_wms_up Application liveness</span>
<span class="c-blue">mini_wms_up</span>               <span class="c-green">1</span>
<span class="c-blue">mini_wms_db_up</span>            <span class="c-green">1</span>
<span class="c-blue">mini_wms_redis_up</span>         <span class="c-green">1</span>
<span class="c-blue">mini_wms_uptime_seconds</span>   <span class="c-amber">1477</span>
<span class="c-blue">mini_wms_scrape_duration_seconds</span> <span class="c-amber">0.00296</span></code></pre>
                </div>

                <div class="code-block">
                    <div class="code-header">
                        <span class="dot" style="background:#ff5f57"></span>
                        <span class="dot" style="background:#febc2e"></span>
                        <span class="dot" style="background:#28c840"></span>
                        <span class="code-label mono">logs/app.log — structured JSON</span>
                    </div>
<pre><code><span class="c-gray">{</span>
  <span class="c-blue">"level"</span>       <span class="c-gray">:</span> <span class="c-cyan">"INFO"</span><span class="c-gray">,</span>
  <span class="c-blue">"request_id"</span>  <span class="c-gray">:</span> <span class="c-cyan">"8a866a24-c597-4f91"</span><span class="c-gray">,</span>
  <span class="c-blue">"status_code"</span> <span class="c-gray">:</span> <span class="c-amber">200</span><span class="c-gray">,</span>
  <span class="c-blue">"response_ms"</span> <span class="c-gray">:</span> <span class="c-amber">0.08</span><span class="c-gray">,</span>
  <span class="c-blue">"uri"</span>         <span class="c-gray">:</span> <span class="c-cyan">"/dashboard.php"</span>
<span class="c-gray">}</span></code></pre>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── RESILIENCE ────────────────────────────────────────────────────────────── -->
<section class="section-alt">
    <div class="container">
        <div style="margin-bottom:3rem;">
            <div class="tag">Resilience Engineering</div>
            <h2 class="section-title">Tested to fail.<br><span class="text-gradient">Designed to recover.</span></h2>
            <p class="section-sub">Failure scenarios were deliberately induced and the system's behaviour was observed and documented. Not assumed — verified.</p>
        </div>

        <div class="card" style="overflow:hidden;margin-bottom:1.5rem;">
            <table class="res-table">
                <thead>
                    <tr>
                        <th>Failure scenario</th>
                        <th>Detection</th>
                        <th>App behaviour</th>
                        <th>Recovery</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach([
                        ['App container crash', 'nginx → 504 Gateway Timeout', 'In-flight requests fail; Docker restart policy activates', 'Auto-restart · back online in ~5s'],
                        ['MySQL outage', '/health.php → HTTP 503 (db: false)', 'Health probe fails · pod removed from load balancer', 'Auto-reconnect on next PDO call · < 10s'],
                        ['Redis outage', '/health.php → HTTP 503 (redis: false)', 'Sessions fail gracefully · page requests return HTTP 200', 'Auto-reconnect · < 15s recovery'],
                        ['Network partition', 'TCP timeout on DB/Redis sockets', 'PHP exception caught · 500 logged with request_id', 'Container restart policy triggers'],
                    ] as $s): ?>
                    <tr>
                        <td><?= $s[0] ?></td>
                        <td><?= $s[1] ?></td>
                        <td><?= $s[2] ?></td>
                        <td><?= $s[3] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="code-block">
            <div class="code-header">
                <span class="dot" style="background:#ff5f57"></span>
                <span class="dot" style="background:#febc2e"></span>
                <span class="dot" style="background:#28c840"></span>
                <span class="code-label mono">Simulate database failure</span>
            </div>
<pre><code><span class="c-gray"># Bring down the database</span>
<span class="c-green">$</span> <span class="c-white">docker stop mini-wms-db</span>
<span class="c-green">$</span> <span class="c-white">curl http://localhost:8080/health.php</span>
  <span class="c-red">{"ok":false,"db":false,"redis":true,...}  →  HTTP 503</span>

<span class="c-gray"># Restore — self-heals in under 10 seconds</span>
<span class="c-green">$</span> <span class="c-white">docker start mini-wms-db</span>
<span class="c-green">$</span> <span class="c-white">curl http://localhost:8080/health.php</span>
  <span class="c-green">{"ok":true,"db":true,"redis":true,...}   →  HTTP 200</span></code></pre>
        </div>
    </div>
</section>

<!-- ─── SCALING ───────────────────────────────────────────────────────────────── -->
<section>
    <div class="container">
        <div class="two-col">
            <div style="display:flex;flex-direction:column;gap:1rem;">
                <div class="code-block">
                    <div class="code-header">
                        <span class="dot" style="background:#ff5f57"></span>
                        <span class="dot" style="background:#febc2e"></span>
                        <span class="dot" style="background:#28c840"></span>
                        <span class="code-label mono">Docker Compose horizontal scale</span>
                    </div>
<pre><code><span class="c-green">$</span> <span class="c-white">docker compose \
    -f deploy/docker/docker-compose.yml \
    up -d --scale app=3</span>

<span class="c-gray"># 3 PHP-FPM replicas behind Nginx
# Sessions in Redis → no sticky sessions needed
# Nginx load-balances across all replicas</span></code></pre>
                </div>

                <div class="code-block">
                    <div class="code-header">
                        <span class="dot" style="background:#ff5f57"></span>
                        <span class="dot" style="background:#febc2e"></span>
                        <span class="dot" style="background:#28c840"></span>
                        <span class="code-label mono">Kubernetes HPA auto-scaling</span>
                    </div>
<pre><code><span class="c-green">$</span> <span class="c-white">kubectl apply -f deploy/k8s/</span>
<span class="c-green">$</span> <span class="c-white">kubectl get hpa -n mini-wms -w</span>

<span class="c-gray">NAME       MIN  MAX  REPLICAS  CPU</span>
<span class="c-gray">mini-wms   1    5    1         12%</span>
<span class="c-gray">mini-wms   1    5    3         54%</span>  <span class="c-amber">← scaling up</span>
<span class="c-gray">mini-wms   1    5    5         48%</span>  <span class="c-green">← stabilised</span></code></pre>
                </div>

                <div class="code-block">
                    <div class="code-header">
                        <span class="dot" style="background:#ff5f57"></span>
                        <span class="dot" style="background:#febc2e"></span>
                        <span class="dot" style="background:#28c840"></span>
                        <span class="code-label mono">Load test results (ApacheBench)</span>
                    </div>
<pre><code><span class="c-green">$</span> <span class="c-white">ab -n 500 -c 20 http://localhost:8080/health.php</span>

<span class="c-blue">Requests per second</span>  <span class="c-green">1943.18</span> <span class="c-gray">req/s</span>
<span class="c-blue">Time per request</span>     <span class="c-green">10.292</span> <span class="c-gray">ms (mean)</span>
<span class="c-blue">Failed requests</span>      <span class="c-green">0</span>
<span class="c-blue">Non-2xx responses</span>    <span class="c-green">0</span></code></pre>
                </div>
            </div>
            <div>
                <div class="tag">Horizontal Scaling</div>
                <h2 class="section-title">Stateless by design.<br><span class="text-gradient-green">Scales on demand.</span></h2>
                <p class="section-sub" style="margin-bottom:2rem;">Sessions are stored in Redis, not on the application container's filesystem. Any replica can serve any request — the hard prerequisite for true horizontal scaling.</p>
                <div class="feat-list">
                    <?php foreach([
                        ['feat-dot-green', 'Docker Compose scaling',   'Scale to N replicas with a single flag. Nginx automatically load-balances across all upstream PHP-FPM workers.'],
                        ['feat-dot-green', 'Kubernetes HPA',           'Horizontal Pod Autoscaler scales 1 → 5 replicas at 50% CPU. Scales back down automatically when load drops.'],
                        ['feat-dot-green', 'Zero-downtime deploys',    'Rolling update strategy in Kubernetes. Readiness probe prevents routing to pods that haven\'t initialised.'],
                        ['feat-dot-green', 'Memory footprint',         'App + nginx containers use ~28 MiB at rest. Five replicas occupy approximately 90 MiB total.'],
                    ] as $s): ?>
                    <div class="feat-item">
                        <div class="feat-dot <?= $s[0] ?>"></div>
                        <div>
                            <div class="feat-title"><?= $s[1] ?></div>
                            <div class="feat-desc"><?= $s[2] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── CI/CD ─────────────────────────────────────────────────────────────────── -->
<section class="section-alt" id="pipeline">
    <div class="container">
        <div style="text-align:center;margin-bottom:3rem;">
            <div class="tag" style="margin:0 auto 1.25rem;">CI/CD Pipeline</div>
            <h2 class="section-title"><span class="text-gradient">Ship with confidence.</span></h2>
            <p class="section-sub" style="margin:0 auto;">GitHub Actions pipeline on every push. Security scanning baked in. Integration tests against a live Docker stack before any image is published.</p>
        </div>

        <div class="card" style="margin-bottom:2rem;">
            <div class="pipeline">
                <?php
                $steps = [
                    ['Lint',        'PHP parallel-lint',           '#6366f1','rgba(99,102,241,.15)'],
                    ['Build',       'docker buildx · cache',       '#6366f1','rgba(99,102,241,.15)'],
                    ['Security',    'Trivy CRITICAL/HIGH',         '#ef4444','rgba(239,68,68,.15)'],
                    ['Integration', 'compose up · smoke test',     '#10b981','rgba(16,185,129,.15)'],
                    ['Push',        'DockerHub · v2 + SHA',        '#06b6d4','rgba(6,182,212,.15)'],
                ];
                foreach ($steps as $i => $step):
                    if ($i > 0): ?><div class="pipe-arrow">›</div><?php endif; ?>
                <div class="pipe-step" style="background:<?= $step[3] ?>;border:1px solid <?= $step[2] ?>40;color:<?= $step[2] ?>;">
                    <div><?= $step[0] ?></div>
                    <div style="font-size:.68rem;font-weight:400;opacity:.7;margin-top:.15rem;"><?= $step[1] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <p style="text-align:center;font-size:.72rem;color:var(--slate600);font-family:monospace;padding-bottom:1.25rem;">push / pull_request → all jobs → push to DockerHub (main branch only)</p>
        </div>

        <div class="grid-2" style="grid-template-columns:repeat(4,1fr);">
            <?php foreach([
                ['Lint gate',       'PHP parallel-lint validates every .php file. Syntax errors block the build before a single container is built.'],
                ['Layer caching',   'Docker buildx with GitHub Actions cache. Unchanged layers reused across runs — build times stay fast.'],
                ['DevSecOps',       'Trivy scans the final image for CRITICAL and HIGH CVEs. Results published to GitHub Security tab as SARIF.'],
                ['Integration test','Full docker compose stack spun up. Health probe must return HTTP 200 and login page must respond before push.'],
            ] as $c): ?>
            <div class="card card-hover" style="padding:1.25rem;">
                <div class="hl-title" style="margin-bottom:.375rem;"><?= $c[0] ?></div>
                <div class="hl-desc"><?= $c[1] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ─── HIGHLIGHTS ────────────────────────────────────────────────────────────── -->
<section>
    <div class="container">
        <div style="text-align:center;margin-bottom:3rem;">
            <div class="tag" style="margin:0 auto 1.25rem;">Engineering Highlights</div>
            <h2 class="section-title">Built like <span class="text-gradient">production.</span></h2>
            <p class="section-sub" style="margin:0 auto;">Every decision was made with real-world operational requirements in mind.</p>
        </div>
        <div class="grid-3">
            <?php foreach([
                ['PHP-FPM Runtime',     '#6366f1', 'Alpine Linux base. OPcache enabled. Redis extension for session storage. Non-root user. Health checks via FastCGI ping.'],
                ['Structured Logging',  '#06b6d4', 'UUIDv4 request_id per request. response_time_ms, status_code, method, URI on every entry. Ready for any log aggregator.'],
                ['Health Probes',       '#10b981', 'Readiness removes the pod during outage. Liveness restarts on deadlock. Implements the Kubernetes probe contract correctly.'],
                ['Prometheus Metrics',  '#f59e0b', '/metrics.php in Prometheus exposition format. Alert rules for uptime, DB health, Redis health ship with the project.'],
                ['Kubernetes Manifests','#6366f1', '10 manifests: Namespace, Deployment, Services, ConfigMap, Secret, PVC, Ingress, HPA. Deploys to any CNCF-compliant cluster.'],
                ['Redis Session Store', '#06b6d4', 'Sessions externalised from the application container. Required for stateless horizontal scaling without session affinity.'],
                ['CI/CD Automation',    '#10b981', 'GitHub Actions: lint, build, Trivy scan, integration test, push. Every merge to main produces a versioned Docker image.'],
                ['Audit Logging',       '#f59e0b', 'Every write operation records an audit entry with actor, timestamp, entity type, and changed fields. Admin-only trail.'],
                ['CSRF Protection',     '#6366f1', 'Per-session token generated on render, verified on POST. Defence-in-depth for every state-changing form in the application.'],
            ] as $h): ?>
            <div class="card card-hover hl-card">
                <div class="hl-icon" style="background:<?= $h[1] ?>18;border:1px solid <?= $h[1] ?>30;">
                    <div class="hl-dot" style="background:<?= $h[1] ?>;"></div>
                </div>
                <div class="hl-title"><?= $h[0] ?></div>
                <div class="hl-desc"><?= $h[2] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ─── STRUCTURE ─────────────────────────────────────────────────────────────── -->
<section class="section-alt">
    <div class="container">
        <div class="two-col two-col-center">
            <div>
                <div class="tag">Project Structure</div>
                <h2 class="section-title">Clean separation of concerns.<br><span class="text-gradient">Always.</span></h2>
                <div class="feat-list" style="margin-top:1.5rem;">
                    <?php foreach([
                        ['app/',    '#a5b4fc', 'Application layer — config, models, views, library code. Nothing here is publicly accessible.'],
                        ['public/', '#a5b4fc', 'Web root. Only files that must be served publicly. The Nginx document root.'],
                        ['deploy/', '#67e8f9', 'All infrastructure as code. Docker, Kubernetes, Prometheus config, load test scripts.'],
                        ['docs/',   '#67e8f9', 'Operational runbooks. Resilience tests, scaling tests, observability guide, logging reference.'],
                    ] as $d): ?>
                    <div class="feat-item">
                        <code class="mono" style="color:<?= $d[1] ?>;font-size:.875rem;font-weight:700;margin-top:.1rem;flex-shrink:0;"><?= $d[0] ?></code>
                        <div class="feat-desc" style="margin-left:.5rem;"><?= $d[2] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="code-block">
                <div class="code-header">
                    <span class="dot" style="background:#ff5f57"></span>
                    <span class="dot" style="background:#febc2e"></span>
                    <span class="dot" style="background:#28c840"></span>
                    <span class="code-label mono">mini-wms-v2/</span>
                </div>
                <div class="tree mono">
<span class="tree-dir">mini-wms-v2/</span>
<span class="tree-line">├── </span><span class="tree-dir">app/</span>
<span class="tree-line">│   ├── </span><span class="tree-dir">config/</span>      <span class="tree-cmt">bootstrap · db · env</span>
<span class="tree-line">│   ├── </span><span class="tree-dir">lib/</span>         <span class="tree-cmt">auth · csrf · logger · validators</span>
<span class="tree-line">│   ├── </span><span class="tree-dir">models/</span>      <span class="tree-cmt">User · Product · Order · StockMove</span>
<span class="tree-line">│   └── </span><span class="tree-dir">views/</span>       <span class="tree-cmt">layout · partials · pages</span>
<span class="tree-line">├── </span><span class="tree-dir">public/</span>          <span class="tree-cmt">Nginx document root</span>
<span class="tree-line">│   ├── </span><span class="tree-file">health.php</span>   <span class="tree-cmt">readiness + liveness probe</span>
<span class="tree-line">│   ├── </span><span class="tree-file">metrics.php</span>  <span class="tree-cmt">Prometheus endpoint</span>
<span class="tree-line">│   └── </span><span class="tree-dir">assets/</span>      <span class="tree-cmt">css · js</span>
<span class="tree-line">├── </span><span class="tree-dir">deploy/</span>
<span class="tree-line">│   ├── </span><span class="tree-dir">docker/</span>      <span class="tree-cmt">Dockerfile · compose · nginx.conf</span>
<span class="tree-line">│   ├── </span><span class="tree-dir">k8s/</span>         <span class="tree-cmt">10 Kubernetes manifests + HPA</span>
<span class="tree-line">│   └── </span><span class="tree-dir">monitoring/</span>  <span class="tree-cmt">prometheus.yml · grafana dashboard</span>
<span class="tree-line">├── </span><span class="tree-dir">docs/</span>
<span class="tree-line">│   ├── </span><span class="tree-file">RESILIENCE_TEST.md</span>
<span class="tree-line">│   ├── </span><span class="tree-file">SCALING_TEST.md</span>
<span class="tree-line">│   └── </span><span class="tree-file">OBSERVABILITY.md</span>
<span class="tree-line">├── </span><span class="tree-dir">sql/</span>             <span class="tree-cmt">schema.sql · seed.sql</span>
<span class="tree-line">└── </span><span class="tree-dir">.github/workflows/</span><span class="tree-file">ci.yml</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── TRY IT ────────────────────────────────────────────────────────────────── -->
<section class="try-section">
    <div class="glow glow-purple" style="width:700px;height:700px;top:50%;left:50%;transform:translate(-50%,-50%);animation-delay:1s;"></div>
    <div class="container" style="position:relative;z-index:1;">
        <div class="tag" style="margin:0 auto 1.5rem;width:fit-content;">Quick Start</div>
        <h2 class="section-title" style="text-align:center;"><span class="text-gradient">Running in 60 seconds.</span></h2>
        <p class="section-sub" style="text-align:center;margin:1rem auto 2.5rem;">Clone, compose up, and the entire stack is live — app, database, cache, metrics, and dashboards.</p>

        <div class="code-block" style="max-width:640px;margin:0 auto 2rem;">
            <div class="code-header">
                <span class="dot" style="background:#ff5f57"></span>
                <span class="dot" style="background:#febc2e"></span>
                <span class="dot" style="background:#28c840"></span>
                <span class="code-label mono">Terminal</span>
            </div>
<pre style="font-size:.85rem;padding:1.5rem;line-height:2;"><code><span class="c-gray"># Clone the repository</span>
<span class="c-green">$</span> <span class="c-white">git clone https://github.com/AmineAIT-ALI/mini-wms.git &amp;&amp; cd mini-wms</span>

<span class="c-gray"># Start all 7 containers</span>
<span class="c-green">$</span> <span class="c-white">docker compose -f deploy/docker/docker-compose.yml up -d</span>

<span class="c-gray"># Verify everything is healthy (~60s)</span>
<span class="c-green">$</span> <span class="c-white">docker compose -f deploy/docker/docker-compose.yml ps</span>
<span class="c-green">$</span> <span class="c-white">curl http://localhost:8080/health.php</span></code></pre>
        </div>

        <div class="try-urls">
            <?php foreach([
                ['App',        'http://localhost:8080', '#6366f1'],
                ['Prometheus', 'http://localhost:9090', '#f59e0b'],
                ['Grafana',    'http://localhost:3000', '#10b981'],
                ['cAdvisor',   'http://localhost:8081', '#06b6d4'],
            ] as $u): ?>
            <div class="url-pill">
                <div class="url-dot" style="background:<?= $u[2] ?>;"></div>
                <span class="url-name"><?= $u[0] ?></span>
                <code class="url-addr"><?= $u[1] ?></code>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="try-btns">
            <a href="login.php" class="btn btn-primary">Launch the App</a>
            <a href="https://github.com/AmineAIT-ALI/mini-wms" target="_blank" class="btn btn-outline">View on GitHub</a>
        </div>
    </div>
</section>

<!-- ─── FOOTER ────────────────────────────────────────────────────────────────── -->
<footer class="footer">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-brand">
                <div class="footer-logo">
                    <img src="assets/img/logo.png" width="28" height="28" alt="Mini WMS logo">
                </div>
                <span class="footer-name">Mini WMS</span>
                <span class="footer-meta">· Cloud-Native PHP · MIT License</span>
            </div>
            <div class="footer-links">
                <a href="login.php"    class="footer-link">App</a>
                <a href="health.php"   class="footer-link mono" style="font-size:.8rem;">/health.php</a>
                <a href="metrics.php"  class="footer-link mono" style="font-size:.8rem;">/metrics.php</a>
                <a href="https://github.com/AmineAIT-ALI/mini-wms" target="_blank" class="footer-link">GitHub</a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
