<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/../includes/pwa-head.php'; ?>
    <title>Portal do cliente | <?= e($clinicName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(assetUrl('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <main class="auth-card">
        <div class="auth-brand">
            <?php if ($clinicLogo): ?><img src="<?= e($clinicLogo) ?>" alt="Logo"><?php else: ?><span class="brand-mark">P</span><?php endif; ?>
            <h1><?= e($clinicName) ?></h1>
            <p>Acompanhe exames, preços e pagamentos</p>
        </div>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="formulario" value="login_cliente">
            <label class="form-label">Telefone</label>
            <input class="form-control" name="telefone" value="<?= e($loginPhone) ?>" placeholder="(11) 99999-9999" required>
            <label class="form-label mt-3">Senha do portal</label>
            <input class="form-control" type="password" name="senha" required>
            <button class="btn btn-primary w-100 mt-4" type="submit">Entrar</button>
        </form>
        <p class="auth-help">A clínica libera seu acesso na ficha de cliente e informa a senha.</p>
        <a class="auth-switch" href="agendar.php">Agendar um horário</a>
        <a class="auth-switch" href="login.php">Sou da equipe da clínica</a>
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
