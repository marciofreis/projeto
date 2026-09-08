<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

if (!empty($_SESSION['staff_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['senha'] ?? '');
    if ($email === '' || $password === '') {
        $error = 'Informe e-mail e senha.';
    } elseif (!empty($GLOBALS['schema_error'])) {
        $error = 'Banco ainda não configurado. Importe database/schema.sql no phpMyAdmin.';
    } elseif (!loginStaff($email, $password)) {
        $error = 'E-mail ou senha inválidos.';
    } else {
        header('Location: index.php');
        exit;
    }
}

$clinic = clinica();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php $clinicName = $clinic['nome']; require __DIR__ . '/includes/pwa-head.php'; ?>
    <title>Entrar | <?= e($clinic['nome']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(assetUrl('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <main class="auth-card">
        <div class="auth-brand">
            <?php if (!empty($clinic['logo_path'])): ?>
                <img src="<?= e($clinic['logo_path']) ?>" alt="Logo">
            <?php else: ?>
                <span class="brand-mark">P</span>
            <?php endif; ?>
            <h1><?= e($clinic['nome']) ?></h1>
            <p>Acesso da clínica ou salão</p>
        </div>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <label class="form-label">E-mail</label>
            <input class="form-control" type="email" name="email" value="<?= e($email) ?>" required>
            <label class="form-label mt-3">Senha</label>
            <input class="form-control" type="password" name="senha" required>
            <button class="btn btn-primary w-100 mt-4" type="submit">Entrar</button>
        </form>
        <p class="auth-help">Primeiro acesso: <strong>admin@podocare.local</strong> / <strong>admin123</strong></p>
        <a class="auth-switch" href="agendar.php">Quero agendar um horário</a>
        <a class="auth-switch" href="portal.php">Sou cliente e quero acompanhar meus exames</a>
        <?php require __DIR__ . '/includes/phone-access.php'; ?>
    </main>
    <div class="install-banner" id="installBanner" hidden>
        <span>Instale o app na tela inicial</span>
        <div class="welcome-actions">
            <button type="button" class="btn btn-light btn-sm" id="dismissInstall">Agora não</button>
            <button type="button" class="btn btn-primary btn-sm" id="installApp">Instalar</button>
        </div>
    </div>
    <script src="<?= e(assetUrl('assets/js/pwa.js')) ?>"></script>
</body>
</html>
