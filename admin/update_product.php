<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inventory.php');
    exit;
}

$pdo = getDB();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name = trim((string)($_POST['name'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$price = isset($_POST['price']) ? (float)$_POST['price'] : -1;
$stock = isset($_POST['stock']) ? (int)$_POST['stock'] : -1;
$returnTo = trim((string)($_POST['return_to'] ?? ''));
$redirectUrl = 'inventory.php' . ($returnTo !== '' ? '?' . $returnTo : '');

if ($id <= 0 || $name === '' || $description === '' || $price < 0 || $stock < 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid product data.'];
    header('Location: ' . $redirectUrl);
    exit;
}

// Handle optional image upload
$imageUploaded = isset($_FILES['image'])
    && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
    && $_FILES['image']['size'] > 0;

if ($imageUploaded) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($_FILES['image']['tmp_name']);

    if (!in_array($fileType, $allowedTypes, true)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.'];
        header('Location: ' . $redirectUrl);
        exit;
    }

    $imageName = basename((string)$_FILES['image']['name']);
    $uploadDir = __DIR__ . '/../assets/images/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadPath = $uploadDir . $imageName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to upload image.'];
        header('Location: ' . $redirectUrl);
        exit;
    }

    // Update all fields including image
    $stmt = $pdo->prepare('
        UPDATE products
        SET name = ?, description = ?, price = ?, stock = ?, image = ?
        WHERE id = ?
    ');
    $stmt->execute([$name, $description, $price, $stock, $imageName, $id]);
} else {
    // Update without changing image
    $stmt = $pdo->prepare('
        UPDATE products
        SET name = ?, description = ?, price = ?, stock = ?
        WHERE id = ?
    ');
    $stmt->execute([$name, $description, $price, $stock, $id]);
}

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Product "' . $name . '" updated successfully.'];
header('Location: ' . $redirectUrl);
exit;