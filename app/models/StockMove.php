<?php
/**
 * StockMove model
 */
declare(strict_types=1);

class StockMove
{
    private const PER_PAGE = 20;

    /**
     * Total count with optional filters.
     */
    public static function count(array $filters = []): int
    {
        [$where, $params] = self::buildWhere($filters);
        $stmt = db()->prepare("SELECT COUNT(*) FROM stock_moves sm {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginated list with optional filters.
     * Filters: product_id, reason, date_from (Y-m-d), date_to (Y-m-d)
     */
    public static function findAll(array $filters = [], int $page = 1, int $perPage = self::PER_PAGE): array
    {
        [$where, $params] = self::buildWhere($filters);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT sm.*, p.name AS product_name, p.sku, u.email AS creator_email
                FROM stock_moves sm
                JOIN products p ON sm.product_id = p.id
                JOIN users u    ON sm.created_by  = u.id
                {$where}
                ORDER BY sm.created_at DESC
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

    /**
     * Recent moves for dashboard.
     */
    public static function recent(int $limit = 5): array
    {
        $stmt = db()->prepare(
            'SELECT sm.*, p.name AS product_name, p.sku, u.email AS creator_email
             FROM stock_moves sm
             JOIN products p ON sm.product_id = p.id
             JOIN users u    ON sm.created_by  = u.id
             ORDER BY sm.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create a manual stock move atomically (INSERT move + UPDATE products.stock).
     * Returns true on success or a string error message.
     */
    public static function createManual(
        int    $productId,
        int    $qty,
        string $reason,
        int    $createdBy
    ): true|string {
        if (!in_array($reason, ['manual_in', 'manual_out'], true)) {
            return 'Invalid reason for a manual stock move.';
        }

        $delta = $reason === 'manual_in' ? $qty : -$qty;
        $pdo   = db();

        try {
            $pdo->beginTransaction();

            // For manual_out, check stock won't go negative
            if ($reason === 'manual_out') {
                $row = $pdo->prepare('SELECT stock FROM products WHERE id = :id FOR UPDATE');
                $row->execute([':id' => $productId]);
                $product = $row->fetch();
                if (!$product || (int) $product['stock'] < $qty) {
                    $pdo->rollBack();
                    $available = $product ? $product['stock'] : 0;
                    return "Insufficient stock (available: {$available}, requested: {$qty}).";
                }
                $upd = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id');
            } else {
                $upd = $pdo->prepare('UPDATE products SET stock = stock + :qty WHERE id = :id');
            }

            $upd->execute([':qty' => $qty, ':id' => $productId]);

            $ins = $pdo->prepare(
                'INSERT INTO stock_moves (product_id, delta, reason, order_id, created_by)
                 VALUES (:pid, :delta, :reason, NULL, :uid)'
            );
            $ins->execute([
                ':pid'    => $productId,
                ':delta'  => $delta,
                ':reason' => $reason,
                ':uid'    => $createdBy,
            ]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[StockMove::createManual] ' . $e->getMessage(), 3, LOG_PATH);
            return 'Internal error while recording stock move.';
        }
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private static function buildWhere(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['product_id'])) {
            $clauses[]              = 'sm.product_id = :product_id';
            $params[':product_id']  = (int) $filters['product_id'];
        }
        if (!empty($filters['reason'])) {
            $clauses[]         = 'sm.reason = :reason';
            $params[':reason'] = $filters['reason'];
        }
        if (!empty($filters['date_from'])) {
            $clauses[]             = 'DATE(sm.created_at) >= :date_from';
            $params[':date_from']  = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clauses[]           = 'DATE(sm.created_at) <= :date_to';
            $params[':date_to']  = $filters['date_to'];
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }
}
