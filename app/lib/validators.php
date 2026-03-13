<?php
/**
 * Input validation helpers
 */
declare(strict_types=1);

/**
 * Validate an email address.
 */
function validate_email(string $email): bool
{
    return (bool) filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}

/**
 * Validate a SKU: alphanumeric + hyphens, 2–64 chars.
 */
function validate_sku(string $sku): bool
{
    return (bool) preg_match('/^[A-Za-z0-9\-]{2,64}$/', $sku);
}

/**
 * Validate a positive integer (qty, stock, threshold).
 */
function validate_positive_int(mixed $value): bool
{
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
}

/**
 * Validate a non-negative integer (stock, threshold starting at 0).
 */
function validate_non_negative_int(mixed $value): bool
{
    return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) !== false;
}

/**
 * Validate an order status transition.
 * Returns true if the transition from $from to $to is allowed.
 */
function validate_status_transition(string $from, string $to): bool
{
    $allowed = [
        'pending' => ['picked', 'cancelled'],
        'picked'  => ['shipped', 'cancelled'],
        'shipped' => [],
        'cancelled' => [],
    ];
    return in_array($to, $allowed[$from] ?? [], true);
}

/**
 * Validate a stock_moves reason.
 */
function validate_move_reason(string $reason): bool
{
    return in_array($reason, ['manual_in', 'manual_out', 'order_pick', 'order_cancel'], true);
}

/**
 * Validate a date string (Y-m-d format).
 */
function validate_date(string $date): bool
{
    $d = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

/**
 * Sanitize a plain string: strip tags and trim.
 */
function sanitize_string(string $value, int $maxLen = 255): string
{
    return mb_substr(trim(strip_tags($value)), 0, $maxLen);
}
