-- ============================================================
-- Store Database Schema
-- Run this once to set up the database.
-- All tables use InnoDB for foreign key support.
-- ============================================================

CREATE DATABASE IF NOT EXISTS store_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE store_db;

-- ------------------------------------------------------------
-- USERS TABLE
-- Stores registered customer accounts.
-- Passwords stored as bcrypt hashes — NEVER plain text.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)  NOT NULL UNIQUE,
    email        VARCHAR(150) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,        -- bcrypt hash
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- PRODUCTS TABLE
-- Stores the product catalogue.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150)   NOT NULL,
    description TEXT           NOT NULL,
    price       DECIMAL(10,2)  NOT NULL,
    image       VARCHAR(255)   NOT NULL DEFAULT 'placeholder.jpg',
    stock       INT UNSIGNED   NOT NULL DEFAULT 0,
    created_at  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- CART TABLE
-- One cart row per user session.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_cart (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- CART ITEMS TABLE
-- Line items inside a cart.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cart_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL DEFAULT 1,
    added_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id)    REFERENCES cart(id)     ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_product (cart_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- SEED DATA — Sample Products
-- ------------------------------------------------------------
INSERT INTO products (name, description, price, image, stock) VALUES
('Item One',   'A premium quality product with excellent durability and modern design. Perfect for everyday use.', 29.99, 'item1.jpg', 50),
('Item Two',   'An elegant solution for your daily needs. Crafted with care and built to last for years to come.', 49.99, 'item2.jpg', 30),
('Item Three', 'Our flagship product offering outstanding performance and value. Trusted by thousands of customers.', 79.99, 'item3.jpg', 20);

-- ------------------------------------------------------------
-- SEED DATA — Demo Admin User
-- Password: Admin1234!  (bcrypt hash below — change in production)
-- Generate your own: php -r "echo password_hash('YourPassword', PASSWORD_BCRYPT);"
-- ------------------------------------------------------------
INSERT INTO users (username, email, password) VALUES
('admin', 'admin@store.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- ------------------------------------------------------------
-- RECOMMENDATIONS TABLE
-- Stores user quiz recommendations 
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS recommendations (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NULL,
    product_title VARCHAR(150) NULL,
    answers_json TEXT,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
