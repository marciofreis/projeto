<?php

declare(strict_types=1);

function pixTlv(string $id, string $value): string
{
    return $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
}

function pixSanitize(string $text, int $max): string
{
    $map = [
        'á' => 'A', 'à' => 'A', 'ã' => 'A', 'â' => 'A', 'ä' => 'A',
        'é' => 'E', 'ê' => 'E', 'è' => 'E',
        'í' => 'I', 'ì' => 'I',
        'ó' => 'O', 'ô' => 'O', 'õ' => 'O', 'ò' => 'O',
        'ú' => 'U', 'ù' => 'U', 'ü' => 'U',
        'ç' => 'C', 'ñ' => 'N',
        'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A',
        'É' => 'E', 'Ê' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O',
        'Ú' => 'U', 'Ç' => 'C',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^A-Za-z0-9 ]/', '', $text) ?? '';
    $text = strtoupper(trim($text));

    return substr($text !== '' ? $text : 'PODOCARE', 0, $max);
}

function pixCrc16(string $payload): string
{
    $crc = 0xFFFF;
    $length = strlen($payload);
    for ($i = 0; $i < $length; $i++) {
        $crc ^= ord($payload[$i]) << 8;
        for ($bit = 0; $bit < 8; $bit++) {
            $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) & 0xFFFF : ($crc << 1) & 0xFFFF;
        }
    }

    return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
}

function normalizarChavePix(string $chave, string $tipo): string
{
    $chave = trim($chave);
    if (in_array($tipo, ['cpf', 'cnpj'], true)) {
        return digits($chave);
    }
    if ($tipo === 'telefone') {
        $numbers = digits($chave);
        if ($numbers !== '' && !str_starts_with($numbers, '55')) {
            $numbers = '55' . $numbers;
        }
        return '+' . $numbers;
    }

    return $chave;
}

function gerarPixCopiaECola(string $chave, string $nome, string $cidade, float $valor, string $txid): string
{
    $merchant = pixTlv('00', 'br.gov.bcb.pix') . pixTlv('01', $chave);
    $additional = pixTlv('05', substr($txid, 0, 25));
    $payload = pixTlv('00', '01')
        . pixTlv('26', $merchant)
        . pixTlv('52', '0000')
        . pixTlv('53', '986')
        . pixTlv('54', number_format($valor, 2, '.', ''))
        . pixTlv('58', 'BR')
        . pixTlv('59', pixSanitize($nome, 25))
        . pixTlv('60', pixSanitize($cidade, 15))
        . pixTlv('62', $additional)
        . '6304';

    return $payload . pixCrc16($payload);
}

function criarCobrancaPix(int $clienteId, float $valor, string $descricao, ?int $agendamentoId = null): array
{
    $clinic = clinica();
    $chave = trim((string) ($clinic['pix_chave'] ?? ''));
    if ($chave === '') {
        throw new RuntimeException('Cadastre a chave PIX em Configurações antes de gerar a cobrança.');
    }

    $txid = strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));
    $payload = gerarPixCopiaECola(
        normalizarChavePix($chave, (string) $clinic['pix_tipo']),
        (string) ($clinic['pix_nome'] ?: $clinic['nome']),
        (string) ($clinic['pix_cidade'] ?: 'SAO PAULO'),
        $valor,
        $txid
    );

    db()->prepare('INSERT INTO pagamentos (cliente_id, agendamento_id, valor, forma_pagamento, status, descricao, pix_txid, pix_payload) VALUES (?,?,?,?,?,?,?,?)')->execute([
        $clienteId,
        $agendamentoId,
        $valor,
        'pix',
        'pendente',
        $descricao !== '' ? $descricao : 'Atendimento',
        $txid,
        $payload,
    ]);

    return [
        'id' => (int) db()->lastInsertId(),
        'valor' => $valor,
        'descricao' => $descricao,
        'pix_payload' => $payload,
        'pix_txid' => $txid,
        'status' => 'pendente',
    ];
}
