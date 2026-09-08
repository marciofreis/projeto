<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
requireStaff();

$clinic = clinica();
$clinicName = $clinic['nome'] ?: 'Podocare';
$clinicLogo = (string) ($clinic['logo_path'] ?? '');
$staffUser = staff();

$page = $_GET['page'] ?? 'dashboard';
$routes = [
    'dashboard' => ['title' => 'Visão geral', 'active' => 'dashboard', 'file' => 'dashboard.php'],
    'agenda' => ['title' => 'Agenda', 'active' => 'agenda', 'file' => 'agenda.php'],
    'clientes' => ['title' => 'Clientes', 'active' => 'clients', 'file' => 'clientes.php'],
    'servicos' => ['title' => 'Serviços', 'active' => 'services', 'file' => 'servicos.php'],
    'financeiro' => ['title' => 'Financeiro', 'active' => 'finance', 'file' => 'financeiro.php'],
    'prontuario' => ['title' => 'Prontuário', 'active' => 'clients', 'file' => 'prontuario.php'],
    'configuracoes' => ['title' => 'Configurações', 'active' => 'settings', 'file' => 'configuracoes.php'],
    'usuarios' => ['title' => 'Usuários', 'active' => 'users', 'file' => 'usuarios.php'],
];
$current = $routes[$page] ?? $routes['dashboard'];
if (!can(pagePermission($page))) {
    flash('danger', 'Você não tem permissão para esta área.');
    header('Location: ' . firstAllowedUrl());
    exit;
}
$pageTitle = $current['title'];
$activePage = $current['active'];

ob_start();
require __DIR__ . '/pages/' . $current['file'];
$pageHtml = ob_get_clean();

require __DIR__ . '/includes/header.php';
echo $pageHtml;
require __DIR__ . '/includes/footer.php';
