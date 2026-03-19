<?php
/**
 * includes/order_helpers.php
 * Order-related database queries.
 */

/**
 * Returns all orders for a user, newest first, with their items.
 */
function getUserOrders(int $userId): array {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT * FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();

    if (empty($orders)) {
        return [];
    }

    // Fetch items for all orders in one query
    $orderIds    = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.id ASC
    ");
    $stmt->execute($orderIds);
    $allItems = $stmt->fetchAll();

    // Group items by order_id
    $itemsByOrder = [];
    foreach ($allItems as $item) {
        $itemsByOrder[$item['order_id']][] = $item;
    }

    // Attach items to each order
    foreach ($orders as &$order) {
        $order['items'] = $itemsByOrder[$order['id']] ?? [];
    }

    return $orders;
}
/**
 * Cancels an order only if it belongs to the user and is still pending.
 * Returns true on success, false otherwise.
 */
function cancelOrder(int $orderId, int $userId): bool {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        UPDATE orders
        SET status = 'cancelled'
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->execute([$orderId, $userId]);

    return $stmt->rowCount() > 0;
}