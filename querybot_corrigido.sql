DROP DATABASE IF EXISTS querybot;
CREATE DATABASE querybot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE querybot;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NULL,
    phone VARCHAR(30) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compatibilidade com o nome escrito errado no SQL antigo: costumers
CREATE OR REPLACE VIEW costumers AS
SELECT * FROM customers;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(40) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_orders_customer_id (customer_id),
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_product_id (product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela em português para compatibilidade com testes antigos
CREATE TABLE pedido (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(40) NOT NULL DEFAULT 'pendente',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pedido_customer_id (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO products (name, description, price, stock) VALUES
('Picanha', 'Produto de exemplo', 89.90, 20),
('Pizza', 'Produto de exemplo', 49.90, 35),
('Hambúrguer', 'Produto de exemplo', 29.90, 50),
('Refrigerante', 'Produto de exemplo', 9.90, 100);

INSERT INTO customers (name, email, phone) VALUES
('Mariana Silva', 'mariana@example.com', '71999990001'),
('João Santos', 'joao@example.com', '71999990002'),
('Ana Costa', 'ana@example.com', '71999990003');

INSERT INTO orders (customer_id, total, status) VALUES
(1, 89.90, 'paid'),
(2, 59.80, 'paid'),
(3, 29.90, 'pending');

INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES
(1, 1, 1, 89.90, 89.90),
(2, 2, 1, 49.90, 49.90),
(2, 4, 1, 9.90, 9.90),
(3, 3, 1, 29.90, 29.90);

INSERT INTO pedido (customer_id, total, status) VALUES
(1, 89.90, 'pago'),
(2, 59.80, 'pago'),
(3, 29.90, 'pendente');

SHOW TABLES;

SELECT * FROM products;
SELECT * FROM orders;
SELECT * FROM order_items;
SELECT * FROM customers;
SELECT * FROM costumers;
SELECT * FROM pedido;
