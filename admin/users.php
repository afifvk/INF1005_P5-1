<?php
session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();

$pdo = getDB();
$pageTitle = 'User Management';

$search = trim((string)($_GET['name'] ?? ''));
$email = trim((string)($_GET['email'] ?? ''));
$role = trim((string)($_GET['role'] ?? ''));

$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (first_name LIKE ? OR last_name LIKE ?)";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
}

if ($email !== '') {
    $sql .= " AND email LIKE ?";
    $params[] = "%" . $email . "%";
}

if ($role !== '') {
    $sql .= " AND role = ?";
    $params[] = $role;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">User Management</h1>
            <p class="text-muted mb-0">Manage and update user accounts and their permissions.</p>
        </div>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">Filter Users</div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="user-name" class="form-label">Name</label>
                    <input type="text"
                           id="user-name"
                           name="name"
                           class="form-control"
                           placeholder="Search by first or last name"
                           value="<?= e($search) ?>">
                </div>

                <div class="col-md-3">
                    <label for="user-email" class="form-label">Email</label>
                    <input type="text"
                           id="user-email"
                           name="email"
                           class="form-control"
                           placeholder="Search by email"
                           value="<?= e($email) ?>">
                </div>

                <div class="col-md-3">
                    <label for="user-role" class="form-label">Role</label>
                    <select id="user-role" name="role" class="form-select">
                        <option value="" <?= $role === '' ? 'selected' : '' ?>>All Roles</option>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="customer" <?= $role === 'customer' ? 'selected' : '' ?>>Customer</option>
                    </select>
                </div>

                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>

                <div class="col-12 d-flex justify-content-between align-items-center pt-1">
                    <small class="text-muted">Showing <?= count($users) ?> user<?= count($users) === 1 ? '' : 's' ?>.</small>
                    <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-outline-secondary btn-sm">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">User List</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Save</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php $formId = 'update-user-' . (int)$user['id']; ?>
                            <tr>
                                <td>
                                    <?= (int)$user['id'] ?>
                                    <form id="<?= $formId ?>" action="<?= SITE_URL ?>/admin/update_users.php" method="POST" class="d-none">
                                        <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                    </form>
                                </td>

                                <td>
                                    <input type="text"
                                           name="first_name"
                                           value="<?= e((string)$user['first_name']) ?>"
                                           class="form-control form-control-sm"
                                           form="<?= $formId ?>"
                                           aria-label="First name for user <?= (int)$user['id'] ?>"
                                           required>
                                </td>

                                <td>
                                    <input type="text"
                                           name="last_name"
                                           value="<?= e((string)$user['last_name']) ?>"
                                           class="form-control form-control-sm"
                                           form="<?= $formId ?>"
                                           aria-label="Last name for user <?= (int)$user['id'] ?>"
                                           required>
                                </td>

                                <td>
                                    <input type="email"
                                           name="email"
                                           value="<?= e((string)$user['email']) ?>"
                                           class="form-control form-control-sm"
                                           form="<?= $formId ?>"
                                           aria-label="Email for user <?= (int)$user['id'] ?>"
                                           required>
                                </td>

                                <td>
                                    <span class="badge <?= $user['role'] === 'admin' ? 'text-bg-danger' : 'text-bg-info' ?>">
                                        <?= e(ucfirst((string)$user['role'])) ?>
                                    </span>
                                </td>

                                <td>
                                    <button type="submit" class="btn btn-primary btn-sm" form="<?= $formId ?>">Save</button>
                                </td>

                                <td>
                                    <a href="<?= SITE_URL ?>/admin/delete_user.php?id=<?= (int)$user['id'] ?>"
                                       onclick="return confirm('Delete this user?')"
                                       class="btn btn-danger btn-sm">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">No users found for the selected filters.</td>
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
