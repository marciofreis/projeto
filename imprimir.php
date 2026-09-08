<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
require_once __DIR__ . '/includes/foot-map.php';

$staffUser = staff();
$clientePortal = clienteLogado();
if (!$staffUser && !$clientePortal) {
    header('Location: login.php');
    exit;
}

$tipo = $_GET['tipo'] ?? 'exame';
$id = (int) ($_GET['id'] ?? 0);
$versao = $_GET['versao'] ?? 'clinica';
$clinic = clinica();

if ($staffUser && !can('imprimir')) {
    flash('danger', 'Você não tem permissão para imprimir.');
    header('Location: index.php');
    exit;
}

if ($clientePortal) {
    $versao = 'cliente';
}

$titulo = 'Impressão';

if ($tipo === 'exame') {
    $exam = db()->prepare('SELECT p.*, c.nome AS cliente, c.telefone, c.anamnese_diabetes, c.anamnese_gestante, c.anamnese_alergia, c.anamnese_observacoes
        FROM prontuarios p JOIN clientes c ON c.id = p.cliente_id WHERE p.id = ?');
    $exam->execute([$id]);
    $exam = $exam->fetch();
    if (!$exam || ($clientePortal && (int) $exam['cliente_id'] !== (int) $clientePortal['id'])) {
        http_response_code(404);
        exit('Exame não encontrado.');
    }
    $marks = db()->prepare('SELECT pe, zona, tipo, observacao FROM prontuario_marcas WHERE prontuario_id = ?');
    $marks->execute([$id]);
    $marks = $marks->fetchAll();
    $photos = db()->prepare('SELECT momento, foto_path FROM prontuario_fotos WHERE prontuario_id = ? ORDER BY id');
    $photos->execute([$id]);
    $photos = $photos->fetchAll();
    $titulo = 'Exame · ' . $exam['cliente'] . ' · ' . date('d/m/Y', strtotime((string) $exam['data_exame']));
} elseif ($tipo === 'ficha') {
    if (!$staffUser || !can('clientes')) {
        header('Location: index.php');
        exit;
    }
    $client = db()->prepare('SELECT * FROM clientes WHERE id = ?');
    $client->execute([$id]);
    $client = $client->fetch();
    if (!$client) {
        exit('Cliente não encontrado.');
    }
    $exams = db()->prepare('SELECT id, data_exame, queixa FROM prontuarios WHERE cliente_id = ? ORDER BY data_exame DESC');
    $exams->execute([$id]);
    $exams = $exams->fetchAll();
    $titulo = 'Ficha · ' . $client['nome'];
} elseif ($tipo === 'recibo') {
    $pay = db()->prepare('SELECT p.*, c.nome AS cliente, c.telefone FROM pagamentos p LEFT JOIN clientes c ON c.id = p.cliente_id WHERE p.id = ?');
    $pay->execute([$id]);
    $pay = $pay->fetch();
    if (!$pay || ($clientePortal && (int) $pay['cliente_id'] !== (int) $clientePortal['id'])) {
        exit('Recibo não encontrado.');
    }
    if ($staffUser && !can('financeiro') && !can('imprimir')) {
        header('Location: index.php');
        exit;
    }
    $titulo = 'Recibo · ' . brl($pay['valor']);
} else {
    exit('Tipo de impressão inválido.');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php $clinicName = $clinic['nome'] ?? 'Podocare'; require __DIR__ . '/includes/pwa-head.php'; ?>
    <title><?= e($titulo) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="<?= e(assetUrl('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="print-body">
    <div class="print-toolbar no-print">
        <button class="btn btn-primary" type="button" onclick="window.print()">Imprimir</button>
        <button class="btn btn-light" type="button" onclick="history.back()">Voltar</button>
    </div>
    <article class="print-sheet">
        <header class="print-head">
            <?php if (!empty($clinic['logo_path'])): ?><img src="<?= e($clinic['logo_path']) ?>" alt="Logo"><?php endif; ?>
            <div>
                <h1><?= e($clinic['nome']) ?></h1>
                <p><?= e($clinic['endereco'] ?: '') ?><?= !empty($clinic['telefone']) ? ' · ' . e($clinic['telefone']) : '' ?></p>
            </div>
        </header>

        <?php if ($tipo === 'exame'): ?>
            <h2>Prontuário podológico</h2>
            <p><strong>Paciente:</strong> <?= e($exam['cliente']) ?> · <?= e($exam['telefone']) ?></p>
            <p><strong>Data:</strong> <?= e(date('d/m/Y', strtotime((string) $exam['data_exame']))) ?></p>
            <?php if ($exam['queixa']): ?><p><strong>Queixa / procedimento:</strong> <?= e($exam['queixa']) ?></p><?php endif; ?>
            <?php if ($versao === 'clinica'): ?>
                <p><strong>Anamnese:</strong>
                    <?= !empty($exam['anamnese_diabetes']) ? 'Diabetes. ' : '' ?>
                    <?= !empty($exam['anamnese_gestante']) ? 'Gestante. ' : '' ?>
                    <?= $exam['anamnese_alergia'] ? 'Alergia: ' . e($exam['anamnese_alergia']) . '. ' : '' ?>
                    <?= e((string) $exam['anamnese_observacoes']) ?>
                </p>
                <?php if ($exam['notas_internas']): ?><p><strong>Notas internas:</strong> <?= nl2br(e($exam['notas_internas'])) ?></p><?php endif; ?>
            <?php endif; ?>
            <?php if ($exam['orientacao_cliente']): ?>
                <p><strong>Orientação ao paciente:</strong> <?= nl2br(e($exam['orientacao_cliente'])) ?></p>
            <?php endif; ?>
            <h3>Mapa do pé</h3>
            <?php renderFootMap($marks, false); ?>
            <?php if ($photos): ?>
                <h3>Fotos</h3>
                <div class="exam-photos">
                    <?php foreach ($photos as $photo): ?>
                        <figure>
                            <img src="<?= e($photo['foto_path']) ?>" alt="">
                            <figcaption><?= e(ucfirst($photo['momento'])) ?></figcaption>
                        </figure>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php elseif ($tipo === 'ficha'): ?>
            <h2>Ficha do paciente</h2>
            <p><strong>Nome:</strong> <?= e($client['nome']) ?></p>
            <p><strong>Telefone:</strong> <?= e($client['telefone']) ?><?= $client['email'] ? ' · ' . e($client['email']) : '' ?></p>
            <p><strong>Nascimento:</strong> <?= $client['data_nascimento'] ? e(date('d/m/Y', strtotime((string) $client['data_nascimento']))) : '—' ?></p>
            <p><strong>Diabetes:</strong> <?= $client['anamnese_diabetes'] ? 'Sim' : 'Não' ?> · <strong>Gestante:</strong> <?= $client['anamnese_gestante'] ? 'Sim' : 'Não' ?></p>
            <p><strong>Alergia:</strong> <?= e($client['anamnese_alergia'] ?: 'Não informada') ?></p>
            <?php if ($client['observacoes']): ?><p><strong>Observações:</strong> <?= nl2br(e($client['observacoes'])) ?></p><?php endif; ?>
            <h3>Exames</h3>
            <?php if (!$exams): ?><p>Nenhum exame registrado.</p><?php endif; ?>
            <ul>
                <?php foreach ($exams as $item): ?>
                    <li><?= e(date('d/m/Y', strtotime((string) $item['data_exame']))) ?> — <?= e($item['queixa'] ?: 'Exame clínico') ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <h2>Recibo</h2>
            <p><strong>Cliente:</strong> <?= e($pay['cliente'] ?: 'Avulso') ?></p>
            <p><strong>Descrição:</strong> <?= e($pay['descricao'] ?: 'Atendimento') ?></p>
            <p><strong>Valor:</strong> <?= e(brl($pay['valor'])) ?></p>
            <p><strong>Forma:</strong> <?= e(strtoupper((string) $pay['forma_pagamento'])) ?> · <strong>Status:</strong> <?= e(statusLabel((string) $pay['status'])) ?></p>
            <p><strong>Data:</strong> <?= e(date('d/m/Y H:i', strtotime((string) ($pay['pago_em'] ?: $pay['criado_em'])))) ?></p>
        <?php endif; ?>

        <footer class="print-foot">
            <p>Documento emitido em <?= e(date('d/m/Y H:i')) ?><?= $staffUser ? ' por ' . e($staffUser['nome']) : '' ?>.</p>
        </footer>
    </article>
</body>
</html>
