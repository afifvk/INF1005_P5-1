<?php
session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();

// --- Order-status helpers (mirrors manageOrders.php) ---
function getAdminOrderStatuses(): array {
    return [
        'pending'   => 'Pending',
        'confirmed' => 'Confirmed',
        'shipped'   => 'Shipped',
        'delivered'  => 'Delivered',
        'cancelled' => 'Canceled',
    ];
}

function normalizeAdminOrderStatus(string $status): ?string {
    $status = strtolower(trim($status));
    if ($status === 'canceled' || $status === 'cancled') {
        $status = 'cancelled';
    }
    return array_key_exists($status, getAdminOrderStatuses()) ? $status : null;
}

function getAdminOrderStatusBadgeClass(string $status): string {
    $normalized = normalizeAdminOrderStatus($status) ?? $status;
    switch ($normalized) {
        case 'pending':    return 'text-bg-warning';
        case 'confirmed':  return 'text-bg-info';
        case 'shipped':    return 'text-bg-primary';
        case 'delivered':  return 'text-bg-success';
        case 'cancelled':  return 'text-bg-danger';
        default:           return 'text-bg-secondary';
    }
}

$pdo = getDB();
$lowStockThreshold = 5;

$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

$totalOrders = 0;
$orderBreakdown = array_fill_keys(array_keys(getAdminOrderStatuses()), 0);
try {
    $totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    foreach ($pdo->query("SELECT status, COUNT(*) AS total FROM orders GROUP BY status") as $row) {
        $normalizedStatus = normalizeAdminOrderStatus((string)$row['status']);
        if ($normalizedStatus !== null) {
            $orderBreakdown[$normalizedStatus] = (int)$row['total'];
        }
    }
} catch (PDOException $e) {
    // orders table may not exist yet
}

$lowStockCountStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE stock BETWEEN 1 AND ?");
$lowStockCountStmt->execute([$lowStockThreshold]);
$lowStockCount = (int)$lowStockCountStmt->fetchColumn();

$outOfStockCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();

$lowStockStmt = $pdo->prepare("
    SELECT id, name, stock
    FROM products
    WHERE stock <= ?
    ORDER BY stock ASC, name ASC
    LIMIT 6
");
$lowStockStmt->execute([$lowStockThreshold]);
$lowStockProducts = $lowStockStmt->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Admin Dashboard</h1>
            <p class="text-muted mb-0">Quick access to users, inventory, and order management.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge text-bg-dark fs-6">Users: <?= $totalUsers ?></span>
            <span class="badge text-bg-primary fs-6">Products: <?= $totalProducts ?></span>
            <span class="badge text-bg-success fs-6">Orders: <?= $totalOrders ?></span>
        </div>
    </div>

    <?php if ($lowStockCount > 0 || $outOfStockCount > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <h5 class="alert-heading mb-2">
                        <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>
                        Inventory Alerts
                    </h5>
                    <p class="mb-1">
                        <?= $lowStockCount ?> item<?= $lowStockCount === 1 ? '' : 's' ?> low on stock
                        and <?= $outOfStockCount ?> item<?= $outOfStockCount === 1 ? '' : 's' ?> out of stock.
                    </p>
                    <p class="mb-0 small text-muted">Low stock is currently defined as <?= $lowStockThreshold ?> units or fewer.</p>
                </div>
                <div class="d-flex align-items-start">
                    <a href="<?= SITE_URL ?>/admin/inventory.php?status=out_of_stock" class="btn btn-outline-dark">
                        Review Inventory
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-people-fill" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h4 class="h5 mb-1">Users</h4>
                            <p class="text-muted mb-0"><?= $totalUsers ?> registered account<?= $totalUsers === 1 ? '' : 's' ?></p>
                        </div>
                    </div>
                    <p class="text-muted flex-grow-1">Open the user list to search customer accounts and update account details.</p>
                    <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-primary">Manage Users</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h4 class="h5 mb-1">Inventory</h4>
                            <p class="text-muted mb-0"><?= $lowStockCount ?> low-stock, <?= $outOfStockCount ?> out-of-stock</p>
                        </div>
                    </div>
                    <p class="text-muted flex-grow-1">Check product stock levels, update quantities, and add new inventory from one page.</p>
                    <a href="<?= SITE_URL ?>/admin/inventory.php" class="btn btn-primary">Manage Inventory</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                            <i class="bi bi-truck" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h4 class="h5 mb-1">Orders</h4>
                            <p class="text-muted mb-0"><?= $orderBreakdown['pending'] ?> pending, <?= $orderBreakdown['shipped'] ?> shipped</p>
                        </div>
                    </div>
                    <p class="text-muted flex-grow-1">Track each order from pending to delivered and update statuses directly.</p>
                    <a href="<?= SITE_URL ?>/admin/manageOrders.php" class="btn btn-primary">Manage Orders</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Low Stock Alerts</span>
                    <span class="badge text-bg-warning"><?= $lowStockCount + $outOfStockCount ?></span>
                </div>
                <div class="card-body">
                    <?php if (!empty($lowStockProducts)): ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Stock Left</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockProducts as $product): ?>
                                        <tr>
                                            <td><?= e((string)$product['name']) ?></td>
                                            <td>
                                                <span class="badge <?= (int)$product['stock'] === 0 ? 'text-bg-danger' : 'text-bg-warning' ?>">
                                                    <?= (int)$product['stock'] ?> left
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= SITE_URL ?>/admin/inventory.php?search=<?= urlencode((string)$product['name']) ?>" class="btn btn-sm btn-outline-primary">
                                                    Open
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">All products are stocked above the low-stock threshold.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header">Order Status Summary</div>
                <div class="card-body d-flex flex-column gap-3">
                    <?php foreach (getAdminOrderStatuses() as $statusKey => $statusLabel): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge <?= getAdminOrderStatusBadgeClass($statusKey) ?>"><?= e($statusLabel) ?></span>
                            <strong><?= $orderBreakdown[$statusKey] ?></strong>
                        </div>
                    <?php endforeach; ?>
                    <div class="pt-2 border-top">
                        <a href="<?= SITE_URL ?>/admin/manageOrders.php?status=pending" class="btn btn-outline-primary w-100">
                            Review Pending Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>