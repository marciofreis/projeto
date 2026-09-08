<?php

require_once __DIR__ . '/../config/database.php';

$viewId = (int) ($_GET['id'] ?? $_POST['cliente_id'] ?? 0);
$editing = isset($_GET['editar']);
$formError = null;
$formSuccess = pullFlash();
$formData = [
    'nome' => '', 'telefone' => '', 'email' => '', 'data_nascimento' => '', 'observacoes' => '',
    'portal_liberado' => '0', 'anamnese_diabetes' => '0', 'anamnese_gestante' => '0',
    'anamnese_alergia' => '', 'anamnese_observacoes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'cliente') {
    $editId = (int) ($_POST['cliente_id'] ?? 0);
    $formData = [
        'nome' => trim((string) ($_POST['nome'] ?? '')),
        'telefone' => trim((string) ($_POST['telefone'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'data_nascimento' => trim((string) ($_POST['data_nascimento'] ?? '')),
        'observacoes' => trim((string) ($_POST['observacoes'] ?? '')),
        'portal_liberado' => isset($_POST['portal_liberado']) ? '1' : '0',
        'anamnese_diabetes' => isset($_POST['anamnese_diabetes']) ? '1' : '0',
        'anamnese_gestante' => isset($_POST['anamnese_gestante']) ? '1' : '0',
        'anamnese_alergia' => trim((string) ($_POST['anamnese_alergia'] ?? '')),
        'anamnese_observacoes' => trim((string) ($_POST['anamnese_observacoes'] ?? '')),
    ];

    if ($formData['nome'] === '' || $formData['telefone'] === '') {
        $formError = 'Nome e telefone são obrigatórios.';
    } else {
        try {
            $generatedPassword = null;
            $passwordHash = null;
            if ($formData['portal_liberado'] === '1') {
                if (!empty($_POST['gerar_senha']) || trim((string) ($_POST['senha_portal'] ?? '')) !== '') {
                    $generatedPassword = !empty($_POST['gerar_senha']) ? senhaTemporaria() : trim((string) $_POST['senha_portal']);
                    $passwordHash = password_hash($generatedPassword, PASSWORD_DEFAULT);
                } elseif ($editId < 1) {
                    $formError = 'Defina uma senha ou gere automaticamente para liberar o portal.';
                }
            }

            if ($formError === null) {
                $fotoPath = uploadImage($_FILES['foto'] ?? [], 'clientes', 'cliente');
                if ($editId > 0) {
                    $current = db()->prepare('SELECT foto_path, senha_hash FROM clientes WHERE id = ?');
                    $current->execute([$editId]);
                    $row = $current->fetch();
                    if (!$row) {
                        throw new RuntimeException('Cliente não encontrado.');
                    }
                    $fotoPath = $fotoPath !== '' ? $fotoPath : (string) $row['foto_path'];
                    $passwordSql = $passwordHash ? ', senha_hash = ?' : '';
                    $sql = "UPDATE clientes SET nome=?, telefone=?, email=?, data_nascimento=?, observacoes=?, foto_path=?, portal_liberado=?, anamnese_diabetes=?, anamnese_gestante=?, anamnese_alergia=?, anamnese_observacoes=? $passwordSql WHERE id=?";
                    $params = [
                        $formData['nome'], $formData['telefone'], $formData['email'] ?: null, $formData['data_nascimento'] ?: null,
                        $formData['observacoes'] ?: null, $fotoPath ?: null, $formData['portal_liberado'], $formData['anamnese_diabetes'],
                        $formData['anamnese_gestante'], $formData['anamnese_alergia'] ?: null, $formData['anamnese_observacoes'] ?: null,
                    ];
                    if ($passwordHash) {
                        $params[] = $passwordHash;
                    }
                    $params[] = $editId;
                    db()->prepare($sql)->execute($params);
                    $message = 'Cliente atualizado.';
                    $viewId = $editId;
                } else {
                    db()->prepare('INSERT INTO clientes (nome, telefone, email, data_nascimento, observacoes, foto_path, senha_hash, portal_liberado, anamnese_diabetes, anamnese_gestante, anamnese_alergia, anamnese_observacoes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
                        $formData['nome'], $formData['telefone'], $formData['email'] ?: null, $formData['data_nascimento'] ?: null,
                        $formData['observacoes'] ?: null, $fotoPath ?: null, $passwordHash, $formData['portal_liberado'],
                        $formData['anamnese_diabetes'], $formData['anamnese_gestante'], $formData['anamnese_alergia'] ?: null,
                        $formData['anamnese_observacoes'] ?: null,
                    ]);
                    $viewId = (int) db()->lastInsertId();
                    $message = 'Cliente cadastrado.';
                }
                if ($generatedPassword) {
                    $message .= ' Senha do portal: ' . $generatedPassword;
                    $_SESSION['portal_convite'] = ['id' => $viewId, 'senha' => $generatedPassword];
                }
                flash('success', $message);
                header('Location: ?page=clientes&id=' . $viewId);
                exit;
            }
        } catch (Throwable $exception) {
            $formError = $exception->getMessage();
        }
    }
}

$client = null;
$exams = [];
$payments = [];
if ($viewId > 0) {
    $statement = db()->prepare('SELECT * FROM clientes WHERE id = ?');
    $statement->execute([$viewId]);
    $client = $statement->fetch() ?: null;
    if ($client && !$editing && $formError === null) {
        $exams = db()->prepare('SELECT id, data_exame, queixa FROM prontuarios WHERE cliente_id = ? ORDER BY data_exame DESC, id DESC');
        $exams->execute([$viewId]);
        $exams = $exams->fetchAll();
        $payments = db()->prepare('SELECT id, valor, status, descricao, criado_em FROM pagamentos WHERE cliente_id = ? ORDER BY id DESC LIMIT 8');
        $payments->execute([$viewId]);
        $payments = $payments->fetchAll();
    }
    if ($client && ($editing || $formError) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $formData = array_merge($formData, [
            'nome' => $client['nome'],
            'telefone' => $client['telefone'],
            'email' => (string) $client['email'],
            'data_nascimento' => (string) $client['data_nascimento'],
            'observacoes' => (string) $client['observacoes'],
            'portal_liberado' => (string) $client['portal_liberado'],
            'anamnese_diabetes' => (string) $client['anamnese_diabetes'],
            'anamnese_gestante' => (string) $client['anamnese_gestante'],
            'anamnese_alergia' => (string) $client['anamnese_alergia'],
            'anamnese_observacoes' => (string) $client['anamnese_observacoes'],
        ]);
    }
}

$clients = [];
try {
    $clients = db()->query('SELECT id, nome, telefone, email, foto_path, portal_liberado, criado_em FROM clientes WHERE ativo = 1 ORDER BY nome')->fetchAll();
} catch (Throwable $exception) {
    $formError ??= 'Banco ainda não configurado. Importe database/schema.sql no phpMyAdmin.';
}

$showForm = $client === null || $editing || $formError;
?>
<section class="welcome-row">
    <div>
        <p class="eyebrow">RELACIONAMENTO</p>
        <h1><?= $client && !$showForm ? e($client['nome']) : 'Clientes' ?></h1>
        <p class="muted"><?= $client && !$showForm ? 'Ficha, portal, exames e cobranças.' : 'Cadastre clientes e libere o acompanhamento no portal.' ?></p>
    </div>
    <?php if ($client && !$showForm): ?>
        <div class="welcome-actions">
            <a class="btn btn-light" href="?page=clientes&id=<?= (int) $client['id'] ?>&editar=1">Editar</a>
            <?php if (can('imprimir')): ?>
                <a class="btn btn-light" href="imprimir.php?tipo=ficha&id=<?= (int) $client['id'] ?>" target="_blank"><i class="bi bi-printer"></i> Imprimir ficha</a>
            <?php endif; ?>
            <?php if (can('prontuario')): ?>
                <a class="btn btn-primary" href="?page=prontuario&cliente_id=<?= (int) $client['id'] ?>"><i class="bi bi-clipboard2-pulse"></i> Novo exame</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <a class="btn btn-primary" href="?page=clientes#cliente-form"><i class="bi bi-plus-lg"></i> Novo cliente</a>
    <?php endif; ?>
</section>
<?php if ($formError): ?><div class="alert alert-danger"><?= e($formError) ?></div><?php endif; ?>
<?php if ($formSuccess): ?><div class="alert alert-<?= e($formSuccess['type'] === 'success' ? 'success' : 'danger') ?>"><?= e($formSuccess['message']) ?></div><?php endif; ?>

<?php if ($client && !$showForm): ?>
    <div class="content-grid">
        <section class="panel">
            <div class="profile-head">
                <div class="photo-preview">
                    <?php if ($client['foto_path']): ?><img src="<?= e($client['foto_path']) ?>" alt=""><?php else: ?><i class="bi bi-person"></i><?php endif; ?>
                </div>
                <div>
                    <h2><?= e($client['nome']) ?></h2>
                    <p class="muted"><?= e($client['telefone']) ?><?= $client['email'] ? ' · ' . e($client['email']) : '' ?></p>
                    <span class="status <?= $client['portal_liberado'] ? 'confirmed' : 'waiting' ?>"><?= $client['portal_liberado'] ? 'Portal liberado' : 'Sem acesso ao portal' ?></span>
                </div>
            </div>
            <div class="info-list">
                <p><strong>Nascimento</strong> <?= $client['data_nascimento'] ? e(date('d/m/Y', strtotime($client['data_nascimento']))) : '—' ?></p>
                <p><strong>Diabetes</strong> <?= $client['anamnese_diabetes'] ? 'Sim' : 'Não' ?> · <strong>Gestante</strong> <?= $client['anamnese_gestante'] ? 'Sim' : 'Não' ?></p>
                <p><strong>Alergia</strong> <?= e($client['anamnese_alergia'] ?: 'Não informada') ?></p>
                <p><strong>Observações internas</strong> <?= e($client['observacoes'] ?: '—') ?></p>
            </div>
            <div class="form-actions">
                <?php
                $convite = $_SESSION['portal_convite'] ?? null;
                $senhaConvite = is_array($convite) && (int) $convite['id'] === (int) $client['id'] ? (string) $convite['senha'] : null;
                echo whatsappButton($client['telefone'], mensagemPortal($client, $senhaConvite), $senhaConvite ? 'Enviar senha no WhatsApp' : 'Enviar portal no WhatsApp');
                ?>
                <?php if (can('financeiro')): ?><a class="btn btn-light" href="?page=financeiro&cliente_id=<?= (int) $client['id'] ?>">Gerar PIX</a><?php endif; ?>
                <a class="btn btn-light" href="?page=clientes">Voltar à lista</a>
            </div>
        </section>
        <div>
            <section class="panel">
                <div class="panel-heading"><div><h2>Exames</h2><p>Histórico que o cliente também vê</p></div></div>
                <?php if (!$exams): ?><p class="muted">Nenhum exame registrado.</p><?php endif; ?>
                <?php foreach ($exams as $exam): ?>
                    <div class="quick-action">
                        <span class="quick-icon green"><i class="bi bi-file-earmark-medical"></i></span>
                        <span>
                            <strong><?= e(date('d/m/Y', strtotime($exam['data_exame']))) ?></strong>
                            <small><?= e($exam['queixa'] ?: 'Exame clínico') ?></small>
                        </span>
                        <?php if (can('prontuario')): ?><a class="text-link" href="?page=prontuario&id=<?= (int) $exam['id'] ?>">Abrir</a><?php endif; ?>
                        <?php if (can('imprimir')): ?><a class="text-link" href="imprimir.php?tipo=exame&id=<?= (int) $exam['id'] ?>" target="_blank">Imprimir</a><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
            <section class="panel mt-3">
                <div class="panel-heading"><div><h2>Cobranças</h2></div></div>
                <?php if (!$payments): ?><p class="muted">Nenhuma cobrança ainda.</p><?php endif; ?>
                <?php foreach ($payments as $payment): ?>
                    <div class="pay-row">
                        <strong><?= e(brl($payment['valor'])) ?></strong>
                        <span><?= e($payment['descricao'] ?: 'Atendimento') ?></span>
                        <span class="status <?= e(statusClass($payment['status'])) ?>"><?= e(statusLabel($payment['status'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </section>
        </div>
    </div>
<?php endif; ?>

<?php if ($showForm): ?>
<div class="content-grid form-grid">
    <section class="panel" id="cliente-form">
        <div class="panel-heading"><div><h2><?= $client ? 'Editar cliente' : 'Novo cliente' ?></h2><p>Os campos com * são obrigatórios.</p></div></div>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="formulario" value="cliente">
            <input type="hidden" name="cliente_id" value="<?= $client ? (int) $client['id'] : 0 ?>">
            <div class="photo-upload">
                <div class="photo-preview"><?php if ($client && $client['foto_path']): ?><img src="<?= e($client['foto_path']) ?>" alt=""><?php else: ?><i class="bi bi-person"></i><?php endif; ?></div>
                <div><label class="form-label">Foto do cliente</label><input class="form-control form-control-sm" type="file" name="foto" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG ou WEBP. Máximo 3 MB.</small></div>
            </div>
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Nome completo *</label><input class="form-control" name="nome" value="<?= e($formData['nome']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">Telefone *</label><input class="form-control" name="telefone" value="<?= e($formData['telefone']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" value="<?= e($formData['email']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Data de nascimento</label><input class="form-control" type="date" name="data_nascimento" value="<?= e($formData['data_nascimento']) ?>"></div>
                <div class="col-12"><label class="form-label">Observações internas</label><textarea class="form-control" name="observacoes" rows="2"><?= e($formData['observacoes']) ?></textarea></div>
                <div class="col-12"><hr><strong>Anamnese</strong></div>
                <div class="col-md-6"><label class="check-line"><input type="checkbox" name="anamnese_diabetes" value="1" <?= $formData['anamnese_diabetes'] ? 'checked' : '' ?>> Diabetes</label></div>
                <div class="col-md-6"><label class="check-line"><input type="checkbox" name="anamnese_gestante" value="1" <?= $formData['anamnese_gestante'] ? 'checked' : '' ?>> Gestante</label></div>
                <div class="col-12"><label class="form-label">Alergias</label><input class="form-control" name="anamnese_alergia" value="<?= e($formData['anamnese_alergia']) ?>"></div>
                <div class="col-12"><label class="form-label">Notas clínicas</label><textarea class="form-control" name="anamnese_observacoes" rows="2"><?= e($formData['anamnese_observacoes']) ?></textarea></div>
                <div class="col-12"><hr><strong>Portal do cliente</strong><p class="muted">O cliente entra com o telefone e a senha para ver exames, preços e PIX.</p></div>
                <div class="col-12"><label class="check-line"><input type="checkbox" name="portal_liberado" value="1" <?= $formData['portal_liberado'] ? 'checked' : '' ?>> Liberar acesso ao portal</label></div>
                <div class="col-md-7"><label class="form-label">Senha do portal</label><input class="form-control" type="text" name="senha_portal" placeholder="<?= $client && $client['senha_hash'] ? 'Deixe em branco para manter' : 'Defina ou gere' ?>"></div>
                <div class="col-md-5 d-flex align-items-end"><label class="check-line"><input type="checkbox" name="gerar_senha" value="1"> Gerar senha automática</label></div>
            </div>
            <div class="form-actions">
                <?php if ($client): ?><a class="btn btn-light" href="?page=clientes&id=<?= (int) $client['id'] ?>">Cancelar</a><?php endif; ?>
                <button class="btn btn-primary" type="submit">Salvar cliente</button>
            </div>
        </form>
    </section>
    <section class="panel">
        <div class="panel-heading">
            <div><h2>Clientes cadastrados</h2><p><?= count($clients) ?> registro(s) ativo(s)</p></div>
        </div>
        <div class="table-responsive">
            <table class="table clients-table">
                <thead><tr><th>Cliente</th><th>Contato</th><th>Portal</th><th></th></tr></thead>
                <tbody>
                <?php if (!$clients): ?><tr><td colspan="4" class="empty-table">Nenhum cliente cadastrado.</td></tr><?php endif; ?>
                <?php foreach ($clients as $item): ?>
                    <tr>
                        <td>
                            <div class="client-cell">
                                <?php if ($item['foto_path']): ?><img src="<?= e($item['foto_path']) ?>" alt=""><?php else: ?><span class="client-initial"><?= e(strtoupper(substr($item['nome'], 0, 1))) ?></span><?php endif; ?>
                                <strong><?= e($item['nome']) ?></strong>
                            </div>
                        </td>
                        <td><?= e($item['telefone']) ?><small><?= e((string) $item['email']) ?></small></td>
                        <td><span class="status <?= $item['portal_liberado'] ? 'confirmed' : 'waiting' ?>"><?= $item['portal_liberado'] ? 'Liberado' : 'Fechado' ?></span></td>
                        <td><a class="text-link" href="?page=clientes&id=<?= (int) $item['id'] ?>">Abrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php endif; ?>
