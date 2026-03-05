<?php
/**
 * includes/auth_helpers.php
 * User registration, login, and session management.
 * - No username field
 * - First name optional
 * - Last name required
 */

function registerUser($firstName, $lastName, $address, $email, $password) {
    $pdo = getDB();

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return "Email already exists.";
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, address, email, password, role)
        VALUES (?, ?, ?, ?, ?, 'customer')
    ");
    $stmt->execute([$firstName, $lastName, $address, $email, $hash]);

    return true;
}

function loginUser($email, $password) {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, role, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return "Invalid email or password.";
    }

    regenerateSession();

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name']  = $user['last_name'];
    $_SESSION['role']       = $user['role'];

    return true;
}

function logoutUser() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Validate registration inputs.
 * - firstName: optional
 * - lastName:  required
 * - email:     required, must be valid format
 * - password:  min 8 chars, 1 uppercase, 1 number
 * - confirm:   must match password
 */
function validateRegistration($firstName, $lastName, $email, $password, $confirm) {
    $errors = [];

    // Last name required, first name optional
    if (empty($lastName)) {
        $errors[] = "Last name is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must include at least one uppercase letter.";
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must include at least one number.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    return $errors;
}