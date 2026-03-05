<?php
/**
 * includes/cart_helpers.php
 * All cart-related database operations.
 * Compatible with PHP 7.x and 8.x
 */

function getOrCreateCart(int $userId): int {
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    $cart = $stmt->fetch();

    if ($cart) {
        return (int) $cart['id'];
    }

    $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
    $stmt->execute([$userId]);
    return (int) $pdo->lastInsertId();
}

function addToCart(int $userId, int $productId, int $quantity = 1): bool {
    $cartId = getOrCreateCart($userId);
    $pdo    = getDB();

    $stmt = $pdo->prepare("
        INSERT INTO cart_items (cart_id, product_id, quantity)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
    ");
    return $stmt->execute([$cartId, $productId, $quantity]);
}

function removeFromCart(int $userId, int $productId): bool {
    $cartId = getOrCreateCart($userId);
    $pdo    = getDB();

    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?");
    return $stmt->execute([$cartId, $productId]);
}

function updateCartItem(int $userId, int $productId, int $quantity): bool {
    if ($quantity <= 0) {
        return removeFromCart($userId, $productId);
    }
    $cartId = getOrCreateCart($userId);
    $pdo    = getDB();

    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?");
    return $stmt->execute([$quantity, $cartId, $productId]);
}

function getCartItems(int $userId): array {
    $cartId = getOrCreateCart($userId);
    $pdo    = getDB();

    $stmt = $pdo->prepare("
        SELECT ci.id, ci.quantity,
               p.id AS product_id, p.name, p.price, p.image
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.cart_id = ?
        ORDER BY ci.added_at DESC
    ");
    $stmt->execute([$cartId]);
    return $stmt->fetchAll();
}

function getCartCount(int $userId): int {
    $cartId = getOrCreateCart($userId);
    $pdo    = getDB();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cartId]);
    return (int) $stmt->fetchColumn();
}

function clearCart(int $userId): bool {
    $cartId = getOrCreateCart($userId);
    $pdo    = getDB();

    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    return $stmt->execute([$cartId]);
}

function getCartTotal(int $userId): float {
    $items = getCartItems($userId);
    $total = 0.0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}