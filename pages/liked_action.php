<?php

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_helpers.php';
require_once dirname(__DIR__) . '/includes/liked_helpers.php';
require_once dirname(__DIR__) . '/includes/product_helpers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to like teas.']);
    exit;
}

$csrf = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
if (!verifyCsrfToken($csrf)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
if ($productId < 1 || !getProductById($productId)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$result = toggleLikedProduct($userId, $productId);

if (!$result['success']) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not update liked teas. Please try again.']);
    exit;
}

$liked = !empty($result['liked']);
echo json_encode([
    'success' => true,
    'liked' => $liked,
    'product_id' => $productId,
    'message' => $liked ? 'Added to liked teas.' : 'Removed from liked teas.'
]);
