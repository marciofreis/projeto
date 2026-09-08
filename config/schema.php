<?php

declare(strict_types=1);

function tableExists(PDO $pdo, string $table): bool
{
    $statement = $pdo->prepare('SHOW TABLES LIKE ?');
    $statement->execute([$table]);
    return (bool) $statement->fetch();
}

function tableHasColumn(PDO $pdo, string $table, string $column): bool
{
    $statement = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $statement->execute([$column]);
    return (bool) $statement->fetch();
}

function addColumnIfMissing(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!tableHasColumn($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `$table` ADD `$column` $definition");
    }
}

function ensureSchema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();

    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(120) NOT NULL,
        email VARCHAR(160) NOT NULL UNIQUE,
        senha_hash VARCHAR(255) NOT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS prontuarios (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT UNSIGNED NOT NULL,
        agendamento_id INT UNSIGNED NULL,
        data_exame DATE NOT NULL,
        queixa TEXT,
        notas_internas TEXT,
        orientacao_cliente TEXT,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS prontuario_marcas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        prontuario_id INT UNSIGNED NOT NULL,
        pe ENUM('esquerdo','direito') NOT NULL,
        zona VARCHAR(40) NOT NULL,
        tipo VARCHAR(40) NOT NULL,
        observacao VARCHAR(255)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS prontuario_fotos (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        prontuario_id INT UNSIGNED NOT NULL,
        momento ENUM('antes','depois','evolucao') NOT NULL DEFAULT 'evolucao',
        foto_path VARCHAR(255) NOT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    if (tableExists($pdo, 'clientes')) {
        addColumnIfMissing($pdo, 'clientes', 'senha_hash', 'VARCHAR(255) NULL');
        addColumnIfMissing($pdo, 'clientes', 'portal_liberado', 'TINYINT(1) NOT NULL DEFAULT 0');
        addColumnIfMissing($pdo, 'clientes', 'anamnese_diabetes', 'TINYINT(1) NOT NULL DEFAULT 0');
        addColumnIfMissing($pdo, 'clientes', 'anamnese_gestante', 'TINYINT(1) NOT NULL DEFAULT 0');
        addColumnIfMissing($pdo, 'clientes', 'anamnese_alergia', 'VARCHAR(255) NULL');
        addColumnIfMissing($pdo, 'clientes', 'anamnese_observacoes', 'TEXT NULL');
        addColumnIfMissing($pdo, 'clientes', 'foto_path', 'VARCHAR(255) NULL');
    }

    if (tableExists($pdo, 'clinicas')) {
        addColumnIfMissing($pdo, 'clinicas', 'pix_chave', 'VARCHAR(100) NULL');
        addColumnIfMissing($pdo, 'clinicas', 'pix_tipo', "ENUM('cpf','cnpj','email','telefone','aleatoria') NOT NULL DEFAULT 'aleatoria'");
        addColumnIfMissing($pdo, 'clinicas', 'pix_nome', 'VARCHAR(25) NULL');
        addColumnIfMissing($pdo, 'clinicas', 'pix_cidade', 'VARCHAR(15) NULL');
        addColumnIfMissing($pdo, 'clinicas', 'horario_inicio', "TIME NOT NULL DEFAULT '08:00:00'");
        addColumnIfMissing($pdo, 'clinicas', 'horario_fim', "TIME NOT NULL DEFAULT '18:00:00'");
        addColumnIfMissing($pdo, 'clinicas', 'sinal_online', 'TINYINT(1) NOT NULL DEFAULT 0');
        addColumnIfMissing($pdo, 'clinicas', 'sinal_valor', 'DECIMAL(10,2) NULL');
        addColumnIfMissing($pdo, 'clinicas', 'cor_primaria', "VARCHAR(7) NOT NULL DEFAULT '#1f5e50'");
        addColumnIfMissing($pdo, 'clinicas', 'dias_abertos', "VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6'");
        addColumnIfMissing($pdo, 'clinicas', 'almoco_inicio', 'TIME NULL');
        addColumnIfMissing($pdo, 'clinicas', 'almoco_fim', 'TIME NULL');
        addColumnIfMissing($pdo, 'clinicas', 'intervalo_minutos', 'SMALLINT UNSIGNED NOT NULL DEFAULT 30');
        addColumnIfMissing($pdo, 'clinicas', 'msg_portal', 'TEXT NULL');
        addColumnIfMissing($pdo, 'clinicas', 'msg_pix', 'TEXT NULL');
        addColumnIfMissing($pdo, 'clinicas', 'msg_agendamento', 'TEXT NULL');
        addColumnIfMissing($pdo, 'clinicas', 'msg_link', 'TEXT NULL');
    }

    if (tableExists($pdo, 'pagamentos')) {
        addColumnIfMissing($pdo, 'pagamentos', 'cliente_id', 'INT UNSIGNED NULL');
        addColumnIfMissing($pdo, 'pagamentos', 'status', "ENUM('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente'");
        addColumnIfMissing($pdo, 'pagamentos', 'descricao', 'VARCHAR(160) NULL');
        addColumnIfMissing($pdo, 'pagamentos', 'pix_txid', 'VARCHAR(32) NULL');
        addColumnIfMissing($pdo, 'pagamentos', 'pix_payload', 'TEXT NULL');
        addColumnIfMissing($pdo, 'pagamentos', 'criado_em', 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP');
        $pagoEm = $pdo->query("SHOW COLUMNS FROM pagamentos LIKE 'pago_em'")->fetch();
        if ($pagoEm && strtoupper((string) $pagoEm['Null']) === 'NO') {
            $pdo->exec('ALTER TABLE pagamentos MODIFY pago_em DATETIME NULL');
        }
    }

    if (tableExists($pdo, 'usuarios')) {
        $hadPapel = tableHasColumn($pdo, 'usuarios', 'papel');
        addColumnIfMissing($pdo, 'usuarios', 'papel', "ENUM('admin','recepcao','profissional','financeiro') NOT NULL DEFAULT 'profissional'");
        addColumnIfMissing($pdo, 'usuarios', 'cargo', 'VARCHAR(80) NULL');
        addColumnIfMissing($pdo, 'usuarios', 'permissoes', 'TEXT NULL');
        if (!$hadPapel && tableHasColumn($pdo, 'usuarios', 'papel')) {
            $pdo->exec("UPDATE usuarios SET papel = 'admin'");
        }
    }

    $users = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    if ($users === 0) {
        $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, papel, cargo) VALUES (?, ?, ?, ?, ?)')->execute([
            'Marcio Freis',
            'admin@podocare.local',
            password_hash('admin123', PASSWORD_DEFAULT),
            'admin',
            'Administrador',
        ]);
    }

    if (tableExists($pdo, 'clinicas')) {
        $clinic = $pdo->query('SELECT id FROM clinicas WHERE id = 1')->fetch();
        if (!$clinic) {
            $pdo->exec("INSERT INTO clinicas (id, nome, tipo, pix_nome, pix_cidade) VALUES (1, 'Podocare', 'podologia', 'PODOCARE', 'SAO PAULO')");
        }
    }

    $ready = true;
}
