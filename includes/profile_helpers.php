<?php
/**
 * includes/profile_helpers.php
 * Reusable CRUD functions for user profile management.
 */

function getUserById($id) {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, address, role, created_at
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllUsers() {
    $pdo  = getDB();
    $stmt = $pdo->query("
        SELECT id, first_name, last_name, email, role, created_at
        FROM users
        ORDER BY created_at DESC
    ");
    return $stmt->fetchAll();
}

function updateUserProfile($id, $firstName, $lastName, $email, $address, $newPassword = '') {
    $pdo = getDB();

    // Check email not taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        return "That email is already used by another account.";
    }

    if (!empty($newPassword)) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("
            UPDATE users
            SET first_name=?, last_name=?, email=?, address=?, password=?
            WHERE id=?
        ");
        $stmt->execute([$firstName, $lastName, $email, $address, $hash, $id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET first_name=?, last_name=?, email=?, address=?
            WHERE id=?
        ");
        $stmt->execute([$firstName, $lastName, $email, $address, $id]);
    }

    return true;
}

function deleteUser($id) {
    $pdo  = getDB();
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Validate profile update.
 * firstName is optional, lastName is required.
 */
function validateProfileUpdate($firstName, $lastName, $email, $newPassword, $confirmPassword) {
    $errors = [];

    // First name optional — only validate length if provided
    if (!empty($firstName) && strlen($firstName) > 80) {
        $errors[] = 'First name must be under 80 characters.';
    }

    if (empty($lastName)) {
        $errors[] = 'Last name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!empty($newPassword)) {
        if (strlen($newPassword) < 8)             $errors[] = 'New password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $newPassword)) $errors[] = 'New password needs at least one uppercase letter.';
        if (!preg_match('/[0-9]/', $newPassword)) $errors[] = 'New password needs at least one number.';
        if ($newPassword !== $confirmPassword)    $errors[] = 'New passwords do not match.';
    }

    return $errors;
}