<?php
$pageTitle = $pageTitle ?? 'Agenda';
$activePage = $activePage ?? 'dashboard';
$clinicLogo = $clinicLogo ?? '';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | Clinica xxx</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="mainSidebar">
        <div class="brand"><span class="brand-mark">P</span><span>podocare</span></div>
        <p class="sidebar-label">Menu principal</p>
        <nav class="nav flex-column gap-1">
            <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php"><i class="bi bi-grid-1x2-fill"></i> Visão geral</a>
            <a class="nav-link <?= $activePage === 'agenda' ? 'active' : '' ?>" href="?page=agenda"><i class="bi bi-calendar3"></i> Agenda</a>
            <a class="nav-link <?= $activePage === 'clients' ? 'active' : '' ?>" href="?page=clientes"><i class="bi bi-people"></i> Clientes</a>
            <a class="nav-link <?= $activePage === 'services' ? 'active' : '' ?>" href="?page=servicos"><i class="bi bi-stars"></i> Serviços</a>
        </nav>
        <p class="sidebar-label mt-4">Gestão</p>
        <nav class="nav flex-column gap-1">
            <a class="nav-link" href="?page=financeiro"><i class="bi bi-bar-chart"></i> Financeiro</a>
            <a class="nav-link <?= $activePage === 'settings' ? 'active' : '' ?>" href="?page=configuracoes"><i class="bi bi-gear"></i> Configurações</a>
        </nav>
        <div class="sidebar-user mt-auto">
            <div class="avatar avatar-small"><?php if ($clinicLogo): ?><img src="<?= e($clinicLogo) ?>" alt="Logo da clínica"><?php else: ?>MF<?php endif; ?></div>
            <div><strong>Marcio Freis</strong><small>Administrador</small></div>
            <i class="bi bi-three-dots ms-auto"></i>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <button class="btn icon-button d-lg-none" id="sidebarToggle" aria-label="Abrir menu"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-text"><span>Espaço podocare</span><i class="bi bi-chevron-right"></i><strong><?= htmlspecialchars($pageTitle) ?></strong></div>
            <div class="topbar-actions ms-auto">
                <button class="btn icon-button" aria-label="Notificações"><i class="bi bi-bell"></i><span class="notification-dot"></span></button>
                <div class="avatar"><?php if ($clinicLogo): ?><img src="<?= e($clinicLogo) ?>" alt="Logo da clínica"><?php else: ?>MF<?php endif; ?></div>
            </div>
        </header>
        <div class="page-container">