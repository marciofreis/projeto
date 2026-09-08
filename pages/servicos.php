<?php

require_once __DIR__ . '/../config/database.php';

$error = null;
$flash = pullFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'servico') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $categoria = $_POST['categoria'] ?? 'podologia';
    $duracao = (int) ($_POST['duracao_minutos'] ?? 60);
    $preco = (float) str_replace(['.', ','], ['', '.'], (string) ($_POST['preco'] ?? '0'));
    if ($nome === '') {
        $error = 'Informe o nome do serviço.';
    } else {
        try {
            db()->prepare('INSERT INTO servicos (nome, categoria, duracao_minutos, preco) VALUES (?, ?, ?, ?)')->execute([
                $nome,
                $categoria === 'salao' ? 'salao' : 'podologia',
                max(15, $duracao),
                $preco,
            ]);
            flash('success', 'Serviço publicado. O preço já aparece no portal do cliente.');
            header('Location: ?page=servicos');
            exit;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

$services = [];
try {
    $services = db()->query('SELECT id, nome, categoria, duracao_minutos, preco, ativo FROM servicos ORDER BY categoria, nome')->fetchAll();
} catch (Throwable $exception) {
    $error ??= 'Banco ainda não configurado.';
}
?>
<section class="welcome-row">
    <div>
        <p class="eyebrow">CATÁLOGO</p>
        <h1>Serviços</h1>
        <p class="muted">Estes preços são os mesmos que o cliente vê ao entrar no portal.</p>
    </div>
</section>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($flash): ?><div class="alert alert-success"><?= e($flash['message']) ?></div><?php endif; ?>
<div class="content-grid form-grid">
    <section class="panel">
        <div class="panel-heading"><div><h2>Novo serviço</h2><p>Podologia ou salão.</p></div></div>
        <form method="post">
            <input type="hidden" name="formulario" value="servico">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Nome *</label><input class="form-control" name="nome" required></div>
                <div class="col-md-5">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" name="categoria">
                        <option value="podologia">Podologia</option>
                        <option value="salao">Salão</option>
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">Minutos</label><input class="form-control" type="number" name="duracao_minutos" value="60" min="15"></div>
                <div class="col-md-4"><label class="form-label">Preço</label><input class="form-control" name="preco" placeholder="0,00"></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Salvar serviço</button></div>
        </form>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><h2>Catálogo</h2><p><?= count($services) ?> serviço(s)</p></div></div>
        <div class="table-responsive">
            <table class="table clients-table">
                <thead><tr><th>Serviço</th><th>Categoria</th><th>Duração</th><th>Preço</th></tr></thead>
                <tbody>
                <?php foreach ($services as $service): ?>
                    <tr>
                        <td><strong><?= e($service['nome']) ?></strong></td>
                        <td><?= $service['categoria'] === 'salao' ? 'Salão' : 'Podologia' ?></td>
                        <td><?= (int) $service['duracao_minutos'] ?> min</td>
                        <td><?= e(brl($service['preco'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
