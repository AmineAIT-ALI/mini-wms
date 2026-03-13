<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Mini WMS') ?> – Mini WMS</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/css/app.css') ?>">
</head>
<body>
<div class="app-shell">

    <?php include __DIR__ . '/partials/nav.php'; ?>

    <!-- Mobile overlay -->
    <div id="sidebar-overlay" class="sidebar-overlay" aria-hidden="true"></div>

    <div class="app-main">
        <!-- Topbar -->
        <header class="topbar">
            <button id="nav-toggle" class="nav-toggle" aria-label="Open navigation">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <line x1="3" y1="6"  x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <span class="topbar-brand">Mini WMS</span>
        </header>

        <main class="page-content">
            <?php include __DIR__ . '/partials/flash.php'; ?>
            <?php if (isset($content)) echo $content; ?>
        </main>
    </div>

</div>
<script src="assets/js/app.js?v=<?= filemtime(dirname(__DIR__, 2) . '/public/assets/js/app.js') ?>"></script>
</body>
</html>
