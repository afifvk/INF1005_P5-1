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

if ($name === '' || $description === '' || $price < 0 || $stock < 0) {
    die('Invalid product data.');
}

if (!isset($_FILES['image']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    die('Please upload a product image.');
}

$imageName = basename((string)$_FILES['image']['name']);
$uploadPath = __DIR__ . '/../assets/images/' . $imageName;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
    die('Failed to upload image.');
}

$stmt = $pdo->prepare('
    INSERT INTO products (name, description, price, stock, image)
    VALUES (?, ?, ?, ?, ?)
');

$stmt->execute([$name, $description, $price, $stock, $imageName]);

header('Location: ' . $redirectUrl);
exit;