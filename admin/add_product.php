<?php
    session_start();

    require_once __DIR__.'/../config/database.php';
    require_once __DIR__.'/../includes/auth_helpers.php';

    if (!isAdmin()) {
        die("Access denied");
    }

    $pdo = getDB();

    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];

    $imageName = basename($_FILES['image']['name']);

    move_uploaded_file(
        $_FILES['image']['tmp_name'],
        "../uploads/".$imageName
    );

    $stmt = $pdo->prepare("
        INSERT INTO products (name, description, price, stock, image)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([$name,$description,$price,$stock,$imageName]);

    header("Location: inventory.php");
exit;