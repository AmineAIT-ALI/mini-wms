<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

// Logout must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

csrf_verify();

$user = current_user();
if ($user) {
    audit_log('logout', 'user', $user['id'], ['email' => $user['email']]);
}

logout_user(); // redirects to login.php
