<?php

require_once __DIR__ . '/../config/database.php';

$error = null;
$flash = pullFlash();
$clients = [];
$services = [];
$hoje = date('Y-m-d');
$mesAtual = date('Y-m');
$mes = (string) ($_GET['mes'] ?? $mesAtual);
if (!preg_match('/^\d{4}-\d{2}$/', $mes) || strtotime($mes . '-01') === false) {
    $mes = $mesAtual;
}
$dia = (string) ($_GET['dia'] ?? $hoje);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia) || strtotime($dia) === false) {
    $dia = $hoje;
}
if (substr($dia, 0, 7) !== $mes) {
    $dia = $mes === $mesAtual ? $hoje : ($mes . '-01');
}

$clinic = clinica();
$prefill = $dia . 'T' . substr((string) ($clinic['horario_inicio'] ?: '08:00'), 0, 5);

try {
    $clients = db()->query('SELECT id, nome FROM clientes WHERE ativo = 1 ORDER BY nome')->fetchAll();
    $services = db()->query('SELECT id, nome, duracao_minutos, preco FROM servicos WHERE ativo = 1 ORDER BY nome')->fetchAll();
} catch (Throwable $exception) {
    $error = 'Banco ainda não configurado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'status_agendamento') {
    $id = (int) ($_POST['id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');
    if ($id > 0 && in_array($status, ['agendado', 'confirmado', 'concluido', 'cancelado'], true)) {
        try {
            db()->prepare('UPDATE agendamentos SET status = ? WHERE id = ?')->execute([$status, $id]);
            flash('success', 'Status do horário atualizado.');
            header('Location: ' . agendaUrl($mes, $dia));
            exit;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'agendamento') {
    $clienteId = (int) ($_POST['cliente_id'] ?? 0);
    $servicoId = (int) ($_POST['servico_id'] ?? 0);
    $inicio = trim((string) ($_POST['inicio'] ?? ''));
    $status = $_POST['status'] ?? 'agendado';
    $observacoes = trim((string) ($_POST['observacoes'] ?? ''));

    if ($clienteId < 1 || $servicoId < 1 || $inicio === '') {
        $error = 'Cliente, serviço e horário são obrigatórios.';
    } else {
        try {
            $inicioDb = str_replace('T', ' ', $inicio);
            if (strlen($inicioDb) === 16) {
                $inicioDb .= ':00';
            }
            $statement = db()->prepare('INSERT INTO agendamentos (cliente_id, servico_id, inicio, status, observacoes) VALUES (?, ?, ?, ?, ?)');
            $statement->execute([$clienteId, $servicoId, $inicioDb, $status, $observacoes ?: null]);
            $_SESSION['ultimo_agendamento'] = (int) db()->lastInsertId();
            $diaSalvo = date('Y-m-d', strtotime($inicioDb)) ?: $dia;
            flash('success', 'Agendamento salvo. Envie a confirmação no WhatsApp.');
            header('Location: ' . agendaUrl(substr($diaSalvo, 0, 7), $diaSalvo));
            exit;
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

$monthStart = $mes . '-01';
$monthEnd = date('Y-m-01', strtotime($monthStart . ' +1 month'));
$appointments = [];
$byDay = [];
try {
    $statement = db()->prepare("SELECT a.id, a.inicio, a.status, a.observacoes, c.nome AS cliente, c.telefone, s.nome AS servico, s.duracao_minutos
        FROM agendamentos a
        JOIN clientes c ON c.id = a.cliente_id
        JOIN servicos s ON s.id = a.servico_id
        WHERE a.inicio >= ? AND a.inicio < ?
        ORDER BY a.inicio");
    $statement->execute([$monthStart . ' 00:00:00', $monthEnd . ' 00:00:00']);
    $appointments = $statement->fetchAll();
    foreach ($appointments as $item) {
        $byDay[date('Y-m-d', strtotime((string) $item['inicio']))][] = $item;
    }
} catch (Throwable $exception) {
}

$dayAppointments = $byDay[$dia] ?? [];
$ativosMes = count(array_filter($appointments, static fn ($item) => $item['status'] !== 'cancelado'));
$canceladosMes = count($appointments) - $ativosMes;
$ultimoId = (int) ($_SESSION['ultimo_agendamento'] ?? 0);
$prevMes = date('Y-m', strtotime($monthStart . ' -1 month'));
$nextMes = date('Y-m', strtotime($monthStart . ' +1 month'));
$firstWeekday = (int) date('N', strtotime($monthStart));
$gridStart = strtotime($monthStart . ' -' . ($firstWeekday - 1) . ' days');
$weekDays = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
?>
<section class="welcome-row">
    <div>
        <p class="eyebrow">ROTINA</p>
        <h1>Agenda</h1>
        <p class="muted">Calendário central com todos os horários do mês. Toque em um dia para ver a lista.</p>
    </div>
    <div class="welcome-actions">
        <a class="btn btn-light" href="<?= e(agendaUrl($mesAtual, $hoje)) ?>">Hoje</a>
        <a class="btn btn-whatsapp" href="<?= e(whatsappLink('', mensagemLinkAgenda())) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> Enviar link</a>
    </div>
</section>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type'] === 'success' ? 'success' : 'danger') ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<?php
foreach ($dayAppointments as $item) {
    if ((int) $item['id'] === $ultimoId) {
        echo '<div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2"><span>Confirme com ' . e($item['cliente']) . '.</span>' . whatsappButton((string) $item['telefone'], mensagemAgendamento(['nome' => $item['cliente'], 'telefone' => $item['telefone']], $item), 'Confirmar no WhatsApp') . '</div>';
        break;
    }
}
?>
<section class="panel calendar-panel">
    <div class="panel-heading cal-nav">
        <div>
            <h2><?= e(mesNome($mes)) ?></h2>
            <p><?= $ativosMes ?> horário(s) ativo(s)<?= $canceladosMes ? ' · ' . $canceladosMes . ' cancelado(s)' : '' ?></p>
        </div>
        <div class="cal-nav-actions">
            <a class="btn btn-light" href="<?= e(agendaUrl($prevMes, $prevMes === $mesAtual ? $hoje : $prevMes . '-01')) ?>" aria-label="Mês anterior"><i class="bi bi-chevron-left"></i></a>
            <a class="btn btn-light" href="<?= e(agendaUrl($nextMes, $nextMes === $mesAtual ? $hoje : $nextMes . '-01')) ?>" aria-label="Próximo mês"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
    <div class="cal-wrap">
    <table class="cal-table" style="width:100%;table-layout:fixed;border-collapse:separate;border-spacing:6px">
        <thead>
            <tr>
                <?php foreach ($weekDays as $label): ?>
                    <th><?= e($label) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php
        $daysInMonth = (int) date('t', strtotime($monthStart));
        $totalCells = (int) (ceil(($firstWeekday - 1 + $daysInMonth) / 7) * 7);
        for ($cell = 0; $cell < $totalCells; $cell++):
            if ($cell % 7 === 0) {
                echo $cell === 0 ? '<tr>' : '</tr><tr>';
            }
            $time = strtotime('+' . $cell . ' days', $gridStart);
            $cellDay = date('Y-m-d', $time);
            $cellMes = date('Y-m', $time);
            $items = $byDay[$cellDay] ?? [];
            $ativos = array_values(array_filter($items, static fn ($item) => $item['status'] !== 'cancelado'));
            $classes = ['cal-day'];
            if ($cellMes !== $mes) {
                $classes[] = 'is-muted';
            }
            if ($cellDay === $hoje) {
                $classes[] = 'is-today';
            }
            if ($cellDay === $dia) {
                $classes[] = 'is-selected';
            }
            if (!diaEstaAberto($cellDay)) {
                $classes[] = 'is-closed';
            }
            ?>
            <td>
                <a class="<?= e(implode(' ', $classes)) ?>" href="<?= e(agendaUrl($cellMes, $cellDay)) ?>">
                    <span class="cal-day-num"><?= (int) date('j', $time) ?></span>
                    <?php if ($ativos): ?>
                        <span class="cal-count"><?= count($ativos) ?></span>
                    <?php endif; ?>
                    <span class="cal-events">
                        <?php foreach (array_slice($ativos, 0, 3) as $item): ?>
                            <span class="cal-event <?= e(statusClass($item['status'])) ?>"><?= e(date('H:i', strtotime((string) $item['inicio']))) ?> <?= e($item['cliente']) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($ativos) > 3): ?>
                            <span class="cal-more">+<?= count($ativos) - 3 ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="cal-dots">
                        <?php foreach (array_slice($ativos, 0, 4) as $item): ?>
                            <i class="<?= e(statusClass($item['status'])) ?>"></i>
                        <?php endforeach; ?>
                    </span>
                </a>
            </td>
        <?php endfor; ?>
        </tr>
        </tbody>
    </table>
    </div>
    <div class="cal-legend">
        <span><i class="waiting"></i> Agendado</span>
        <span><i class="confirmed"></i> Confirmado / concluído</span>
        <span class="muted">Dias esmaecidos estão fechados nas configurações.</span>
    </div>
</section>
<div class="content-grid agenda-split">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2><?= e(date('d/m/Y', strtotime($dia))) ?></h2>
                <p><?= count($dayAppointments) ?> horário(s) neste dia<?= diaEstaAberto($dia) ? '' : ' · clínica fechada' ?></p>
            </div>
        </div>
        <?php if (!$dayAppointments): ?>
            <div class="empty-note compact"><i class="bi bi-calendar2-week"></i><p>Nenhum agendamento neste dia.</p></div>
        <?php endif; ?>
        <?php foreach ($dayAppointments as $item): ?>
            <article class="cal-item <?= $item['status'] === 'cancelado' ? 'is-cancelled' : '' ?>">
                <time><?= e(date('H:i', strtotime((string) $item['inicio']))) ?></time>
                <div>
                    <strong><?= e($item['cliente']) ?></strong>
                    <span><?= e($item['servico']) ?> · <?= (int) $item['duracao_minutos'] ?> min</span>
                </div>
                <div class="cal-item-actions">
                    <form method="post" class="cal-status">
                        <input type="hidden" name="formulario" value="status_agendamento">
                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                        <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                            <?php foreach (['agendado' => 'Agendado', 'confirmado' => 'Confirmado', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $item['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?= whatsappButton((string) $item['telefone'], mensagemAgendamento(['nome' => $item['cliente'], 'telefone' => $item['telefone']], $item), 'WhatsApp') ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><h2>Novo horário</h2><p>Já preenchido com o dia selecionado no calendário.</p></div></div>
        <form method="post">
            <input type="hidden" name="formulario" value="agendamento">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Cliente *</label>
                    <select class="form-select" name="cliente_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= (int) $client['id'] ?>"><?= e($client['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Serviço *</label>
                    <select class="form-select" name="servico_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?= (int) $service['id'] ?>"><?= e($service['nome']) ?> · <?= e(brl($service['preco'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Data e hora *</label>
                    <input class="form-control" type="datetime-local" name="inicio" value="<?= e($prefill) ?>" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="agendado">Agendado</option>
                        <option value="confirmado">Confirmado</option>
                        <option value="concluido">Concluído</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Observações</label>
                    <textarea class="form-control" name="observacoes" rows="2"></textarea>
                </div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Salvar agendamento</button></div>
        </form>
    </section>
</div>
