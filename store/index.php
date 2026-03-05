<?php
$pageTitle = 'Welcome';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cart_helpers.php';
require_once __DIR__ . '/includes/product_helpers.php';
require_once __DIR__ . '/includes/header.php';

$featuredProducts = array_slice(getAllProducts(), 0, 3);
?>

<a href="#main-content" class="skip-link">Skip to main content</a>

<!-- HERO -->
<section class="hero" aria-labelledby="hero-heading">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <p class="hero-eyebrow">Welcome to</p>
                <h1 class="hero-title" id="hero-heading">
                    The Modern<br><span>Store.</span>
                </h1>
                <p class="hero-subtitle">
                    Curated products crafted for quality and style.
                    Shop our collection and discover what sets us apart.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="/pages/products.php" class="btn-store">
                        Shop Now <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                    <a href="/pages/about.php" class="btn-store-outline"
                       style="color:#fff;border-color:rgba(255,255,255,0.4);">Our Story</a>
                </div>
            </div>
            <div class="col-lg-5 offset-lg-1">
                <div class="hero-image-block">
                    <svg width="100%" viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg"
                         role="img" aria-label="Store decorative graphic">
                        <rect width="400" height="300" rx="12" fill="rgba(255,255,255,0.05)"/>
                        <rect x="40" y="40" width="140" height="120" rx="8" fill="rgba(200,169,110,0.3)"/>
                        <rect x="220" y="40" width="140" height="120" rx="8" fill="rgba(200,169,110,0.2)"/>
                        <rect x="40" y="180" width="320" height="80" rx="8" fill="rgba(200,169,110,0.15)"/>
                        <text x="200" y="30" text-anchor="middle" fill="rgba(200,169,110,0.6)"
                              font-size="12" font-family="Georgia">CURATED COLLECTION</text>
                        <text x="200" y="225" text-anchor="middle" fill="rgba(255,255,255,0.5)"
                              font-size="14">★★★★★  Trusted by customers</text>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES STRIP -->
<section class="features-strip" aria-label="Store features">
    <div class="container">
        <div class="row g-3 justify-content-center">
            <div class="col-6 col-md-3">
                <div class="feature-item">
                    <span class="feature-icon"><i class="bi bi-truck" aria-hidden="true"></i></span>
                    <div>
                        <div class="feature-text-label">Free Shipping</div>
                        <div class="feature-text-sub">On orders over £50</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-item">
                    <span class="feature-icon"><i class="bi bi-arrow-return-left" aria-hidden="true"></i></span>
                    <div>
                        <div class="feature-text-label">Easy Returns</div>
                        <div class="feature-text-sub">30-day policy</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-item">
                    <span class="feature-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                    <div>
                        <div class="feature-text-label">Secure Checkout</div>
                        <div class="feature-text-sub">Encrypted payments</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-item">
                    <span class="feature-icon"><i class="bi bi-headset" aria-hidden="true"></i></span>
                    <div>
                        <div class="feature-text-label">24/7 Support</div>
                        <div class="feature-text-sub">Always here to help</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="section-pad" aria-labelledby="products-heading">
    <div class="container">
        <div class="section-header">
            <p class="eyebrow">Our Collection</p>
            <h2 id="products-heading">Featured Products</h2>
            <p>Carefully selected for quality and value.</p>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredProducts as $product): ?>
            <div class="col-sm-6 col-lg-4">
                <article class="product-card">
                    <div class="card-img-wrapper">
                        <img src="/assets/images/<?= e($product['image']) ?>"
                             alt="<?= e($product['name']) ?>"
                             loading="lazy"
                             onerror="this.src='/assets/images/placeholder.svg'">
                    </div>
                    <div class="card-body">
                        <h3 class="product-name"><?= e($product['name']) ?></h3>
                        <p class="product-desc"><?= e(substr($product['description'], 0, 100)) ?>…</p>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span class="product-price">£<?= number_format($product['price'], 2) ?></span>
                            <div class="d-flex gap-2">
                                <a href="/pages/product_detail.php?id=<?= (int)$product['id'] ?>"
                                   class="btn-store-outline btn-sm">View</a>

                                <?php if (isLoggedIn()): ?>
                                <form method="POST" action="/pages/cart_action.php" data-ajax-cart="true">
                                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="btn-store btn-sm">
                                        <i class="bi bi-cart-plus" aria-hidden="true"></i> Add
                                    </button>
                                </form>
                                <?php else: ?>
                                <a href="/pages/login.php" class="btn-store btn-sm">
                                    <i class="bi bi-cart-plus" aria-hidden="true"></i> Add
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="/pages/products.php" class="btn-store">
                View All Products <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="section-pad-sm"
         style="background:var(--color-surface);border-top:1px solid var(--color-border);"
         aria-labelledby="reviews-heading">
    <div class="container">
        <div class="section-header">
            <p class="eyebrow">Reviews</p>
            <h2 id="reviews-heading">What Customers Say</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-stars" aria-label="5 stars">★★★★★</div>
                    <p class="testimonial-quote">"Absolutely love the quality. Fast shipping and great packaging."</p>
                    <p class="testimonial-author">— Sarah M.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-stars" aria-label="5 stars">★★★★★</div>
                    <p class="testimonial-quote">"Best purchase I've made this year. Arrived ahead of schedule."</p>
                    <p class="testimonial-author">— James T.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="testimonial-stars" aria-label="4 stars">★★★★☆</div>
                    <p class="testimonial-quote">"Great value for money. Customer support was very responsive."</p>
                    <p class="testimonial-author">— Priya K.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>