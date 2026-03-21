<?php
session_start();

require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/auth_helpers.php';

if (!isAdmin()) {
    die("Access denied");
}

$pdo = getDB();

$id = $_POST['id'] ?? null;
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';

$sql = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$first_name, $last_name, $email, $id]);

header("Location: users.php");
exit;