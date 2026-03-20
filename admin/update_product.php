<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

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
    die('Invalid product data.');
}

$stmt = $pdo->prepare('
    UPDATE products
    SET name = ?, description = ?, price = ?, stock = ?
    WHERE id = ?
');

$stmt->execute([$name, $description, $price, $stock, $id]);

header('Location: ' . $redirectUrl);
exit;