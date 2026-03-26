<?php
session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();

$pdo = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$returnSearch = trim((string)($_GET['return_search'] ?? ''));
$returnStatus = trim((string)($_GET['return_status'] ?? ''));

$returnParams = [];
if ($returnSearch !== '') {
    $returnParams['search'] = $returnSearch;
}
if ($returnStatus !== '' && $returnStatus !== 'all') {
    $returnParams['status'] = $returnStatus;
}
$redirectUrl = SITE_URL . '/admin/manageOrders.php' . (!empty($returnParams) ? '?' . http_build_query($returnParams) : '');

if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Invalid order ID.'];
    header('Location: ' . $redirectUrl);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('DELETE FROM order_items WHERE order_id = ?');
    $stmt->execute([$id]);

    $stmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
    $stmt->execute([$id]);

    $pdo->commit();

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order #' . $id . ' has been deleted.'];
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Delete order failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to delete the order. Please try again.'];
}

header('Location: ' . $redirectUrl);
exit;
