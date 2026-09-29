CREATE DATABASE IF NOT EXISTS stockflow;
USE stockflow;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    valor_unitario DECIMAL(10,2) NOT NULL,
    categoria VARCHAR(80) NOT NULL,
    quantidade INT NOT NULL DEFAULT 0,
    estoque_minimo INT NOT NULL DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo ENUM('entrada', 'saida') NOT NULL,
    quantidade INT NOT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimentacoes_produto
        FOREIGN KEY (produto_id) REFERENCES produtos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

INSERT INTO usuarios (nome, email, senha) VALUES (
    'Administrador',
    'admin@stockflow.com',
    '$2y$10$Dz8mDxmAQzTpak7JJwAu9ujg7jm/zE3ZuWHu8qEDuY4GZq4Ld.rFi'
);

INSERT INTO produtos (nome, codigo, valor_unitario, categoria, quantidade, estoque_minimo) VALUES
('Café Torrado', 'PROD-0001', 18.90, 'Bebidas', 25, 10),
('Detergente', 'PROD-0002', 9.50, 'Limpeza', 12, 8),
('Papel A4', 'PROD-0003', 22.00, 'Papelaria', 40, 15),
('Sabonete', 'PROD-0004', 4.20, 'Higiene', 5, 8),
('Alho', 'PROD-0005', 8.00, 'Alimentos', 7, 5);

INSERT INTO movimentacoes (produto_id, tipo, quantidade, data_hora) VALUES
(1, 'entrada', 15, '2026-09-20 09:30:00'),
(2, 'saida', 3, '2026-09-21 11:00:00'),
(3, 'entrada', 20, '2026-09-22 14:10:00'),
(4, 'saida', 4, '2026-09-23 08:40:00'),
(5, 'entrada', 10, '2026-09-24 16:50:00');
