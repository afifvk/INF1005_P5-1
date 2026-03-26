<?php
session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$redirectUrl = SITE_URL . '/admin/users.php';

if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Invalid user ID.'];
    header('Location: ' . $redirectUrl);
    exit;
}

if ($id === (int)($_SESSION['user_id'] ?? 0)) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'You cannot delete your own account while logged in.'];
    header('Location: ' . $redirectUrl);
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'User #' . $id . ' deleted successfully.'];
} catch (PDOException $e) {
    error_log('Delete user failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to delete the user. Please try again.'];
}

header('Location: ' . $redirectUrl);
exit;
