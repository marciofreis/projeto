<?php

declare(strict_types=1);

$size = (int) ($_GET['s'] ?? 192);
if (!in_array($size, [180, 192, 512], true)) {
    $size = 192;
}

$hex = strtolower(preg_replace('/[^0-9a-f]/i', '', (string) ($_GET['c'] ?? '1f5e50')) ?? '');
if (strlen($hex) !== 6) {
    $hex = '1f5e50';
}
$bg = chr(hexdec(substr($hex, 0, 2))) . chr(hexdec(substr($hex, 2, 2))) . chr(hexdec(substr($hex, 4, 2))) . "\xFF";

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
echo pngIcon($size, $bg);

function pngIcon(int $size, string $bg): string
{
    $raw = '';
    $radius = (int) round($size * 0.22);
    $stemLeft = (int) round($size * 0.34);
    $stemRight = (int) round($size * 0.46);
    $bowlRight = (int) round($size * 0.66);
    $top = (int) round($size * 0.28);
    $mid = (int) round($size * 0.54);
    $bottom = (int) round($size * 0.74);

    for ($y = 0; $y < $size; $y++) {
        $raw .= "\x00";
        for ($x = 0; $x < $size; $x++) {
            $inside = inRoundedSquare($x, $y, $size, $radius);
            $letter = $inside && inLetterP($x, $y, $stemLeft, $stemRight, $bowlRight, $top, $mid, $bottom);
            $raw .= $letter ? "\xFF\xFF\xFF\xFF" : ($inside ? $bg : "\x00\x00\x00\x00");
        }
    }

    return pngFile($size, $raw);
}

function inRoundedSquare(int $x, int $y, int $size, int $radius): bool
{
    $max = $size - 1;
    $cx = $x < $radius ? $radius : ($x > $max - $radius ? $max - $radius : $x);
    $cy = $y < $radius ? $radius : ($y > $max - $radius ? $max - $radius : $y);
    $dx = $x - $cx;
    $dy = $y - $cy;

    return ($dx * $dx) + ($dy * $dy) <= $radius * $radius;
}

function inLetterP(int $x, int $y, int $stemLeft, int $stemRight, int $bowlRight, int $top, int $mid, int $bottom): bool
{
    if ($x >= $stemLeft && $x <= $stemRight && $y >= $top && $y <= $bottom) {
        return true;
    }

    $cx = (int) round(($stemRight + $bowlRight) / 2);
    $cy = (int) round(($top + $mid) / 2);
    $rx = max(1, (int) round(($bowlRight - $stemRight) * 0.85));
    $ry = max(1, (int) round(($mid - $top) / 2));
    $nx = ($x - $cx) / $rx;
    $ny = ($y - $cy) / $ry;
    $outer = ($nx * $nx) + ($ny * $ny) <= 1;
    $inner = ($nx * $nx) + ($ny * $ny) <= 0.35;

    return $x >= $stemRight && $outer && !$inner;
}

function pngFile(int $size, string $raw): string
{
    $ihdr = pack('NNCCCCC', $size, $size, 8, 6, 0, 0, 0);
    $idat = function_exists('gzcompress') ? gzcompress($raw, 6) : $raw;

    return "\x89PNG\r\n\x1a\n"
        . pngChunk('IHDR', $ihdr)
        . pngChunk('IDAT', $idat ?: $raw)
        . pngChunk('IEND', '');
}

function pngChunk(string $type, string $data): string
{
    return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
}
