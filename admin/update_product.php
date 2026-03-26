<?php

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
$uploadHelpersPath = __DIR__ . '/../includes/upload_helpers.php';

if ($id <= 0 || $name === '' || $description === '' || $price < 0 || $stock < 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid product data.'];
    header('Location: ' . $redirectUrl);
    exit;
}

if (!is_file($uploadHelpersPath)) {
    error_log('Admin update product upload failed: missing upload helper at ' . $uploadHelpersPath);
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Image upload support is not available on the server.'];
    header('Location: ' . $redirectUrl);
    exit;
}

require_once $uploadHelpersPath;

if (!function_exists('storeUploadedProductImage')) {
    error_log('Admin update product upload failed: upload helper did not load correctly.');
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Image upload support is not available on the server.'];
    header('Location: ' . $redirectUrl);
    exit;
}

// Handle optional image upload
$imageUploaded = isset($_FILES['image'])
    && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
    && $_FILES['image']['size'] > 0;

if ($imageUploaded) {
    $uploadDir = __DIR__ . '/../assets/images';
    $imageName = null;

    try {
        $imageName = storeUploadedProductImage($_FILES['image'], $uploadDir);

        $stmt = $pdo->prepare('
            UPDATE products
            SET name = ?, description = ?, price = ?, stock = ?, image = ?
            WHERE id = ?
        ');
        $stmt->execute([$name, $description, $price, $stock, $imageName, $id]);
    } catch (Throwable $e) {
        if ($imageName !== null) {
            $uploadedImagePath = $uploadDir . DIRECTORY_SEPARATOR . $imageName;
            if (is_file($uploadedImagePath)) {
                @unlink($uploadedImagePath);
            }
        }

        error_log('Admin update product upload failed: ' . $e->getMessage());
        $_SESSION['flash'] = [
            'type' => 'danger',
            'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update the product right now.',
        ];
        header('Location: ' . $redirectUrl);
        exit;
    }
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
