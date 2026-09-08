<?php

declare(strict_types=1);

function requireStaff(): void
{
    if (empty($_SESSION['staff_id'])) {
        header('Location: login.php');
        exit;
    }
}

function staff(): ?array
{
    if (empty($_SESSION['staff_id'])) {
        return null;
    }

    static $user;
    if (is_array($user)) {
        return $user;
    }

    $statement = db()->prepare('SELECT id, nome, email, papel, cargo, permissoes FROM usuarios WHERE id = ? AND ativo = 1');
    $statement->execute([(int) $_SESSION['staff_id']]);
    $user = $statement->fetch() ?: null;
    if (!$user) {
        unset($_SESSION['staff_id']);
    }

    return $user;
}

function requireCliente(): void
{
    if (empty($_SESSION['cliente_id'])) {
        header('Location: portal.php');
        exit;
    }
}

function clienteLogado(): ?array
{
    if (empty($_SESSION['cliente_id'])) {
        return null;
    }

    static $cliente;
    if (is_array($cliente)) {
        return $cliente;
    }

    $statement = db()->prepare('SELECT * FROM clientes WHERE id = ? AND ativo = 1 AND portal_liberado = 1');
    $statement->execute([(int) $_SESSION['cliente_id']]);
    $cliente = $statement->fetch() ?: null;
    if (!$cliente) {
        unset($_SESSION['cliente_id']);
    }

    return $cliente;
}

function loginStaff(string $email, string $password): bool
{
    $statement = db()->prepare('SELECT id, senha_hash FROM usuarios WHERE email = ? AND ativo = 1');
    $statement->execute([strtolower(trim($email))]);
    $user = $statement->fetch();
    if (!$user || !password_verify($password, $user['senha_hash'])) {
        return false;
    }

    $_SESSION['staff_id'] = (int) $user['id'];
    return true;
}

function loginCliente(string $telefone, string $password): bool
{
    $digits = digits($telefone);
    if ($digits === '') {
        return false;
    }

    $statement = db()->query('SELECT id, telefone, senha_hash, portal_liberado FROM clientes WHERE ativo = 1 AND portal_liberado = 1 AND senha_hash IS NOT NULL');
    foreach ($statement->fetchAll() as $cliente) {
        if (digits((string) $cliente['telefone']) === $digits && password_verify($password, $cliente['senha_hash'])) {
            $_SESSION['cliente_id'] = (int) $cliente['id'];
            return true;
        }
    }

    return false;
}

function senhaTemporaria(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $password = '';
    for ($i = 0; $i < 8; $i++) {
        $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    return $password;
}
