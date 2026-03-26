<?php
session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();
$csrf = generateCsrfToken();

function manageOrdersStatuses(): array {
    return [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Canceled',
    ];
}

function normalizeManageOrdersStatus(string $status): ?string {
    $status = strtolower(trim($status));

    if ($status === 'canceled' || $status === 'cancled') {
        $status = 'cancelled';
    }

    return array_key_exists($status, manageOrdersStatuses()) ? $status : null;
}

function manageOrdersStatusLabel(string $status): string {
    $normalized = normalizeManageOrdersStatus($status) ?? $status;
    $labels = manageOrdersStatuses();

    return $labels[$normalized] ?? ucfirst($status);
}

function manageOrdersStatusBadgeClass(string $status): string {
    $normalized = normalizeManageOrdersStatus($status) ?? $status;

    switch ($normalized) {
        case 'pending':
            return 'text-bg-warning';
        case 'confirmed':
            return 'text-bg-info';
        case 'shipped':
            return 'text-bg-primary';
        case 'delivered':
            return 'text-bg-success';
        case 'cancelled':
            return 'text-bg-danger';
        default:
            return 'text-bg-secondary';
    }
}

$pdo = getDB();
$pageTitle = 'Order Management';

$search = trim((string)($_GET['search'] ?? ''));
$status = trim((string)($_GET['status'] ?? 'all'));
$allowedStatuses = array_merge(['all'], array_keys(manageOrdersStatuses()));

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'all';
}

$where = [];
$params = [];

if ($search !== '') {
    $searchClauses = [
        'u.email LIKE ?',
        'u.first_name LIKE ?',
        'u.last_name LIKE ?',
        'o.shipping_name LIKE ?',
    ];
    $searchLike = '%' . $search . '%';
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;

    if (ctype_digit($search)) {
        $searchClauses[] = 'o.id = ?';
        $params[] = (int)$search;
    }

    $where[] = '(' . implode(' OR ', $searchClauses) . ')';
}

if ($status !== 'all') {
    if ($status === 'cancelled') {
        $where[] = 'o.status IN (?, ?)';
        $params[] = 'cancelled';
        $params[] = 'canceled';
    } else {
        $where[] = 'o.status = ?';
        $params[] = $status;
    }
}

$sql = "
    SELECT
        o.*,
        u.first_name,
        u.last_name,
        u.email
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY o.created_at DESC, o.id DESC';

$orders = [];

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Manage orders query failed: ' . $e->getMessage());
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'Unable to load order records right now. Please try again later.',
    ];
}

if (!empty($orders)) {
    $orderIds = [];
    foreach ($orders as $order) {
        $orderIds[] = (int)$order['id'];
    }

    $orderMetrics = [];

    try {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $metricsStmt = $pdo->prepare("
            SELECT
                order_id,
                COUNT(*) AS line_count,
                COALESCE(SUM(quantity), 0) AS item_quantity
            FROM order_items
            WHERE order_id IN ($placeholders)
            GROUP BY order_id
        ");
        $metricsStmt->execute($orderIds);

        foreach ($metricsStmt->fetchAll() as $metricRow) {
            $orderMetrics[(int)$metricRow['order_id']] = [
                'line_count' => (int)$metricRow['line_count'],
                'item_quantity' => (int)$metricRow['item_quantity'],
            ];
        }
    } catch (Exception $e) {
        error_log('Manage orders metrics query failed: ' . $e->getMessage());
    }

    foreach ($orders as &$order) {
        $metrics = $orderMetrics[(int)$order['id']] ?? ['line_count' => 0, 'item_quantity' => 0];
        $order['line_count'] = $metrics['line_count'];
        $order['item_quantity'] = $metrics['item_quantity'];
        $order['shipping_name'] = (string)($order['shipping_name'] ?? '');
        $order['shipping_address'] = (string)($order['shipping_address'] ?? '');
        $order['email'] = (string)($order['email'] ?? '');
        $order['first_name'] = (string)($order['first_name'] ?? '');
        $order['last_name'] = (string)($order['last_name'] ?? '');
    }
    unset($order);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Order Management</h1>
            <p class="text-muted mb-0">Track every order and update its fulfillment status from one page.</p>
        </div>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">Filter Orders</div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-7">
                    <label for="order-search" class="form-label">Search</label>
                    <input
                        type="text"
                        id="order-search"
                        name="search"
                        class="form-control"
                        placeholder="Search by order ID, customer name, email, or shipping name"
                        value="<?= e($search) ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label for="order-status" class="form-label">Status</label>
                    <select id="order-status" name="status" class="form-select">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                        <?php foreach (manageOrdersStatuses() as $statusKey => $statusLabel): ?>
                            <option value="<?= e($statusKey) ?>" <?= $status === $statusKey ? 'selected' : '' ?>>
                                <?= e($statusLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>

                <div class="col-12 d-flex justify-content-between align-items-center pt-1">
                    <small class="text-muted">Showing <?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?>.</small>
                    <a href="<?= SITE_URL ?>/admin/manageOrders.php" class="btn btn-outline-secondary btn-sm">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">Order List</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Shipping</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Update</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php $currentStatus = normalizeManageOrdersStatus((string)$order['status']) ?? 'pending'; ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold">#<?= (int)$order['id'] ?></div>
                                    <div class="text-muted small">
                                        <?= date('d M Y, H:i', strtotime((string)$order['created_at'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        <?= e(trim(((string)$order['first_name']) . ' ' . ((string)$order['last_name']))) ?>
                                    </div>
                                    <div class="text-muted small"><?= e((string)$order['email']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= e((string)$order['shipping_name']) ?></div>
                                    <div class="text-muted small"><?= e((string)$order['shipping_address']) ?></div>
                                </td>
                                <td>
                                    <div><?= (int)$order['line_count'] ?> line<?= (int)$order['line_count'] === 1 ? '' : 's' ?></div>
                                    <div class="text-muted small"><?= (int)$order['item_quantity'] ?> item<?= (int)$order['item_quantity'] === 1 ? '' : 's' ?></div>
                                </td>
                                <td class="fw-semibold">£<?= number_format((float)$order['total_cost'], 2) ?></td>
                                <td>
                                    <span class="badge <?= manageOrdersStatusBadgeClass($currentStatus) ?>">
                                        <?= e(manageOrdersStatusLabel($currentStatus)) ?>
                                    </span>
                                </td>
                                <td style="min-width: 220px;">
                                    <form method="POST" action="<?= SITE_URL ?>/admin/update_order_status.php" class="d-flex flex-column gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                        <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                        <input type="hidden" name="return_search" value="<?= e($search) ?>">
                                        <input type="hidden" name="return_status" value="<?= e($status) ?>">

                                        <select name="status" class="form-select form-select-sm" aria-label="Update status for order #<?= (int)$order['id'] ?>">
                                            <?php foreach (manageOrdersStatuses() as $statusKey => $statusLabel): ?>
                                                <option value="<?= e($statusKey) ?>" <?= $currentStatus === $statusKey ? 'selected' : '' ?>>
                                                    <?= e($statusLabel) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit" class="btn btn-primary btn-sm">Save Status</button>
                                    </form>
                                </td>
                                <td>
                                    <a href="<?= SITE_URL ?>/admin/delete_order.php?id=<?= (int)$order['id'] ?>&return_search=<?= urlencode($search) ?>&return_status=<?= urlencode($status) ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete Order #<?= (int)$order['id'] ?>? This action cannot be undone.');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No orders found for the selected filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/accessibility_landmarks.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
