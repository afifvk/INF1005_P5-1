<?php
session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();


$pdo = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$returnTo = trim((string)($_GET['return_to'] ?? ''));
$redirectUrl = SITE_URL . '/admin/inventory.php' . ($returnTo !== '' ? '?' . $returnTo : '');

if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Invalid product ID.'];
    header('Location: ' . $redirectUrl);
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Product deleted successfully.'];
} catch (PDOException $e) {
    error_log('Delete product failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to delete the product. Please try again.'];
}

header('Location: ' . $redirectUrl);
exit;
