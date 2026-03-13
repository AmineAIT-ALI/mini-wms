<?php
/**
 * Product model
 */
declare(strict_types=1);

class Product
{
    private const PER_PAGE = 20;

    /**
     * Total count of products matching search.
     */
    public static function count(string $search = ''): int
    {
        if ($search !== '') {
            $stmt = db()->prepare(
                "SELECT COUNT(*) FROM products
                 WHERE name LIKE :s OR sku LIKE :s"
            );
            $stmt->execute([':s' => '%' . $search . '%']);
        } else {
            $stmt = db()->query('SELECT COUNT(*) FROM products');
        }
        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginated list with optional search.
     */
    public static function findAll(string $search = '', int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $offset = ($page - 1) * $perPage;
        if ($search !== '') {
            $stmt = db()->prepare(
                "SELECT * FROM products
                 WHERE name LIKE :s OR sku LIKE :s
                 ORDER BY name ASC
                 LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue(':s',      '%' . $search . '%');
            $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = db()->prepare(
                'SELECT * FROM products ORDER BY name ASC LIMIT :limit OFFSET :offset'
            );
            $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    /**
     * Find one product by ID.
     */
    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find one product by SKU.
     */
    public static function findBySku(string $sku): ?array
    {
        $stmt = db()->prepare('SELECT * FROM products WHERE sku = :sku LIMIT 1');
        $stmt->execute([':sku' => $sku]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Create a new product. Returns new ID or false.
     */
    public static function create(string $sku, string $name, int $stock, int $threshold): int|false
    {
        try {
            $stmt = db()->prepare(
                'INSERT INTO products (sku, name, stock, threshold)
                 VALUES (:sku, :name, :stock, :threshold)'
            );
            $stmt->execute([
                ':sku'       => $sku,
                ':name'      => $name,
                ':stock'     => $stock,
                ':threshold' => $threshold,
            ]);
            return (int) db()->lastInsertId();
        } catch (\PDOException $e) {
            error_log('[Product::create] ' . $e->getMessage(), 3, LOG_PATH);
            return false;
        }
    }

    /**
     * Update an existing product. Returns true on success.
     */
    public static function update(int $id, string $sku, string $name, int $stock, int $threshold): bool
    {
        try {
            $stmt = db()->prepare(
                'UPDATE products
                 SET sku = :sku, name = :name, stock = :stock, threshold = :threshold
                 WHERE id = :id'
            );
            $stmt->execute([
                ':sku'       => $sku,
                ':name'      => $name,
                ':stock'     => $stock,
                ':threshold' => $threshold,
                ':id'        => $id,
            ]);
            return true;
        } catch (\PDOException $e) {
            error_log('[Product::update] ' . $e->getMessage(), 3, LOG_PATH);
            return false;
        }
    }

    /**
     * Check if product is referenced by order_items (prevent delete if so).
     */
    public static function isReferenced(int $id): bool
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Delete a product by ID. Returns true on success.
     */
    public static function delete(int $id): bool
    {
        try {
            $stmt = db()->prepare('DELETE FROM products WHERE id = :id');
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log('[Product::delete] ' . $e->getMessage(), 3, LOG_PATH);
            return false;
        }
    }

    /**
     * Count products with stock <= threshold (low stock).
     */
    public static function countLowStock(): int
    {
        $stmt = db()->query('SELECT COUNT(*) FROM products WHERE stock <= threshold');
        return (int) $stmt->fetchColumn();
    }

    /**
     * Total number of products.
     */
    public static function total(): int
    {
        $stmt = db()->query('SELECT COUNT(*) FROM products');
        return (int) $stmt->fetchColumn();
    }

    /**
     * All products (no pagination) – for order form dropdowns.
     */
    public static function all(): array
    {
        return db()->query('SELECT id, sku, name, stock FROM products ORDER BY name ASC')->fetchAll();
    }
}
