<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_role('admin');

$id = (int)($_GET['id'] ?? 0);
$product = $id > 0 ? Product::findById($id) : null;

if (!$product) {
    flash('error', 'Product not found.');
    redirect('products.php');
}

$errors = [];
$form   = [
    'sku'       => $product['sku'],
    'name'      => $product['name'],
    'stock'     => (string)$product['stock'],
    'threshold' => (string)$product['threshold'],
];

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

    if (empty($errors)) {
        $existing = Product::findBySku($form['sku']);
        if ($existing && (int)$existing['id'] !== $id) {
            $errors[] = 'This SKU is already used by another product.';
        }
    }

    if (empty($errors)) {
        try {
            $ok = Product::update(
                $id,
                $form['sku'],
                $form['name'],
                (int)$form['stock'],
                (int)$form['threshold']
            );
            if ($ok) {
                audit_log('product_update', 'product', $id, [
                    'sku'       => $form['sku'],
                    'name'      => $form['name'],
                    'stock'     => (int)$form['stock'],
                    'threshold' => (int)$form['threshold'],
                ]);
                redirect('products.php', 'success', "Product \"{$form['name']}\" updated.");
            }
            $errors[] = 'Failed to update product.';
        } catch (\Throwable $e) {
            error_log('[product_edit] ' . $e->getMessage(), 3, LOG_PATH);
            $errors[] = 'Internal error.';
        }
    }
}

$pageTitle = 'Edit: ' . $product['name'];
ob_start();
?>
<div class="page-header">
    <div>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="products.php">Products</a>
            <span aria-hidden="true">/</span>
            <span>Edit</span>
        </nav>
        <h1 class="page-title"><?= e($product['name']) ?></h1>
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
    <form method="post" action="product_edit.php?id=<?= $id ?>" data-spinner>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="sku" class="form-label">SKU <span class="required">*</span></label>
            <input type="text" id="sku" name="sku"
                   value="<?= e($form['sku']) ?>"
                   required maxlength="64" class="form-control"
                   pattern="[A-Za-z0-9\-]{2,64}">
        </div>

        <div class="form-group">
            <label for="name" class="form-label">Name <span class="required">*</span></label>
            <input type="text" id="name" name="name"
                   value="<?= e($form['name']) ?>"
                   required maxlength="255" class="form-control">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="stock" class="form-label">Stock</label>
                <input type="number" id="stock" name="stock"
                       value="<?= e($form['stock']) ?>"
                       min="0" required class="form-control">
            </div>
            <div class="form-group">
                <label for="threshold" class="form-label">Alert threshold</label>
                <input type="number" id="threshold" name="threshold"
                       value="<?= e($form['threshold']) ?>"
                       min="0" required class="form-control">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary" data-saving-text="Saving…">
                <span class="btn-label">Save</span>
                <span class="spinner" style="display:none" aria-hidden="true"></span>
            </button>
            <a href="products.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/app/views/layout.php';
