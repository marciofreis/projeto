<?php

declare(strict_types=1);

function findClienteByPhone(string $telefone): ?array
{
    $digits = digits($telefone);
    if ($digits === '') {
        return null;
    }

    foreach (db()->query('SELECT * FROM clientes WHERE ativo = 1')->fetchAll() as $row) {
        if (digits((string) $row['telefone']) === $digits) {
            return $row;
        }
    }

    return null;
}

function findOrCreateCliente(string $nome, string $telefone, string $email = ''): array
{
    $existing = findClienteByPhone($telefone);
    if ($existing) {
        return ['cliente' => $existing, 'senha' => null, 'novo' => false];
    }

    $senha = senhaTemporaria();
    db()->prepare('INSERT INTO clientes (nome, telefone, email, senha_hash, portal_liberado) VALUES (?, ?, ?, ?, 1)')->execute([
        $nome,
        $telefone,
        $email !== '' ? $email : null,
        password_hash($senha, PASSWORD_DEFAULT),
    ]);

    $statement = db()->prepare('SELECT * FROM clientes WHERE id = ?');
    $statement->execute([(int) db()->lastInsertId()]);

    return ['cliente' => $statement->fetch(), 'senha' => $senha, 'novo' => true];
}

function horariosOcupados(string $data): array
{
    $statement = db()->prepare("SELECT a.inicio, s.duracao_minutos
        FROM agendamentos a
        JOIN servicos s ON s.id = a.servico_id
        WHERE DATE(a.inicio) = ? AND a.status <> 'cancelado'");
    $statement->execute([$data]);
    $ocupados = [];
    foreach ($statement->fetchAll() as $row) {
        $inicio = strtotime((string) $row['inicio']);
        $ocupados[] = [$inicio, $inicio + ((int) $row['duracao_minutos'] * 60)];
    }

    return $ocupados;
}

function diasSemana(): array
{
    return [0 => 'Domingo', 1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado'];
}

function parseDiasAbertos(?string $raw = null): array
{
    $raw ??= (string) (clinica()['dias_abertos'] ?? '1,2,3,4,5,6');
    $days = array_map('intval', array_filter(explode(',', $raw), static fn ($item) => $item !== ''));
    $days = array_values(array_intersect($days, [0, 1, 2, 3, 4, 5, 6]));

    return $days !== [] ? $days : [1, 2, 3, 4, 5, 6];
}

function diaEstaAberto(string $data): bool
{
    $time = strtotime($data);
    if ($time === false) {
        return false;
    }

    return in_array((int) date('w', $time), parseDiasAbertos(), true);
}

function intervaloAgenda(): int
{
    $minutos = (int) (clinica()['intervalo_minutos'] ?? 30);

    return in_array($minutos, [15, 20, 30, 45, 60], true) ? $minutos : 30;
}

function resumoHorarioClinica(): string
{
    $clinic = clinica();
    $abertos = parseDiasAbertos();
    $nomes = diasSemana();
    $labels = array_map(static fn (int $day) => $nomes[$day], $abertos);
    $abre = substr((string) ($clinic['horario_inicio'] ?: '08:00'), 0, 5);
    $fecha = substr((string) ($clinic['horario_fim'] ?: '18:00'), 0, 5);
    $texto = implode(', ', $labels) . ' · ' . $abre . ' às ' . $fecha;
    $almocoIni = substr(trim((string) ($clinic['almoco_inicio'] ?? '')), 0, 5);
    $almocoFim = substr(trim((string) ($clinic['almoco_fim'] ?? '')), 0, 5);
    if (preg_match('/^\d{2}:\d{2}$/', $almocoIni) && preg_match('/^\d{2}:\d{2}$/', $almocoFim)) {
        $texto .= ' · almoço ' . $almocoIni . '–' . $almocoFim;
    }

    return $texto;
}

function horariosLivres(string $data, int $duracaoMinutos): array
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || strtotime($data) === false) {
        return [];
    }
    if (!diaEstaAberto($data)) {
        return [];
    }

    $clinic = clinica();
    $inicioDia = strtotime($data . ' ' . substr((string) ($clinic['horario_inicio'] ?: '08:00:00'), 0, 5));
    $fimDia = strtotime($data . ' ' . substr((string) ($clinic['horario_fim'] ?: '18:00:00'), 0, 5));
    if ($inicioDia === false || $fimDia === false || $inicioDia >= $fimDia) {
        return [];
    }

    $duracao = max(15, $duracaoMinutos) * 60;
    $passo = intervaloAgenda() * 60;
    $agora = time();
    $livres = [];
    $ocupados = horariosOcupados($data);
    $almocoIniStr = substr(trim((string) ($clinic['almoco_inicio'] ?? '')), 0, 5);
    $almocoFimStr = substr(trim((string) ($clinic['almoco_fim'] ?? '')), 0, 5);
    $temAlmoco = (bool) preg_match('/^\d{2}:\d{2}$/', $almocoIniStr) && (bool) preg_match('/^\d{2}:\d{2}$/', $almocoFimStr);
    $almocoIni = $temAlmoco ? strtotime($data . ' ' . $almocoIniStr) : false;
    $almocoFim = $temAlmoco ? strtotime($data . ' ' . $almocoFimStr) : false;
    $temAlmoco = $almocoIni && $almocoFim && $almocoFim > $almocoIni;

    for ($cursor = $inicioDia; $cursor + $duracao <= $fimDia; $cursor += $passo) {
        if ($cursor <= $agora) {
            continue;
        }
        $fim = $cursor + $duracao;
        if ($temAlmoco && $cursor < $almocoFim && $almocoIni < $fim) {
            continue;
        }
        $livre = true;
        foreach ($ocupados as [$ocupadoInicio, $ocupadoFim]) {
            if ($cursor < $ocupadoFim && $ocupadoInicio < $fim) {
                $livre = false;
                break;
            }
        }
        if ($livre) {
            $livres[] = date('H:i', $cursor);
        }
    }

    return $livres;
}

