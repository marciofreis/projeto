<?php

declare(strict_types=1);

function permissoesCatalogo(): array
{
    return [
        'dashboard' => 'Visão geral',
        'agenda' => 'Agenda',
        'clientes' => 'Clientes',
        'servicos' => 'Serviços',
        'prontuario' => 'Prontuário e exames',
        'financeiro' => 'Financeiro e PIX',
        'configuracoes' => 'Configurações da clínica',
        'usuarios' => 'Usuários e acessos',
        'imprimir' => 'Imprimir exames e fichas',
    ];
}

function papeisPadrao(): array
{
    return [
        'admin' => ['label' => 'Administrador', 'perms' => array_keys(permissoesCatalogo())],
        'recepcao' => ['label' => 'Recepção', 'perms' => ['dashboard', 'agenda', 'clientes', 'servicos', 'imprimir']],
        'profissional' => ['label' => 'Profissional', 'perms' => ['dashboard', 'agenda', 'clientes', 'prontuario', 'imprimir']],
        'financeiro' => ['label' => 'Financeiro', 'perms' => ['dashboard', 'clientes', 'financeiro', 'imprimir']],
    ];
}

function papelLabel(string $papel): string
{
    return papeisPadrao()[$papel]['label'] ?? 'Equipe';
}

function decodePermissoes(?string $raw, string $papel): array
{
    $raw = trim((string) $raw);
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_intersect($decoded, array_keys(permissoesCatalogo())));
        }
    }

    return papeisPadrao()[$papel]['perms'] ?? ['dashboard'];
}

function staffPermissoes(?array $user = null): array
{
    $user ??= staff();
    if (!$user) {
        return [];
    }
    if (($user['papel'] ?? '') === 'admin') {
        return array_keys(permissoesCatalogo());
    }

    return decodePermissoes($user['permissoes'] ?? null, (string) ($user['papel'] ?? 'profissional'));
}

function can(string $permission): bool
{
    return in_array($permission, staffPermissoes(), true);
}

function requirePermission(string $permission): void
{
    requireStaff();
    if (!can($permission)) {
        flash('danger', 'Você não tem permissão para esta área.');
        header('Location: index.php');
        exit;
    }
}

function countAdmins(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM usuarios WHERE papel = 'admin' AND ativo = 1")->fetchColumn();
}

function pagePermission(string $page): string
{
    return [
        'dashboard' => 'dashboard',
        'agenda' => 'agenda',
        'clientes' => 'clientes',
        'servicos' => 'servicos',
        'prontuario' => 'prontuario',
        'financeiro' => 'financeiro',
        'configuracoes' => 'configuracoes',
        'usuarios' => 'usuarios',
    ][$page] ?? 'dashboard';
}

function firstAllowedUrl(): string
{
    foreach (['dashboard', 'agenda', 'clientes', 'prontuario', 'financeiro', 'servicos', 'configuracoes', 'usuarios'] as $page) {
        if (can(pagePermission($page))) {
            return $page === 'dashboard' ? 'index.php' : 'index.php?page=' . $page;
        }
    }

    return 'logout.php';
}
