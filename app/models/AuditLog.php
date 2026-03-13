<?php
/**
 * AuditLog model – read-only queries for the audit page
 */
declare(strict_types=1);

class AuditLog
{
    private const PER_PAGE = 20;

    /**
     * Total audit entries (optional filter by user or action).
     */
    public static function count(array $filters = []): int
    {
        [$where, $params] = self::buildWhere($filters);
        $stmt = db()->prepare("SELECT COUNT(*) FROM audit_log al {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginated list of audit entries with user email.
     */
    public static function findAll(array $filters = [], int $page = 1, int $perPage = self::PER_PAGE): array
    {
        [$where, $params] = self::buildWhere($filters);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT al.*, u.email AS user_email
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.id
                {$where}
                ORDER BY al.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = db()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private static function buildWhere(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['action'])) {
            $clauses[]         = 'al.action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['entity'])) {
            $clauses[]         = 'al.entity = :entity';
            $params[':entity'] = $filters['entity'];
        }
        if (!empty($filters['user_id'])) {
            $clauses[]           = 'al.user_id = :user_id';
            $params[':user_id']  = (int) $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $clauses[]            = 'DATE(al.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clauses[]          = 'DATE(al.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }
}
