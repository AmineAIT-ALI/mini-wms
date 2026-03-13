<?php
/**
 * Flash message helpers (one-time session messages)
 */
declare(strict_types=1);

/**
 * Store a flash message.
 * @param string $type  'success' | 'error' | 'warning' | 'info'
 */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear all flash messages.
 * @return array<int, array{type: string, message: string}>
 */
function flash_get(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

/**
 * Check if there are pending flash messages.
 */
function flash_has(): bool
{
    return !empty($_SESSION['_flash']);
}
