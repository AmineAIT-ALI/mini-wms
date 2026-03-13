<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('products.php');
}

csrf_verify();

$id = (int)($_POST['id'] ?? 0);
$product = $id > 0 ? Product::findById($id) : null;

if (!$product) {
    redirect('products.php', 'error', 'Product not found.');
}

// Check FK constraint (order_items reference)
if (Product::isReferenced($id)) {
    redirect('products.php', 'error',
        "Cannot delete \"{$product['name']}\": it is referenced by existing orders.");
}

try {
    $ok = Product::delete($id);
    if ($ok) {
        audit_log('product_delete', 'product', $id, [
            'sku'  => $product['sku'],
            'name' => $product['name'],
        ]);
        redirect('products.php', 'success', "Product \"{$product['name']}\" deleted.");
    }
    redirect('products.php', 'error', 'Failed to delete product.');
} catch (\Throwable $e) {
    error_log('[product_delete] ' . $e->getMessage(), 3, LOG_PATH);
    redirect('products.php', 'error', 'Internal error.');
}
