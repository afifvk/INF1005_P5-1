<?php
/**
 * includes/header.php
 * Shared HTML head + navigation bar.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/../includes/cart_helpers.php';
require_once __DIR__ . '/../includes/liked_helpers.php';

if (!defined('SKIP_PAGE_RATE_LIMIT')) {
    enforcePageRateLimit();
}

$cartCount = isLoggedIn() ? getCartCount($_SESSION['user_id']) : 0;
$csrf = generateCsrfToken();
$likedProductIds = isLoggedIn() ? getLikedProductIdsByUser((int)$_SESSION['user_id']) : [];

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
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=5">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= SITE_URL ?>/assets/images/favicon.png?v=1">
    <link rel="shortcut icon" href="<?= SITE_URL ?>/assets/images/favicon.png?v=1">


    <!-- Products page filter styles -->
     <style>
/* ── Filter Sidebar ─────────────────────────────────── */
.filter-sidebar {
    background: #fff;
    border: 1px solid #e8e2d9;
    border-radius: 12px;
    overflow: hidden;
    position: sticky;
    top: 20px;
}
.filter-sidebar__header {
    background: #3d6b4f;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.filter-sidebar__title {
    font-size: 1rem;
    font-weight: 600;
    color: #fff;
    margin: 0;
}
.filter-clear-btn {
    font-size: .75rem;
    color: #fff;
    background: none;
    border: 1px solid rgba(255,255,255,.6);
    border-radius: 20px;
    padding: 3px 10px;
    cursor: pointer;
    transition: all .2s;
}
.filter-clear-btn:hover { background: rgba(255,255,255,.15); color: #fff; }

.filter-section {
    padding: 16px 18px;
    border-bottom: 1px solid #f0ece4;
}
.filter-section:last-child { border-bottom: none; }
.filter-section__label {
    font-size: .7rem;
    font-weight: 600;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #7c5c3e;
    margin-bottom: 10px;
    display: block;
}

.filter-search {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid #ddd6c9;
    border-radius: 8px;
    font-size: .88rem;
    outline: none;
    transition: border-color .2s;
}
.filter-search:focus { border-color: #3d6b4f; }

.filter-sort {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid #ddd6c9;
    border-radius: 8px;
    font-size: .88rem;
    background: #faf8f4;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%233d6b4f' stroke-width='1.8' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    cursor: pointer;
}
.filter-sort:focus { outline: none; border-color: #3d6b4f; }

.filter-pills { display: flex; flex-wrap: wrap; gap: 6px; }

/* Accessible visually-hidden checkbox — still focusable by keyboard */
.filter-pill input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
}
.filter-pill label {
    display: inline-block;
    padding: 4px 11px;
    border-radius: 20px;
    border: 1.5px solid #ddd6c9;
    font-size: .78rem;
    color: #555;
    cursor: pointer;
    transition: all .2s;
    user-select: none;
}
.filter-pill input:checked + label {
    background: #3d6b4f;
    border-color: #3d6b4f;
    color: #fff;
    font-weight: 500;
}
.filter-pill label:hover { border-color: #3d6b4f; color: #3d6b4f; }

/* Keyboard focus ring on pill labels */
.filter-pill input:focus-visible + label {
    outline: 2px solid #3d6b4f;
    outline-offset: 2px;
}

.caffeine-none   input:checked + label { background: #6aaa6a; border-color: #6aaa6a; }
.caffeine-low    input:checked + label { background: #89b04a; border-color: #89b04a; }
.caffeine-medium input:checked + label { background: #c9a84c; border-color: #c9a84c; }
.caffeine-high   input:checked + label { background: #c0572a; border-color: #c0572a; }

/* AXE fix: darkened from #666 to #444 to pass WCAG AA contrast ratio */
.results-count { font-size: .88rem; color: #444; margin-bottom: 16px; min-height: 1.2em; }
.results-count strong { color: #1e2d24; }

.filter-loading { display: none; text-align: center; padding: 48px 0; }
.filter-loading.is-active { display: block; }
.filter-spinner {
    width: 36px; height: 36px;
    border: 3px solid #e0d9ce;
    border-top-color: #3d6b4f;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin: 0 auto 12px;
}
@keyframes spin { to { transform: rotate(360deg); } }

.caffeine-badge {
    display: inline-block;
    font-size: .68rem;
    font-weight: 500;
    padding: 2px 8px;
    border-radius: 20px;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 6px;
}
/* AXE fix: darker backgrounds so white text passes WCAG AA 4.5:1 contrast ratio */
.caffeine-badge--none   { background: #3a7a3a; }
.caffeine-badge--low    { background: #5a7a28; }
.caffeine-badge--medium { background: #8a6518; }
.caffeine-badge--high   { background: #c0572a; }

.tea-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px; }
.tea-tag {
    font-size: .68rem;
    background: #f5f0e8;
    color: #7c5c3e;
    padding: 2px 8px;
    border-radius: 20px;
}

@media (max-width: 767px) {
    .filter-sidebar { position: static; margin-bottom: 24px; }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.product-card {
    animation: fadeInUp 0.3s ease both;
}
</style>
</head>
<body>  

    <!-- ADMIN RIBBON -->
    <?php if (isAdmin()): ?>
    <div class="admin-ribbon">
        <i class="bi bi-shield-lock"></i> ADMIN MODE
    </div>
    <?php endif; ?>

<nav class="navbar navbar-expand-lg sticky-top" aria-label="Main navigation">
    <div class="container ">

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
                            <li>
                                <a class="dropdown-item" href="<?= SITE_URL ?>/pages/orders.php">
                                    <i class="bi bi-bag-check me-2" aria-hidden="true"></i>My Orders
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= SITE_URL ?>/pages/saved_teas.php">
                                    <i class="bi bi-bookmark-heart me-2" aria-hidden="true"></i>My Saved Teas
                                </a>
                            </li>
                        <?php if (isAdmin()): ?>

                        <li><hr class="dropdown-divider"></li>

                                           <li>
                            <a class="dropdown-item" href="<?= SITE_URL ?>/admin/dashboard.php">
                                <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>
                                Admin Dashboard
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="<?= SITE_URL ?>/admin/inventory.php">
                                <i class="bi bi-box-seam me-2" aria-hidden="true"></i>
                                Manage Inventory
                            </a>
                        </li>
                                                <li>
                            <a class="dropdown-item" href="<?= SITE_URL ?>/admin/manageOrders.php">
                                <i class="bi bi-box-seam me-2" aria-hidden="true"></i>
                                Manage Orders
                            </a>
                        </li>
                                                <li>
                            <a class="dropdown-item" href="<?= SITE_URL ?>/admin/users.php">
                                <i class="bi bi-box-seam me-2" aria-hidden="true"></i>
                                Manage Users
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