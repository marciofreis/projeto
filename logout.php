<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$target = !empty($_GET['portal']) ? 'portal.php' : 'login.php';
if (!empty($_GET['portal'])) {
    unset($_SESSION['cliente_id']);
} else {
    unset($_SESSION['staff_id']);
}

header('Location: ' . $target);
exit;
