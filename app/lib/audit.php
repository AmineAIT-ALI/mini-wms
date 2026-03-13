<?php
/**
 * Audit logging helper
 * All audit events are written to the audit_log table.
 */
declare(strict_types=1);

/**
 * Log an audit event.
 *
 * @param string     $action    e.g. 'login_success', 'product_create'
 * @param string     $entity    e.g. 'user', 'product', 'order', 'stock_move'
 * @param int|null   $entityId  The primary key of the affected entity
 * @param array      $meta      Additional context data (will be JSON-encoded)
 * @param int|null   $userId    Override user ID (defaults to session user)
 */
function audit_log(
    string $action,
    string $entity,
    ?int $entityId = null,
    array $meta = [],
    ?int $userId = null
): void {
    if ($userId === null) {
        $user   = current_user();
        $userId = $user['id'] ?? null;
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO audit_log (user_id, action, entity, entity_id, meta, created_at)
             VALUES (:user_id, :action, :entity, :entity_id, :meta, NOW())'
        );
        $stmt->execute([
            ':user_id'   => $userId,
            ':action'    => $action,
            ':entity'    => $entity,
            ':entity_id' => $entityId,
            ':meta'      => empty($meta) ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
        ]);
    } catch (\Throwable $e) {
        error_log('[AUDIT] Failed to write audit log: ' . $e->getMessage(), 3, LOG_PATH);
    }
}
