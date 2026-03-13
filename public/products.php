<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_login();

$search  = sanitize_string($_GET['search'] ?? '', 100);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

try {
    $total    = Product::count($search);
    $products = Product::findAll($search, $page, $perPage);
    $pages    = (int)ceil($total / $perPage);
} catch (\Throwable $e) {
    error_log('[products] ' . $e->getMessage(), 3, LOG_PATH);
    $total = $pages = 0;
    $products = [];
}

$pageTitle = 'Products';
$isAdmin   = has_role('admin');

ob_start();
?>
<!-- Action bar -->
<div class="page-header">
    <div>
        <h1 class="page-title">Products</h1>
        <?php if ($total > 0): ?>
        <p class="page-subtitle"><?= e((string)$total) ?> product<?= $total > 1 ? 's' : '' ?></p>
        <?php endif; ?>
    </div>
    <?php if ($isAdmin): ?>
    <a href="product_new.php" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New product
    </a>
    <?php endif; ?>
</div>

<!-- Search bar -->
<form method="get" action="products.php" class="filter-card">
    <div class="search-bar">
        <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" value="<?= e($search) ?>"
               placeholder="Search by name or SKU…" class="form-control search-input">
        <button type="submit" class="btn btn-outline btn-sm">Search</button>
        <?php if ($search): ?>
        <a href="products.php" class="btn btn-ghost btn-sm">✕ Clear</a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73L11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        <p><?= $search ? 'No products matching "' . e($search) . '".' : 'No products yet.' ?></p>
        <?php if ($isAdmin && !$search): ?>
        <a href="product_new.php" class="btn btn-primary btn-sm">Create first product</a>
        <?php endif; ?>
    </div>
<?php else: ?>

<div class="table-wrap">
<table class="table">
    <thead>
        <tr>
            <th>SKU</th>
            <th>Name</th>
            <th>Stock</th>
            <th>Threshold</th>
            <th>Updated</th>
            <?php if ($isAdmin): ?><th class="col-actions">Actions</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($products as $p): ?>
        <?php $lowStock = (int)$p['stock'] <= (int)$p['threshold']; ?>
        <tr>
            <td class="font-mono text-muted"><?= e($p['sku']) ?></td>
            <td class="font-medium"><?= e($p['name']) ?></td>
            <td>
                <span class="<?= $lowStock ? 'text-danger font-medium' : '' ?>"><?= e((string)$p['stock']) ?></span>
                <?php if ($lowStock): ?>
                    <span class="badge badge-danger ml-1">Low stock</span>
                <?php endif; ?>
            </td>
            <td class="text-muted"><?= e((string)$p['threshold']) ?></td>
            <td class="text-muted"><?= e($p['updated_at']) ?></td>
            <?php if ($isAdmin): ?>
            <td class="col-actions">
                <a href="product_edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-xs btn-outline" aria-label="Edit <?= e($p['name']) ?>">Edit</a>
                <form method="post" action="product_delete.php" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-xs btn-danger"
                            data-confirm="Delete product &quot;<?= e($p['name']) ?>&quot;? This action cannot be undone."
                            aria-label="Delete <?= e($p['name']) ?>">Delete</button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="pagination" aria-label="Pagination">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
        <?php if ($i === $page): ?>
            <span class="page-item active" aria-current="page"><?= $i ?></span>
        <?php else: ?>
            <a href="products.php?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-item"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/app/views/layout.php';
