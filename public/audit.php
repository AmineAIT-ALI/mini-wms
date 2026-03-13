<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_role('admin');

$filters  = [];
$action   = sanitize_string($_GET['action']    ?? '', 64);
$entity   = sanitize_string($_GET['entity']    ?? '', 32);
$dateFrom = sanitize_string($_GET['date_from'] ?? '', 10);
$dateTo   = sanitize_string($_GET['date_to']   ?? '', 10);

if ($action)                  $filters['action']    = $action;
if ($entity)                  $filters['entity']    = $entity;
if (validate_date($dateFrom)) $filters['date_from'] = $dateFrom;
if (validate_date($dateTo))   $filters['date_to']   = $dateTo;

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

try {
    $total   = AuditLog::count($filters);
    $entries = AuditLog::findAll($filters, $page, $perPage);
    $pages   = (int)ceil($total / $perPage);
} catch (\Throwable $e) {
    error_log('[audit] ' . $e->getMessage(), 3, LOG_PATH);
    $total = $pages = 0;
    $entries = [];
}

$pageTitle = 'Audit Log';
$knownActions  = [
    'login_success', 'login_fail', 'logout',
    'product_create', 'product_update', 'product_delete',
    'order_create', 'order_status_change',
    'stock_move',
];
$knownEntities = ['user', 'product', 'order', 'stock_move'];

$actionColors = [
    'login_success'      => 'success',
    'login_fail'         => 'danger',
    'logout'             => 'secondary',
    'product_create'     => 'primary',
    'product_update'     => 'info',
    'product_delete'     => 'danger',
    'order_create'       => 'primary',
    'order_status_change'=> 'warning',
    'stock_move'         => 'secondary',
];

ob_start();
?>
<!-- Action bar -->
<div class="page-header">
    <div>
        <h1 class="page-title">Audit Log</h1>
        <?php if ($total > 0): ?>
        <p class="page-subtitle"><?= e((string)$total) ?> entr<?= $total > 1 ? 'ies' : 'y' ?></p>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<form method="get" action="audit.php" class="filter-card">
    <div class="form-row flex-wrap">
        <div class="form-group">
            <label for="action" class="form-label">Action</label>
            <select id="action" name="action" class="form-control">
                <option value="">All</option>
                <?php foreach ($knownActions as $a): ?>
                <option value="<?= e($a) ?>" <?= $action === $a ? 'selected' : '' ?>><?= e($a) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="entity" class="form-label">Entity</label>
            <select id="entity" name="entity" class="form-control">
                <option value="">All</option>
                <?php foreach ($knownEntities as $ent): ?>
                <option value="<?= e($ent) ?>" <?= $entity === $ent ? 'selected' : '' ?>><?= e($ent) ?></option>
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
            <a href="audit.php" class="btn btn-ghost btn-sm">Reset</a>
        </div>
    </div>
</form>

<?php if (empty($entries)): ?>
    <div class="empty-state">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
        </svg>
        <p>No audit entries.</p>
    </div>
<?php else: ?>

<div class="table-wrap">
<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>User</th>
            <th>Action</th>
            <th>Entity</th>
            <th>ID</th>
            <th>Meta</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($entries as $entry): ?>
        <tr>
            <td class="text-muted font-mono"><?= (int)$entry['id'] ?></td>
            <td class="text-muted">
                <?= $entry['user_email'] ? e($entry['user_email']) : '<em class="text-subtle">system</em>' ?>
            </td>
            <td>
                <span class="badge badge-<?= e($actionColors[$entry['action']] ?? 'secondary') ?>">
                    <?= e($entry['action']) ?>
                </span>
            </td>
            <td class="text-muted"><?= e($entry['entity']) ?></td>
            <td class="text-muted font-mono"><?= $entry['entity_id'] ? (int)$entry['entity_id'] : '–' ?></td>
            <td>
                <?php if ($entry['meta']): ?>
                    <code class="meta-preview"><?= e(mb_substr($entry['meta'], 0, 80)) ?><?= mb_strlen($entry['meta']) > 80 ? '…' : '' ?></code>
                <?php else: ?><span class="text-muted">–</span><?php endif; ?>
            </td>
            <td class="text-muted"><?= e($entry['created_at']) ?></td>
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
        'action'    => $action,
        'entity'    => $entity,
        'date_from' => $dateFrom,
        'date_to'   => $dateTo,
    ]));
    for ($i = 1; $i <= $pages; $i++):
    ?>
        <?php if ($i === $page): ?>
            <span class="page-item active" aria-current="page"><?= $i ?></span>
        <?php else: ?>
            <a href="audit.php?page=<?= $i ?>&<?= $qs ?>" class="page-item"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/app/views/layout.php';
