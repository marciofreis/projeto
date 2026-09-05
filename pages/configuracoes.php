<?php

require_once __DIR__ . '/../config/database.php';

$settingsError = null;
$settingsSuccess = null;
$settings = ['nome' => '', 'tipo' => 'podologia', 'telefone' => '', 'email' => '', 'endereco' => '', 'logo_path' => ''];

try {
    $savedSettings = db()->query('SELECT nome, tipo, telefone, email, endereco, logo_path FROM clinicas WHERE id = 1')->fetch();
    if ($savedSettings) {
        $settings = array_merge($settings, $savedSettings);
    }
} catch (Throwable $exception) {
    $settingsError = 'Banco ainda não configurado. Importe database/schema.sql no phpMyAdmin.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'clinica') {
    $settings = array_merge($settings, [
        'nome' => trim($_POST['nome'] ?? ''),
        'tipo' => $_POST['tipo'] ?? 'podologia',
        'telefone' => trim($_POST['telefone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'endereco' => trim($_POST['endereco'] ?? ''),
    ]);
    if ($settings['nome'] === '') {
        $settingsError = 'Informe o nome da clínica ou salão.';
    } else {
        try {
            $logoPath = uploadImage($_FILES['logo'] ?? [], 'clinica', 'logo');
            if ($logoPath === '') {
                $logoPath = $settings['logo_path'];
            }
            $statement = db()->prepare('INSERT INTO clinicas (id, nome, tipo, telefone, email, endereco, logo_path) VALUES (1, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE nome = VALUES(nome), tipo = VALUES(tipo), telefone = VALUES(telefone), email = VALUES(email), endereco = VALUES(endereco), logo_path = VALUES(logo_path)');
            $statement->execute([$settings['nome'], $settings['tipo'], $settings['telefone'] ?: null, $settings['email'] ?: null, $settings['endereco'] ?: null, $logoPath ?: null]);
            $settings['logo_path'] = $logoPath;
            $settingsSuccess = 'Dados do espaço salvos com sucesso.';
        } catch (Throwable $exception) {
            $settingsError = $exception->getMessage();
        }
    }
}
?>
<section class="welcome-row"><div><p class="eyebrow">IDENTIDADE DO ESPAÇO</p><h1>Clínica ou salão</h1><p class="muted">Esses dados podem aparecer nos documentos e na comunicação com clientes.</p></div></section>
<?php if ($settingsError): ?><div class="alert alert-danger"><?= e($settingsError) ?></div><?php endif; ?>
<?php if ($settingsSuccess): ?><div class="alert alert-success"><?= e($settingsSuccess) ?></div><?php endif; ?>
<section class="panel settings-panel"><div class="panel-heading"><div><h2>Dados do estabelecimento</h2><p>Configure a identidade básica do seu espaço.</p></div></div>
    <form method="post" enctype="multipart/form-data"><input type="hidden" name="formulario" value="clinica"><div class="brand-upload"><div class="logo-preview"><?php if ($settings['logo_path']): ?><img src="<?= e($settings['logo_path']) ?>" alt="Logo da clínica"><?php else: ?><i class="bi bi-building"></i><?php endif; ?></div><div><label class="form-label">Logo da clínica ou salão</label><input class="form-control form-control-sm" type="file" name="logo" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG ou WEBP. Máximo 3 MB.</small></div></div><div class="row g-3"><div class="col-md-8"><label class="form-label">Nome do espaço *</label><input class="form-control" name="nome" value="<?= e($settings['nome']) ?>" placeholder="Ex.: Espaço Podocare" required></div><div class="col-md-4"><label class="form-label">Tipo de negócio</label><select class="form-select" name="tipo"><option value="podologia" <?= $settings['tipo'] === 'podologia' ? 'selected' : '' ?>>Clínica de podologia</option><option value="salao" <?= $settings['tipo'] === 'salao' ? 'selected' : '' ?>>Salão de beleza</option><option value="misto" <?= $settings['tipo'] === 'misto' ? 'selected' : '' ?>>Podologia e salão</option></select></div><div class="col-md-6"><label class="form-label">Telefone</label><input class="form-control" name="telefone" value="<?= e($settings['telefone']) ?>"></div><div class="col-md-6"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" value="<?= e($settings['email']) ?>"></div><div class="col-12"><label class="form-label">Endereço</label><input class="form-control" name="endereco" value="<?= e($settings['endereco']) ?>"></div></div><div class="form-actions"><button class="btn btn-primary" type="submit">Salvar configurações</button></div></form>
</section>