<?php

$next = db()->prepare("SELECT a.inicio, a.status, s.nome AS servico FROM agendamentos a JOIN servicos s ON s.id = a.servico_id WHERE a.cliente_id = ? AND a.inicio >= NOW() AND a.status <> 'cancelado' ORDER BY a.inicio LIMIT 1");
$next->execute([(int) $cliente['id']]);
$next = $next->fetch();

$lastExam = db()->prepare('SELECT id, data_exame, orientacao_cliente FROM prontuarios WHERE cliente_id = ? ORDER BY data_exame DESC, id DESC LIMIT 1');
$lastExam->execute([(int) $cliente['id']]);
$lastExam = $lastExam->fetch();

$pending = db()->prepare("SELECT COUNT(*) FROM pagamentos WHERE cliente_id = ? AND status = 'pendente'");
$pending->execute([(int) $cliente['id']]);
$pending = (int) $pending->fetchColumn();
?>
<section class="welcome-row">
    <div>
        <p class="eyebrow">PORTAL DO CLIENTE</p>
        <h1>Seu acompanhamento</h1>
        <p class="muted">Exames, preços e PIX em um só lugar.</p>
    </div>
</section>
<section class="stats-grid portal-stats">
    <article class="stat-card"><div class="stat-icon mint"><i class="bi bi-calendar-event"></i></div><div><span>Próximo horário</span><strong><?= $next ? e(date('d/m H:i', strtotime($next['inicio']))) : '—' ?></strong><small><?= $next ? e($next['servico']) : 'Nenhum agendamento' ?></small></div></article>
    <article class="stat-card"><div class="stat-icon peach"><i class="bi bi-qr-code"></i></div><div><span>PIX em aberto</span><strong><?= $pending ?></strong><small><?= $pending ? 'Toque em PIX para pagar' : 'Nada pendente' ?></small></div></article>
    <article class="stat-card"><div class="stat-icon lilac"><i class="bi bi-clipboard2-pulse"></i></div><div><span>Último exame</span><strong><?= $lastExam ? e(date('d/m', strtotime($lastExam['data_exame']))) : '—' ?></strong><small><?= $lastExam ? 'Ver orientação' : 'Ainda sem registro' ?></small></div></article>
</section>
<section class="panel">
    <div class="quick-actions">
        <a class="quick-action" href="portal.php?view=exames"><span class="quick-icon green"><i class="bi bi-file-earmark-medical"></i></span><span><strong>Meus exames</strong><small>Fotos, mapa do pé e orientação</small></span><i class="bi bi-chevron-right"></i></a>
        <a class="quick-action" href="portal.php?view=precos"><span class="quick-icon yellow"><i class="bi bi-tag"></i></span><span><strong>Tabela de preços</strong><small>Serviços atuais da clínica</small></span><i class="bi bi-chevron-right"></i></a>
        <a class="quick-action" href="portal.php?view=pagamentos"><span class="quick-icon blue"><i class="bi bi-qr-code"></i></span><span><strong>Pagar com PIX</strong><small>QR Code e copia-e-cola</small></span><i class="bi bi-chevron-right"></i></a>
        <a class="quick-action" href="agendar.php"><span class="quick-icon green"><i class="bi bi-calendar-plus"></i></span><span><strong>Agendar horário</strong><small>Escolha o serviço e o dia</small></span><i class="bi bi-chevron-right"></i></a>
    </div>
</section>
<?php if ($lastExam && $lastExam['orientacao_cliente']): ?>
    <section class="panel mt-3">
        <div class="panel-heading"><div><h2>Última orientação</h2><p><?= e(date('d/m/Y', strtotime($lastExam['data_exame']))) ?></p></div></div>
        <p><?= nl2br(e($lastExam['orientacao_cliente'])) ?></p>
        <a class="text-link" href="portal.php?view=exame&id=<?= (int) $lastExam['id'] ?>">Ver exame completo</a>
    </section>
<?php endif; ?>
