<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inventory.php');
    exit;
}

$pdo = getDB();

$name = trim((string)($_POST['name'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$price = isset($_POST['price']) ? (float)$_POST['price'] : -1;
$stock = isset($_POST['stock']) ? (int)$_POST['stock'] : -1;
$returnTo = trim((string)($_POST['return_to'] ?? ''));
$redirectUrl = 'inventory.php' . ($returnTo !== '' ? '?' . $returnTo : '');
$uploadHelpersPath = __DIR__ . '/../includes/upload_helpers.php';

if ($name === '' || $description === '' || $price < 0 || $stock < 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid product data.'];
    header('Location: ' . $redirectUrl);
    exit;
}

if (!is_file($uploadHelpersPath)) {
    error_log('Admin add product upload failed: missing upload helper at ' . $uploadHelpersPath);
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Image upload support is not available on the server.'];
    header('Location: ' . $redirectUrl);
    exit;
}

require_once $uploadHelpersPath;

if (!function_exists('storeUploadedProductImage')) {
    error_log('Admin add product upload failed: upload helper did not load correctly.');
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Image upload support is not available on the server.'];
    header('Location: ' . $redirectUrl);
    exit;
}

if (!isset($_FILES['image'])) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Please upload a product image.'];
    header('Location: ' . $redirectUrl);
    exit;
}

$uploadDir = __DIR__ . '/../assets/images';
$imageName = null;

try {
    $imageName = storeUploadedProductImage($_FILES['image'], $uploadDir);

    $stmt = $pdo->prepare('
        INSERT INTO products (name, description, price, stock, image)
        VALUES (?, ?, ?, ?, ?)
    ');

    $stmt->execute([$name, $description, $price, $stock, $imageName]);
} catch (Throwable $e) {
    if ($imageName !== null) {
        $uploadedImagePath = $uploadDir . DIRECTORY_SEPARATOR . $imageName;
        if (is_file($uploadedImagePath)) {
            @unlink($uploadedImagePath);
        }
    }

    error_log('Admin add product upload failed: ' . $e->getMessage());
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Unable to add the product right now.',
    ];
    header('Location: ' . $redirectUrl);
    exit;
}

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Product added successfully.'];

header('Location: ' . $redirectUrl);
exit;
