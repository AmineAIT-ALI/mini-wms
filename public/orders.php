<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_login();

$status  = sanitize_string($_GET['status'] ?? '', 20);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$validStatuses = ['', 'pending', 'picked', 'shipped', 'cancelled'];
if (!in_array($status, $validStatuses, true)) {
    $status = '';
}

try {
    $total  = Order::count($status);
    $orders = Order::findAll($status, $page, $perPage);
    $pages  = (int)ceil($total / $perPage);
} catch (\Throwable $e) {
    error_log('[orders] ' . $e->getMessage(), 3, LOG_PATH);
    $total = $pages = 0;
    $orders = [];
}

$pageTitle = 'Orders';
$statusLabels = [
    'pending'   => 'Pending',
    'picked'    => 'Picked',
    'shipped'   => 'Shipped',
    'cancelled' => 'Cancelled',
];
$statusColors = [
    'pending'   => 'warning',
    'picked'    => 'primary',
    'shipped'   => 'success',
    'cancelled' => 'danger',
];

ob_start();
?>
<!-- Action bar -->
<div class="page-header">
    <div>
        <h1 class="page-title">Orders</h1>
        <?php if ($total > 0): ?>
        <p class="page-subtitle"><?= e((string)$total) ?> order<?= $total > 1 ? 's' : '' ?></p>
        <?php endif; ?>
    </div>
    <a href="order_new.php" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New order
    </a>
</div>

<!-- Status tabs -->
<div class="status-tabs" role="tablist" aria-label="Filter by status">
    <a href="orders.php"
       class="tab-link <?= $status === '' ? 'active' : '' ?>"
       data-status="all" role="tab"
       aria-selected="<?= $status === '' ? 'true' : 'false' ?>">All</a>
    <a href="orders.php?status=pending"
       class="tab-link <?= $status === 'pending' ? 'active' : '' ?>"
       data-status="pending" role="tab"
       aria-selected="<?= $status === 'pending' ? 'true' : 'false' ?>">Pending</a>
    <a href="orders.php?status=picked"
       class="tab-link <?= $status === 'picked' ? 'active' : '' ?>"
       data-status="picked" role="tab"
       aria-selected="<?= $status === 'picked' ? 'true' : 'false' ?>">Picked</a>
    <a href="orders.php?status=shipped"
       class="tab-link <?= $status === 'shipped' ? 'active' : '' ?>"
       data-status="shipped" role="tab"
       aria-selected="<?= $status === 'shipped' ? 'true' : 'false' ?>">Shipped</a>
    <a href="orders.php?status=cancelled"
       class="tab-link <?= $status === 'cancelled' ? 'active' : '' ?>"
       data-status="cancelled" role="tab"
       aria-selected="<?= $status === 'cancelled' ? 'true' : 'false' ?>">Cancelled</a>
</div>

<?php if (empty($orders)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        <p>No orders<?= $status ? ' for this status' : '' ?>.</p>
        <a href="order_new.php" class="btn btn-primary btn-sm">Create an order</a>
    </div>
<?php else: ?>

<div class="table-wrap">
<table class="table">
    <thead>
        <tr>
            <th>Reference</th>
            <th>Status</th>
            <th>Created by</th>
            <th>Created at</th>
            <th>Updated</th>
            <th class="col-actions">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
        <tr data-href="order_view.php?id=<?= (int)$order['id'] ?>">
            <td class="font-medium"><a href="order_view.php?id=<?= (int)$order['id'] ?>"><?= e($order['reference']) ?></a></td>
            <td><span class="badge badge-<?= e($statusColors[$order['status']] ?? 'secondary') ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
            <td class="text-muted"><?= e($order['creator_email']) ?></td>
            <td class="text-muted"><?= e($order['created_at']) ?></td>
            <td class="text-muted"><?= e($order['updated_at']) ?></td>
            <td class="col-actions">
                <a href="order_view.php?id=<?= (int)$order['id'] ?>" class="btn btn-xs btn-outline">View</a>
            </td>
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
            <a href="orders.php?page=<?= $i ?>&status=<?= urlencode($status) ?>" class="page-item"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/app/views/layout.php';
