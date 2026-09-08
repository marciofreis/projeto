<?php

$examId = (int) ($_GET['id'] ?? 0);
$statement = db()->prepare('SELECT * FROM prontuarios WHERE id = ? AND cliente_id = ?');
$statement->execute([$examId, (int) $cliente['id']]);
$exam = $statement->fetch();
if (!$exam) {
    echo '<section class="panel"><p class="muted">Exame não encontrado.</p></section>';
    return;
}

$marks = db()->prepare('SELECT pe, zona, tipo, observacao FROM prontuario_marcas WHERE prontuario_id = ?');
$marks->execute([$examId]);
$marks = $marks->fetchAll();
$photos = db()->prepare('SELECT momento, foto_path FROM prontuario_fotos WHERE prontuario_id = ? ORDER BY id');
$photos->execute([$examId]);
$photos = $photos->fetchAll();
?>
<section class="welcome-row">
    <div>
        <p class="eyebrow">EXAME</p>
        <h1><?= e(date('d/m/Y', strtotime($exam['data_exame']))) ?></h1>
        <p class="muted"><?= e($exam['queixa'] ?: 'Acompanhamento clínico') ?></p>
    </div>
    <div class="welcome-actions">
        <a class="btn btn-light" href="imprimir.php?tipo=exame&id=<?= (int) $exam['id'] ?>&versao=cliente" target="_blank"><i class="bi bi-printer"></i> Imprimir</a>
        <a class="btn btn-light" href="portal.php?view=exames">Voltar</a>
    </div>
</section>
<section class="panel">
    <?php if ($exam['orientacao_cliente']): ?>
        <h2>Orientação</h2>
        <p><?= nl2br(e($exam['orientacao_cliente'])) ?></p>
    <?php endif; ?>
    <h2 class="mt-4">Mapa do pé</h2>
    <?php renderFootMap($marks, false); ?>
    <?php if ($photos): ?>
        <h2 class="mt-4">Fotos</h2>
        <div class="exam-photos">
            <?php foreach ($photos as $photo): ?>
                <figure>
                    <img src="<?= e($photo['foto_path']) ?>" alt="Foto do exame">
                    <figcaption><?= e(ucfirst($photo['momento'])) ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
