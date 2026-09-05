CREATE DATABASE IF NOT EXISTS podologia_salao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE podologia_salao;

CREATE TABLE clientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    email VARCHAR(160),
    data_nascimento DATE,
    observacoes TEXT,
    foto_path VARCHAR(255),
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clinicas (
    id TINYINT UNSIGNED PRIMARY KEY,
    nome VARCHAR(160) NOT NULL,
    tipo ENUM('podologia', 'salao', 'misto') NOT NULL DEFAULT 'podologia',
    telefone VARCHAR(20),
    email VARCHAR(160),
    endereco VARCHAR(255),
    logo_path VARCHAR(255),
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE servicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    categoria ENUM('podologia', 'salao') NOT NULL DEFAULT 'podologia',
    duracao_minutos SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE agendamentos (
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

CREATE TABLE pagamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id INT UNSIGNED,
    valor DECIMAL(10,2) NOT NULL,
    forma_pagamento ENUM('dinheiro', 'pix', 'cartao', 'transferencia') NOT NULL,
    pago_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pagamento_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE SET NULL
);

INSERT INTO servicos (nome, categoria, duracao_minutos, preco) VALUES
('Podologia preventiva', 'podologia', 60, 95.00),
('Tratamento de micose', 'podologia', 45, 120.00),
('Spa dos pés', 'salao', 60, 80.00),
('Unha encravada', 'podologia', 45, 110.00);