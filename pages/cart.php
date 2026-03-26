<?php
/**
 * pages/cart.php — Shopping Cart
 * Displays the user's cart items with update/remove controls.
 * Requires login.
 */

$pageTitle = 'Your Cart';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/cart_helpers.php';
require_once __DIR__ . '/../includes/profile_helpers.php';

// Redirect to login if not authenticated
if (!isLoggedIn()) {
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'Please log in to view your cart.'];
    redirect(SITE_URL . '/pages/login.php');
}

$userId = (int)$_SESSION['user_id'];
$items  = getCartItems($userId);
$total  = getCartTotal($userId);
$user   = getUserById($userId);
?>

<section class="section-pad" aria-labelledby="cart-heading">
    
    <div class="container">
        <!-- Shipping Address Banner -->
        <div class="mb-4 p-3 rounded border">
            <p class="text-muted small text-uppercase fw-semibold mb-1">
                <i class="bi bi-truck me-1" aria-hidden="true"></i> Shipping Address
            </p>
            <?php if (!empty($user['address'])): ?>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <p class="mb-0"><?= nl2br(e($user['address'])) ?></p>
                    <a href="profile.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil me-1" aria-hidden="true"></i> Edit
                    </a>
                </div>
            <?php else: ?>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <p class="mb-0 text-muted">No shipping address on file.</p>
                    <a href="profile.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil me-1" aria-hidden="true"></i> Add Address
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cart</li>
            </ol>
        </nav>

        <h1 id="cart-heading" class="mb-4">
            <i class="bi bi-cart3 me-2" aria-hidden="true"></i>
            Your Cart
        </h1>

        <?php if (empty($items)): ?>
        <!-- Empty cart state -->
        <div class="text-center py-5" role="status" aria-live="polite">
            <i class="bi bi-cart-x display-1 text-muted" aria-hidden="true"></i>
            <h2 class="h4 mt-3">Your cart is empty</h2>
            <p class="text-muted mb-4">Looks like you haven't added anything yet.</p>
            <a href="products.php" class="btn-store">
                <i class="bi bi-bag me-1" aria-hidden="true"></i> Browse Products
            </a>
        </div>

        <?php else: ?>
        <div class="row g-4">

            <!-- Cart Items -->
            <div class="col-lg-8">
                <div role="list" aria-label="Cart items">
                    <?php foreach ($items as $item): ?>
                    <div class="cart-item" role="listitem" aria-label="<?= e($item['name']) ?>">

                        <!-- Product Image -->
                        <img class="cart-item-img"
                             src="<?= SITE_URL ?>/assets/images/<?= e($item['image']) ?>"
                             alt="<?= e($item['name']) ?>"
                             loading="lazy"
                             onerror="this.src='<?= SITE_URL ?>/assets/images/placeholder.svg'">

                        <!-- Product Info -->
                        <div class="flex-grow-1">
                            <p class="cart-item-name mb-1"><?= e($item['name']) ?></p>
                            <p class="text-muted small mb-0">
                                Unit price: $<?= number_format($item['price'], 2) ?>
                            </p>
                        </div>

                        <!-- Quantity Update Form -->
                        <form method="POST"
                              action="cart_action.php"
                              aria-label="Update quantity for <?= e($item['name']) ?>">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                            <div class="qty-control" role="group" aria-label="Quantity for <?= e($item['name']) ?>">
                                <button type="button" data-action="decrement"
                                        aria-label="Decrease quantity">−</button>
                                <input type="number"
                                       name="quantity"
                                       value="<?= (int)$item['quantity'] ?>"
                                       min="0"
                                       aria-label="Quantity"
                                       onchange="this.form.submit()">
                                <button type="button" data-action="increment"
                                        aria-label="Increase quantity">+</button>
                            </div>
                        </form>

                        <!-- Line Total -->
                        <p class="fw-bold text-nowrap mb-0">
                            Line total: $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </p>

                        <!-- Remove Button -->
                        <form method="POST"
                              action="cart_action.php"
                              aria-label="Remove <?= e($item['name']) ?> from cart">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?= (int)$item['product_id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger"
                                    aria-label="Remove <?= e($item['name']) ?> from cart"
                                    onclick="return confirm('Remove this item?')">
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>
                        </form>

                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Clear cart -->
                <form method="POST" action="cart_action.php" class="mt-3">
                    <input type="hidden" name="action" value="clear">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit"
                            class="btn btn-sm btn-outline-secondary"
                            onclick="return confirm('Clear your entire cart?')"
                            aria-label="Clear all items from cart">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>
                        Clear Cart
                    </button>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <section class="cart-total-box" aria-label="Order summary">
                    <h2 class="h5 mb-3">Order Summary</h2>
                    <hr class="divider-line my-2">

                    <table class="table table-borderless mb-0" aria-label="Price breakdown">
                        <tbody>
                            <tr>
                                <td class="text-muted ps-0">Subtotal</td>
                                <td class="text-end fw-semibold">$<?= number_format($total, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-0">Shipping</td>
                                <td class="text-end">
                                    <?php if ($total >= 50): ?>
                                        <span class="text-success">Free</span>
                                    <?php else: ?>
                                        $3.99
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <hr class="divider-line my-2">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="fw-bold">Total</span>
                        <span class="cart-total-amount">
                            $<?= number_format($total >= 50 ? $total : $total + 3.99, 2) ?>
                        </span>
                    </div>

                    <?php if ($total < 50): ?>
                    <p class="small text-muted mb-3">
                        <i class="bi bi-truck me-1" aria-hidden="true"></i>
                        Add $<?= number_format(50 - $total, 2) ?> more for free shipping.
                    </p>
                    <?php endif; ?>

                    <!-- Checkout form -->
                    <?php if (empty($user['address'])): ?>
                        <p class="small text-danger mb-2">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            Please <a href="profile.php" class="text-decoration-underline">add a shipping address</a> before checking out.
                        </p>
                        <button class="btn-gold w-100 py-3 rounded" disabled>
                            <i class="bi bi-lock me-1" aria-hidden="true"></i>
                            Proceed to Checkout
                        </button>
                    <?php else: ?>
                        <form method="POST" action="cart_action.php">
                            <input type="hidden" name="action" value="checkout">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit"
                                    class="btn-gold w-100 py-3 rounded"
                                    onclick="return confirm('Confirm your order?')">
                                <i class="bi bi-lock me-1" aria-hidden="true"></i>
                                Proceed to Checkout
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="products.php" class="small text-muted">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>
                            Continue shopping
                        </a>
                    </div>
                </section>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>