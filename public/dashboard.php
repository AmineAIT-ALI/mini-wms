<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_login();

try {
    $totalProducts   = Product::total();
    $lowStockCount   = Product::countLowStock();
    $ordersByStatus  = Order::countByStatus();
    $recentOrders    = Order::recent(5);
    $recentMoves     = StockMove::recent(5);
} catch (\Throwable $e) {
    error_log('[dashboard] ' . $e->getMessage(), 3, LOG_PATH);
    $totalProducts  = $lowStockCount = 0;
    $ordersByStatus = ['pending' => 0, 'picked' => 0, 'shipped' => 0, 'cancelled' => 0];
    $recentOrders   = $recentMoves = [];
}

$pageTitle = 'Dashboard';

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
<!-- Page header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Inventory and orders overview</p>
    </div>
</div>

<!-- KPI Cards -->
<div class="kpi-grid">

    <!-- Active products -->
    <div class="kpi-card kpi-accent--blue">
        <div class="kpi-inner">
            <div class="kpi-body">
                <div class="kpi-label">Active products</div>
                <div class="kpi-value"><?= e((string)$totalProducts) ?></div>
            </div>
            <div class="kpi-icon kpi-blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73L11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                    <line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Low stock -->
    <div class="kpi-card <?= $lowStockCount > 0 ? 'kpi-accent--danger' : 'kpi-accent--success' ?>">
        <div class="kpi-inner">
            <div class="kpi-body">
                <div class="kpi-label">Low stock</div>
                <div class="kpi-value"><?= e((string)$lowStockCount) ?></div>
            </div>
            <div class="kpi-icon <?= $lowStockCount > 0 ? 'kpi-red' : 'kpi-green' ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Pending -->
    <div class="kpi-card kpi-accent--warning">
        <div class="kpi-inner">
            <div class="kpi-body">
                <div class="kpi-label">Pending</div>
                <div class="kpi-value"><?= e((string)$ordersByStatus['pending']) ?></div>
            </div>
            <div class="kpi-icon kpi-amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 22h14"/><path d="M5 2h14"/>
                    <path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/>
                    <path d="M7 2v4.172a2 2 0 0 1 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Picked -->
    <div class="kpi-card kpi-accent--blue">
        <div class="kpi-inner">
            <div class="kpi-body">
                <div class="kpi-label">Picked</div>
                <div class="kpi-value"><?= e((string)$ordersByStatus['picked']) ?></div>
            </div>
            <div class="kpi-icon kpi-blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="1"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Shipped -->
    <div class="kpi-card kpi-accent--success">
        <div class="kpi-inner">
            <div class="kpi-body">
                <div class="kpi-label">Shipped</div>
                <div class="kpi-value"><?= e((string)$ordersByStatus['shipped']) ?></div>
            </div>
            <div class="kpi-icon kpi-green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13" rx="1"/>
                    <path d="M16 8h4l3 3v5h-7V8z"/>
                    <circle cx="5.5" cy="18.5" r="2.5"/>
                    <circle cx="18.5" cy="18.5" r="2.5"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Cancelled -->
    <div class="kpi-card kpi-accent--cyan">
        <div class="kpi-inner">
            <div class="kpi-body">
                <div class="kpi-label">Cancelled</div>
                <div class="kpi-value"><?= e((string)($ordersByStatus['cancelled'] ?? 0)) ?></div>
            </div>
            <div class="kpi-icon kpi-cyan">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
        </div>
    </div>

</div>

<!-- Recent tables -->
<div class="dashboard-grid">

    <!-- Recent orders -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent orders</h2>
            <a href="orders.php" class="btn btn-sm btn-outline">View all</a>
        </div>
        <?php if (empty($recentOrders)): ?>
            <div class="empty-state">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                <p>No orders yet.</p>
            </div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Status</th>
                    <th>Created by</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentOrders as $order): ?>
                <tr data-href="order_view.php?id=<?= (int)$order['id'] ?>">
                    <td><a href="order_view.php?id=<?= (int)$order['id'] ?>"><?= e($order['reference']) ?></a></td>
                    <td><span class="badge badge-<?= e($statusColors[$order['status']] ?? 'secondary') ?>"><?= e($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                    <td class="text-muted"><?= e($order['creator_email']) ?></td>
                    <td class="text-muted"><?= e($order['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent stock moves -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent stock moves</h2>
            <a href="stock_moves.php" class="btn btn-sm btn-outline">View all</a>
        </div>
        <?php if (empty($recentMoves)): ?>
            <div class="empty-state">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/></svg>
                <p>No stock moves yet.</p>
            </div>
        <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Delta</th>
                    <th>Reason</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recentMoves as $move): ?>
                <tr>
                    <td><?= e($move['product_name']) ?></td>
                    <td class="font-mono <?= (int)$move['delta'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= (int)$move['delta'] >= 0 ? '+' : '' ?><?= e((string)$move['delta']) ?>
                    </td>
                    <td><span class="badge badge-secondary"><?= e($move['reason']) ?></span></td>
                    <td class="text-muted"><?= e($move['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/app/views/layout.php';
