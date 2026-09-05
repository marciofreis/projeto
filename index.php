<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

$clinicLogo = '';
try {
    $clinicLogo = (string) (db()->query('SELECT logo_path FROM clinicas WHERE id = 1')->fetchColumn() ?: '');
} catch (Throwable $exception) {
}

$page = $_GET['page'] ?? 'dashboard';
$routes = [
    'dashboard' => ['title' => 'Visão geral', 'active' => 'dashboard', 'file' => 'dashboard.php'],
    'agenda' => ['title' => 'Agenda', 'active' => 'agenda', 'file' => 'agenda.php'],
    'clientes' => ['title' => 'Clientes', 'active' => 'clients', 'file' => 'clientes.php'],
    'servicos' => ['title' => 'Serviços', 'active' => 'services', 'file' => 'servicos.php'],
    'financeiro' => ['title' => 'Financeiro', 'active' => 'dashboard', 'file' => 'placeholder.php'],
    'configuracoes' => ['title' => 'Configurações', 'active' => 'settings', 'file' => 'configuracoes.php'],
];
$current = $routes[$page] ?? $routes['dashboard'];
$pageTitle = $current['title'];
$activePage = $current['active'];

require __DIR__ . '/includes/header.php';
require __DIR__ . '/pages/' . $current['file'];
require __DIR__ . '/includes/footer.php';
