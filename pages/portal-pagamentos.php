<?php

$payments = db()->prepare('SELECT valor, status, descricao, pix_payload, criado_em, pago_em FROM pagamentos WHERE cliente_id = ? ORDER BY id DESC');
$payments->execute([(int) $cliente['id']]);
$payments = $payments->fetchAll();
?>
<section class="welcome-row"><div><p class="eyebrow">PAGAMENTOS</p><h1>PIX e cobranças</h1><p class="muted">Pague pelo QR Code ou copie o código.</p></div></section>
<?php foreach ($payments as $payment): ?>
    <section class="panel mb-3">
        <div class="pay-row">
            <strong><?= e(brl($payment['valor'])) ?></strong>
            <span><?= e($payment['descricao'] ?: 'Atendimento') ?></span>
            <span class="status <?= e(statusClass($payment['status'])) ?>"><?= e(statusLabel($payment['status'])) ?></span>
        </div>
        <?php if ($payment['status'] === 'pendente' && $payment['pix_payload']): ?>
            <canvas class="pix-canvas js-pix-auto" data-payload="<?= e($payment['pix_payload']) ?>"></canvas>
            <textarea class="form-control mt-3" rows="3" readonly><?= e($payment['pix_payload']) ?></textarea>
            <div class="welcome-actions mt-3">
                <button class="btn btn-primary js-copy-payload" type="button">Copiar código PIX</button>
                <?= whatsappButton((string) $cliente['telefone'], mensagemPix($cliente, $payment), 'Enviar para meu WhatsApp') ?>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
<?php if (!$payments): ?>
    <section class="panel"><div class="empty-note"><i class="bi bi-wallet2"></i><h3>Nenhuma cobrança</h3><p>Quando a clínica gerar um PIX, ele aparece aqui.</p></div></section>
<?php endif; ?>
