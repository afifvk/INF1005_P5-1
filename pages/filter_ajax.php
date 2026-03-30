<?php
/**
 * filter_ajax.php
 * AJAX endpoint — returns filtered tea cards.
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/product_helpers.php';
require_once dirname(__DIR__) . '/includes/liked_helpers.php';
require_once dirname(__DIR__) . '/includes/tea_helpers.php';

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    exit('Forbidden');
}

$flavours = isset($_GET['flavours']) && is_array($_GET['flavours']) ? array_map('trim', $_GET['flavours']) : [];
$benefits = isset($_GET['benefits']) && is_array($_GET['benefits']) ? array_map('trim', $_GET['benefits']) : [];
$caffeine = isset($_GET['caffeine']) && is_array($_GET['caffeine']) ? array_map('trim', $_GET['caffeine']) : [];
$origins  = isset($_GET['origins'])  && is_array($_GET['origins'])  ? array_map('trim', $_GET['origins'])  : [];
$sort     = isset($_GET['sort'])     ? trim($_GET['sort'])           : 'name_asc';
$search   = isset($_GET['search'])   ? trim($_GET['search'])         : '';

$products = getFilteredTeas($flavours, $benefits, $caffeine, $origins, $sort, $search);
$likedIds = isLoggedIn() ? getLikedProductIdsByUser((int)$_SESSION['user_id']) : [];

if (empty($products)): ?>
    <div class="text-center py-5">
        <i class="bi bi-search display-3 text-muted" aria-hidden="true"></i>
        <p class="mt-3 text-muted">No teas match your filters.</p>
        <p class="text-muted small">Try adjusting your selection or clearing filters.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($products as $product): ?>
        <div class="col-sm-6 col-lg-4">
            <article class="product-card h-100">

                <!-- Image with heart overlaid in top-right corner -->
                <div class="card-img-wrapper" style="position:relative;">
                    <a href="product_detail.php?id=<?= (int)$product['id'] ?>">
                        <img src="/assets/images/<?= e($product['image']) ?>"
                             alt="<?= e($product['name']) ?>"
                             loading="lazy"
                             onerror="this.src='/assets/images/placeholder.svg'">
                    </a>
                    <?php if (isLoggedIn()): ?>
                        <?php $isLiked = in_array((int)$product['id'], $likedIds, true); ?>
                        <button type="button"
                                class="tea-like-btn <?= $isLiked ? 'is-liked' : '' ?>"
                                data-like-button="true"
                                data-product-id="<?= (int)$product['id'] ?>"
                                aria-pressed="<?= $isLiked ? 'true' : 'false' ?>"
                                aria-label="<?= $isLiked ? 'Remove from liked teas' : 'Add to liked teas' ?>"
                                style="position:absolute;top:10px;right:10px;">
                            <i class="bi <?= $isLiked ? 'bi-heart-fill' : 'bi-heart' ?>" aria-hidden="true"></i>
                        </button>
                    <?php else: ?>
                        <a href="login.php"
                           class="tea-like-btn"
                           aria-label="Login to save liked teas"
                           style="position:absolute;top:10px;right:10px;">
                            <i class="bi bi-heart" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="card-body d-flex flex-column">
                    <span class="caffeine-badge caffeine-badge--<?= strtolower(e($product['caffeine_level'])) ?>">
                        <?= e($product['caffeine_level']) ?> Caffeine
                    </span>
                    <p class="text-muted small mb-1">
                        📍 <?= e($product['origin']) ?> &nbsp;·&nbsp; 🌿 <?= e($product['flavour']) ?>
                    </p>
                    <h2 class="product-name h5">
                        <a href="product_detail.php?id=<?= (int)$product['id'] ?>" style="color:inherit;text-decoration:none;">
                            <?= e($product['name']) ?>
                        </a>
                    </h2>
                    <p class="product-desc flex-grow-1"><?= e($product['description']) ?></p>
                    <div class="tea-tags">
                        <?php foreach (explode(',', $product['health_benefits']) as $b): ?>
                            <span class="tea-tag"><?= e(trim($b)) ?></span>
                        <?php endforeach; ?>
                    </div>
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
                        <span class="product-price">$<?= number_format($product['price'], 2) ?></span>
                        <div class="d-flex gap-2">
                            <a href="product_detail.php?id=<?= (int)$product['id'] ?>" class="btn-store-outline btn-sm">Details</a>
                            <?php if (isLoggedIn() && $product['stock'] > 0): ?>
                            <form method="POST" action="cart_action.php" data-ajax-cart="true">
                                <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                <input type="hidden" name="action"     value="add">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
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