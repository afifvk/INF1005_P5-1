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
$returnTo = trim((string)($_POST['return_to'] ?? ''));
$redirectUrl = 'inventory.php' . ($returnTo !== '' ? '?' . $returnTo : '');
$uploadHelpersPath = __DIR__ . '/../includes/upload_helpers.php';

if ($id <= 0) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid product ID.'];
    header('Location: ' . $redirectUrl);
    exit;
}

if (!is_file($uploadHelpersPath)) {
    error_log('Admin update product image failed: missing upload helper at ' . $uploadHelpersPath);
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Image upload support is not available on the server.'];
    header('Location: ' . $redirectUrl);
    exit;
}

require_once $uploadHelpersPath;

if (!function_exists('storeUploadedProductImage')) {
    error_log('Admin update product image failed: upload helper did not load correctly.');
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Image upload support is not available on the server.'];
    header('Location: ' . $redirectUrl);
    exit;
}

if (!isset($_FILES['image']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $_FILES['image']['size'] <= 0) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Please select an image file.'];
    header('Location: ' . $redirectUrl);
    exit;
}

$uploadDir = __DIR__ . '/../assets/images';
$imageName = null;

try {
    $imageName = storeUploadedProductImage($_FILES['image'], $uploadDir);

    $stmt = $pdo->prepare('UPDATE products SET image = ? WHERE id = ?');
    $stmt->execute([$imageName, $id]);
} catch (Throwable $e) {
    if ($imageName !== null) {
        $uploadedImagePath = $uploadDir . DIRECTORY_SEPARATOR . $imageName;
        if (is_file($uploadedImagePath)) {
            @unlink($uploadedImagePath);
        }
    }

    error_log('Admin update product image failed: ' . $e->getMessage());
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update the product image right now.',
    ];
    header('Location: ' . $redirectUrl);
    exit;
}

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Product image updated successfully.'];
header('Location: ' . $redirectUrl);
exit;
