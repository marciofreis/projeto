<?php

require_once __DIR__ . '/../config/database.php';

$formError = null;
$formSuccess = null;
$formData = ['nome' => '', 'telefone' => '', 'email' => '', 'data_nascimento' => '', 'observacoes' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'cliente') {
    $formData = [
        'nome' => trim($_POST['nome'] ?? ''),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'data_nascimento' => trim($_POST['data_nascimento'] ?? ''),
        'observacoes' => trim($_POST['observacoes'] ?? ''),
    ];

    if ($formData['nome'] === '' || $formData['telefone'] === '') {
        $formError = 'Nome e telefone são obrigatórios.';
    } else {
        try {
            $fotoPath = uploadImage($_FILES['foto'] ?? [], 'clientes', 'cliente');
            $statement = db()->prepare('INSERT INTO clientes (nome, telefone, email, data_nascimento, observacoes, foto_path) VALUES (?, ?, ?, ?, ?, ?)');
            $statement->execute([
                $formData['nome'],
                $formData['telefone'],
                $formData['email'] ?: null,
                $formData['data_nascimento'] ?: null,
                $formData['observacoes'] ?: null,
                $fotoPath ?: null,
            ]);
            $formSuccess = 'Cliente cadastrado com sucesso.';
            $formData = ['nome' => '', 'telefone' => '', 'email' => '', 'data_nascimento' => '', 'observacoes' => ''];
        } catch (Throwable $exception) {
            $formError = $exception->getMessage();
        }
    }
}

$clients = [];
try {
    $clients = db()->query('SELECT id, nome, telefone, email, foto_path, criado_em FROM clientes WHERE ativo = 1 ORDER BY nome')->fetchAll();
} catch (Throwable $exception) {
    $formError ??= 'Banco ainda não configurado. Importe database/schema.sql no phpMyAdmin.';
}
?>
<section class="welcome-row"><div><p class="eyebrow">RELACIONAMENTO</p><h1>Clientes</h1><p class="muted">Cadastre clientes e mantenha o histórico organizado.</p></div><a class="btn btn-primary" href="#cliente-form"><i class="bi bi-plus-lg"></i> Novo cliente</a></section>
<?php if ($formError): ?><div class="alert alert-danger"><?= e($formError) ?></div><?php endif; ?>
<?php if ($formSuccess): ?><div class="alert alert-success"><?= e($formSuccess) ?></div><?php endif; ?>
<div class="content-grid form-grid">
    <section class="panel" id="cliente-form"><div class="panel-heading"><div><h2>Novo cliente</h2><p>Os campos com * são obrigatórios.</p></div></div>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="formulario" value="cliente">
            <div class="photo-upload"><div class="photo-preview"><i class="bi bi-person"></i></div><div><label class="form-label">Foto do cliente</label><input class="form-control form-control-sm" type="file" name="foto" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG ou WEBP. Máximo 3 MB.</small></div></div>
            <div class="row g-3"><div class="col-12"><label class="form-label">Nome completo *</label><input class="form-control" name="nome" value="<?= e($formData['nome']) ?>" required></div><div class="col-md-6"><label class="form-label">Telefone *</label><input class="form-control" name="telefone" value="<?= e($formData['telefone']) ?>" required></div><div class="col-md-6"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" value="<?= e($formData['email']) ?>"></div><div class="col-md-6"><label class="form-label">Data de nascimento</label><input class="form-control" type="date" name="data_nascimento" value="<?= e($formData['data_nascimento']) ?>"></div><div class="col-12"><label class="form-label">Observações</label><textarea class="form-control" name="observacoes" rows="3"><?= e($formData['observacoes']) ?></textarea></div></div>
            <div class="form-actions"><button class="btn btn-light" type="reset">Limpar</button><button class="btn btn-primary" type="submit">Salvar cliente</button></div>
        </form>
    </section>
    <section class="panel"><div class="panel-heading"><div><h2>Clientes cadastrados</h2><p><?= count($clients) ?> registro(s) ativo(s)</p></div><div class="search-box"><i class="bi bi-search"></i><input type="search" placeholder="Buscar cliente..."></div></div>
        <div class="table-responsive"><table class="table clients-table"><thead><tr><th>Cliente</th><th>Contato</th><th>Cadastro</th><th></th></tr></thead><tbody><?php if (!$clients): ?><tr><td colspan="4" class="empty-table">Nenhum cliente cadastrado.</td></tr><?php endif; ?><?php foreach ($clients as $client): ?><tr><td><div class="client-cell"><?php if ($client['foto_path']): ?><img src="<?= e($client['foto_path']) ?>" alt=""><?php else: ?><span class="client-initial"><?= e(strtoupper(substr($client['nome'], 0, 1))) ?></span><?php endif; ?><strong><?= e($client['nome']) ?></strong></div></td><td><?= e($client['telefone']) ?><small><?= e($client['email']) ?></small></td><td><?= e(date('d/m/Y', strtotime($client['criado_em']))) ?></td><td><button class="btn icon-button"><i class="bi bi-three-dots"></i></button></td></tr><?php endforeach; ?></tbody></table></div>
    </section>
</div>