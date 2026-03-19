<?php
/**
 * pages/orders.php — My Orders
 * Shows all orders placed by the logged-in user.
 * Requires login.
 */

$pageTitle = 'My Orders';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/order_helpers.php';

if (!isLoggedIn()) {
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'Please log in to view your orders.'];
    redirect(SITE_URL . '/pages/login.php');
}

$userId = (int)$_SESSION['user_id'];
$orders = getUserOrders($userId);
?>

<section class="section-pad" aria-labelledby="orders-heading">
    <div class="container">

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Orders</li>
            </ol>
        </nav>

        <h1 id="orders-heading" class="mb-4">
            <i class="bi bi-bag-check me-2" aria-hidden="true"></i>
            My Orders
        </h1>

        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bag-x display-1 text-muted" aria-hidden="true"></i>
                <h2 class="h4 mt-3">No orders yet</h2>
                <p class="text-muted mb-4">You haven't placed any orders yet.</p>
                <a href="products.php" class="btn-store">
                    <i class="bi bi-bag me-1" aria-hidden="true"></i> Browse Products
                </a>
            </div>

        <?php else: ?>
            <div class="d-flex flex-column gap-4">
                <?php foreach ($orders as $order): ?>
                <?php
                    $statusColors = [
                        'pending'   => 'warning',
                        'confirmed' => 'info',
                        'shipped'   => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                    ];
                    $statusIcons = [
                        'pending'   => 'bi-clock',
                        'confirmed' => 'bi-check-circle',
                        'shipped'   => 'bi-truck',
                        'delivered' => 'bi-bag-check',
                        'cancelled' => 'bi-x-circle',
                    ];
                    $color = $statusColors[$order['status']] ?? 'secondary';
                    $icon  = $statusIcons[$order['status']]  ?? 'bi-circle';
                ?>
                <div class="border rounded p-4">

                    <!-- Order Header -->
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <p class="mb-0 fw-bold">Order #<?= $order['id'] ?></p>
                            <p class="mb-0 text-muted small">
                                Placed on <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
                            </p>
                        </div>
                        <span class="badge bg-<?= $color ?> fs-6 px-3 py-2">
                            <i class="bi <?= $icon ?> me-1" aria-hidden="true"></i>
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>

                    <!-- Status Progress Bar -->
                    <?php
                        $steps    = ['pending', 'confirmed', 'shipped', 'delivered'];
                        $current  = array_search($order['status'], $steps);
                        $cancelled = $order['status'] === 'cancelled';
                    ?>
                    <?php if (!$cancelled): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between position-relative" style="z-index:1;">
                            <?php foreach ($steps as $i => $step): ?>
                            <?php $done = $current !== false && $i <= $current; ?>
                            <div class="text-center flex-fill">
                                <div class="mx-auto mb-1 rounded-circle d-flex align-items-center justify-content-center"
                                     style="width:32px;height:32px;
                                            background:<?= $done ? 'var(--bs-' . $color . ')' : '#dee2e6' ?>;
                                            color:<?= $done ? '#fff' : '#aaa' ?>;">
                                    <i class="bi <?= $statusIcons[$step] ?> small" aria-hidden="true"></i>
                                </div>
                                <p class="mb-0 small <?= $done ? 'fw-semibold' : 'text-muted' ?>">
                                    <?= ucfirst($step) ?>
                                </p>
                            </div>
                            <?php if ($i < count($steps) - 1): ?>
                            <div class="flex-fill" style="margin-top:15px;">
                                <div style="height:2px;background:<?= ($current !== false && $i < $current) ? 'var(--bs-' . $color . ')' : '#dee2e6' ?>;"></div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-danger py-2 small mb-3">
                        <i class="bi bi-x-circle me-1"></i> This order was cancelled.
                    </div>
                    <?php endif; ?>

                    <!-- Items -->
                    <div class="mb-3">
                        <?php foreach ($order['items'] as $item): ?>
                        <div class="d-flex align-items-center gap-3 py-2 border-top">
                            <img src="<?= SITE_URL ?>/assets/images/<?= e($item['image']) ?>"
                                 alt="<?= e($item['name']) ?>"
                                 style="width:50px;height:50px;object-fit:cover;border-radius:6px;"
                                 onerror="this.src='<?= SITE_URL ?>/assets/images/placeholder.svg'">
                            <div class="flex-grow-1">
                                <p class="mb-0 fw-semibold"><?= e($item['name']) ?></p>
                                <p class="mb-0 text-muted small">
                                    £<?= number_format($item['unit_price'], 2) ?> × <?= (int)$item['quantity'] ?>
                                </p>
                            </div>
                            <p class="mb-0 fw-bold">£<?= number_format($item['subtotal'], 2) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Footer: address + total -->
                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 border-top pt-3">
                        <div>
                            <p class="mb-0 text-muted small text-uppercase fw-semibold">Shipping to</p>
                            <p class="mb-0 small"><?= e($order['shipping_name']) ?></p>
                            <p class="mb-0 small text-muted"><?= e($order['shipping_address']) ?></p>
                        </div>
                        <div class="text-end">
                            <p class="mb-0 text-muted small">Order Total</p>
                            <p class="mb-0 fw-bold fs-5">£<?= number_format($order['total_cost'], 2) ?></p>
                        </div>
                    </div>

                    <!-- Cancel button — only for pending orders -->
                    <?php if ($order['status'] === 'pending'): ?>
                    <div class="mt-3 border-top pt-3">
                        <form method="POST" action="order_action.php"
                              onsubmit="return confirm('Are you sure you want to cancel this order?')">
                            <input type="hidden" name="action" value="cancel">
                            <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-x-circle me-1" aria-hidden="true"></i>
                                Cancel Order
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>