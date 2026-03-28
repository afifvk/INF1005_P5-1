<?php
/**
 * pages/product_detail.php — Single Product View
 * Validates the ID parameter, fetches the product, displays details.
 * Never trusts the URL parameter — always casts and validates.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/product_helpers.php';

// Validate and sanitize the ID from the URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid product ID.'];
    redirect(SITE_URL . '/pages/products.php');
}

$product = getProductById($id);

if (!$product) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Product not found.'];
    redirect(SITE_URL . '/pages/products.php');
}

$pageTitle = $product['name'];
require_once __DIR__ . '/../includes/header.php';
?>

<section class="section-pad" aria-labelledby="product-heading">
    <div class="container">

        <!-- Breadcrumb -->
        <nav aria-label="Breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($product['name']) ?></li>
            </ol>
        </nav>

        <div class="row g-5 align-items-start">

            <!-- Product Image -->
            <div class="col-md-5">
                <div class="product-detail-img">
                    <img src="<?= SITE_URL ?>/assets/images/<?= e($product['image']) ?>"
                         alt="<?= e($product['name']) ?> — detailed product image"
                         loading="eager"
                         onerror="this.src='<?= SITE_URL ?>/assets/images/placeholder.svg'">
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-md-7">

                <div class="d-flex align-items-center gap-3 mb-2">
                    <h1 id="product-heading" class="mb-0"><?= e($product['name']) ?></h1>
                    <?php if (isLoggedIn()): ?>
                    <button data-like-button="true"
                            data-product-id="<?= (int)$product['id'] ?>"
                            class="tea-like-btn<?= in_array((int)$product['id'], $likedProductIds ?? []) ? ' is-liked' : '' ?>"
                            aria-label="<?= in_array((int)$product['id'], $likedProductIds ?? []) ? 'Unlike' : 'Like' ?> <?= e($product['name']) ?>"
                            aria-pressed="<?= in_array((int)$product['id'], $likedProductIds ?? []) ? 'true' : 'false' ?>">
                        <i class="bi <?= in_array((int)$product['id'], $likedProductIds ?? []) ? 'bi-heart-fill' : 'bi-heart' ?>" aria-hidden="true"></i>
                    </button>
                    <?php else: ?>
                    <a href="login.php" class="tea-like-btn" aria-label="Log in to like <?= e($product['name']) ?>">
                        <i class="bi bi-heart" aria-hidden="true"></i>
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Stock status -->
                <div class="mb-3">
                    <?php if ($product['stock'] > 0): ?>
                        <span class="stock-badge in-stock" role="status">
                            <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
                            In Stock — <?= (int)$product['stock'] ?> available
                        </span>
                    <?php else: ?>
                        <span class="stock-badge out-of-stock" role="status">
                            <i class="bi bi-x-circle-fill me-1" aria-hidden="true"></i>
                            Out of Stock
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Price -->
                <p class="product-detail-price" aria-label="Price: $<?= number_format($product['price'], 2) ?>">
                    $<?= number_format($product['price'], 2) ?>
                </p>

                <hr class="divider-line">

                <!-- Description -->
                <div class="mb-4">
                    <h2 class="h6 text-uppercase text-muted mb-2" style="letter-spacing:.08em;">
                        Product Description
                    </h2>
                    <p><?= e($product['description']) ?></p>
                </div>

                <!-- Add to Cart -->
                <?php if ($product['stock'] > 0): ?>

                    <?php if (isLoggedIn()): ?>
                    <form method="POST"
                          action="cart_action.php"
                          data-ajax-cart="true"
                          aria-label="Add <?= e($product['name']) ?> to cart"
                          class="d-flex align-items-center gap-3 flex-wrap">

                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <!-- Quantity selector -->
                        <div class="qty-control" role="group" aria-label="Quantity selector">
                            <button type="button" data-action="decrement"
                                    aria-label="Decrease quantity">−</button>
                            <input type="number"
                                   name="quantity"
                                   value="1"
                                   min="1"
                                   max="<?= (int)$product['stock'] ?>"
                                   aria-label="Product quantity">
                            <button type="button" data-action="increment"
                                    aria-label="Increase quantity">+</button>
                        </div>

                        <button type="submit" class="btn-store">
                            <i class="bi bi-cart-plus me-1" aria-hidden="true"></i>
                            Add to Cart
                        </button>
                    </form>

                    <?php else: ?>
                    <div class="alert-info p-3 rounded border" role="alert">
                        <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                        <a href="login.php">Log in</a> or
                        <a href="register.php">register</a> to add this item to your cart.
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="text-muted">This product is currently unavailable.</p>
                <?php endif; ?>

                <hr class="divider-line">

                <!-- Trust badges -->
                <div class="d-flex gap-4 flex-wrap text-muted small">
                    <span><i class="bi bi-truck me-1" aria-hidden="true"></i>Free shipping over $50</span>
                    <span><i class="bi bi-arrow-return-left me-1" aria-hidden="true"></i>30-day returns</span>
                    <span><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Secure payment</span>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
