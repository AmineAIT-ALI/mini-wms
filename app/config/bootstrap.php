<?php
/**
 * Bootstrap – included at the top of every public/*.php page
 */
declare(strict_types=1);

$appRoot = dirname(__DIR__, 2);

require_once $appRoot . '/app/config/env.php';
require_once $appRoot . '/app/config/db.php';
require_once $appRoot . '/app/lib/auth.php';
require_once $appRoot . '/app/lib/csrf.php';
require_once $appRoot . '/app/lib/flash.php';
require_once $appRoot . '/app/lib/validators.php';
require_once $appRoot . '/app/lib/audit.php';
require_once $appRoot . '/app/lib/logger.php';
require_once $appRoot . '/app/models/User.php';
require_once $appRoot . '/app/models/Product.php';
require_once $appRoot . '/app/models/Order.php';
require_once $appRoot . '/app/models/StockMove.php';
require_once $appRoot . '/app/models/AuditLog.php';

session_ensure();
Logger::startRequest();
register_shutdown_function(static function (): void {
    Logger::finish();
});
