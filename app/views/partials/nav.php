<?php
/** @var array $currentUser – provided by layout.php */
$page    = basename($_SERVER['PHP_SELF']);
$isAdmin = ($currentUser['role'] ?? '') === 'admin';

function nav_active(string $file, string $current, array $also = []): string {
    $files = array_merge([$file], $also);
    return in_array($current, $files, true) ? ' active' : '';
}
?>
<aside id="sidebar" class="sidebar" role="navigation" aria-label="Main navigation">

    <!-- Brand -->
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <img src="assets/img/logo.png" width="30" height="30" alt="Mini WMS logo" style="border-radius:6px;flex-shrink:0;">
            <span>Mini WMS</span>
        </a>
    </div>

    <!-- Nav links -->
    <nav class="sidebar-nav">
        <ul role="list">
            <li>
                <a href="dashboard.php" class="nav-link<?= nav_active('dashboard.php', $page) ?>">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                        <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
                    </svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="products.php" class="nav-link<?= nav_active('products.php', $page, ['product_new.php','product_edit.php']) ?>">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73L13 2.27a2 2 0 0 0-2 0L4 6.27A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73L11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                    Products
                </a>
            </li>
            <li>
                <a href="orders.php" class="nav-link<?= nav_active('orders.php', $page, ['order_new.php','order_view.php']) ?>">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 0 1-8 0"/>
                    </svg>
                    Orders
                </a>
            </li>
            <li>
                <a href="stock_moves.php" class="nav-link<?= nav_active('stock_moves.php', $page, ['stock_move_new.php']) ?>">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                        <polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                    </svg>
                    Stock Moves
                </a>
            </li>
            <?php if ($isAdmin): ?>
            <li>
                <a href="audit.php" class="nav-link<?= nav_active('audit.php', $page) ?>">
                    <svg class="nav-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    Audit Log
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Footer: user info + logout -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar" aria-hidden="true">
                <?= strtoupper(substr($currentUser['email'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-email"><?= e($currentUser['email'] ?? '') ?></span>
                <span class="sidebar-user-role badge badge-<?= $isAdmin ? 'primary' : 'secondary' ?>"><?= e($currentUser['role'] ?? '') ?></span>
            </div>
        </div>
        <form method="post" action="logout.php">
            <?= csrf_field() ?>
            <button type="submit" class="sidebar-logout-btn" aria-label="Sign out">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Sign out
            </button>
        </form>
    </div>

</aside>
