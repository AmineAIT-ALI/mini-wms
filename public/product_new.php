<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_role('admin');

$errors = [];
$form   = ['sku' => '', 'name' => '', 'stock' => '0', 'threshold' => '0'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $form['sku']       = sanitize_string($_POST['sku']       ?? '', 64);
    $form['name']      = sanitize_string($_POST['name']      ?? '', 255);
    $form['stock']     = trim($_POST['stock']     ?? '0');
    $form['threshold'] = trim($_POST['threshold'] ?? '0');

    if (!validate_sku($form['sku'])) {
        $errors[] = 'Invalid SKU (alphanumeric + hyphens, 2–64 characters).';
    }
    if (strlen($form['name']) < 2) {
        $errors[] = 'Name must be at least 2 characters.';
    }
    if (!validate_non_negative_int($form['stock'])) {
        $errors[] = 'Stock must be an integer ≥ 0.';
    }
    if (!validate_non_negative_int($form['threshold'])) {
        $errors[] = 'Threshold must be an integer ≥ 0.';
    }
    if (empty($errors) && Product::findBySku($form['sku'])) {
        $errors[] = 'This SKU already exists.';
    }

    if (empty($errors)) {
        try {
            $id = Product::create(
                $form['sku'],
                $form['name'],
                (int)$form['stock'],
                (int)$form['threshold']
            );
            if ($id) {
                audit_log('product_create', 'product', $id, [
                    'sku'  => $form['sku'],
                    'name' => $form['name'],
                ]);
                redirect('products.php', 'success', "Product \"{$form['name']}\" created.");
            }
            $errors[] = 'Failed to create product.';
        } catch (\Throwable $e) {
            error_log('[product_new] ' . $e->getMessage(), 3, LOG_PATH);
            $errors[] = 'Internal error.';
        }
    }
}

$pageTitle = 'New product';
ob_start();
?>
<div class="page-header">
    <div>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="products.php">Products</a>
            <span aria-hidden="true">/</span>
            <span>New</span>
        </nav>
        <h1 class="page-title">New product</h1>
    </div>
    <a href="products.php" class="btn btn-outline">← Back</a>
</div>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error" role="alert">
        <svg class="alert-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span class="alert-text"><?= e($err) ?></span>
    </div>
<?php endforeach; ?>

<div class="card form-card">
    <form method="post" action="product_new.php" data-spinner>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="sku" class="form-label">SKU <span class="required">*</span></label>
            <input type="text" id="sku" name="sku"
                   value="<?= e($form['sku']) ?>"
                   required maxlength="64" class="form-control"
                   pattern="[A-Za-z0-9\-]{2,64}"
                   placeholder="e.g. PROD-001">
            <small class="form-hint">Alphanumeric + hyphens, 2–64 characters</small>
        </div>

        <div class="form-group">
            <label for="name" class="form-label">Name <span class="required">*</span></label>
            <input type="text" id="name" name="name"
                   value="<?= e($form['name']) ?>"
                   required maxlength="255" class="form-control"
                   placeholder="e.g. White cotton t-shirt">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="stock" class="form-label">Initial stock</label>
                <input type="number" id="stock" name="stock"
                       value="<?= e($form['stock']) ?>"
                       min="0" required class="form-control">
            </div>
            <div class="form-group">
                <label for="threshold" class="form-label">Alert threshold</label>
                <input type="number" id="threshold" name="threshold"
                       value="<?= e($form['threshold']) ?>"
                       min="0" required class="form-control">
                <small class="form-hint">Triggers the "Low stock" alert</small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary" data-saving-text="Creating…">
                <span class="btn-label">Create product</span>
                <span class="spinner" style="display:none" aria-hidden="true"></span>
            </button>
            <a href="products.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/app/views/layout.php';
