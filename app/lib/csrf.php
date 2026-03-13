<?php
/**
 * CSRF protection helpers
 */
declare(strict_types=1);

/**
 * Return (and lazily create) the session CSRF token.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify the token submitted via POST.
 * Dies with 403 if invalid.
 */
function csrf_verify(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';

    if (!$stored || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
    // Rotate token after successful verification
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Render a hidden CSRF input field.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}
