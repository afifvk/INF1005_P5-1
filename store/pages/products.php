<?php
$pageTitle = 'Products';
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/includes/product_helpers.php';
require_once dirname(__DIR__) . '/includes/header.php';

$products = getAllProducts();
?>

<section class="section-pad" aria-labelledby="catalogue-heading">
    <div class="container">

        <div class="row mb-5">
            <div class="col">
                <nav aria-label="Breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Products</li>
                    </ol>
                </nav>
                <h1 id="catalogue-heading" class="mb-2">Our Products</h1>
                <p class="text-muted"><?= count($products) ?> item<?= count($products) !== 1 ? 's' : '' ?> available</p>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bag-x display-3 text-muted" aria-hidden="true"></i>
                <p class="mt-3 text-muted">No products available at this time.</p>
            </div>

        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products as $product): ?>
            <div class="col-sm-6 col-lg-4">
                <article class="product-card h-100">

                    <a href="product_detail.php?id=<?= (int)$product['id'] ?>">
                        <div class="card-img-wrapper">
                            <img src="/assets/images/<?= e($product['image']) ?>"
                                 alt="<?= e($product['name']) ?>"
                                 loading="lazy"
                                 onerror="this.src='/assets/images/placeholder.svg'">
                        </div>
                    </a>

                    <div class="card-body d-flex flex-column">
                        <h2 class="product-name h5">
                            <a href="product_detail.php?id=<?= (int)$product['id'] ?>"
                               style="color:inherit;text-decoration:none;">
                                <?= e($product['name']) ?>
                            </a>
                        </h2>

                        <p class="product-desc flex-grow-1"><?= e($product['description']) ?></p>

                        <div class="mb-2">
                            <?php if ($product['stock'] > 0): ?>
                                <span class="stock-badge in-stock">
                                    <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>
                                    In Stock (<?= (int)$product['stock'] ?>)
                                </span>
                            <?php else: ?>
                                <span class="stock-badge out-of-stock">
                                    <i class="bi bi-x-circle-fill me-1" aria-hidden="true"></i>
                                    Out of Stock
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-auto pt-2 flex-wrap gap-2">
                            <span class="product-price">£<?= number_format($product['price'], 2) ?></span>

                            <div class="d-flex gap-2">
                                <a href="product_detail.php?id=<?= (int)$product['id'] ?>"
                                   class="btn-store-outline btn-sm">Details</a>

                                <?php if (isLoggedIn() && $product['stock'] > 0): ?>
                                <form method="POST" action="cart_action.php" data-ajax-cart="true">
                                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="btn-store btn-sm">
                                        <i class="bi bi-cart-plus" aria-hidden="true"></i> Add
                                    </button>
                                </form>
                                <?php elseif (!isLoggedIn()): ?>
                                <a href="login.php" class="btn-store btn-sm">Login to Buy</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>