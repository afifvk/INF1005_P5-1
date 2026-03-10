<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

session_start();

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth_helpers.php';

if (!isAdmin()) {
    die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: inventory.php");
    exit;
}

/* CREATE DATABASE CONNECTION */
$pdo = getDB();

/* GET FORM DATA SAFELY */

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
$is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

/* VALIDATION */

if ($id <= 0) {
    die("Invalid product ID");
}

/* UPDATE DATABASE */

$stmt = $pdo->prepare("
UPDATE products
SET name=?, description=?, price=?, stock=?, is_active=?
WHERE id=?
");

$stmt->execute([$name,$description,$price,$stock,$is_active,$id]);

/* REDIRECT BACK */

header("Location: inventory.php");
exit;