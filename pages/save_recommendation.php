<?php

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_helpers.php';
require_once dirname(__DIR__) . '/includes/recommendation_helpers.php';

header('Content-Type: application/json');


if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}


$userId = (int)$_SESSION['user_id'];
$csrf = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Allow either a concrete product_id or a product_title
// Accept either product_id or product_title (frontend may send encoded title)
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$productTitle = isset($_POST['product_title']) ? trim($_POST['product_title']) : '';
$productTitle = strlen($productTitle) ? urldecode($productTitle) : '';
$answers   = isset($_POST['answers']) ? $_POST['answers'] : null;

// If no productId provided but title given, try to resolve to an id 
if ($productId < 1 && $productTitle !== '') {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name LIKE ? LIMIT 1");
    $stmt->execute(["%" . $productTitle . "%"]);
    $row = $stmt->fetch();
    if ($row) {
        $productId = (int)$row['id'];
    }
}

if (!$answers) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing answers data']);
    exit;
}

// Save recommendation even if we couldn't map title -> product id. Store title for later reconciliation.
$ok = createRecommendation($userId, ($productId > 0 ? $productId : null), $productTitle, $answers);
if ($ok) {
    echo json_encode(['success' => true, 'message' => 'Recommendation saved.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
