<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/bootstrap.php';

$currentUser = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('orders.php');
}

csrf_verify();

$id        = (int)($_POST['id']         ?? 0);
$newStatus = sanitize_string($_POST['new_status'] ?? '', 20);

// Validate status value
$validStatuses = ['pending', 'picked', 'shipped', 'cancelled'];
if (!in_array($newStatus, $validStatuses, true)) {
    redirect('orders.php', 'error', 'Invalid status.');
}

$order = $id > 0 ? Order::findById($id) : null;
if (!$order) {
    redirect('orders.php', 'error', 'Order not found.');
}

try {
    $result = Order::changeStatus($id, $newStatus, $currentUser['id']);

    if ($result === true) {
        audit_log('order_status_change', 'order', $id, [
            'from'      => $order['status'],
            'to'        => $newStatus,
            'reference' => $order['reference'],
        ]);
        redirect(
            'order_view.php?id=' . $id,
            'success',
            "Order \"{$order['reference']}\" → {$newStatus}."
        );
    }
    // $result is an error string
    redirect('order_view.php?id=' . $id, 'error', $result);
} catch (\Throwable $e) {
    error_log('[order_status] ' . $e->getMessage(), 3, LOG_PATH);
    redirect('order_view.php?id=' . $id, 'error', 'Internal error.');
}
