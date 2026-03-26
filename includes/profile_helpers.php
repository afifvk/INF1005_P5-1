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

function updateUserProfile($id, $firstName, $lastName, $address, $newPassword = '') {
    $pdo = getDB();

    if (!empty($newPassword)) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare("
            UPDATE users
            SET first_name=?, last_name=?, address=?, password=?
            WHERE id=?
        ");
        $stmt->execute([$firstName, $lastName, $address, $hash, $id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET first_name=?, last_name=?, address=?
            WHERE id=?
        ");
        $stmt->execute([$firstName, $lastName, $address, $id]);
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
function validateProfileUpdate($firstName, $lastName, $newPassword, $confirmPassword) {
    $errors = [];

    // First name — required
    if (empty($firstName)) {
        $errors[] = 'First name is required.';
    } elseif (strlen($firstName) > 80) {
        $errors[] = 'First name must be under 80 characters.';
    }

    // Last name — required
    if (empty($lastName)) {
        $errors[] = 'Last name is required.';
    } elseif (strlen($lastName) > 80) {
        $errors[] = 'Last name must be under 80 characters.';
    }


    if (!empty($newPassword)) {
        if (strlen($newPassword) < 10)                      $errors[] = 'New password must be at least 10 characters.';
        if (!preg_match('/[A-Z]/', $newPassword))           $errors[] = 'New password needs at least one uppercase letter.';
        if (!preg_match('/[a-z]/', $newPassword))           $errors[] = 'New password needs at least one lowercase letter.';
        if (!preg_match('/[0-9]/', $newPassword))           $errors[] = 'New password needs at least one number.';
        if (!preg_match('/[^A-Za-z0-9]/', $newPassword))   $errors[] = 'New password needs at least one special character.';
        if ($newPassword !== $confirmPassword)              $errors[] = 'New passwords do not match.';
    }

    return $errors;
}