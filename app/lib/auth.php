<?php
/**
 * Authentication helpers
 */
declare(strict_types=1);

/**
 * XSS-safe output helper – must be declared here as it's used by all layers.
 */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Ensure a session is started (idempotent).
 */
function session_ensure(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        session_start();
    }
}

/**
 * Log the current user in: store data in session and regenerate ID.
 */
function login_user(array $user): void
{
    session_ensure();
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
}

/**
 * Destroy the session and redirect to login.
 */
function logout_user(): void
{
    session_ensure();
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

/**
 * Return the authenticated user's data from session, or null.
 */
function current_user(): ?array
{
    session_ensure();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'    => (int) $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role']  ?? 'user',
    ];
}

/**
 * Redirect to login if not authenticated.
 */
function require_login(): array
{
    $user = current_user();
    if ($user === null) {
        header('Location: login.php');
        exit;
    }
    return $user;
}

/**
 * Require a specific role; redirect with error flash if unauthorized.
 */
function require_role(string $role): array
{
    $user = require_login();
    if ($user['role'] !== $role) {
        flash('error', 'Access denied: insufficient permissions.');
        header('Location: dashboard.php');
        exit;
    }
    return $user;
}

/**
 * Check if current user has a given role (no redirect).
 */
function has_role(string $role): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === $role;
}

/**
 * Redirect helper with optional flash message.
 */
function redirect(string $url, string $flashType = '', string $flashMsg = ''): never
{
    if ($flashType && $flashMsg) {
        flash($flashType, $flashMsg);
    }
    header("Location: $url");
    exit;
}
