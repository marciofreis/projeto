<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
require_once __DIR__ . '/includes/foot-map.php';

$clinic = clinica();
$clinicName = $clinic['nome'] ?: 'Podocare';
$clinicLogo = (string) ($clinic['logo_path'] ?? '');
$error = null;
$loginPhone = '';

if (isset($_GET['sair'])) {
    unset($_SESSION['cliente_id']);
    header('Location: portal.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'login_cliente') {
    $loginPhone = trim((string) ($_POST['telefone'] ?? ''));
    $password = (string) ($_POST['senha'] ?? '');
    if ($loginPhone === '' || $password === '') {
        $error = 'Informe telefone e senha.';
    } elseif (!loginCliente($loginPhone, $password)) {
        $error = 'Telefone ou senha inválidos, ou o portal ainda não foi liberado.';
    } else {
        header('Location: portal.php');
        exit;
    }
}

$cliente = clienteLogado();
$page = $_GET['view'] ?? 'home';
$allowed = ['home', 'exames', 'exame', 'precos', 'pagamentos', 'agenda'];
if (!in_array($page, $allowed, true)) {
    $page = 'home';
}

if (!$cliente) {
    require __DIR__ . '/pages/portal-login.php';
    exit;
}

require __DIR__ . '/includes/portal-header.php';
require __DIR__ . '/pages/portal-' . $page . '.php';
require __DIR__ . '/includes/portal-footer.php';
