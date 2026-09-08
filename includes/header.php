<?php
$pageTitle = $pageTitle ?? 'Agenda';
$activePage = $activePage ?? 'dashboard';
$clinicName = $clinicName ?? 'Podocare';
$clinicLogo = $clinicLogo ?? '';
$staffUser = $staffUser ?? staff();
$staffName = $staffUser['nome'] ?? 'Equipe';
$staffRole = trim((string) ($staffUser['cargo'] ?? '')) ?: papelLabel((string) ($staffUser['papel'] ?? 'profissional'));
$staffInitials = strtoupper(substr(preg_replace('/\s+/', '', $staffName) ?: 'EQ', 0, 2));
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/pwa-head.php'; ?>
    <title><?= e($pageTitle) ?> | <?= e($clinicName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(assetUrl('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="mainSidebar">
        <div class="brand">
            <?php if ($clinicLogo): ?><span class="brand-logo"><img src="<?= e($clinicLogo) ?>" alt="Logo de <?= e($clinicName) ?>"></span><?php else: ?><span class="brand-mark">P</span><?php endif; ?>
            <span class="brand-name"><?= e($clinicName) ?></span>
        </div>
        <p class="sidebar-label">Menu principal</p>
        <nav class="nav flex-column gap-1">
            <?php if (can('dashboard')): ?><a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php"><i class="bi bi-grid-1x2-fill"></i> Visão geral</a><?php endif; ?>
            <?php if (can('agenda')): ?><a class="nav-link <?= $activePage === 'agenda' ? 'active' : '' ?>" href="?page=agenda"><i class="bi bi-calendar3"></i> Agenda</a><?php endif; ?>
            <?php if (can('clientes')): ?><a class="nav-link <?= $activePage === 'clients' ? 'active' : '' ?>" href="?page=clientes"><i class="bi bi-people"></i> Clientes</a><?php endif; ?>
            <?php if (can('servicos')): ?><a class="nav-link <?= $activePage === 'services' ? 'active' : '' ?>" href="?page=servicos"><i class="bi bi-stars"></i> Serviços</a><?php endif; ?>
        </nav>
        <p class="sidebar-label mt-4">Gestão</p>
        <nav class="nav flex-column gap-1">
            <?php if (can('financeiro')): ?><a class="nav-link <?= $activePage === 'finance' ? 'active' : '' ?>" href="?page=financeiro"><i class="bi bi-bar-chart"></i> Financeiro</a><?php endif; ?>
            <?php if (can('usuarios')): ?><a class="nav-link <?= $activePage === 'users' ? 'active' : '' ?>" href="?page=usuarios"><i class="bi bi-person-badge"></i> Usuários</a><?php endif; ?>
            <?php if (can('configuracoes')): ?><a class="nav-link <?= $activePage === 'settings' ? 'active' : '' ?>" href="?page=configuracoes"><i class="bi bi-gear"></i> Configurações</a><?php endif; ?>
            <a class="nav-link" href="agendar.php"><i class="bi bi-link-45deg"></i> Link de agendamento</a>
            <a class="nav-link" href="portal.php"><i class="bi bi-phone"></i> Portal do cliente</a>
            <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-left"></i> Sair</a>
        </nav>
        <div class="sidebar-user mt-auto">
            <div class="avatar avatar-small"><?php if ($clinicLogo): ?><img src="<?= e($clinicLogo) ?>" alt="Logo da clínica"><?php else: ?><?= e($staffInitials) ?><?php endif; ?></div>
            <div><strong><?= e($staffName) ?></strong><small><?= e($staffRole) ?></small></div>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <button class="btn icon-button d-lg-none" id="sidebarToggle" aria-label="Abrir menu"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-text"><span><?= e($clinicName) ?></span><i class="bi bi-chevron-right"></i><strong><?= e($pageTitle) ?></strong></div>
            <div class="topbar-actions ms-auto">
                <button class="btn icon-button" aria-label="Notificações"><i class="bi bi-bell"></i><span class="notification-dot"></span></button>
                <div class="avatar"><?php if ($clinicLogo): ?><img src="<?= e($clinicLogo) ?>" alt="Logo da clínica"><?php else: ?><?= e($staffInitials) ?><?php endif; ?></div>
            </div>
        </header>
        <nav class="app-tabbar clinic-tabbar">
            <?php if (can('dashboard')): ?><a class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php"><i class="bi bi-grid-1x2-fill"></i><span>Início</span></a><?php endif; ?>
            <?php if (can('agenda')): ?><a class="<?= $activePage === 'agenda' ? 'active' : '' ?>" href="?page=agenda"><i class="bi bi-calendar3"></i><span>Agenda</span></a><?php endif; ?>
            <?php if (can('clientes')): ?><a class="<?= $activePage === 'clients' ? 'active' : '' ?>" href="?page=clientes"><i class="bi bi-people"></i><span>Clientes</span></a><?php endif; ?>
            <?php if (can('financeiro')): ?><a class="<?= $activePage === 'finance' ? 'active' : '' ?>" href="?page=financeiro"><i class="bi bi-qr-code"></i><span>PIX</span></a><?php endif; ?>
            <button type="button" id="sidebarToggleTab" aria-label="Mais"><i class="bi bi-list"></i><span>Mais</span></button>
        </nav>
        <div class="page-container">