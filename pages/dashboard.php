<?php

$attendances = 0;
$revenue = 0.0;
$newClients = 0;
$appointments = [];

try {
    $attendances = (int) db()->query("SELECT COUNT(*) FROM agendamentos WHERE DATE(inicio) = CURRENT_DATE AND status <> 'cancelado'")->fetchColumn();
    $revenue = (float) db()->query("SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE status = 'pago' AND DATE(COALESCE(pago_em, criado_em)) = CURRENT_DATE")->fetchColumn();
    $newClients = (int) db()->query("SELECT COUNT(*) FROM clientes WHERE MONTH(criado_em) = MONTH(CURRENT_DATE) AND YEAR(criado_em) = YEAR(CURRENT_DATE)")->fetchColumn();
    $appointments = db()->query("SELECT a.id, a.inicio, a.status, c.nome AS cliente, s.nome AS servico, s.duracao_minutos
        FROM agendamentos a
        JOIN clientes c ON c.id = a.cliente_id
        JOIN servicos s ON s.id = a.servico_id
        WHERE DATE(a.inicio) = CURRENT_DATE
        ORDER BY a.inicio")->fetchAll();
} catch (Throwable $exception) {
}
?>
<section class="welcome-row">
    <div>
        <p class="eyebrow">PAINEL DO ESPAÇO</p>
        <h1>Olá, <?= e(explode(' ', $staffUser['nome'] ?? 'equipe')[0]) ?> <span>☀</span></h1>
        <p class="muted">Resumo real do seu espaço hoje.</p>
    </div>
    <a class="btn btn-primary" href="?page=agenda"><i class="bi bi-plus-lg"></i> Novo agendamento</a>
</section>
<section class="stats-grid">
    <article class="stat-card"><div class="stat-icon mint"><i class="bi bi-calendar-check"></i></div><div><span>Atendimentos hoje</span><strong><?= e(str_pad((string) $attendances, 2, '0', STR_PAD_LEFT)) ?></strong><small class="neutral"><?= e(date('d/m/Y')) ?></small></div></article>
    <article class="stat-card"><div class="stat-icon peach"><i class="bi bi-currency-dollar"></i></div><div><span>Faturamento do dia</span><strong><?= e(brl($revenue)) ?></strong><small class="neutral">Pagamentos confirmados</small></div></article>
    <article class="stat-card"><div class="stat-icon lilac"><i class="bi bi-person-plus"></i></div><div><span>Novos clientes</span><strong><?= e(str_pad((string) $newClients, 2, '0', STR_PAD_LEFT)) ?></strong><small class="neutral">Neste mês</small></div></article>
</section>
<div class="content-grid">
    <section class="panel agenda-panel">
        <div class="panel-heading">
            <div><h2>Agenda de hoje</h2><p>Atendimentos programados</p></div>
            <a href="?page=agenda" class="text-link">Ver calendário <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="appointment-list">
            <?php if (!$appointments): ?>
                <div class="empty-note compact"><i class="bi bi-calendar2-week"></i><p>Nenhum horário marcado para hoje.</p></div>
            <?php endif; ?>
            <?php foreach ($appointments as $item): ?>
                <div class="appointment">
                    <time><?= e(date('H:i', strtotime($item['inicio']))) ?></time>
                    <div class="appointment-line"></div>
                    <div class="appointment-info">
                        <strong><?= e($item['cliente']) ?></strong>
                        <span><?= e($item['servico']) ?> <b>•</b> <?= (int) $item['duracao_minutos'] ?> min</span>
                    </div>
                    <span class="status <?= e(statusClass($item['status'])) ?>"><?= e(statusLabel($item['status'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="panel quick-panel">
        <div class="panel-heading"><div><h2>Ações rápidas</h2><p>Facilite sua rotina</p></div></div>
        <div class="quick-actions">
            <?php if (can('clientes')): ?><a href="?page=clientes" class="quick-action"><span class="quick-icon blue"><i class="bi bi-person-plus"></i></span><span><strong>Cadastrar cliente</strong><small>Perfil, anamnese e portal</small></span><i class="bi bi-chevron-right"></i></a><?php endif; ?>
            <?php if (can('financeiro')): ?><a href="?page=financeiro" class="quick-action"><span class="quick-icon green"><i class="bi bi-qr-code"></i></span><span><strong>Gerar PIX</strong><small>Cobrança para o cliente</small></span><i class="bi bi-chevron-right"></i></a><?php endif; ?>
            <?php if (can('servicos')): ?><a href="?page=servicos" class="quick-action"><span class="quick-icon yellow"><i class="bi bi-stars"></i></span><span><strong>Gerenciar serviços</strong><small>Preços visíveis no portal</small></span><i class="bi bi-chevron-right"></i></a><?php endif; ?>
            <?php if (can('usuarios')): ?><a href="?page=usuarios" class="quick-action"><span class="quick-icon lilac"><i class="bi bi-person-badge"></i></span><span><strong>Usuários da equipe</strong><small>Acessos de funcionários</small></span><i class="bi bi-chevron-right"></i></a><?php endif; ?>
            <a href="agendar.php" class="quick-action"><span class="quick-icon blue"><i class="bi bi-whatsapp"></i></span><span><strong>Link de agendamento</strong><small>Cliente marca sozinho no WhatsApp</small></span><i class="bi bi-chevron-right"></i></a>
        </div>
    </section>
</div>
