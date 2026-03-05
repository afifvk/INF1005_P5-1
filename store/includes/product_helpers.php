<?php
/**
 * includes/product_helpers.php
 * CRUD operations for the products table.
 * Compatible with PHP 7.x and 8.x
 */

function getAllProducts(): array {
    $pdo  = getDB();
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

/**
 * Get a single product by ID.
 * Returns false if not found.
 */
function getProductById(int $id) {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Create a new product (Admin use).
 */
function createProduct(string $name, string $desc, float $price, string $image, int $stock): int {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO products (name, description, price, image, stock)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $desc, $price, $image, $stock]);
    return (int) $pdo->lastInsertId();
}

/**
 * Update an existing product (Admin use).
 */
function updateProduct(int $id, string $name, string $desc, float $price, string $image, int $stock): bool {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        UPDATE products SET name=?, description=?, price=?, image=?, stock=?
        WHERE id=?
    ");
    return $stmt->execute([$name, $desc, $price, $image, $stock, $id]);
}

/**
 * Delete a product (Admin use).
 */
function deleteProduct(int $id): bool {
    $pdo  = getDB();
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    return $stmt->execute([$id]);
}