<?php
/**
 * includes/header.php
 * Shared HTML head + navigation bar.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/cart_helpers.php';

$cartCount = isLoggedIn() ? getCartCount($_SESSION['user_id']) : 0;
$csrf = generateCsrfToken();

// Helper: display name — first name if set, otherwise last name
$displayName = '';
if (isLoggedIn()) {
    $displayName = !empty($_SESSION['first_name'])
        ? $_SESSION['first_name']
        : ($_SESSION['last_name'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Store – Quality products at great prices.">
    <title><?= e($pageTitle ?? 'Store') ?> | <?= SITE_NAME ?></title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=3">
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top" role="navigation" aria-label="Main navigation">
    <div class="container">

        <!-- Brand -->
        <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>/index.php">
            <i class="bi bi-bag-heart-fill me-1" aria-hidden="true"></i>
            <?= SITE_NAME ?>
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">

            <!-- Left nav links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/pages/products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/pages/about.php">About Us</a>
                </li>
            </ul>

            <!-- Right side: cart + auth -->
            <div class="d-flex align-items-center gap-2">

                <!-- Cart icon with badge -->
                <a class="btn btn-outline-secondary position-relative"
                   href="<?= SITE_URL ?>/pages/cart.php"
                   aria-label="Shopping cart, <?= $cartCount ?> items">
                    <i class="bi bi-cart3" aria-hidden="true"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                          id="cart-badge"
                          <?= $cartCount === 0 ? 'style="display:none"' : '' ?>>
                        <?= $cartCount ?>
                        <span class="visually-hidden">items in cart</span>
                    </span>
                </a>

                <?php if (isLoggedIn()): ?>

                    <!-- Profile dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-label="Account menu for <?= e($displayName) ?>">
                            <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                            <?= e($displayName) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <span class="dropdown-item-text small text-muted">
                                    <?= e(trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''))) ?>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= SITE_URL ?>/pages/profile.php">
                                    <i class="bi bi-person me-2" aria-hidden="true"></i>My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= SITE_URL ?>/pages/cart.php">
                                    <i class="bi bi-cart3 me-2" aria-hidden="true"></i>My Cart
                                </a>
                            </li>
                            <?php if (isAdmin()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= SITE_URL ?>/admin/index.php">
                                    <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>Admin Panel
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= SITE_URL ?>/pages/logout.php">
                                    <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>

                <?php else: ?>
                    <a href="<?= SITE_URL ?>/pages/login.php"    class="btn btn-sm btn-outline-primary">Login</a>
                    <a href="<?= SITE_URL ?>/pages/register.php" class="btn btn-sm btn-primary">Register</a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</nav>

<!-- Flash messages -->
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="container mt-3" role="alert" aria-live="polite">
        <div class="alert alert-<?= e($_SESSION['flash']['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($_SESSION['flash']['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<main id="main-content">