<?php
/**
 * pages/about.php — About Us
 */

$pageTitle = 'About Us';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Hero -->
<section class="about-hero" aria-labelledby="about-heading">
    <div class="container">
        <div class="row align-items-center gy-4">
            <div class="col-md-6">
                <p class="eyebrow" style="color:var(--color-highlight);font-size:.75rem;letter-spacing:.15em;text-transform:uppercase;">
                    Our Story
                </p>
                <h1 id="about-heading" class="mb-3">Built Around Quality &amp; Trust</h1>
                <p class="lead text-muted">
                    We started <?= SITE_NAME ?> with a simple belief: everyone deserves products
                    that are genuinely good — designed well, built to last, and priced fairly.
                </p>
                <a href="products.php" class="btn-store mt-2 d-inline-block">
                    Explore Products
                </a>
            </div>
            <div class="col-md-5 offset-md-1 text-center">
                <!-- Decorative visual -->
                <svg viewBox="0 0 320 240" xmlns="http://www.w3.org/2000/svg"
                     role="img" aria-label="Store visual illustration" width="100%">
                    <rect width="320" height="240" rx="12" fill="#e8e2d9"/>
                    <circle cx="160" cy="100" r="60" fill="rgba(45,74,62,0.15)"/>
                    <circle cx="160" cy="100" r="40" fill="rgba(45,74,62,0.2)"/>
                    <text x="160" y="106" text-anchor="middle" font-size="28"
                          font-family="Georgia" fill="#2d4a3e">S</text>
                    <text x="160" y="185" text-anchor="middle" font-size="11"
                          font-family="Georgia" fill="#6b6560" letter-spacing="3">SINCE 2024</text>
                </svg>
            </div>
        </div>
    </div>
</section>

<!-- Values -->
<section class="section-pad" aria-labelledby="values-heading">
    <div class="container">

        <div class="section-header">
            <p class="eyebrow">What We Stand For</p>
            <h2 id="values-heading">Our Values</h2>
            <p>These principles guide everything we do — from sourcing to shipping.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="value-card">
                    <div class="value-icon" aria-hidden="true">
                        <i class="bi bi-gem"></i>
                    </div>
                    <h3 class="h5">Quality First</h3>
                    <p class="text-muted small">
                        Every product in our catalogue is vetted for durability, design,
                        and real-world performance. We only sell things we'd use ourselves.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="value-card">
                    <div class="value-icon" aria-hidden="true">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3 class="h5">Transparent &amp; Honest</h3>
                    <p class="text-muted small">
                        Prices are fair, descriptions are accurate, and returns are easy.
                        No hidden fees, no fine print surprises.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="value-card">
                    <div class="value-icon" aria-hidden="true">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="h5">Customer First</h3>
                    <p class="text-muted small">
                        Our support team is real people who care. Reach us any time and
                        we'll resolve your issue — guaranteed.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Team -->
<section class="section-pad-sm" style="background:var(--color-surface);border-top:1px solid var(--color-border);"
         aria-labelledby="team-heading">
    <div class="container">

        <div class="section-header">
            <p class="eyebrow">The People</p>
            <h2 id="team-heading">Meet Our Team</h2>
        </div>

        <div class="row g-4 justify-content-center">
            <?php
            $team = [
                ['name' => 'Alex Rivera',   'role' => 'Founder & CEO',           'icon' => 'person-badge'],
                ['name' => 'Jordan Kim',    'role' => 'Head of Product',          'icon' => 'box-seam'],
                ['name' => 'Sam Okafor',    'role' => 'Lead Developer',           'icon' => 'code-slash'],
                ['name' => 'Morgan Blake',  'role' => 'Customer Experience Lead', 'icon' => 'headset'],
            ];
            foreach ($team as $member):
            ?>
            <div class="col-6 col-md-3">
                <div class="text-center p-3">
                    <div class="value-icon mx-auto mb-3" style="width:64px;height:64px;font-size:1.75rem;"
                         aria-hidden="true">
                        <i class="bi bi-<?= $member['icon'] ?>"></i>
                    </div>
                    <h3 class="h6 mb-1"><?= e($member['name']) ?></h3>
                    <p class="text-muted small mb-0"><?= e($member['role']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section-pad text-center" aria-labelledby="cta-heading">
    <div class="container">
        <h2 id="cta-heading" class="mb-3">Ready to Shop?</h2>
        <p class="text-muted mb-4">Browse our full range of quality products today.</p>
        <a href="products.php" class="btn-store">
            View All Products <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
