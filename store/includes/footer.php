<!-- ======================================================
     END of <main>
     ====================================================== -->
</main>

<!-- ======================================================
     FOOTER
     ====================================================== -->
<footer class="site-footer mt-auto py-4" role="contentinfo">
    <div class="container">
        <div class="row g-4">

            <div class="col-md-4">
                <h5 class="footer-brand">
                    <i class="bi bi-bag-heart-fill me-1" aria-hidden="true"></i>
                    <?= SITE_NAME ?>
                </h5>
                <p class="text-muted small">Quality products crafted for modern living. Shop with confidence.</p>
            </div>

            <div class="col-md-4">
                <h6 class="footer-heading">Quick Links</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                    <li><a href="<?= SITE_URL ?>/pages/products.php">Products</a></li>
                    <li><a href="<?= SITE_URL ?>/pages/about.php">About Us</a></li>
                    <li><a href="<?= SITE_URL ?>/pages/cart.php">Cart</a></li>
                </ul>
            </div>

            <div class="col-md-4">
                <h6 class="footer-heading">Account</h6>
                <ul class="list-unstyled footer-links">
                    <?php if (isLoggedIn()): ?>
                        <li><a href="<?= SITE_URL ?>/pages/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= SITE_URL ?>/pages/login.php">Login</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <hr class="footer-divider">
        <p class="text-center text-muted small mb-0">
            &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
        </p>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>

<!-- Custom JS -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
