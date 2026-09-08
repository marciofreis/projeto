CREATE DATABASE IF NOT EXISTS podologia_salao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE podologia_salao;

CREATE TABLE IF NOT EXISTS clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    email VARCHAR(160),
    data_nascimento DATE,
    observacoes TEXT,
    foto_path VARCHAR(255),
    senha_hash VARCHAR(255),
    portal_liberado TINYINT(1) NOT NULL DEFAULT 0,
    anamnese_diabetes TINYINT(1) NOT NULL DEFAULT 0,
    anamnese_gestante TINYINT(1) NOT NULL DEFAULT 0,
    anamnese_alergia VARCHAR(255),
    anamnese_observacoes TEXT,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS clinicas (
    id TINYINT UNSIGNED PRIMARY KEY,
    nome VARCHAR(160) NOT NULL,
    tipo ENUM('podologia', 'salao', 'misto') NOT NULL DEFAULT 'podologia',
    telefone VARCHAR(20),
    email VARCHAR(160),
    endereco VARCHAR(255),
    logo_path VARCHAR(255),
    pix_chave VARCHAR(100),
    pix_tipo ENUM('cpf', 'cnpj', 'email', 'telefone', 'aleatoria') NOT NULL DEFAULT 'aleatoria',
    pix_nome VARCHAR(25),
    pix_cidade VARCHAR(15),
    horario_inicio TIME NOT NULL DEFAULT '08:00:00',
    horario_fim TIME NOT NULL DEFAULT '18:00:00',
    sinal_online TINYINT(1) NOT NULL DEFAULT 0,
    sinal_valor DECIMAL(10,2),
    cor_primaria VARCHAR(7) NOT NULL DEFAULT '#1f5e50',
    dias_abertos VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6',
    almoco_inicio TIME,
    almoco_fim TIME,
    intervalo_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    msg_portal TEXT,
    msg_pix TEXT,
    msg_agendamento TEXT,
    msg_link TEXT,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    papel ENUM('admin', 'recepcao', 'profissional', 'financeiro') NOT NULL DEFAULT 'profissional',
    cargo VARCHAR(80),
    permissoes TEXT,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS servicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    categoria ENUM('podologia', 'salao') NOT NULL DEFAULT 'podologia',
    duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS agendamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    servico_id INT UNSIGNED NOT NULL,
    inicio DATETIME NOT NULL,
    status ENUM('agendado', 'confirmado', 'concluido', 'cancelado') NOT NULL DEFAULT 'agendado',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_agendamento_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT fk_agendamento_servico FOREIGN KEY (servico_id) REFERENCES servicos(id)
);

CREATE TABLE IF NOT EXISTS pagamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED,
    agendamento_id INT UNSIGNED,
    valor DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('dinheiro', 'pix', 'cartao', 'transferencia') NOT NULL,
    status ENUM('pendente', 'pago', 'cancelado') NOT NULL DEFAULT 'pendente',
    descricao VARCHAR(160),
    pix_txid VARCHAR(32),
    pix_payload TEXT,
    pago_em DATETIME,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pagamento_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    CONSTRAINT fk_pagamento_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS prontuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    agendamento_id INT UNSIGNED,
    data_exame DATE NOT NULL,
    queixa TEXT,
    notas_internas TEXT,
    orientacao_cliente TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prontuario_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT fk_prontuario_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS prontuario_marcas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prontuario_id INT UNSIGNED NOT NULL,
    pe ENUM('esquerdo', 'direito') NOT NULL,
    zona VARCHAR(40) NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    observacao VARCHAR(255),
    CONSTRAINT fk_marca_prontuario FOREIGN KEY (prontuario_id) REFERENCES prontuarios(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS prontuario_fotos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prontuario_id INT UNSIGNED NOT NULL,
    momento ENUM('antes', 'depois', 'evolucao') NOT NULL DEFAULT 'evolucao',
    foto_path VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_foto_prontuario FOREIGN KEY (prontuario_id) REFERENCES prontuarios(id) ON DELETE CASCADE
);

INSERT INTO servicos (nome, categoria, duracao_minutos, preco) VALUES
('Podologia preventiva', 'podologia', 60, 95.00),
('Tratamento de micose', 'podologia', 45, 120.00),
('Spa dos pés', 'salao', 60, 80.00),
('Unha encravada', 'podologia', 45, 110.00);

INSERT INTO clinicas (id, nome, tipo, pix_nome, pix_cidade) VALUES
(1, 'Podocare', 'podologia', 'PODOCARE', 'SAO PAULO');
