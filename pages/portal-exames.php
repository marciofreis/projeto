<?php

$exams = db()->prepare('SELECT id, data_exame, queixa, orientacao_cliente FROM prontuarios WHERE cliente_id = ? ORDER BY data_exame DESC, id DESC');
$exams->execute([(int) $cliente['id']]);
$exams = $exams->fetchAll();
?>
<section class="welcome-row"><div><p class="eyebrow">ACOMPANHAMENTO</p><h1>Meus exames</h1><p class="muted">Histórico clínico compartilhado pela clínica.</p></div></section>
<section class="panel">
    <?php if (!$exams): ?>
        <div class="empty-note"><i class="bi bi-clipboard2-pulse"></i><h3>Nenhum exame ainda</h3><p>Quando a clínica registrar um atendimento, ele aparece aqui.</p></div>
    <?php endif; ?>
    <?php foreach ($exams as $exam): ?>
        <a class="quick-action" href="portal.php?view=exame&id=<?= (int) $exam['id'] ?>">
            <span class="quick-icon green"><i class="bi bi-file-earmark-medical"></i></span>
            <span>
                <strong><?= e(date('d/m/Y', strtotime($exam['data_exame']))) ?></strong>
                <small><?= e($exam['queixa'] ?: ($exam['orientacao_cliente'] ?: 'Exame clínico')) ?></small>
            </span>
            <i class="bi bi-chevron-right"></i>
        </a>
    <?php endforeach; ?>
</section>
