<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

// Already logged in → dashboard
if (current_user()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!validate_email($email)) {
        $errors[] = 'Invalid email address.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        try {
            $user = User::attempt($email, $password);
            if ($user) {
                login_user($user);
                audit_log('login_success', 'user', (int) $user['id'],
                    ['email' => $user['email']], (int) $user['id']);
                redirect('dashboard.php');
            } else {
                audit_log('login_fail', 'user', null, ['email' => $email], null);
                $errors[] = 'Invalid email or password.';
            }
        } catch (\Throwable $e) {
            error_log('[login] ' . $e->getMessage(), 3, LOG_PATH);
            $errors[] = 'Internal error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Mini WMS</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/app.css?v=<?= filemtime(dirname(__DIR__) . '/public/assets/css/app.css') ?>">
</head>
<body class="login-shell">

<div class="login-card">

    <div class="login-logo" aria-hidden="true">
        <img src="assets/img/logo.png" width="72" height="72" alt="" style="display:block;border-radius:12px;">
    </div>

    <h1 class="login-title">Mini WMS</h1>
    <p class="login-subtitle">Sign in to your account</p>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" role="alert">
            <svg class="alert-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span class="alert-text"><?= e($errors[0]) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php" data-spinner>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="email" class="form-label">Email address</label>
            <input type="email" id="email" name="email"
                   value="<?= e($_POST['email'] ?? '') ?>"
                   required autofocus autocomplete="email"
                   class="form-control"
                   placeholder="admin@example.com">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password"
                   required autocomplete="current-password"
                   class="form-control"
                   placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary btn-block" data-saving-text="Signing in…">
            <span class="btn-label">Sign in</span>
            <span class="spinner" style="display:none" aria-hidden="true"></span>
        </button>
    </form>

    <div style="margin-top:1.25rem; text-align:center;">
        <a href="index.php" class="btn btn-ghost btn-sm" style="color:var(--text-subtle); gap:.35rem;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Back to home
        </a>
    </div>

</div>

<script src="assets/js/app.js"></script>
</body>
</html>
