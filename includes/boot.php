<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

ob_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/schema.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/permissions.php';
require_once __DIR__ . '/../config/pix.php';
require_once __DIR__ . '/../config/booking.php';

try {
    ensureSchema();
} catch (Throwable $exception) {
    $GLOBALS['schema_error'] = $exception->getMessage();
}
