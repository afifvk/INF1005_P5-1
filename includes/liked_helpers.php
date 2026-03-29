<?php
/**
 * includes/liked_helpers.php
 * Helpers for user liked teas (liked_products table).
 */

function isProductLikedByUser(int $userId, int $productId): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT 1 FROM liked_products WHERE user_id = ? AND product_id = ? LIMIT 1');
    $stmt->execute([$userId, $productId]);
    return (bool)$stmt->fetchColumn();
}

function getLikedProductIdsByUser(int $userId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT product_id FROM liked_products WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return array_map('intval', $rows ?: []);
}

function getLikedProductsByUser(int $userId): array {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'SELECT p.*, lp.created_at AS liked_at
         FROM liked_products lp
         INNER JOIN products p ON p.id = lp.product_id
         WHERE lp.user_id = ?
         ORDER BY lp.created_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function addLikedProduct(int $userId, int $productId): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare(
        'INSERT INTO liked_products (user_id, product_id)
         VALUES (?, ?)
         ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP'
    );
    return $stmt->execute([$userId, $productId]);
}

function removeLikedProduct(int $userId, int $productId): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare('DELETE FROM liked_products WHERE user_id = ? AND product_id = ?');
    return $stmt->execute([$userId, $productId]);
}

function toggleLikedProduct(int $userId, int $productId): array {
    if (isProductLikedByUser($userId, $productId)) {
        $ok = removeLikedProduct($userId, $productId);
        return ['success' => $ok, 'liked' => false];
    }

    $ok = addLikedProduct($userId, $productId);
    return ['success' => $ok, 'liked' => true];
}
