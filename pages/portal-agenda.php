<?php

$appointments = db()->prepare('SELECT a.inicio, a.status, s.nome AS servico, s.duracao_minutos FROM agendamentos a JOIN servicos s ON s.id = a.servico_id WHERE a.cliente_id = ? ORDER BY a.inicio DESC LIMIT 20');
$appointments->execute([(int) $cliente['id']]);
$appointments = $appointments->fetchAll();
?>
<section class="welcome-row"><div><p class="eyebrow">HORÁRIOS</p><h1>Minha agenda</h1><p class="muted">Seus atendimentos em <?= e($clinicName) ?>.</p></div></section>
<section class="panel">
    <?php if (!$appointments): ?>
        <div class="empty-note"><i class="bi bi-calendar2-week"></i><h3>Nenhum horário</h3><p>Quando a clínica marcar um atendimento, ele aparece aqui.</p></div>
    <?php endif; ?>
    <?php foreach ($appointments as $item): ?>
        <div class="appointment">
            <time><?= e(date('d/m H:i', strtotime($item['inicio']))) ?></time>
            <div class="appointment-line"></div>
            <div class="appointment-info">
                <strong><?= e($item['servico']) ?></strong>
                <span><?= (int) $item['duracao_minutos'] ?> min</span>
            </div>
            <span class="status <?= e(statusClass($item['status'])) ?>"><?= e(statusLabel($item['status'])) ?></span>
        </div>
    <?php endforeach; ?>
</section>
