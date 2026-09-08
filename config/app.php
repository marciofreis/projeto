<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pullFlash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function uploadImage(array $file, string $folder, string $prefix): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Não foi possível enviar a imagem.');
    }
    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        throw new RuntimeException('A imagem deve ter no máximo 3 MB.');
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Envie uma imagem JPG, PNG ou WEBP válida.');
    }

    $directory = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Não foi possível preparar a pasta de imagens.');
    }
    $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }
    return 'uploads/' . $folder . '/' . $filename;
}

function uploadImages(array $files, string $folder, string $prefix): array
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        $path = uploadImage($files, $folder, $prefix);
        return $path !== '' ? [$path] : [];
    }

    $paths = [];
    foreach ($files['name'] as $index => $name) {
        $file = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
        $path = uploadImage($file, $folder, $prefix);
        if ($path !== '') {
            $paths[] = $path;
        }
    }

    return $paths;
}

function digits(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function brl(float|string|null $value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function clinica(bool $refresh = false): array
{
    static $clinic;
    if ($refresh) {
        $clinic = null;
    }
    if (is_array($clinic)) {
        return $clinic;
    }

    try {
        $clinic = db()->query('SELECT * FROM clinicas WHERE id = 1')->fetch() ?: [];
    } catch (Throwable $exception) {
        $clinic = [];
    }

    return $clinic + [
        'nome' => 'Podocare',
        'tipo' => 'podologia',
        'logo_path' => '',
        'pix_chave' => '',
        'pix_tipo' => 'aleatoria',
        'pix_nome' => 'PODOCARE',
        'pix_cidade' => 'SAO PAULO',
        'horario_inicio' => '08:00:00',
        'horario_fim' => '18:00:00',
        'sinal_online' => 0,
        'sinal_valor' => null,
        'cor_primaria' => '#1f5e50',
        'dias_abertos' => '1,2,3,4,5,6',
        'almoco_inicio' => null,
        'almoco_fim' => null,
        'intervalo_minutos' => 30,
        'msg_portal' => '',
        'msg_pix' => '',
        'msg_agendamento' => '',
        'msg_link' => '',
    ];
}

function coresProntas(): array
{
    return [
        '#1f5e50' => 'Verde clínico',
        '#2d6a4f' => 'Verde folha',
        '#1d4e89' => 'Azul spa',
        '#9d4e6c' => 'Rosa salão',
        '#9c4a3a' => 'Terracota',
        '#5c4b8a' => 'Roxo',
        '#2c3338' => 'Preto elegante',
    ];
}

function normalizeHexColor(?string $value, string $fallback = '#1f5e50'): string
{
    $value = trim((string) $value);
    if (preg_match('/^#?([0-9a-fA-F]{6})$/', $value, $match)) {
        return '#' . strtolower($match[1]);
    }

    return $fallback;
}

function hexToRgb(string $hex): array
{
    $hex = ltrim(normalizeHexColor($hex), '#');

    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

function mixHex(string $from, string $to, float $amount): string
{
    $amount = max(0, min(1, $amount));
    [$r1, $g1, $b1] = hexToRgb($from);
    [$r2, $g2, $b2] = hexToRgb($to);

    return sprintf(
        '#%02x%02x%02x',
        (int) round($r1 + ($r2 - $r1) * $amount),
        (int) round($g1 + ($g2 - $g1) * $amount),
        (int) round($b1 + ($b2 - $b1) * $amount)
    );
}

function themePalette(?array $clinic = null): array
{
    $primary = normalizeHexColor(($clinic ?? clinica())['cor_primaria'] ?? null);

    return [
        'primary' => $primary,
        'hover' => mixHex($primary, '#000000', 0.18),
        'green' => mixHex($primary, '#ffffff', 0.12),
        'soft' => mixHex($primary, '#ffffff', 0.88),
        'mint' => mixHex($primary, '#ffffff', 0.82),
        'mid' => mixHex($primary, '#ffffff', 0.55),
        'cream' => mixHex($primary, '#f7faf8', 0.92),
        'focus' => mixHex($primary, '#ffffff', 0.78),
    ];
}

function themeStyleTag(): string
{
    $palette = themePalette();

    return '<style>:root{'
        . '--dark-green:' . $palette['primary'] . ';'
        . '--green:' . $palette['green'] . ';'
        . '--mint:' . $palette['mint'] . ';'
        . '--cream:' . $palette['cream'] . ';'
        . '--brand-hover:' . $palette['hover'] . ';'
        . '--brand-soft:' . $palette['soft'] . ';'
        . '--brand-mid:' . $palette['mid'] . ';'
        . '--brand-focus:' . $palette['focus'] . ';'
        . '}</style>';
}

function iconColorParam(): string
{
    return ltrim(themePalette()['primary'], '#');
}

function appBaseUrl(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = rtrim(dirname($script), '/');
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        $dir = '';
    }

    return ($https ? 'https://' : 'http://') . $host . $dir;
}

function appUrl(string $path): string
{
    return appBaseUrl() . '/' . ltrim($path, '/');
}

function lanIps(): array
{
    $ips = [];
    $output = @shell_exec('ipconfig');
    if (!is_string($output) || !preg_match_all('/IPv4[^:]*:\s*([0-9.]+)/', $output, $matches)) {
        return $ips;
    }

    foreach ($matches[1] as $ip) {
        if (str_starts_with($ip, '127.') || str_starts_with($ip, '169.254.') || preg_match('/^192\.168\.(56|96|149)\./', $ip)) {
            continue;
        }
        $ips[] = $ip;
    }

    return array_values(array_unique($ips));
}

function phoneAccessUrl(string $path = 'login.php'): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $port = (string) ($_SERVER['SERVER_PORT'] ?? '80');
    $dir = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    if ($dir === '/' || $dir === '.') {
        $dir = '';
    }

    $ip = lanIps()[0] ?? '';
    if ($ip === '' || str_contains($host, 'localhost') || str_starts_with($host, '127.')) {
        if ($ip === '') {
            return appUrl($path);
        }
        $suffix = ($port === '80' || $port === '443') ? '' : ':' . $port;
        return 'http://' . $ip . $suffix . $dir . '/' . ltrim($path, '/');
    }

    return appUrl($path);
}

function whatsappNumber(string $phone): string
{
    $number = digits($phone);
    if ($number === '') {
        return '';
    }
    if (!str_starts_with($number, '55')) {
        $number = '55' . $number;
    }

    return $number;
}

function whatsappLink(string $phone, string $message): string
{
    $number = whatsappNumber($phone);
    $base = $number !== '' ? 'https://wa.me/' . $number : 'https://wa.me/';

    return $base . '?text=' . rawurlencode($message);
}

function whatsappButton(string $phone, string $message, string $label = 'Enviar no WhatsApp'): string
{
    return '<a class="btn btn-whatsapp" href="' . e(whatsappLink($phone, $message)) . '" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> ' . e($label) . '</a>';
}

function assetUrl(string $path): string
{
    $file = dirname(__DIR__) . '/' . ltrim($path, '/');
    $version = is_file($file) ? (string) filemtime($file) : (string) time();

    return $path . (str_contains($path, '?') ? '&' : '?') . 'v=' . $version;
}

function mesNome(string $ym): string
{
    $meses = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'];
    $time = strtotime(strlen($ym) >= 7 ? substr($ym, 0, 7) . '-01' : 'now') ?: time();

    return ($meses[(int) date('n', $time)] ?? '') . ' de ' . date('Y', $time);
}

function agendaUrl(string $mes, string $dia): string
{
    return '?page=agenda&mes=' . rawurlencode($mes) . '&dia=' . rawurlencode($dia);
}

function statusLabel(string $status): string
{
    return match ($status) {
        'agendado' => 'Agendado',
        'confirmado' => 'Confirmado',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',
        'pendente' => 'Pendente',
        'pago' => 'Pago',
        default => ucfirst($status),
    };
}

function statusClass(string $status): string
{
    return match ($status) {
        'confirmado', 'pago', 'concluido' => 'confirmed',
        'agendado', 'pendente' => 'waiting',
        'cancelado' => 'cancelled',
        default => 'neutral-status',
    };
}

function marcaTipos(): array
{
    return [
        'calo' => 'Calo',
        'rachadura' => 'Rachadura',
        'encravada' => 'Unha encravada',
        'micose' => 'Micose',
        'verruga' => 'Verruga',
        'fissura' => 'Fissura',
        'ulcera' => 'Úlcera',
        'inflamacao' => 'Inflamação',
        'outro' => 'Outro',
    ];
}

function zonaLabels(): array
{
    return [
        'hallux' => 'Hálux',
        'd2' => '2º dedo',
        'd3' => '3º dedo',
        'd4' => '4º dedo',
        'd5' => '5º dedo',
        'antepe' => 'Antepé',
        'arco' => 'Arco',
        'calcaneo' => 'Calcanhar',
        'interdigital' => 'Interdígitos',
    ];
}

function decodeMarcas(?string $json): array
{
    $decoded = json_decode($json ?? '[]', true);
    return is_array($decoded) ? $decoded : [];
}
