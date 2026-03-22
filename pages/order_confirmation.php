<?php
/**
 * pages/order_confirmation.php — Order Confirmation
 * Shown after a successful checkout.
 * Requires login.
 */

$pageTitle = 'Order Confirmed';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/cart_helpers.php';

if (!isLoggedIn()) {
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'Please log in to view your order.'];
    redirect(SITE_URL . '/pages/login.php');
}

$userId  = (int)$_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId < 1) {
    redirect(SITE_URL . '/pages/products.php');
}

$order = getOrderWithItems($orderId, $userId);

if (!$order) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Order not found.'];
    redirect(SITE_URL . '/pages/products.php');
}
?>

<section class="section-pad" aria-labelledby="confirmation-heading">
    <div class="container" style="max-width: 720px;">

        <!-- Success Banner -->
        <div class="text-center mb-5">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;" aria-hidden="true"></i>
            <h1 id="confirmation-heading" class="mt-3 mb-1">Order Confirmed!</h1>
            <p class="text-muted">Thank you for your purchase. Your order has been placed successfully.</p>
            <p class="text-muted small">Order ID: <strong>#<?= $order['id'] ?></strong></p>
        </div>

        <!-- Shipping Info -->
        <div class="mb-4 p-3 rounded border">
            <p class="text-muted small text-uppercase fw-semibold mb-1">
                <i class="bi bi-truck me-1" aria-hidden="true"></i> Shipping To
            </p>
            <p class="mb-0 fw-semibold"><?= e($order['shipping_name']) ?></p>
            <p class="mb-0"><?= nl2br(e($order['shipping_address'])) ?></p>
        </div>

        <!-- Order Items -->
        <div class="mb-4">
            <h2 class="h5 mb-3">Items Ordered</h2>
            <?php foreach ($order['items'] as $item): ?>
            <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                <img src="<?= SITE_URL ?>/assets/images/<?= e($item['image']) ?>"
                     alt="<?= e($item['name']) ?>"
                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                     onerror="this.src='<?= SITE_URL ?>/assets/images/placeholder.svg'">
                <div class="flex-grow-1">
                    <p class="mb-0 fw-semibold"><?= e($item['name']) ?></p>
                    <p class="mb-0 text-muted small">
                        $<?= number_format($item['unit_price'], 2) ?> × <?= (int)$item['quantity'] ?>
                    </p>
                </div>
                <p class="mb-0 fw-bold">$<?= number_format($item['subtotal'], 2) ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Order Total -->
        <div class="p-3 rounded border">
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted">Subtotal</span>
                <?php
                    $itemsTotal = array_sum(array_column($order['items'], 'subtotal'));
                    $shipping   = $order['total_cost'] - $itemsTotal;
                ?>
                <span>$<?= number_format($itemsTotal, 2) ?></span>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">Shipping</span>
                <span>
                    <?php if ($shipping <= 0): ?>
                        <span class="text-success">Free</span>
                    <?php else: ?>
                        $<?= number_format($shipping, 2) ?>
                    <?php endif; ?>
                </span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between fw-bold fs-5">
                <span>Total</span>
                <span>$<?= number_format($order['total_cost'], 2) ?></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="text-center mt-5">
            <a href="products.php" class="btn-store">
                <i class="bi bi-bag me-1" aria-hidden="true"></i> Continue Shopping
            </a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>