function horarioAindaLivre(string $inicio, int $duracaoMinutos): bool
{
    $data = date('Y-m-d', strtotime($inicio));
    $hora = date('H:i', strtotime($inicio));

    return in_array($hora, horariosLivres($data, $duracaoMinutos), true);
}

function mensagensPadrao(): array
{
    return [
        'portal' => "Olá, {nome}! Seu acesso ao portal da {clinica} está liberado.\n\nLink: {portal}\nTelefone: {telefone}\n{linha_senha}\n\nLá você vê exames, preços e PIX.",
        'pix' => "Olá, {nome}! Segue o PIX de {valor} da {clinica}.\n{linha_descricao}\n\nCole este código no app do banco:\n{pix}\n\nOu pague pelo portal: {portal}",
        'agendamento' => "Olá, {nome}! Seu horário na {clinica} está marcado.\n\n{servico} em {quando}\n{endereco}\n{bloco_sinal}\n{bloco_portal}\n\nConfirme respondendo esta mensagem.",
        'link' => 'Agende seu horário na {clinica}: {link}',
    ];
}

function templateMensagem(string $tipo): string
{
    $custom = trim((string) (clinica()['msg_' . $tipo] ?? ''));

    return $custom !== '' ? $custom : (mensagensPadrao()[$tipo] ?? '');
}

function aplicarTemplate(string $template, array $vars): string
{
    $replaces = [];
    foreach ($vars as $key => $value) {
        $replaces['{' . $key . '}'] = (string) $value;
    }
    $texto = strtr($template, $replaces);
    $texto = preg_replace("/[ \t]+\n/", "\n", $texto) ?? $texto;
    $texto = preg_replace("/\n{3,}/", "\n\n", $texto) ?? $texto;

    return trim($texto);
}

function mensagemPortal(array $cliente, ?string $senha = null): string
{
    $clinic = clinica();

    return aplicarTemplate(templateMensagem('portal'), [
        'nome' => (string) $cliente['nome'],
        'clinica' => (string) $clinic['nome'],
        'telefone' => (string) $cliente['telefone'],
        'senha' => (string) $senha,
        'linha_senha' => $senha ? 'Senha: ' . $senha : '',
        'portal' => appUrl('portal.php'),
        'link' => appUrl('portal.php'),
    ]);
}

function mensagemPix(array $cliente, array $pagamento): string
{
    $clinic = clinica();
    $descricao = trim((string) ($pagamento['descricao'] ?? ''));

    return aplicarTemplate(templateMensagem('pix'), [
        'nome' => (string) $cliente['nome'],
        'clinica' => (string) $clinic['nome'],
        'valor' => brl($pagamento['valor'] ?? 0),
        'descricao' => $descricao,
        'linha_descricao' => $descricao !== '' ? 'Referente a: ' . $descricao : '',
        'pix' => (string) ($pagamento['pix_payload'] ?? ''),
        'portal' => appUrl('portal.php'),
        'link' => appUrl('portal.php'),
    ]);
}

function mensagemAgendamento(array $cliente, array $agendamento, ?array $pagamento = null, ?string $senha = null): string
{
    $clinic = clinica();
    $inicio = strtotime((string) $agendamento['inicio']);
    $blocoSinal = '';
    if ($pagamento && !empty($pagamento['pix_payload'])) {
        $blocoSinal = 'PIX de sinal: ' . brl($pagamento['valor']) . "\n" . $pagamento['pix_payload'];
    }
    $blocoPortal = $senha
        ? 'Portal: ' . appUrl('portal.php') . "\nSenha: " . $senha
        : 'Acompanhe no portal: ' . appUrl('portal.php');

    return aplicarTemplate(templateMensagem('agendamento'), [
        'nome' => (string) $cliente['nome'],
        'clinica' => (string) $clinic['nome'],
        'servico' => (string) ($agendamento['servico'] ?? 'Atendimento'),
        'quando' => $inicio ? date('d/m/Y \à\s H:i', $inicio) : '',
        'data' => $inicio ? date('d/m/Y', $inicio) : '',
        'hora' => $inicio ? date('H:i', $inicio) : '',
        'endereco' => (string) ($clinic['endereco'] ?? ''),
        'valor' => isset($pagamento['valor']) ? brl($pagamento['valor']) : '',
        'pix' => (string) ($pagamento['pix_payload'] ?? ''),
        'bloco_sinal' => $blocoSinal,
        'bloco_portal' => $blocoPortal,
        'senha' => (string) $senha,
        'portal' => appUrl('portal.php'),
        'link' => appUrl('agendar.php'),
    ]);
}

function mensagemLinkAgenda(?string $nomeClinica = null, ?string $link = null): string
{
    return aplicarTemplate(templateMensagem('link'), [
        'clinica' => $nomeClinica ?: (string) clinica()['nome'],
        'link' => $link ?: phoneAccessUrl('agendar.php'),
        'portal' => appUrl('portal.php'),
    ]);
}
