<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();


$pdo = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$returnTo = trim((string)($_GET['return_to'] ?? ''));
$redirectUrl = 'inventory.php' . ($returnTo !== '' ? '?' . $returnTo : '');

if ($id <= 0) {
    header('Location: ' . $redirectUrl);
    exit;
}

$stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
$stmt->execute([$id]);

header('Location: ' . $redirectUrl);
exit;