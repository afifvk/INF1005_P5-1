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

/**
 * Creates an order from the user's current cart.
 * Inserts into orders + order_items, then clears the cart.
 * Returns the new order ID on success, or false on failure.
 */
function createOrder(int $userId, string $shippingName, string $shippingAddress): int|false {
    $items = getCartItems($userId);
    if (empty($items)) {
        return false;
    }

    $pdo = getDB();

    // Calculate total (excluding shipping)
    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $shipping   = $subtotal >= 50 ? 0.0 : 3.99;
    $totalCost  = $subtotal + $shipping;

    try {
        $pdo->beginTransaction();

        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, status, total_cost, shipping_name, shipping_address)
            VALUES (?, 'pending', ?, ?, ?)
        ");
        $stmt->execute([$userId, $totalCost, $shippingName, $shippingAddress]);
        $orderId = (int) $pdo->lastInsertId();

        // Insert order items
        $stmt = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($items as $item) {
            $lineSubtotal = $item['price'] * $item['quantity'];
            $stmt->execute([
                $orderId,
                $item['product_id'],
                $item['quantity'],
                $item['price'],
                $lineSubtotal,
            ]);
        }

        // Clear the cart
        clearCart($userId);

        $pdo->commit();
        return $orderId;

    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Fetches a single order with its items for the confirmation page.
 * Returns null if not found or doesn't belong to the user.
 */
function getOrderWithItems(int $orderId, int $userId): ?array {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT * FROM orders WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch();

    if (!$order) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $order['items'] = $stmt->fetchAll();

    return $order;
}