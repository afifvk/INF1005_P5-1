<?php
    session_start();

    require_once __DIR__.'/../config/database.php';
    require_once __DIR__.'/../includes/auth_helpers.php';

    if (!isAdmin()) {
        die("Access denied");
    }

    $pdo = getDB();

    $id = intval($_GET['id']);

    $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
    $stmt->execute([$id]);

    header("Location: inventory.php");
exit;