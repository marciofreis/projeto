<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/foot-map.php';

$error = null;
$flash = pullFlash();
$examId = (int) ($_GET['id'] ?? 0);
$clienteId = (int) ($_GET['cliente_id'] ?? 0);
$exam = null;
$client = null;
$photos = [];
$marks = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'prontuario') {
    $clienteId = (int) ($_POST['cliente_id'] ?? 0);
    $examId = (int) ($_POST['prontuario_id'] ?? 0);
    $dataExame = trim((string) ($_POST['data_exame'] ?? date('Y-m-d')));
    $queixa = trim((string) ($_POST['queixa'] ?? ''));
    $notas = trim((string) ($_POST['notas_internas'] ?? ''));
    $orientacao = trim((string) ($_POST['orientacao_cliente'] ?? ''));
    $marks = decodeMarcas($_POST['marcas_json'] ?? '[]');

    if ($clienteId < 1) {
        $error = 'Selecione um cliente para o exame.';
    } else {
        try {
            if ($examId > 0) {
                db()->prepare('UPDATE prontuarios SET data_exame=?, queixa=?, notas_internas=?, orientacao_cliente=? WHERE id=? AND cliente_id=?')->execute([
                    $dataExame, $queixa ?: null, $notas ?: null, $orientacao ?: null, $examId, $clienteId,
                ]);
                db()->prepare('DELETE FROM prontuario_marcas WHERE prontuario_id = ?')->execute([$examId]);
            } else {
                db()->prepare('INSERT INTO prontuarios (cliente_id, data_exame, queixa, notas_internas, orientacao_cliente) VALUES (?,?,?,?,?)')->execute([
                    $clienteId, $dataExame, $queixa ?: null, $notas ?: null, $orientacao ?: null,
                ]);
                $examId = (int) db()->lastInsertId();
            }

            $insertMark = db()->prepare('INSERT INTO prontuario_marcas (prontuario_id, pe, zona, tipo, observacao) VALUES (?,?,?,?,?)');
            foreach ($marks as $mark) {
                if (empty($mark['pe']) || empty($mark['zona']) || empty($mark['tipo'])) {
                    continue;
                }
                $insertMark->execute([
                    $examId,
                    $mark['pe'] === 'direito' ? 'direito' : 'esquerdo',
                    substr((string) $mark['zona'], 0, 40),
                    substr((string) $mark['tipo'], 0, 40),
                    trim((string) ($mark['observacao'] ?? '')) ?: null,
                ]);
            }

            foreach (uploadImages($_FILES['fotos'] ?? [], 'exames', 'exame') as $path) {
                db()->prepare('INSERT INTO prontuario_fotos (prontuario_id, momento, foto_path) VALUES (?,?,?)')->execute([
                    $examId,
                    $_POST['momento'] ?? 'evolucao',
                    $path,
                ]);
            }

            flash('success', 'Exame salvo. O cliente já pode ver no portal (orientação, mapa e fotos).');
            header('Location: ?page=prontuario&id=' . $examId);
            exit;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

if ($examId > 0) {
    $statement = db()->prepare('SELECT * FROM prontuarios WHERE id = ?');
    $statement->execute([$examId]);
    $exam = $statement->fetch() ?: null;
    if ($exam) {
        $clienteId = (int) $exam['cliente_id'];
        $marksStatement = db()->prepare('SELECT pe, zona, tipo, observacao FROM prontuario_marcas WHERE prontuario_id = ?');
        $marksStatement->execute([$examId]);
        $marks = $marksStatement->fetchAll();
        $photoStatement = db()->prepare('SELECT momento, foto_path FROM prontuario_fotos WHERE prontuario_id = ? ORDER BY id');
        $photoStatement->execute([$examId]);
        $photos = $photoStatement->fetchAll();
    }
}

if ($clienteId > 0) {
    $statement = db()->prepare('SELECT id, nome, telefone, anamnese_diabetes, anamnese_gestante, anamnese_alergia FROM clientes WHERE id = ?');
    $statement->execute([$clienteId]);
    $client = $statement->fetch() ?: null;
}

$clients = db()->query('SELECT id, nome FROM clientes WHERE ativo = 1 ORDER BY nome')->fetchAll();
?>
<section class="welcome-row">
    <div>
        <p class="eyebrow">PRONTUÁRIO</p>
        <h1><?= $exam ? 'Exame de ' . e(date('d/m/Y', strtotime($exam['data_exame']))) : 'Novo exame' ?></h1>
        <p class="muted">Mapa do pé, fotos e orientação que o cliente acompanha no portal.</p>
    </div>
    <div class="welcome-actions">
        <?php if ($exam && can('imprimir')): ?>
            <a class="btn btn-light" href="imprimir.php?tipo=exame&id=<?= (int) $exam['id'] ?>&versao=clinica" target="_blank"><i class="bi bi-printer"></i> Imprimir clínica</a>
            <a class="btn btn-light" href="imprimir.php?tipo=exame&id=<?= (int) $exam['id'] ?>&versao=cliente" target="_blank"><i class="bi bi-printer"></i> Imprimir paciente</a>
        <?php endif; ?>
        <?php if ($client): ?>
            <a class="btn btn-light" href="?page=clientes&id=<?= (int) $client['id'] ?>">Voltar à ficha</a>
        <?php endif; ?>
    </div>
</section>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($flash): ?><div class="alert alert-success"><?= e($flash['message']) ?></div><?php endif; ?>
<?php if ($client && ($client['anamnese_diabetes'] || $client['anamnese_gestante'] || $client['anamnese_alergia'])): ?>
    <div class="alert alert-warning">
        Atenção clínica:
        <?= $client['anamnese_diabetes'] ? ' diabetes' : '' ?>
        <?= $client['anamnese_gestante'] ? ' · gestante' : '' ?>
        <?= $client['anamnese_alergia'] ? ' · alergia: ' . e($client['anamnese_alergia']) : '' ?>
    </div>
<?php endif; ?>

<section class="panel">
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="formulario" value="prontuario">
        <input type="hidden" name="prontuario_id" value="<?= $exam ? (int) $exam['id'] : 0 ?>">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label">Cliente *</label>
                <select class="form-select" name="cliente_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($clients as $item): ?>
                        <option value="<?= (int) $item['id'] ?>" <?= $clienteId === (int) $item['id'] ? 'selected' : '' ?>><?= e($item['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Data do exame</label>
                <input class="form-control" type="date" name="data_exame" value="<?= e($exam['data_exame'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Momento das fotos</label>
                <select class="form-select" name="momento">
                    <option value="antes">Antes</option>
                    <option value="depois">Depois</option>
                    <option value="evolucao" selected>Evolução</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Queixa / procedimento</label>
                <input class="form-control" name="queixa" value="<?= e((string) ($exam['queixa'] ?? '')) ?>" placeholder="Ex.: unha encravada no hálux direito">
            </div>
            <div class="col-md-6">
                <label class="form-label">Notas internas (só a clínica vê)</label>
                <textarea class="form-control" name="notas_internas" rows="3"><?= e((string) ($exam['notas_internas'] ?? '')) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Orientação para o cliente</label>
                <textarea class="form-control" name="orientacao_cliente" rows="3" placeholder="Texto que aparece no portal"><?= e((string) ($exam['orientacao_cliente'] ?? '')) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label">Fotos do exame</label>
                <input class="form-control" type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple>
            </div>
        </div>

        <?php renderFootMap($marks, true); ?>

        <?php if ($photos): ?>
            <div class="exam-photos">
                <?php foreach ($photos as $photo): ?>
                    <figure>
                        <img src="<?= e($photo['foto_path']) ?>" alt="Foto do exame">
                        <figcaption><?= e(ucfirst($photo['momento'])) ?></figcaption>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Salvar exame</button>
        </div>
    </form>
</section>
<script src="assets/js/foot-map.js"></script>
