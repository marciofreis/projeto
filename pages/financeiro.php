<?php

require_once __DIR__ . '/../config/database.php';

$error = null;
$flash = pullFlash();
$clinic = clinica();
$selectedClient = (int) ($_GET['cliente_id'] ?? 0);
$clients = [];
$payments = [];
$monthTotal = 0.0;
$pendingTotal = 0.0;
$todayTotal = 0.0;

try {
    $clients = db()->query('SELECT id, nome FROM clientes WHERE ativo = 1 ORDER BY nome')->fetchAll();
    $monthTotal = (float) db()->query("SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE status = 'pago' AND MONTH(COALESCE(pago_em, criado_em)) = MONTH(CURRENT_DATE) AND YEAR(COALESCE(pago_em, criado_em)) = YEAR(CURRENT_DATE)")->fetchColumn();
    $pendingTotal = (float) db()->query("SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE status = 'pendente'")->fetchColumn();
    $todayTotal = (float) db()->query("SELECT COALESCE(SUM(valor),0) FROM pagamentos WHERE status = 'pago' AND DATE(COALESCE(pago_em, criado_em)) = CURRENT_DATE")->fetchColumn();
    $payments = db()->query("SELECT p.*, c.nome AS cliente, c.telefone FROM pagamentos p LEFT JOIN clientes c ON c.id = p.cliente_id ORDER BY p.id DESC LIMIT 40")->fetchAll();
} catch (Throwable $exception) {
    $error = 'Banco ainda não configurado.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'pagamento') {
    $action = $_POST['acao'] ?? 'criar';
    try {
        if ($action === 'pagar') {
            db()->prepare("UPDATE pagamentos SET status = 'pago', pago_em = NOW() WHERE id = ?")->execute([(int) $_POST['pagamento_id']]);
            flash('success', 'Pagamento confirmado.');
            header('Location: ?page=financeiro');
            exit;
        }

        $clienteId = (int) ($_POST['cliente_id'] ?? 0);
        $valor = (float) str_replace(['.', ','], ['', '.'], (string) ($_POST['valor'] ?? '0'));
        $forma = $_POST['forma_pagamento'] ?? 'pix';
        $descricao = trim((string) ($_POST['descricao'] ?? 'Atendimento'));
        if ($clienteId < 1 || $valor <= 0) {
            $error = 'Selecione o cliente e informe um valor maior que zero.';
        } elseif ($forma === 'pix') {
            $pagamento = criarCobrancaPix($clienteId, $valor, $descricao ?: 'Atendimento');
            $_SESSION['ultimo_pix'] = $pagamento['id'];
            flash('success', 'PIX gerado. Envie no WhatsApp ou peça para o cliente pagar no portal.');
            header('Location: ?page=financeiro');
            exit;
        } else {
            db()->prepare('INSERT INTO pagamentos (cliente_id, valor, forma_pagamento, status, descricao, pago_em) VALUES (?,?,?,?,?,NOW())')->execute([
                $clienteId, $valor, $forma, 'pago', $descricao ?: 'Atendimento',
            ]);
            flash('success', 'Pagamento registrado.');
            header('Location: ?page=financeiro');
            exit;
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
?>
<section class="welcome-row">
    <div>
        <p class="eyebrow">CAIXA</p>
        <h1>Financeiro</h1>
        <p class="muted">Gere PIX, registre recebimentos e acompanhe o mês.</p>
    </div>
</section>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if ($flash): ?><div class="alert alert-success"><?= e($flash['message']) ?></div><?php endif; ?>
<?php
$ultimoPixId = (int) ($_SESSION['ultimo_pix'] ?? 0);
$ultimoPix = null;
if ($ultimoPixId > 0) {
    foreach ($payments as $item) {
        if ((int) $item['id'] === $ultimoPixId) {
            $ultimoPix = $item;
            break;
        }
    }
}
if ($ultimoPix && !empty($ultimoPix['telefone'])):
?>
    <div class="alert alert-success d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>PIX de <?= e(brl($ultimoPix['valor'])) ?> pronto para <?= e($ultimoPix['cliente']) ?>.</span>
        <?= whatsappButton((string) $ultimoPix['telefone'], mensagemPix(['nome' => $ultimoPix['cliente'], 'telefone' => $ultimoPix['telefone']], $ultimoPix), 'Enviar PIX no WhatsApp') ?>
    </div>
<?php endif; ?>
<section class="stats-grid">
    <article class="stat-card"><div class="stat-icon mint"><i class="bi bi-cash-coin"></i></div><div><span>Recebido hoje</span><strong><?= e(brl($todayTotal)) ?></strong></div></article>
    <article class="stat-card"><div class="stat-icon peach"><i class="bi bi-graph-up"></i></div><div><span>Faturamento do mês</span><strong><?= e(brl($monthTotal)) ?></strong></div></article>
    <article class="stat-card"><div class="stat-icon lilac"><i class="bi bi-hourglass-split"></i></div><div><span>A receber</span><strong><?= e(brl($pendingTotal)) ?></strong></div></article>
</section>
<div class="content-grid form-grid">
    <section class="panel">
        <div class="panel-heading"><div><h2>Nova cobrança</h2><p>PIX gera QR e código copia-e-cola.</p></div></div>
        <form method="post">
            <input type="hidden" name="formulario" value="pagamento">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Cliente *</label>
                    <select class="form-select" name="cliente_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($clients as $item): ?>
                            <option value="<?= (int) $item['id'] ?>" <?= $selectedClient === (int) $item['id'] ? 'selected' : '' ?>><?= e($item['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Valor *</label><input class="form-control" name="valor" placeholder="0,00" required></div>
                <div class="col-md-6">
                    <label class="form-label">Forma</label>
                    <select class="form-select" name="forma_pagamento">
                        <option value="pix">PIX</option>
                        <option value="dinheiro">Dinheiro</option>
                        <option value="cartao">Cartão</option>
                        <option value="transferencia">Transferência</option>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Descrição</label><input class="form-control" name="descricao" placeholder="Ex.: Podologia preventiva"></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Gerar cobrança</button></div>
        </form>
    </section>
    <section class="panel">
        <div class="panel-heading"><div><h2>Movimentações</h2><p>Últimas cobranças</p></div></div>
        <div class="table-responsive">
            <table class="table clients-table">
                <thead><tr><th>Cliente</th><th>Valor</th><th>Forma</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (!$payments): ?><tr><td colspan="5" class="empty-table">Nenhuma cobrança ainda.</td></tr><?php endif; ?>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><strong><?= e($payment['cliente'] ?: 'Avulso') ?></strong><small><?= e($payment['descricao'] ?: '') ?></small></td>
                        <td><?= e(brl($payment['valor'])) ?></td>
                        <td><?= e(strtoupper($payment['forma_pagamento'])) ?></td>
                        <td><span class="status <?= e(statusClass($payment['status'])) ?>"><?= e(statusLabel($payment['status'])) ?></span></td>
                        <td>
                            <?php if ($payment['status'] === 'pendente'): ?>
                                <form method="post">
                                    <input type="hidden" name="formulario" value="pagamento">
                                    <input type="hidden" name="acao" value="pagar">
                                    <input type="hidden" name="pagamento_id" value="<?= (int) $payment['id'] ?>">
                                    <button class="btn btn-light btn-sm" type="submit">Confirmar</button>
                                </form>
                            <?php endif; ?>
                            <?php if (can('imprimir')): ?>
                                <a class="btn btn-light btn-sm" href="imprimir.php?tipo=recibo&id=<?= (int) $payment['id'] ?>" target="_blank">Recibo</a>
                            <?php endif; ?>
                            <?php if ($payment['pix_payload']): ?>
                                <button class="btn btn-light btn-sm js-open-pix" type="button" data-payload="<?= e($payment['pix_payload']) ?>" data-value="<?= e(brl($payment['valor'])) ?>">Ver PIX</button>
                                <?php if (!empty($payment['telefone'])): ?>
                                    <?= whatsappButton((string) $payment['telefone'], mensagemPix(['nome' => $payment['cliente'] ?: 'cliente', 'telefone' => $payment['telefone']], $payment), 'WhatsApp') ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<div class="modal fade" id="pixModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <h3 class="modal-title mb-2">PIX para recebimento</h3>
            <p class="muted" id="pixValueLabel"></p>
            <canvas id="pixCanvas" class="pix-canvas"></canvas>
            <textarea class="form-control mt-3" id="pixPayload" rows="4" readonly></textarea>
            <button class="btn btn-primary mt-3" type="button" id="copyPix">Copiar código</button>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script src="assets/js/pix.js"></script>
