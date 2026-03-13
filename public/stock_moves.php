<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_login();

$filters   = [];
$productId = (int)($_GET['product_id'] ?? 0);
$reason    = sanitize_string($_GET['reason']    ?? '', 20);
$dateFrom  = sanitize_string($_GET['date_from'] ?? '', 10);
$dateTo    = sanitize_string($_GET['date_to']   ?? '', 10);

if ($productId > 0)            $filters['product_id'] = $productId;
if (validate_move_reason($reason))  $filters['reason']    = $reason;
if (validate_date($dateFrom))  $filters['date_from'] = $dateFrom;
if (validate_date($dateTo))    $filters['date_to']   = $dateTo;

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

try {
    $total       = StockMove::count($filters);
    $moves       = StockMove::findAll($filters, $page, $perPage);
    $pages       = (int)ceil($total / $perPage);
    $allProducts = Product::all();
} catch (\Throwable $e) {
    error_log('[stock_moves] ' . $e->getMessage(), 3, LOG_PATH);
    $total = $pages = 0;
    $moves = $allProducts = [];
}

$pageTitle = 'Stock Moves';
$reasonLabels = [
    'manual_in'    => 'Manual inbound',
    'manual_out'   => 'Manual outbound',
    'order_pick'   => 'Order pick',
    'order_cancel' => 'Order cancel',
];

ob_start();
?>
<!-- Action bar -->
<div class="page-header">
    <div>
        <h1 class="page-title">Stock Moves</h1>
        <?php if ($total > 0): ?>
        <p class="page-subtitle"><?= e((string)$total) ?> move<?= $total > 1 ? 's' : '' ?></p>
        <?php endif; ?>
    </div>
    <a href="stock_move_new.php" class="btn btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New move
    </a>
</div>

<!-- Filters -->
<form method="get" action="stock_moves.php" class="filter-card">
    <div class="form-row flex-wrap">
        <div class="form-group">
            <label for="product_id" class="form-label">Product</label>
            <select id="product_id" name="product_id" class="form-control">
                <option value="">All</option>
                <?php foreach ($allProducts as $p): ?>
                <option value="<?= (int)$p['id'] ?>" <?= $productId === (int)$p['id'] ? 'selected' : '' ?>>
                    <?= e($p['sku']) ?> – <?= e($p['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="reason" class="form-label">Reason</label>
            <select id="reason" name="reason" class="form-control">
                <option value="">All</option>
                <?php foreach ($reasonLabels as $val => $lbl): ?>
                <option value="<?= e($val) ?>" <?= $reason === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="date_from" class="form-label">From</label>
            <input type="date" id="date_from" name="date_from"
                   value="<?= e($dateFrom) ?>" class="form-control">
        </div>
        <div class="form-group">
            <label for="date_to" class="form-label">To</label>
            <input type="date" id="date_to" name="date_to"
                   value="<?= e($dateTo) ?>" class="form-control">
        </div>
        <div class="form-group align-end">
            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
            <a href="stock_moves.php" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </div>
</form>

<?php if (empty($moves)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>
            <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
        </svg>
        <p>No stock moves found.</p>
    </div>
<?php else: ?>

<div class="table-wrap">
<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>Product</th>
            <th>Delta</th>
            <th>Reason</th>
            <th>Order</th>
            <th>By</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($moves as $move): ?>
        <tr>
            <td class="text-muted font-mono"><?= (int)$move['id'] ?></td>
            <td>
                <span class="font-mono text-muted"><?= e($move['sku']) ?></span>
                <?= e($move['product_name']) ?>
            </td>
            <td class="font-mono font-medium <?= (int)$move['delta'] >= 0 ? 'text-success' : 'text-danger' ?>">
                <?= (int)$move['delta'] >= 0 ? '+' : '' ?><?= e((string)$move['delta']) ?>
            </td>
            <td><span class="badge badge-secondary"><?= e($reasonLabels[$move['reason']] ?? $move['reason']) ?></span></td>
            <td>
                <?php if ($move['order_id']): ?>
                    <a href="order_view.php?id=<?= (int)$move['order_id'] ?>">#<?= (int)$move['order_id'] ?></a>
                <?php else: ?><span class="text-muted">–</span><?php endif; ?>
            </td>
            <td class="text-muted"><?= e($move['creator_email']) ?></td>
            <td class="text-muted"><?= e($move['created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="pagination" aria-label="Pagination">
    <?php
    $qs = http_build_query(array_filter([
        'product_id' => $productId ?: null,
        'reason'     => $reason,
        'date_from'  => $dateFrom,
        'date_to'    => $dateTo,
    ]));
    for ($i = 1; $i <= $pages; $i++):
    ?>
        <?php if ($i === $page): ?>
            <span class="page-item active" aria-current="page"><?= $i ?></span>
        <?php else: ?>
            <a href="stock_moves.php?page=<?= $i ?>&<?= $qs ?>" class="page-item"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/app/views/layout.php';
