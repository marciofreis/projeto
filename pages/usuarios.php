<?php

requirePermission('usuarios');

$error = null;
$flash = pullFlash();
$editId = (int) ($_GET['id'] ?? $_POST['usuario_id'] ?? 0);
$form = [
    'nome' => '', 'email' => '', 'papel' => 'profissional', 'cargo' => '', 'ativo' => '1',
    'permissoes' => papeisPadrao()['profissional']['perms'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'usuario') {
    $editId = (int) ($_POST['usuario_id'] ?? 0);
    $form['nome'] = trim((string) ($_POST['nome'] ?? ''));
    $form['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
    $form['papel'] = (string) ($_POST['papel'] ?? 'profissional');
    $form['cargo'] = trim((string) ($_POST['cargo'] ?? ''));
    $form['ativo'] = isset($_POST['ativo']) ? '1' : '0';
    $form['permissoes'] = array_values(array_intersect((array) ($_POST['permissoes'] ?? []), array_keys(permissoesCatalogo())));
    $senha = trim((string) ($_POST['senha'] ?? ''));

    if (!isset(papeisPadrao()[$form['papel']])) {
        $form['papel'] = 'profissional';
    }
    if ($form['papel'] === 'admin' && (staff()['papel'] ?? '') !== 'admin') {
        $error = 'Somente um administrador pode criar outro administrador.';
    } elseif ($form['nome'] === '' || $form['email'] === '') {
        $error = 'Nome e e-mail são obrigatórios.';
    } elseif ($editId < 1 && $senha === '') {
        $error = 'Defina uma senha para o novo usuário.';
    } else {
        try {
            $current = staff();
            if ($editId > 0 && $editId === (int) $current['id'] && $form['ativo'] !== '1') {
                throw new RuntimeException('Você não pode desativar o próprio acesso.');
            }
            if ($editId > 0) {
                $row = db()->prepare('SELECT papel FROM usuarios WHERE id = ?');
                $row->execute([$editId]);
                $anterior = $row->fetch();
                if ($anterior && $anterior['papel'] === 'admin' && ($form['papel'] !== 'admin' || $form['ativo'] !== '1') && countAdmins() <= 1) {
                    throw new RuntimeException('Mantenha pelo menos um administrador ativo.');
                }
            }

            $hashSql = $senha !== '' ? ', senha_hash = ?' : '';
            $permissoes = json_encode($form['permissoes'], JSON_UNESCAPED_UNICODE);
            if ($editId > 0) {
                $sql = "UPDATE usuarios SET nome=?, email=?, papel=?, cargo=?, permissoes=?, ativo=? $hashSql WHERE id=?";
                $params = [$form['nome'], $form['email'], $form['papel'], $form['cargo'] ?: null, $permissoes, $form['ativo']];
                if ($senha !== '') {
                    $params[] = password_hash($senha, PASSWORD_DEFAULT);
                }
                $params[] = $editId;
                db()->prepare($sql)->execute($params);
                flash('success', 'Usuário atualizado.');
            } else {
                db()->prepare('INSERT INTO usuarios (nome, email, senha_hash, papel, cargo, permissoes, ativo) VALUES (?,?,?,?,?,?,?)')->execute([
                    $form['nome'], $form['email'], password_hash($senha, PASSWORD_DEFAULT), $form['papel'],
                    $form['cargo'] ?: null, $permissoes, $form['ativo'],
                ]);
                flash('success', 'Usuário cadastrado. Peça para entrar com o e-mail e a senha definidos.');
            }
            header('Location: ?page=usuarios');
            exit;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

$usuario = null;
if ($editId > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $statement = db()->prepare('SELECT * FROM usuarios WHERE id = ?');
    $statement->execute([$editId]);
    $usuario = $statement->fetch() ?: null;
    if ($usuario) {
        $form = [
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'papel' => $usuario['papel'],
            'cargo' => (string) $usuario['cargo'],
            'ativo' => (string) $usuario['ativo'],
            'permissoes' => decodePermissoes($usuario['permissoes'], $usuario['papel']),
        ];
    }
}

$users = db()->query('SELECT id, nome, email, papel, cargo, ativo, criado_em FROM usuarios ORDER BY nome')->fetchAll();
$defaultsJson = json_encode(array_map(static fn (array $papel): array => $papel['perms'], papeisPadrao()), JSON_UNESCAPED_UNICODE);
?>
<section class="welcome-row">
    <div>
        <p class="eyebrow">EQUIPE</p>
        <h1>Usuários e acessos</h1>
        <p class="muted">Cadastre funcionários e defina o que cada um pode ver e alterar.</p>
    </div>
</section>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= e($flash['message']) ?></div><?php endif; ?>

<div class="content-grid form-grid">
    <section class="panel">
        <div class="panel-heading"><div><h2><?= $usuario ? 'Editar usuário' : 'Novo usuário' ?></h2><p>Administrador, recepção, profissional ou financeiro.</p></div></div>
        <form method="post" id="userForm">
            <input type="hidden" name="formulario" value="usuario">
            <input type="hidden" name="usuario_id" value="<?= $usuario ? (int) $usuario['id'] : 0 ?>">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Nome *</label><input class="form-control" name="nome" value="<?= e($form['nome']) ?>" required></div>
                <div class="col-md-7"><label class="form-label">E-mail de login *</label><input class="form-control" type="email" name="email" value="<?= e($form['email']) ?>" required></div>
                <div class="col-md-5"><label class="form-label">Senha <?= $usuario ? '' : '*' ?></label><input class="form-control" type="text" name="senha" placeholder="<?= $usuario ? 'Em branco para manter' : 'Senha inicial' ?>" <?= $usuario ? '' : 'required' ?>></div>
                <div class="col-md-6">
                    <label class="form-label">Papel</label>
                    <select class="form-select" name="papel" id="userRole">
                        <?php foreach (papeisPadrao() as $key => $papel): ?>
                            <option value="<?= e($key) ?>" <?= $form['papel'] === $key ? 'selected' : '' ?>><?= e($papel['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Cargo na clínica</label><input class="form-control" name="cargo" value="<?= e($form['cargo']) ?>" placeholder="Ex.: Podóloga, recepcionista"></div>
                <div class="col-12"><label class="check-line"><input type="checkbox" name="ativo" value="1" <?= $form['ativo'] ? 'checked' : '' ?>> Usuário ativo</label></div>
                <div class="col-12"><strong>Permissões</strong><p class="muted">O papel preenche o básico. Você pode marcar ou desmarcar.</p></div>
                <?php foreach (permissoesCatalogo() as $key => $label): ?>
                    <div class="col-md-6">
                        <label class="check-line"><input type="checkbox" class="perm-box" name="permissoes[]" value="<?= e($key) ?>" <?= in_array($key, $form['permissoes'], true) ? 'checked' : '' ?>> <?= e($label) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-actions">
                <?php if ($usuario): ?><a class="btn btn-light" href="?page=usuarios">Cancelar</a><?php endif; ?>
                <button class="btn btn-primary" type="submit">Salvar usuário</button>
            </div>
        </form>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><h2>Equipe cadastrada</h2><p><?= count($users) ?> usuário(s)</p></div></div>
        <div class="table-responsive">
            <table class="table clients-table">
                <thead><tr><th>Nome</th><th>Papel</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($users as $item): ?>
                    <tr>
                        <td><strong><?= e($item['nome']) ?></strong><small><?= e($item['email']) ?><?= $item['cargo'] ? ' · ' . e($item['cargo']) : '' ?></small></td>
                        <td><?= e(papelLabel($item['papel'])) ?></td>
                        <td><span class="status <?= $item['ativo'] ? 'confirmed' : 'cancelled' ?>"><?= $item['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                        <td><a class="text-link" href="?page=usuarios&id=<?= (int) $item['id'] ?>">Editar</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<script>
const rolePerms = <?= $defaultsJson ?>;
document.getElementById('userRole')?.addEventListener('change', (event) => {
    const selected = rolePerms[event.target.value] || [];
    document.querySelectorAll('.perm-box').forEach((box) => {
        box.checked = selected.includes(box.value);
    });
});
</script>
