<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();


/* CREATE DATABASE CONNECTION */
$pdo = getDB();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id = intval($_POST['id']);
    $stock = intval($_POST['stock']);

    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->execute([$stock, $id]);

}

/* REDIRECT BACK TO INVENTORY PAGE */
header("Location: inventory.php");
exit;