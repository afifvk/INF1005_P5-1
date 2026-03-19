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
                    <div class="quiz-side-box quiz-full" aria-hidden="false">
                        <div class="quiz-side-inner">
                            <h3>Create Your Personalitea</h3>
                            <p>Take our 4-question quiz to discover the tea that fits you.</p>
                            <button id="start-quiz-btn" class="btn-store btn-quiz-cta">Create your own personalitea</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Expose login state and CSRF token to quiz JS
    window.__APP__ = window.__APP__ || {};
    window.__APP__.isLoggedIn = <?= isLoggedIn() ? 'true' : 'false' ?>;
    window.__APP__.csrfToken  = '<?= $csrf ?>';
</script>

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
<section id="quiz-section" class="section-pad" aria-labelledby="quiz-heading">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="quiz-panel-wrapper">
                    <div class="quiz-panel quiz-panel-form">
                        <form id="personalitea-quiz" class="form-wrapper" aria-describedby="quiz-desc" style="display:none;" aria-hidden="true">
                            <h3 id="quiz-heading">Create Your Personalitea</h3>
                            <div id="quiz-desc" class="visually-hidden">This form is a demo quiz that maps answers to suggested products.</div>

                            <div class="quiz-questions">
                            <fieldset class="mb-3 quiz-question">
                                <legend class="form-label">1) How do you like your flavour profile?</legend>
                                <div class="quiz-options" role="radiogroup" aria-labelledby="q1-legend">
                                    <label class="quiz-option" data-value="light">
                                        <input type="radio" name="q1" value="light" required>
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Light & floral">
                                        <div class="opt-label">Light &amp; floral</div>
                                        <div class="opt-sub">Delicate, floral notes</div>
                                    </label>
                                    <label class="quiz-option" data-value="earthy">
                                        <input type="radio" name="q1" value="earthy">
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Earthy & robust">
                                        <div class="opt-label">Earthy &amp; robust</div>
                                        <div class="opt-sub">Deep, grounded flavours</div>
                                    </label>
                                    <label class="quiz-option" data-value="sweet">
                                        <input type="radio" name="q1" value="sweet">
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Sweet & aromatic">
                                        <div class="opt-label">Sweet &amp; aromatic</div>
                                        <div class="opt-sub">Warm, fragrant notes</div>
                                    </label>
                                </div>
                            </fieldset>

                            <fieldset class="mb-3 quiz-question">
                                <legend class="form-label">2) When do you usually enjoy tea?</legend>
                                <div class="quiz-options">
                                    <label class="quiz-option" data-value="morning">
                                        <input type="radio" name="q2" value="morning" required>
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Morning boost">
                                        <div class="opt-label">Morning boost</div>
                                        <div class="opt-sub">Energising and bright</div>
                                    </label>
                                    <label class="quiz-option" data-value="afternoon">
                                        <input type="radio" name="q2" value="afternoon">
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Afternoon calm">
                                        <div class="opt-label">Afternoon calm</div>
                                        <div class="opt-sub">Light and soothing</div>
                                    </label>
                                    <label class="quiz-option" data-value="evening">
                                        <input type="radio" name="q2" value="evening">
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Evening relaxation">
                                        <div class="opt-label">Evening relaxation</div>
                                        <div class="opt-sub">Calming, low caffeine</div>
                                    </label>
                                </div>
                            </fieldset>

                            <fieldset class="mb-3 quiz-question">
                                <legend class="form-label">3) Choose a mood:</legend>
                                <div class="quiz-options">
                                    <label class="quiz-option" data-value="adventurous">
                                        <input type="radio" name="q3" value="adventurous" required>
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Adventurous">
                                        <div class="opt-label">Adventurous</div>
                                        <div class="opt-sub">Try bold blends</div>
                                    </label>
                                    <label class="quiz-option" data-value="cozy">
                                        <input type="radio" name="q3" value="cozy">
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Cozy">
                                        <div class="opt-label">Cozy</div>
                                        <div class="opt-sub">Comforting and warm</div>
                                    </label>
                                    <label class="quiz-option" data-value="focused">
                                        <input type="radio" name="q3" value="focused">
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Focused">
                                        <div class="opt-label">Focused</div>
                                        <div class="opt-sub">Crisp and clear</div>
                                    </label>
                                </div>
                            </fieldset>

                            <fieldset class="mb-3 quiz-question">
                                <legend class="form-label">4) Pick a favourite note:</legend>
                                <div class="quiz-options">
                                    <label class="quiz-option" data-value="citrus">
                                        <input type="radio" name="q4" value="citrus" required>
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Citrus">
                                        <div class="opt-label">Citrus</div>
                                        <div class="opt-sub">Zesty, uplifting</div>
                                    </label>
                                    <label class="quiz-option" data-value="vanilla">
                                        <input type="radio" name="q4" value="vanilla">
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Vanilla">
                                        <div class="opt-label">Vanilla</div>
                                        <div class="opt-sub">Creamy, smooth</div>
                                    </label>
                                    <label class="quiz-option" data-value="smoky">
                                        <input type="radio" name="q4" value="smoky">
                                        <img class="opt-img" src="/assets/images/placeholder.svg" alt="Smoky">
                                        <div class="opt-label">Smoky</div>
                                        <div class="opt-sub">Rich, toasted notes</div>
                                    </label>
                                </div>
                            </fieldset>
                            </div>

                            <div class="d-flex gap-3">
                                <button type="submit" class="btn-store">See my result</button>
                                <button type="button" id="quiz-cancel" class="btn-store-outline">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <div class="quiz-panel quiz-panel-results">
                        <div id="quiz-results" style="display:none;margin-top:0;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
// Quiz card interaction: keep radios in sync and visual state
(function(){
    function initQuizCards(root){
        const groups = root.querySelectorAll('.quiz-options');
        groups.forEach(group=>{
            const options = Array.from(group.querySelectorAll('.quiz-option'));
            options.forEach(opt=>{
                const input = opt.querySelector('input[type="radio"]');
                if(!input) return;
                // click on card toggles radio
                opt.addEventListener('click', (e)=>{
                    if(!input.checked){
                        input.checked = true;
                        input.dispatchEvent(new Event('change', {bubbles:true}));
                    }
                });

                // update visual selected state when input changes
                input.addEventListener('change', ()=>{
                    options.forEach(o=> o.classList.remove('selected'));
                    if(input.checked) opt.classList.add('selected');
                });
            });
            // initialise state from checked radios (for back/restore)
            const checked = group.querySelector('input[type="radio"]:checked');
            if(checked){
                const parent = checked.closest('.quiz-option');
                if(parent) parent.classList.add('selected');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', ()=> initQuizCards(document));
})();
</script>
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