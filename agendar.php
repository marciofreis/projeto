<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$clinic = clinica();
$clinicName = $clinic['nome'] ?: 'Podocare';
$clinicLogo = (string) ($clinic['logo_path'] ?? '');
$error = null;
$sucesso = null;
$clienteLogado = clienteLogado();

$services = [];
try {
    $services = db()->query('SELECT id, nome, categoria, duracao_minutos, preco FROM servicos WHERE ativo = 1 ORDER BY categoria, nome')->fetchAll();
} catch (Throwable $exception) {
    $error = 'Agenda temporariamente indisponível.';
}

$form = [
    'nome' => $clienteLogado['nome'] ?? '',
    'telefone' => $clienteLogado['telefone'] ?? '',
    'email' => $clienteLogado['email'] ?? '',
    'servico_id' => (int) ($_GET['servico_id'] ?? $_POST['servico_id'] ?? 0),
    'data' => (string) ($_GET['data'] ?? $_POST['data'] ?? date('Y-m-d')),
    'horario' => (string) ($_POST['horario'] ?? ''),
];

$servico = null;
foreach ($services as $item) {
    if ((int) $item['id'] === $form['servico_id']) {
        $servico = $item;
        break;
    }
}

$horarios = $servico ? horariosLivres($form['data'], (int) $servico['duracao_minutos']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'agendar') {
    $form['nome'] = trim((string) ($_POST['nome'] ?? ''));
    $form['telefone'] = trim((string) ($_POST['telefone'] ?? ''));
    $form['email'] = trim((string) ($_POST['email'] ?? ''));
    $form['horario'] = trim((string) ($_POST['horario'] ?? ''));

    if ($form['nome'] === '' || $form['telefone'] === '' || !$servico || $form['horario'] === '') {
        $error = 'Preencha nome, telefone, serviço e um horário disponível.';
    } elseif (!in_array($form['horario'], $horarios, true)) {
        $error = 'Esse horário acabou de ser ocupado. Escolha outro.';
    } else {
        try {
            $inicio = $form['data'] . ' ' . $form['horario'] . ':00';
            if (!horarioAindaLivre($inicio, (int) $servico['duracao_minutos'])) {
                throw new RuntimeException('Esse horário não está mais disponível.');
            }

            $conta = $clienteLogado
                ? ['cliente' => $clienteLogado, 'senha' => null, 'novo' => false]
                : findOrCreateCliente($form['nome'], $form['telefone'], $form['email']);
            $cliente = $conta['cliente'];

            db()->prepare('INSERT INTO agendamentos (cliente_id, servico_id, inicio, status, observacoes) VALUES (?, ?, ?, ?, ?)')->execute([
                (int) $cliente['id'],
                (int) $servico['id'],
                $inicio,
                'agendado',
                'Agendamento online',
            ]);
            $agendamentoId = (int) db()->lastInsertId();

            $pagamento = null;
            if (!empty($clinic['sinal_online']) && trim((string) ($clinic['pix_chave'] ?? '')) !== '') {
                $valorSinal = (float) ($clinic['sinal_valor'] ?: $servico['preco']);
                if ($valorSinal > 0) {
                    $pagamento = criarCobrancaPix((int) $cliente['id'], $valorSinal, 'Sinal · ' . $servico['nome'], $agendamentoId);
                }
            }

            $sucesso = [
                'cliente' => $cliente,
                'servico' => $servico,
                'inicio' => $inicio,
                'pagamento' => $pagamento,
                'senha' => $conta['senha'],
            ];
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <?php require __DIR__ . '/includes/pwa-head.php'; ?>
    <title>Agendar | <?= e($clinicName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(assetUrl('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
    <main class="auth-card booking-card">
        <div class="auth-brand">
            <?php if ($clinicLogo): ?><img src="<?= e($clinicLogo) ?>" alt="Logo"><?php else: ?><span class="brand-mark">P</span><?php endif; ?>
            <h1><?= e($clinicName) ?></h1>
            <p>Escolha o serviço e o melhor horário</p>
            <p class="muted"><?= e(resumoHorarioClinica()) ?></p>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success">Horário reservado. Confirme no WhatsApp para garantir sua vaga.</div>
            <div class="info-list mb-3">
                <p><strong>Serviço</strong> <?= e($sucesso['servico']['nome']) ?></p>
                <p><strong>Quando</strong> <?= e(date('d/m/Y H:i', strtotime($sucesso['inicio']))) ?></p>
                <p><strong>Valor</strong> <?= e(brl($sucesso['servico']['preco'])) ?></p>
            </div>
            <?php if ($sucesso['pagamento']): ?>
                <p class="muted">Sinal via PIX: <?= e(brl($sucesso['pagamento']['valor'])) ?></p>
                <canvas class="pix-canvas js-pix-auto" data-payload="<?= e($sucesso['pagamento']['pix_payload']) ?>"></canvas>
                <textarea class="form-control mb-3" rows="3" readonly><?= e($sucesso['pagamento']['pix_payload']) ?></textarea>
            <?php endif; ?>
            <?php if ($sucesso['senha']): ?>
                <p class="muted">Acesso ao portal: telefone <?= e($sucesso['cliente']['telefone']) ?> · senha <strong><?= e($sucesso['senha']) ?></strong></p>
            <?php endif; ?>
            <div class="welcome-actions">
                <?= whatsappButton($sucesso['cliente']['telefone'], mensagemAgendamento($sucesso['cliente'], ['inicio' => $sucesso['inicio'], 'servico' => $sucesso['servico']['nome']], $sucesso['pagamento'], $sucesso['senha']), 'Salvar no WhatsApp') ?>
                <a class="btn btn-light" href="portal.php">Abrir portal</a>
            </div>
        <?php else: ?>
            <form method="get" class="mb-3" id="filtroAgenda">
                <label class="form-label">Serviço</label>
                <select class="form-select" name="servico_id" onchange="this.form.submit()">
                    <option value="">Selecione</option>
                    <?php foreach ($services as $item): ?>
                        <option value="<?= (int) $item['id'] ?>" <?= $form['servico_id'] === (int) $item['id'] ? 'selected' : '' ?>>
                            <?= e($item['nome']) ?> · <?= e(brl($item['preco'])) ?> · <?= (int) $item['duracao_minutos'] ?> min
                        </option>
                    <?php endforeach; ?>
                </select>
                <label class="form-label mt-3">Data</label>
                <input class="form-control" type="date" name="data" min="<?= e(date('Y-m-d')) ?>" value="<?= e($form['data']) ?>" onchange="this.form.submit()">
            </form>

            <form method="post">
                <input type="hidden" name="formulario" value="agendar">
                <input type="hidden" name="servico_id" value="<?= (int) $form['servico_id'] ?>">
                <input type="hidden" name="data" value="<?= e($form['data']) ?>">

                <?php if ($servico && !$horarios): ?>
                    <p class="muted"><?= diaEstaAberto($form['data']) ? 'Não há horários livres neste dia. Tente outra data.' : 'A clínica não atende neste dia da semana. Escolha outra data.' ?></p>
                <?php elseif ($servico): ?>
                    <label class="form-label">Horários livres</label>
                    <div class="slot-grid">
                        <?php foreach ($horarios as $hora): ?>
                            <label class="slot <?= $form['horario'] === $hora ? 'active' : '' ?>">
                                <input type="radio" name="horario" value="<?= e($hora) ?>" <?= $form['horario'] === $hora ? 'checked' : '' ?> required>
                                <?= e($hora) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <label class="form-label mt-3">Seu nome *</label>
                <input class="form-control" name="nome" value="<?= e($form['nome']) ?>" required>
                <label class="form-label mt-3">WhatsApp *</label>
                <input class="form-control" name="telefone" value="<?= e($form['telefone']) ?>" required>
                <label class="form-label mt-3">E-mail</label>
                <input class="form-control" type="email" name="email" value="<?= e((string) $form['email']) ?>">
                <button class="btn btn-primary w-100 mt-4" type="submit" <?= $servico && $horarios ? '' : 'disabled' ?>>Confirmar horário</button>
            </form>
        <?php endif; ?>

        <a class="auth-switch" href="portal.php">Já sou cliente e quero entrar no portal</a>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script src="<?= e(assetUrl('assets/js/pix.js')) ?>"></script>
    <script src="<?= e(assetUrl('assets/js/pwa.js')) ?>"></script>
</body>
</html>
