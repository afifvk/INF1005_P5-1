<?php
/**
 * pages/cart_action.php
 * Handles all cart mutations: add, remove, update, clear, checkout.
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/cart_helpers.php';
require_once dirname(__DIR__) . '/includes/profile_helpers.php';

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function jsonResponse($success, $message, $extra = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function flashRedirect($type, $message, $url) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header("Location: $url");
    exit;
}

if (!isLoggedIn()) {
    if ($isAjax) jsonResponse(false, 'You must be logged in to add items to your cart.');
    flashRedirect('danger', 'Please log in first.', SITE_URL . '/pages/login.php');
}

$token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (!verifyCsrfToken($token)) {
    if ($isAjax) jsonResponse(false, 'Invalid request. Please refresh the page and try again.');
    flashRedirect('danger', 'Invalid form token.', SITE_URL . '/pages/cart.php');
}

$action    = isset($_POST['action'])     ? $_POST['action']     : '';
$productId = (int)(isset($_POST['product_id']) ? $_POST['product_id'] : 0);
$quantity  = (int)(isset($_POST['quantity'])   ? $_POST['quantity']   : 1);
$userId    = (int)$_SESSION['user_id'];

switch ($action) {

    case 'add':
        if ($productId < 1 || $quantity < 1) {
            if ($isAjax) jsonResponse(false, 'Invalid product or quantity.');
            flashRedirect('danger', 'Invalid request.', SITE_URL . '/pages/products.php');
        }
        $ok    = addToCart($userId, $productId, $quantity);
        $count = getCartCount($userId);
        if ($isAjax) {
            jsonResponse($ok, $ok ? 'Item added to cart!' : 'Could not add item.', ['cart_count' => $count]);
        }
        flashRedirect($ok ? 'success' : 'danger',
                      $ok ? 'Item added to your cart.' : 'Could not add item.',
                      SITE_URL . '/pages/cart.php');
        break;

    case 'remove':
        if ($productId < 1) {
            if ($isAjax) jsonResponse(false, 'Invalid product.');
            flashRedirect('danger', 'Invalid request.', SITE_URL . '/pages/cart.php');
        }
        $ok    = removeFromCart($userId, $productId);
        $count = getCartCount($userId);
        if ($isAjax) {
            jsonResponse($ok, $ok ? 'Item removed.' : 'Could not remove item.', ['cart_count' => $count]);
        }
        flashRedirect($ok ? 'success' : 'danger',
                      $ok ? 'Item removed from cart.' : 'Error removing item.',
                      SITE_URL . '/pages/cart.php');
        break;

    case 'update':
        if ($productId < 1) {
            flashRedirect('danger', 'Invalid request.', SITE_URL . '/pages/cart.php');
        }
        updateCartItem($userId, $productId, $quantity);
        flashRedirect('success', 'Cart updated.', SITE_URL . '/pages/cart.php');
        break;

    case 'clear':
        clearCart($userId);
        flashRedirect('success', 'Cart cleared.', SITE_URL . '/pages/cart.php');
        break;

    case 'checkout':
        // Fetch user profile for shipping details
        $user = getUserById($userId);

        if (empty($user['address'])) {
            flashRedirect('danger', 'Please add a shipping address to your profile before checking out.', SITE_URL . '/pages/cart.php');
        }

        $shippingName    = trim($user['first_name'] . ' ' . $user['last_name']);
        $shippingAddress = $user['address'];

        $orderId = createOrder($userId, $shippingName, $shippingAddress);

        if (!$orderId) {
            flashRedirect('danger', 'Could not create your order. Please try again.', SITE_URL . '/pages/cart.php');
        }

        flashRedirect('success', 'Order placed successfully!', SITE_URL . '/pages/order_confirmation.php?order_id=' . $orderId);
        break;

    default:
        if ($isAjax) jsonResponse(false, 'Unknown action.');
        flashRedirect('danger', 'Unknown action.', SITE_URL . '/pages/cart.php');
}