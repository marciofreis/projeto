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
