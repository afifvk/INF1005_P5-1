-- ============================================================
-- Store Database Schema
-- Run this once to set up the database.
-- ============================================================

CREATE DATABASE IF NOT EXISTS store_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE store_db;

CREATE TABLE IF NOT EXISTS users (
    id                         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name                 VARCHAR(80)  NULL,
    last_name                  VARCHAR(80)  NOT NULL,
    address                    TEXT         NULL,
    email                      VARCHAR(150) NOT NULL UNIQUE,
    password                   VARCHAR(255) NOT NULL,
    role                       ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    is_verified                TINYINT(1)   NOT NULL DEFAULT 0,
    verification_token_hash    CHAR(64)     NULL,
    verification_expires_at    DATETIME     NULL,
    verification_last_sent_at  DATETIME     NULL,
    verified_at                DATETIME     NULL,
    created_at                 DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_verification_token_hash (verification_token_hash)
    password_reset_token_hash  CHAR(64)     NULL,
    password_reset_expires_at  DATETIME     NULL,
    password_reset_requested_at DATETIME    NULL,
    password_reset_at          DATETIME     NULL,
    created_at                 DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_verification_token_hash (verification_token_hash),
    INDEX idx_password_reset_token_hash (password_reset_token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS cart (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_cart (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
INSERT INTO users (first_name, last_name, email, password, role, is_verified, verified_at) VALUES
('Admin', 'User', 'admin@store.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW());


CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    identifier VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action_identifier_created (action, identifier, created_at)
);

CREATE TABLE IF NOT EXISTS liked_products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_liked_product (user_id, product_id),
    INDEX idx_liked_user_created (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
