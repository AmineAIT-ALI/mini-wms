<?php
/**
 * Order model (orders + order_items)
 */
declare(strict_types=1);

class Order
{
    private const PER_PAGE = 20;

    /**
     * Total count of orders (optionally filtered by status).
     */
    public static function count(string $status = ''): int
    {
        if ($status !== '') {
            $stmt = db()->prepare('SELECT COUNT(*) FROM orders WHERE status = :s');
            $stmt->execute([':s' => $status]);
        } else {
            $stmt = db()->query('SELECT COUNT(*) FROM orders');
        }
        return (int) $stmt->fetchColumn();
    }

    /**
     * Counts per status – returns assoc array.
     */
    public static function countByStatus(): array
    {
        $stmt = db()->query(
            "SELECT status, COUNT(*) AS cnt FROM orders GROUP BY status"
        );
        $rows = $stmt->fetchAll();
        $result = ['pending' => 0, 'picked' => 0, 'shipped' => 0, 'cancelled' => 0];
        foreach ($rows as $row) {
            $result[$row['status']] = (int) $row['cnt'];
        }
        return $result;
    }

    /**
     * Paginated list of orders with creator email.
     */
    public static function findAll(string $status = '', int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = $status !== '' ? 'WHERE o.status = :status' : '';
        $sql    = "SELECT o.*, u.email AS creator_email
                   FROM orders o
                   JOIN users u ON o.created_by = u.id
                   {$where}
                   ORDER BY o.created_at DESC
                   LIMIT :limit OFFSET :offset";
        $stmt = db()->prepare($sql);
        if ($status !== '') {
            $stmt->bindValue(':status', $status);
        }
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Find an order by ID (with creator email).
     */
    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT o.*, u.email AS creator_email
             FROM orders o
             JOIN users u ON o.created_by = u.id
             WHERE o.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get items for an order (with product info).
     */
    public static function getItems(int $orderId): array
    {
        $stmt = db()->prepare(
            'SELECT oi.*, p.name AS product_name, p.sku, p.stock AS current_stock
             FROM order_items oi
             JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = :order_id'
        );
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    /**
     * Create an order with items atomically.
     * $items = [['product_id' => X, 'qty' => Y], ...]
     * Returns new order ID or false on failure.
     */
    public static function create(string $reference, int $createdBy, array $items): int|false
    {
        if (empty($items)) {
            return false;
        }
        $pdo = db();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO orders (reference, status, created_by) VALUES (:ref, 'pending', :by)"
            );
            $stmt->execute([':ref' => $reference, ':by' => $createdBy]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, qty) VALUES (:oid, :pid, :qty)'
            );
            foreach ($items as $item) {
                $itemStmt->execute([
                    ':oid' => $orderId,
                    ':pid' => (int) $item['product_id'],
                    ':qty' => (int) $item['qty'],
                ]);
            }

            $pdo->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[Order::create] ' . $e->getMessage(), 3, LOG_PATH);
            return false;
        }
    }

    /**
     * Transition order status with stock management.
     *
     * pending → picked   : decrement stock (order_pick), block if insufficient
     * pending → cancelled: no stock change
     * picked  → shipped  : no stock change
     * picked  → cancelled: re-increment stock (order_cancel)
     *
     * Returns true on success, or a string error message on failure.
     */
    public static function changeStatus(int $orderId, string $newStatus, int $userId): true|string
    {
        $order = self::findById($orderId);
        if (!$order) {
            return 'Order not found.';
        }
        $fromStatus = $order['status'];

        if (!validate_status_transition($fromStatus, $newStatus)) {
            return "Transition {$fromStatus} → {$newStatus} is not allowed.";
        }

        $pdo   = db();
        $items = self::getItems($orderId);

        try {
            $pdo->beginTransaction();

            if ($fromStatus === 'pending' && $newStatus === 'picked') {
                // Check stock sufficiency BEFORE touching anything
                foreach ($items as $item) {
                    if ((int) $item['current_stock'] < (int) $item['qty']) {
                        $pdo->rollBack();
                        return "Insufficient stock for \"{$item['product_name']}\" "
                            . "(available: {$item['current_stock']}, required: {$item['qty']}).";
                    }
                }
                // Decrement stock and record moves
                $decrStmt = $pdo->prepare(
                    'UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty'
                );
                $moveStmt = $pdo->prepare(
                    "INSERT INTO stock_moves (product_id, delta, reason, order_id, created_by)
                     VALUES (:pid, :delta, 'order_pick', :oid, :uid)"
                );
                foreach ($items as $item) {
                    $decrStmt->execute([':qty' => $item['qty'], ':id' => $item['product_id']]);
                    if ($decrStmt->rowCount() === 0) {
                        $pdo->rollBack();
                        return "Insufficient stock for \"{$item['product_name']}\".";
                    }
                    $moveStmt->execute([
                        ':pid'   => $item['product_id'],
                        ':delta' => -(int) $item['qty'],
                        ':oid'   => $orderId,
                        ':uid'   => $userId,
                    ]);
                }
            } elseif ($fromStatus === 'picked' && $newStatus === 'cancelled') {
                // Re-increment stock and record moves
                $incrStmt = $pdo->prepare(
                    'UPDATE products SET stock = stock + :qty WHERE id = :id'
                );
                $moveStmt = $pdo->prepare(
                    "INSERT INTO stock_moves (product_id, delta, reason, order_id, created_by)
                     VALUES (:pid, :delta, 'order_cancel', :oid, :uid)"
                );
                foreach ($items as $item) {
                    $incrStmt->execute([':qty' => $item['qty'], ':id' => $item['product_id']]);
                    $moveStmt->execute([
                        ':pid'   => $item['product_id'],
                        ':delta' => (int) $item['qty'],
                        ':oid'   => $orderId,
                        ':uid'   => $userId,
                    ]);
                }
            }

            // Update order status
            $upd = $pdo->prepare('UPDATE orders SET status = :s WHERE id = :id');
            $upd->execute([':s' => $newStatus, ':id' => $orderId]);

            $pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[Order::changeStatus] ' . $e->getMessage(), 3, LOG_PATH);
            return 'Internal error while changing order status.';
        }
    }

    /**
     * Recent orders for dashboard.
     */
    public static function recent(int $limit = 5): array
    {
        $stmt = db()->prepare(
            'SELECT o.*, u.email AS creator_email
             FROM orders o
             JOIN users u ON o.created_by = u.id
             ORDER BY o.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
