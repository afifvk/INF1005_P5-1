<?php
/**
 * pages/order_action.php
 * Handles order mutations (e.g. cancel).
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/order_helpers.php';

function flashRedirect($type, $message, $url) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header("Location: $url");
    exit;
}

if (!isLoggedIn()) {
    flashRedirect('danger', 'Please log in first.', SITE_URL . '/pages/login.php');
}

$token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!verifyCsrfToken($token)) {
    flashRedirect('danger', 'Invalid form token.', SITE_URL . '/pages/orders.php');
}

$action  = $_POST['action']   ?? '';
$orderId = (int)($_POST['order_id'] ?? 0);
$userId  = (int)$_SESSION['user_id'];

switch ($action) {

    case 'cancel':
        if ($orderId < 1) {
            flashRedirect('danger', 'Invalid order.', SITE_URL . '/pages/orders.php');
        }

        $ok = cancelOrder($orderId, $userId);

        if ($ok) {
            flashRedirect('success', 'Order #' . $orderId . ' has been cancelled.', SITE_URL . '/pages/orders.php');
        } else {
            // Either not found, not theirs, or no longer pending
            flashRedirect('danger', 'This order cannot be cancelled.', SITE_URL . '/pages/orders.php');
        }
        break;

    default:
        flashRedirect('danger', 'Unknown action.', SITE_URL . '/pages/orders.php');
}