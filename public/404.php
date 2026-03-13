<?php
declare(strict_types=1);
http_response_code(404);

$bootstrapFile = dirname(__DIR__) . '/app/config/bootstrap.php';
$hasBootstrap  = is_file($bootstrapFile);
if ($hasBootstrap) {
    require_once $bootstrapFile;
    $currentUser = current_user();
} else {
    $currentUser = null;
}
$pageTitle = 'Page Not Found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 – Page Not Found – Mini WMS</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="error-body">
<div class="error-wrapper">
    <div class="error-code">404</div>
    <h1>Page Not Found</h1>
    <p>The page you are looking for does not exist or has been moved.</p>
    <a href="<?= $currentUser ? 'dashboard.php' : 'login.php' ?>" class="btn btn-primary">
        Back to home
    </a>
</div>
</body>
</html>
