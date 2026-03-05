<?php
/**
 * includes/auth_helpers.php
 * User registration, login, and session management.
 * Compatible with PHP 7.x and 8.x
 */

/**
 * Register a new user.
 * Returns true on success, or an error string on failure.
 */
function registerUser(string $username, string $email, string $password) {
    $pdo = getDB();

    // Check for existing username or email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return "Username or email already exists.";
    }

    // Hash the password — cost=12 is a good balance of security vs speed
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hash]);

    return true;
}

/**
 * Attempt to log in a user by email + password.
 * Returns true and sets session on success, or error string on failure.
 */
function loginUser(string $email, string $password) {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return "Invalid email or password.";
    }

    // Regenerate session ID to prevent session fixation attack
    regenerateSession();

    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];

    return true;
}

/**
 * Log out the current user and destroy session.
 */
function logoutUser(): void {
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

/**
 * Validate registration input server-side.
 * Returns array of error strings (empty = valid).
 */
function validateRegistration(string $username, string $email, string $password, string $confirm): array {
    $errors = [];

    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be 3–50 characters.";
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