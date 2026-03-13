<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_login();

$errors    = [];
$formRef   = '';
$formItems = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $formRef  = sanitize_string($_POST['reference'] ?? '', 64);
    $rawItems = $_POST['items'] ?? [];

    if (strlen($formRef) < 3) {
        $errors[] = 'Reference must be at least 3 characters.';
    }

    $validItems = [];
    if (!is_array($rawItems) || empty($rawItems)) {
        $errors[] = 'The order must contain at least one item.';
    } else {
        $seenProducts = [];
        foreach ($rawItems as $idx => $item) {
            $pid = (int)($item['product_id'] ?? 0);
            $qty = trim($item['qty'] ?? '');
            if ($pid <= 0) continue;
            if (in_array($pid, $seenProducts, true)) {
                $errors[] = "Product #$pid is duplicated in the order.";
                break;
            }
            if (!validate_positive_int($qty)) {
                $errors[] = "Invalid quantity for item #" . ($idx + 1) . '.';
                break;
            }
            $product = Product::findById($pid);
            if (!$product) {
                $errors[] = "Product #$pid not found.";
                break;
            }
            $seenProducts[] = $pid;
            $validItems[] = ['product_id' => $pid, 'qty' => (int)$qty];
        }
        if (empty($validItems) && empty($errors)) {
            $errors[] = 'No valid items in the order.';
        }
        $formItems = $rawItems;
    }

    if (empty($errors)) {
        try {
            $orderId = Order::create($formRef, $currentUser['id'], $validItems);
            if ($orderId) {
                audit_log('order_create', 'order', $orderId, [
                    'reference' => $formRef,
                    'items'     => count($validItems),
                ]);
                redirect('order_view.php?id=' . $orderId, 'success', "Order \"{$formRef}\" created.");
            }
            $errors[] = 'Failed to create order.';
        } catch (\Throwable $e) {
            error_log('[order_new] ' . $e->getMessage(), 3, LOG_PATH);
            $errors[] = 'Internal error.';
        }
    }
}

try {
    $allProducts = Product::all();
} catch (\Throwable $e) {
    $allProducts = [];
}

$pageTitle = 'New order';
ob_start();
?>
<div class="page-header">
    <div>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="orders.php">Orders</a>
            <span aria-hidden="true">/</span>
            <span>New</span>
        </nav>
        <h1 class="page-title">New order</h1>
    </div>
    <a href="orders.php" class="btn btn-outline">← Back</a>
</div>

<?php foreach ($errors as $err): ?>
    <div class="alert alert-error" role="alert">
        <svg class="alert-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span class="alert-text"><?= e($err) ?></span>
    </div>
<?php endforeach; ?>

<div class="card form-card">
    <form method="post" action="order_new.php" id="orderForm">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="reference" class="form-label">Order reference <span class="required">*</span></label>
            <input type="text" id="reference" name="reference"
                   value="<?= e($formRef) ?>"
                   required maxlength="64" class="form-control"
                   placeholder="e.g. ORD-2024-042">
        </div>

        <div class="form-section-title">Items</div>
        <div id="items-container"></div>

        <button type="button" id="addItem" class="btn btn-ghost btn-sm">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add item
        </button>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create order</button>
            <a href="orders.php" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>

<script>
(function() {
    let idx = 0;
    const container   = document.getElementById('items-container');
    const addBtn      = document.getElementById('addItem');
    const allProducts = <?= json_encode(array_map(fn($p) => [
        'id'    => (int)$p['id'],
        'sku'   => $p['sku'],
        'name'  => $p['name'],
        'stock' => (int)$p['stock'],
    ], $allProducts), JSON_HEX_APOS | JSON_HEX_TAG) ?>;

    function buildRow(i) {
        const row = document.createElement('div');
        row.className = 'order-item-row';
        let options = '<option value="">— Select a product —</option>';
        allProducts.forEach(function(p) {
            options += '<option value="' + p.id + '" data-stock="' + p.stock + '">'
                     + p.sku + ' – ' + p.name + ' (stock\u00a0: ' + p.stock + ')</option>';
        });
        row.innerHTML =
            '<div class="form-row">'
          +   '<div class="form-group flex-grow">'
          +     '<label class="form-label">Product</label>'
          +     '<select name="items[' + i + '][product_id]" class="form-control" required>' + options + '</select>'
          +   '</div>'
          +   '<div class="form-group qty-group">'
          +     '<label class="form-label">Qty</label>'
          +     '<input type="number" name="items[' + i + '][qty]" min="1" value="1" required class="form-control">'
          +   '</div>'
          +   '<div class="form-group align-end">'
          +     '<button type="button" class="btn btn-sm btn-danger remove-item" aria-label="Remove item">'
          +       '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
          +     '</button>'
          +   '</div>'
          + '</div>';
        return row;
    }

    function addRow() {
        container.appendChild(buildRow(idx++));
    }

    addBtn.addEventListener('click', addRow);

    container.addEventListener('click', function(e) {
        var btn = e.target.closest('.remove-item');
        if (!btn) return;
        var rows = container.querySelectorAll('.order-item-row');
        if (rows.length > 1) {
            btn.closest('.order-item-row').remove();
        }
    });

    // Start with one row
    addRow();
})();
</script>
<?php
$content = ob_get_clean();
include dirname(__DIR__) . '/app/views/layout.php';
