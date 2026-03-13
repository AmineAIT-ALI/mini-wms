<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_login();

$errors = [];
$form   = ['product_id' => '', 'qty' => '1', 'reason' => 'manual_in'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $form['product_id'] = trim($_POST['product_id'] ?? '');
    $form['qty']        = trim($_POST['qty']        ?? '');
    $form['reason']     = sanitize_string($_POST['reason'] ?? '', 20);

    $pid = (int)$form['product_id'];

    if ($pid <= 0) {
        $errors[] = 'Please select a product.';
    }
    if (!validate_positive_int($form['qty'])) {
        $errors[] = 'Quantity must be a positive integer.';
    }
    if (!in_array($form['reason'], ['manual_in', 'manual_out'], true)) {
        $errors[] = 'Invalid reason (manual_in or manual_out only).';
    }

    if (empty($errors)) {
        $product = Product::findById($pid);
        if (!$product) {
            $errors[] = 'Product not found.';
        }
    }

    if (empty($errors)) {
        try {
            $result = StockMove::createManual($pid, (int)$form['qty'], $form['reason'], $currentUser['id']);
            if ($result === true) {
                audit_log('stock_move', 'stock_move', null, [
                    'product_id' => $pid,
                    'qty'        => (int)$form['qty'],
                    'reason'     => $form['reason'],
                ]);
                $verb = $form['reason'] === 'manual_in' ? 'Inbound' : 'Outbound';
                redirect('stock_moves.php', 'success', "{$verb} of {$form['qty']} unit(s) recorded.");
            }
            $errors[] = $result;
        } catch (\Throwable $e) {
            error_log('[stock_move_new] ' . $e->getMessage(), 3, LOG_PATH);
            $errors[] = 'Internal error.';
        }
    }
}

try {
    $allProducts = Product::all();
} catch (\Throwable $e) {
    $allProducts = [];
}

$pageTitle = 'New Stock Move';
ob_start();
?>
<div class="page-header">
    <div>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="stock_moves.php">Stock Moves</a>
            <span aria-hidden="true">/</span>
            <span>New</span>
        </nav>
        <h1 class="page-title">New stock move</h1>
    </div>
    <a href="stock_moves.php" class="btn btn-outline">← Back</a>
</div>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error" role="alert">
        <svg class="alert-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span class="alert-text"><?= e($err) ?></span>
    </div>
<?php endforeach; ?>

<div class="card form-card">
    <form method="post" action="stock_move_new.php" data-spinner>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="product_id" class="form-label">Product <span class="required">*</span></label>
            <select id="product_id" name="product_id" required class="form-control">
                <option value="">— Select a product —</option>
                <?php foreach ($allProducts as $p): ?>
                <option value="<?= (int)$p['id'] ?>"
                        data-stock="<?= (int)$p['stock'] ?>"
                        <?= (int)$form['product_id'] === (int)$p['id'] ? 'selected' : '' ?>>
                    <?= e($p['sku']) ?> – <?= e($p['name']) ?> (stock&nbsp;: <?= (int)$p['stock'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="reason" class="form-label">Move type <span class="required">*</span></label>
            <select id="reason" name="reason" required class="form-control">
                <option value="manual_in"  <?= $form['reason'] === 'manual_in'  ? 'selected' : '' ?>>↑ Manual inbound</option>
                <option value="manual_out" <?= $form['reason'] === 'manual_out' ? 'selected' : '' ?>>↓ Manual outbound</option>
            </select>
        </div>

        <div class="form-group">
            <label for="qty" class="form-label">Quantity <span class="required">*</span></label>
            <input type="number" id="qty" name="qty"
                   value="<?= e($form['qty']) ?>"
                   min="1" required class="form-control">
            <p id="stock-warning" class="stock-warning" role="alert" aria-live="polite"></p>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary" data-saving-text="Saving…">
                <span class="btn-label">Save</span>
                <span class="spinner" style="display:none" aria-hidden="true"></span>
            </button>
            <a href="stock_moves.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/app/views/layout.php';
