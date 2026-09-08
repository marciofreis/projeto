<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$clinic = clinica();
$name = trim((string) ($clinic['nome'] ?? '')) ?: 'Podocare';
$theme = themePalette($clinic);
$iconColor = ltrim($theme['primary'], '#');
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($base === '/' || $base === '.') {
    $base = '';
}

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-store');

echo json_encode([
    'id' => $base . '/',
    'name' => $name,
    'short_name' => function_exists('mb_substr') ? mb_substr($name, 0, 12) : substr($name, 0, 12),
    'description' => 'Agenda, exames, PIX e portal da clínica',
    'start_url' => $base . '/login.php',
    'scope' => ($base === '' ? '/' : $base . '/'),
    'display' => 'standalone',
    'orientation' => 'portrait-primary',
    'background_color' => $theme['cream'],
    'theme_color' => $theme['primary'],
    'lang' => 'pt-BR',
    'icons' => [
        ['src' => $base . '/icon.php?s=192&c=' . $iconColor, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => $base . '/icon.php?s=512&c=' . $iconColor, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
    ],
    'shortcuts' => [
        ['name' => 'Clínica', 'url' => $base . '/login.php'],
        ['name' => 'Portal do cliente', 'url' => $base . '/portal.php'],
        ['name' => 'Agendar horário', 'url' => $base . '/agendar.php'],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
