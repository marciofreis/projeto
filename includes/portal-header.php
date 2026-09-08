<?php
$pageTitles = [
    'home' => 'Início',
    'exames' => 'Meus exames',
    'exame' => 'Exame',
    'precos' => 'Preços',
    'pagamentos' => 'Pagamentos',
    'agenda' => 'Agenda',
];
$pageTitle = $pageTitles[$page] ?? 'Portal';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/pwa-head.php'; ?>
    <title><?= e($pageTitle) ?> | <?= e($clinicName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(assetUrl('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="portal-body">
<header class="portal-top">
    <div class="portal-brand">
        <?php if ($clinicLogo): ?><img src="<?= e($clinicLogo) ?>" alt=""><?php else: ?><span class="brand-mark">P</span><?php endif; ?>
        <div>
            <strong><?= e($clinicName) ?></strong>
            <small>Olá, <?= e(explode(' ', $cliente['nome'])[0]) ?></small>
        </div>
    </div>
    <a class="text-link" href="portal.php?sair=1">Sair</a>
</header>
<nav class="portal-nav">
    <a class="<?= $page === 'home' ? 'active' : '' ?>" href="portal.php"><i class="bi bi-house"></i> Início</a>
    <a class="<?= in_array($page, ['exames', 'exame'], true) ? 'active' : '' ?>" href="portal.php?view=exames"><i class="bi bi-clipboard2-pulse"></i> Exames</a>
    <a class="<?= $page === 'precos' ? 'active' : '' ?>" href="portal.php?view=precos"><i class="bi bi-tag"></i> Preços</a>
    <a class="<?= $page === 'pagamentos' ? 'active' : '' ?>" href="portal.php?view=pagamentos"><i class="bi bi-qr-code"></i> PIX</a>
    <a class="<?= $page === 'agenda' ? 'active' : '' ?>" href="portal.php?view=agenda"><i class="bi bi-calendar3"></i> Agenda</a>
    <a href="agendar.php"><i class="bi bi-plus-lg"></i> Agendar</a>
</nav>
<nav class="app-tabbar portal-tabbar">
    <a class="<?= $page === 'home' ? 'active' : '' ?>" href="portal.php"><i class="bi bi-house"></i><span>Início</span></a>
    <a class="<?= in_array($page, ['exames', 'exame'], true) ? 'active' : '' ?>" href="portal.php?view=exames"><i class="bi bi-clipboard2-pulse"></i><span>Exames</span></a>
    <a class="<?= $page === 'pagamentos' ? 'active' : '' ?>" href="portal.php?view=pagamentos"><i class="bi bi-qr-code"></i><span>PIX</span></a>
    <a class="<?= $page === 'agenda' ? 'active' : '' ?>" href="portal.php?view=agenda"><i class="bi bi-calendar3"></i><span>Agenda</span></a>
    <a href="agendar.php"><i class="bi bi-plus-lg"></i><span>Agendar</span></a>
</nav>
<main class="portal-main">
