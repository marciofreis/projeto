<?php

require_once __DIR__ . '/../config/database.php';

$settingsError = null;
$settingsSuccess = null;
$settings = clinica();
$diasSelecionados = parseDiasAbertos((string) ($settings['dias_abertos'] ?? '1,2,3,4,5,6'));
$padroes = mensagensPadrao();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['formulario'] ?? '') === 'clinica') {
    $diasSelecionados = array_map('intval', (array) ($_POST['dias_abertos'] ?? []));
    $diasSelecionados = array_values(array_intersect($diasSelecionados, [0, 1, 2, 3, 4, 5, 6]));
    $intervalo = (int) ($_POST['intervalo_minutos'] ?? 30);
    if (!in_array($intervalo, [15, 20, 30, 45, 60], true)) {
        $intervalo = 30;
    }

    $settings = array_merge($settings, [
        'nome' => trim((string) ($_POST['nome'] ?? '')),
        'tipo' => $_POST['tipo'] ?? 'podologia',
        'telefone' => trim((string) ($_POST['telefone'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'endereco' => trim((string) ($_POST['endereco'] ?? '')),
        'pix_chave' => trim((string) ($_POST['pix_chave'] ?? '')),
        'pix_tipo' => $_POST['pix_tipo'] ?? 'aleatoria',
        'pix_nome' => trim((string) ($_POST['pix_nome'] ?? '')),
        'pix_cidade' => trim((string) ($_POST['pix_cidade'] ?? '')),
        'horario_inicio' => trim((string) ($_POST['horario_inicio'] ?? '08:00')),
        'horario_fim' => trim((string) ($_POST['horario_fim'] ?? '18:00')),
        'sinal_online' => isset($_POST['sinal_online']) ? '1' : '0',
        'sinal_valor' => trim((string) ($_POST['sinal_valor'] ?? '')),
        'cor_primaria' => normalizeHexColor((string) ($_POST['cor_primaria'] ?? '#1f5e50')),
        'dias_abertos' => implode(',', $diasSelecionados),
        'almoco_inicio' => trim((string) ($_POST['almoco_inicio'] ?? '')),
        'almoco_fim' => trim((string) ($_POST['almoco_fim'] ?? '')),
        'intervalo_minutos' => $intervalo,
        'msg_portal' => trim((string) ($_POST['msg_portal'] ?? '')),
        'msg_pix' => trim((string) ($_POST['msg_pix'] ?? '')),
        'msg_agendamento' => trim((string) ($_POST['msg_agendamento'] ?? '')),
        'msg_link' => trim((string) ($_POST['msg_link'] ?? '')),
    ]);

    if ($settings['nome'] === '') {
        $settingsError = 'Informe o nome da clínica ou salão.';
    } elseif ($diasSelecionados === []) {
        $settingsError = 'Marque pelo menos um dia de atendimento.';
    } else {
        try {
            $logoPath = uploadImage($_FILES['logo'] ?? [], 'clinica', 'logo');
            if ($logoPath === '') {
                $logoPath = $settings['logo_path'];
            }
            $sinalValor = $settings['sinal_valor'] !== '' ? (float) str_replace(['.', ','], ['', '.'], $settings['sinal_valor']) : null;
            $almocoInicio = $settings['almoco_inicio'] !== '' ? $settings['almoco_inicio'] : null;
            $almocoFim = $settings['almoco_fim'] !== '' ? $settings['almoco_fim'] : null;
            $salvarMsg = static function (string $tipo, string $texto) use ($padroes): ?string {
                $texto = trim($texto);
                if ($texto === '' || $texto === $padroes[$tipo]) {
                    return null;
                }

                return $texto;
            };

            $statement = db()->prepare('INSERT INTO clinicas (
                id, nome, tipo, telefone, email, endereco, logo_path, pix_chave, pix_tipo, pix_nome, pix_cidade,
                horario_inicio, horario_fim, sinal_online, sinal_valor, cor_primaria, dias_abertos, almoco_inicio, almoco_fim,
                intervalo_minutos, msg_portal, msg_pix, msg_agendamento, msg_link
            ) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                nome = VALUES(nome), tipo = VALUES(tipo), telefone = VALUES(telefone), email = VALUES(email),
                endereco = VALUES(endereco), logo_path = VALUES(logo_path), pix_chave = VALUES(pix_chave),
                pix_tipo = VALUES(pix_tipo), pix_nome = VALUES(pix_nome), pix_cidade = VALUES(pix_cidade),
                horario_inicio = VALUES(horario_inicio), horario_fim = VALUES(horario_fim),
                sinal_online = VALUES(sinal_online), sinal_valor = VALUES(sinal_valor),
                cor_primaria = VALUES(cor_primaria), dias_abertos = VALUES(dias_abertos),
                almoco_inicio = VALUES(almoco_inicio), almoco_fim = VALUES(almoco_fim),
                intervalo_minutos = VALUES(intervalo_minutos), msg_portal = VALUES(msg_portal),
                msg_pix = VALUES(msg_pix), msg_agendamento = VALUES(msg_agendamento), msg_link = VALUES(msg_link)');
            $statement->execute([
                $settings['nome'], $settings['tipo'], $settings['telefone'] ?: null, $settings['email'] ?: null,
                $settings['endereco'] ?: null, $logoPath ?: null, $settings['pix_chave'] ?: null, $settings['pix_tipo'],
                $settings['pix_nome'] ?: null, $settings['pix_cidade'] ?: null,
                $settings['horario_inicio'] ?: '08:00', $settings['horario_fim'] ?: '18:00',
                $settings['sinal_online'], $sinalValor, $settings['cor_primaria'], $settings['dias_abertos'],
                $almocoInicio, $almocoFim, $intervalo,
                $salvarMsg('portal', $settings['msg_portal']),
                $salvarMsg('pix', $settings['msg_pix']),
                $salvarMsg('agendamento', $settings['msg_agendamento']),
                $salvarMsg('link', $settings['msg_link']),
            ]);
            $settings['logo_path'] = $logoPath;
            $settings['intervalo_minutos'] = $intervalo;
            clinica(true);
            $settingsSuccess = 'Configurações salvas. Cores, horários e textos do WhatsApp já valem no app.';
        } catch (Throwable $exception) {
            $settingsError = $exception->getMessage();
        }
    }
}

$corAtual = normalizeHexColor((string) ($settings['cor_primaria'] ?? '#1f5e50'));
$msgPortal = trim((string) ($settings['msg_portal'] ?? '')) !== '' ? (string) $settings['msg_portal'] : templateMensagem('portal');
$msgPix = trim((string) ($settings['msg_pix'] ?? '')) !== '' ? (string) $settings['msg_pix'] : templateMensagem('pix');
$msgAgendamento = trim((string) ($settings['msg_agendamento'] ?? '')) !== '' ? (string) $settings['msg_agendamento'] : templateMensagem('agendamento');
$msgLink = trim((string) ($settings['msg_link'] ?? '')) !== '' ? (string) $settings['msg_link'] : templateMensagem('link');
?>
<section class="welcome-row"><div><p class="eyebrow">IDENTIDADE DO ESPAÇO</p><h1>Clínica ou salão</h1><p class="muted">Cores, horários e mensagens do WhatsApp ficam com a cara do seu espaço.</p></div></section>
<?php if ($settingsError): ?><div class="alert alert-danger"><?= e($settingsError) ?></div><?php endif; ?>
<?php if ($settingsSuccess): ?><div class="alert alert-success"><?= e($settingsSuccess) ?></div><?php endif; ?>
<section class="panel settings-panel">
    <div class="panel-heading"><div><h2>Dados do estabelecimento</h2><p>Identidade básica do seu espaço.</p></div></div>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="formulario" value="clinica">
        <div class="brand-upload">
            <div class="logo-preview"><?php if ($settings['logo_path']): ?><img src="<?= e($settings['logo_path']) ?>" alt="Logo da clínica"><?php else: ?><i class="bi bi-building"></i><?php endif; ?></div>
            <div><label class="form-label">Logo da clínica ou salão</label><input class="form-control form-control-sm" type="file" name="logo" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG ou WEBP. Máximo 3 MB.</small></div>
        </div>
        <div class="row g-3">
            <div class="col-md-8"><label class="form-label">Nome do espaço *</label><input class="form-control" name="nome" value="<?= e($settings['nome']) ?>" required></div>
            <div class="col-md-4">
                <label class="form-label">Tipo de negócio</label>
                <select class="form-select" name="tipo">
                    <option value="podologia" <?= $settings['tipo'] === 'podologia' ? 'selected' : '' ?>>Clínica de podologia</option>
                    <option value="salao" <?= $settings['tipo'] === 'salao' ? 'selected' : '' ?>>Salão de beleza</option>
                    <option value="misto" <?= $settings['tipo'] === 'misto' ? 'selected' : '' ?>>Podologia e salão</option>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">Telefone</label><input class="form-control" name="telefone" value="<?= e((string) $settings['telefone']) ?>"></div>
            <div class="col-md-6"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" value="<?= e((string) $settings['email']) ?>"></div>
            <div class="col-12"><label class="form-label">Endereço</label><input class="form-control" name="endereco" value="<?= e((string) $settings['endereco']) ?>"></div>

            <div class="col-12"><hr><strong>Cores da marca</strong><p class="muted">Botões, menu, portal e ícone do app usam esta cor.</p></div>
            <div class="col-md-4">
                <label class="form-label">Cor principal</label>
                <div class="color-row">
                    <input class="form-control form-control-color" type="color" name="cor_primaria" id="corPrimaria" value="<?= e($corAtual) ?>">
                    <span class="muted" id="corHex"><?= e($corAtual) ?></span>
                </div>
            </div>
            <div class="col-md-8">
                <label class="form-label">Prontas para usar</label>
                <div class="color-presets">
                    <?php foreach (coresProntas() as $hex => $label): ?>
                        <button type="button" class="color-swatch <?= $corAtual === $hex ? 'active' : '' ?>" data-color="<?= e($hex) ?>" style="background:<?= e($hex) ?>" title="<?= e($label) ?>" aria-label="<?= e($label) ?>"></button>
                    <?php endforeach; ?>
                </div>
                <div class="theme-preview" id="themePreview">
                    <button class="btn btn-primary" type="button">Botão</button>
                    <span class="status confirmed">Confirmado</span>
                    <span class="muted">Prévia da cor</span>
                </div>
            </div>

            <div class="col-12"><hr><strong>Recebimento PIX</strong><p class="muted">Usado para gerar QR Code e copia-e-cola para o cliente.</p></div>
            <div class="col-md-4">
                <label class="form-label">Tipo da chave</label>
                <select class="form-select" name="pix_tipo">
                    <option value="aleatoria" <?= $settings['pix_tipo'] === 'aleatoria' ? 'selected' : '' ?>>Chave aleatória</option>
                    <option value="cpf" <?= $settings['pix_tipo'] === 'cpf' ? 'selected' : '' ?>>CPF</option>
                    <option value="cnpj" <?= $settings['pix_tipo'] === 'cnpj' ? 'selected' : '' ?>>CNPJ</option>
                    <option value="email" <?= $settings['pix_tipo'] === 'email' ? 'selected' : '' ?>>E-mail</option>
                    <option value="telefone" <?= $settings['pix_tipo'] === 'telefone' ? 'selected' : '' ?>>Telefone</option>
                </select>
            </div>
            <div class="col-md-8"><label class="form-label">Chave PIX</label><input class="form-control" name="pix_chave" value="<?= e((string) $settings['pix_chave']) ?>" placeholder="Cole sua chave"></div>
            <div class="col-md-6"><label class="form-label">Nome no PIX (até 25 caracteres)</label><input class="form-control" name="pix_nome" maxlength="25" value="<?= e((string) $settings['pix_nome']) ?>"></div>
            <div class="col-md-6"><label class="form-label">Cidade no PIX</label><input class="form-control" name="pix_cidade" maxlength="15" value="<?= e((string) $settings['pix_cidade']) ?>"></div>

            <div class="col-12"><hr><strong>Dias e horários</strong><p class="muted">Controla a agenda da clínica e os horários livres no link público.</p></div>
            <div class="col-12">
                <label class="form-label">Dias de atendimento</label>
                <div class="week-days">
                    <?php foreach (diasSemana() as $numero => $label): ?>
                        <label class="week-day">
                            <input type="checkbox" name="dias_abertos[]" value="<?= (int) $numero ?>" <?= in_array($numero, $diasSelecionados, true) ? 'checked' : '' ?>>
                            <?= e($label) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-3"><label class="form-label">Abre às</label><input class="form-control" type="time" name="horario_inicio" value="<?= e(substr((string) $settings['horario_inicio'], 0, 5)) ?>"></div>
            <div class="col-md-3"><label class="form-label">Fecha às</label><input class="form-control" type="time" name="horario_fim" value="<?= e(substr((string) $settings['horario_fim'], 0, 5)) ?>"></div>
            <div class="col-md-3"><label class="form-label">Almoço de</label><input class="form-control" type="time" name="almoco_inicio" value="<?= e(substr((string) ($settings['almoco_inicio'] ?? ''), 0, 5)) ?>"></div>
            <div class="col-md-3"><label class="form-label">Almoço até</label><input class="form-control" type="time" name="almoco_fim" value="<?= e(substr((string) ($settings['almoco_fim'] ?? ''), 0, 5)) ?>"></div>
            <div class="col-md-4">
                <label class="form-label">Intervalo entre horários</label>
                <select class="form-select" name="intervalo_minutos">
                    <?php foreach ([15, 20, 30, 45, 60] as $minutos): ?>
                        <option value="<?= $minutos ?>" <?= (int) ($settings['intervalo_minutos'] ?? 30) === $minutos ? 'selected' : '' ?>><?= $minutos ?> minutos</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">Sinal (opcional)</label><input class="form-control" name="sinal_valor" value="<?= e((string) ($settings['sinal_valor'] ?? '')) ?>" placeholder="Vazio = valor do serviço"></div>
            <div class="col-md-4 d-flex align-items-end"><label class="check-line"><input type="checkbox" name="sinal_online" value="1" <?= !empty($settings['sinal_online']) ? 'checked' : '' ?>> Pedir PIX de sinal no agendamento online</label></div>
            <div class="col-12">
                <label class="form-label">Link público</label>
                <div class="copy-row">
                    <input class="form-control" id="bookingLink" value="<?= e(phoneAccessUrl('agendar.php')) ?>" readonly>
                    <button class="btn btn-light" type="button" id="copyBookingLink">Copiar</button>
                    <a class="btn btn-whatsapp" href="<?= e(whatsappLink('', mensagemLinkAgenda())) ?>" target="_blank" rel="noopener">WhatsApp</a>
                </div>
            </div>

            <div class="col-12"><hr><strong>Textos do WhatsApp</strong><p class="muted">Use os códigos abaixo. Deixe em branco ou restaure o padrão se quiser voltar ao original.</p></div>
            <div class="col-12"><p class="placeholder-chips"><span>{nome}</span><span>{clinica}</span><span>{telefone}</span><span>{senha}</span><span>{portal}</span><span>{link}</span><span>{servico}</span><span>{quando}</span><span>{endereco}</span><span>{valor}</span><span>{pix}</span></p></div>
            <div class="col-12">
                <div class="msg-head"><label class="form-label">Portal do cliente</label><button class="btn btn-light btn-sm js-restore-msg" type="button" data-target="msgPortal" data-default="<?= e($padroes['portal']) ?>">Restaurar padrão</button></div>
                <textarea class="form-control" name="msg_portal" id="msgPortal" rows="6"><?= e($msgPortal) ?></textarea>
            </div>
            <div class="col-12">
                <div class="msg-head"><label class="form-label">PIX</label><button class="btn btn-light btn-sm js-restore-msg" type="button" data-target="msgPix" data-default="<?= e($padroes['pix']) ?>">Restaurar padrão</button></div>
                <textarea class="form-control" name="msg_pix" id="msgPix" rows="6"><?= e($msgPix) ?></textarea>
            </div>
            <div class="col-12">
                <div class="msg-head"><label class="form-label">Confirmação de horário</label><button class="btn btn-light btn-sm js-restore-msg" type="button" data-target="msgAgendamento" data-default="<?= e($padroes['agendamento']) ?>">Restaurar padrão</button></div>
                <textarea class="form-control" name="msg_agendamento" id="msgAgendamento" rows="8"><?= e($msgAgendamento) ?></textarea>
            </div>
            <div class="col-12">
                <div class="msg-head"><label class="form-label">Link de agendamento</label><button class="btn btn-light btn-sm js-restore-msg" type="button" data-target="msgLink" data-default="<?= e($padroes['link']) ?>">Restaurar padrão</button></div>
                <textarea class="form-control" name="msg_link" id="msgLink" rows="3"><?= e($msgLink) ?></textarea>
            </div>
        </div>
        <div class="form-actions"><button class="btn btn-primary" type="submit">Salvar configurações</button></div>
    </form>
    <?php require __DIR__ . '/../includes/phone-access.php'; ?>
</section>
<script>
(function () {
    const colorInput = document.getElementById('corPrimaria');
    const colorHex = document.getElementById('corHex');
    const mix = (hex, withHex, amount) => {
        const toRgb = (value) => [1, 3, 5].map((i) => parseInt(value.slice(i, i + 2), 16));
        const a = toRgb(hex.startsWith('#') ? hex : '#' + hex);
        const b = toRgb(withHex);
        return '#' + a.map((n, i) => Math.round(n + (b[i] - n) * amount).toString(16).padStart(2, '0')).join('');
    };
    const applyPreview = (hex) => {
        const root = document.documentElement;
        root.style.setProperty('--dark-green', hex);
        root.style.setProperty('--green', mix(hex, '#ffffff', 0.12));
        root.style.setProperty('--mint', mix(hex, '#ffffff', 0.82));
        root.style.setProperty('--cream', mix(hex, '#f7faf8', 0.92));
        root.style.setProperty('--brand-hover', mix(hex, '#000000', 0.18));
        root.style.setProperty('--brand-soft', mix(hex, '#ffffff', 0.88));
        root.style.setProperty('--brand-mid', mix(hex, '#ffffff', 0.55));
        root.style.setProperty('--brand-focus', mix(hex, '#ffffff', 0.78));
        if (colorHex) colorHex.textContent = hex;
        document.querySelectorAll('.color-swatch').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.color === hex);
        });
    };
    colorInput?.addEventListener('input', () => applyPreview(colorInput.value));
    document.querySelectorAll('.color-swatch').forEach((btn) => {
        btn.addEventListener('click', () => {
            colorInput.value = btn.dataset.color;
            applyPreview(btn.dataset.color);
        });
    });
    document.getElementById('copyBookingLink')?.addEventListener('click', () => {
        const field = document.getElementById('bookingLink');
        navigator.clipboard.writeText(field.value).then(() => {
            document.getElementById('copyBookingLink').textContent = 'Copiado';
        });
    });
    document.querySelectorAll('.js-restore-msg').forEach((btn) => {
        btn.addEventListener('click', () => {
            const field = document.getElementById(btn.dataset.target);
            if (field) field.value = btn.dataset.default;
        });
    });
})();
</script>
