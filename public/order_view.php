<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_login();

$id    = (int)($_GET['id'] ?? 0);
$order = $id > 0 ? Order::findById($id) : null;

if (!$order) {
    flash('error', 'Order not found.');
    redirect('orders.php');
}

$items = Order::getItems($id);

$pageTitle = 'Order ' . $order['reference'];

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

$transitions = [
    'pending'   => ['picked' => 'Pick', 'cancelled' => 'Cancel'],
    'picked'    => ['shipped' => 'Ship', 'cancelled' => 'Cancel'],
    'shipped'   => [],
    'cancelled' => [],
];
$nextStatuses = $transitions[$order['status']] ?? [];

ob_start();
?>
<!-- Page header -->
<div class="page-header">
    <div>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="orders.php">Orders</a>
            <span aria-hidden="true">/</span>
            <span><?= e($order['reference']) ?></span>
        </nav>
        <h1 class="page-title">
            <?= e($order['reference']) ?>
            <button type="button"
                    class="btn btn-xs btn-ghost"
                    data-copy="<?= e($order['reference']) ?>"
                    aria-label="Copy reference">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            </button>
        </h1>
    </div>
    <a href="orders.php" class="btn btn-outline">← Back</a>
</div>

<!-- Meta card -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Details</h2>
        <?php if (!empty($nextStatuses)): ?>
        <div class="order-actions">
            <?php foreach ($nextStatuses as $toStatus => $label): ?>
            <form method="post" action="order_status.php" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id"         value="<?= $id ?>">
                <input type="hidden" name="new_status" value="<?= e($toStatus) ?>">
                <button type="submit"
                        class="btn btn-sm <?= $toStatus === 'cancelled' ? 'btn-danger' : 'btn-primary' ?>"
                        data-confirm="Confirm: <?= e($label) ?> order <?= e($order['reference']) ?>?">
                    <?= e($label) ?>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <dl class="meta-grid">
        <dt>Reference</dt>
        <dd class="font-mono"><?= e($order['reference']) ?></dd>

        <dt>Status</dt>
        <dd><span class="badge badge-<?= e($statusColors[$order['status']] ?? 'secondary') ?>">
            <?= e($statusLabels[$order['status']] ?? $order['status']) ?>
        </span></dd>

        <dt>Created by</dt>
        <dd><?= e($order['creator_email']) ?></dd>

        <dt>Created at</dt>
        <dd class="text-muted"><?= e($order['created_at']) ?></dd>

        <dt>Last updated</dt>
        <dd class="text-muted"><?= e($order['updated_at']) ?></dd>
    </dl>
</div>

<!-- Items card -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Items</h2>
        <span class="badge badge-secondary"><?= count($items) ?> item<?= count($items) > 1 ? 's' : '' ?></span>
    </div>
    <?php if (empty($items)): ?>
        <div class="empty-state">
            <p>No items in this order.</p>
        </div>
    <?php else: ?>
    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Ordered qty</th>
                <th>Current stock</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <?php $insufficient = (int)$item['current_stock'] < (int)$item['qty']; ?>
            <tr>
                <td class="font-mono text-muted"><?= e($item['sku']) ?></td>
                <td>
                    <a href="product_edit.php?id=<?= (int)$item['product_id'] ?>"><?= e($item['product_name']) ?></a>
                </td>
                <td class="font-mono"><?= e((string)$item['qty']) ?></td>
                <td class="font-mono <?= $insufficient ? 'text-danger' : 'text-success' ?>">
                    <?= e((string)$item['current_stock']) ?>
                    <?php if ($insufficient && $order['status'] === 'pending'): ?>
                        <span class="badge badge-danger ml-1">Insufficient</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/app/views/layout.php';
