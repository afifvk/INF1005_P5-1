<?php
session_start();

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

requireAdminAccess();

$redirectUrl = SITE_URL . '/admin/users.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectUrl);
    exit;
}

$pdo = getDB();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$first_name = trim((string)($_POST['first_name'] ?? ''));
$last_name = trim((string)($_POST['last_name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));

if ($id <= 0 || $first_name === '' || $last_name === '' || $email === '') {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid user data.'];
    header('Location: ' . $redirectUrl);
    exit;
}

try {
    $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$first_name, $last_name, $email, $id]);

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'User updated successfully.'];
} catch (PDOException $e) {
    error_log('Update user failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Failed to update the user. Please try again.'];
}

header('Location: ' . $redirectUrl);
exit;
