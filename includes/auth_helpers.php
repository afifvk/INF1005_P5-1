<?php
/**
 * includes/auth_helpers.php
 * User registration, login, email verification, password reset, reCAPTCHA, and session management.
 */
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function registerUser($firstName, $lastName, $address, $email, $password) {
    $pdo = getDB();
    $email = normalizeEmail($email);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['status' => 'error', 'message' => 'Email already exists.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $verification = generateVerificationTokenPair();
    $expiresAt = date('Y-m-d H:i:s', time() + (VERIFICATION_EXPIRY_MINUTES * 60));
    $now = date('Y-m-d H:i:s');

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO users (
                first_name,
                last_name,
                address,
                email,
                password,
                role,
                is_verified,
                verification_token_hash,
                verification_expires_at,
                verification_last_sent_at
            )
            VALUES (?, ?, ?, ?, ?, 'customer', 0, ?, ?, ?)
        ");
        $stmt->execute([
            $firstName,
            $lastName,
            $address,
            $email,
            $hash,
            $verification['hash'],
            $expiresAt,
            $now,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Registration error: ' . $e->getMessage());
        return ['status' => 'error', 'message' => 'Unable to create account right now. Please try again later.'];
    }

    $mailSent = sendVerificationEmail($email, $verification['plain'], $firstName, $lastName);

    if (!$mailSent) {
        return [
            'status' => 'pending',
            'message' => 'Your account was created, but we could not send the verification email just now. You can resend it after 1 minute.',
            'email' => $email,
            'mail_sent' => false,
        ];
    }

    return [
        'status' => 'pending',
        'message' => 'Your account was created. Please check your email and verify your account before logging in.',
        'email' => $email,
        'mail_sent' => true,
    ];
}

function loginUser($email, $password) {
    $pdo = getDB();
    $email = normalizeEmail($email);

    $stmt = $pdo->prepare("SELECT id, first_name, last_name, role, password, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return "Invalid email or password.";
    }

    if ((int) $user['is_verified'] !== 1) {
        return "Please verify your email before logging in.";
    }

    regenerateSession();

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['role'] = $user['role'];

    return true;
}

function logoutUser() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdminAccess() {
    if (!isAdmin()) {
        http_response_code(403);
        require_once dirname(__DIR__) . '/pages/403.php';
        exit;
    }
}

function normalizeEmail($email) {
    return strtolower(trim((string) $email));
}

function generateVerificationTokenPair() {
    $plain = bin2hex(random_bytes(32));
    return [
        'plain' => $plain,
        'hash' => hash('sha256', $plain),
    ];
}

function getVerificationUrl($plainToken) {
    return SITE_URL . '/pages/verify_email.php?token=' . urlencode($plainToken);
}

function getPasswordResetUrl($plainToken) {
    return SITE_URL . '/pages/reset_password.php?token=' . urlencode($plainToken);
}

function sendSmtpMessage($email, $recipient, $subject, $body) {
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->Port = SMTP_PORT;

        if (SMTP_ENCRYPTION === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif (SMTP_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($email, $recipient);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML(false);

        return $mail->send();
    } catch (Exception $e) {
        error_log('Email send failed: ' . $e->getMessage());
        return false;
    }
}

function sendVerificationEmail($email, $plainToken, $firstName = '', $lastName = '') {
    $verifyUrl = getVerificationUrl($plainToken);
    $recipient = trim($firstName . ' ' . $lastName);
    if ($recipient === '') {
        $recipient = 'Customer';
    }

    $subject = SITE_NAME . ' Email Verification';
    $body = "Hello {$recipient},\n\n"
        . "Thank you for creating an account with " . SITE_NAME . ".\n"
        . "Please verify your email by clicking the link below:\n\n"
        . $verifyUrl . "\n\n"
        . "This link will expire in " . VERIFICATION_EXPIRY_MINUTES . " minutes.\n\n"
        . "If you did not create this account, you can ignore this email.";

    return sendSmtpMessage($email, $recipient, $subject, $body);
}

function sendPasswordResetEmail($email, $plainToken, $firstName = '', $lastName = '') {
    $resetUrl = getPasswordResetUrl($plainToken);
    $recipient = trim($firstName . ' ' . $lastName);
    if ($recipient === '') {
        $recipient = 'Customer';
    }

    $subject = SITE_NAME . ' Password Reset';
    $body = "Hello {$recipient},\n\n"
        . "We received a request to reset the password for your " . SITE_NAME . " account.\n"
        . "To choose a new password, click the link below:\n\n"
        . $resetUrl . "\n\n"
        . "This link will expire in " . PASSWORD_RESET_EXPIRY_MINUTES . " minutes.\n\n"
        . "If you did not request a password reset, you can safely ignore this email.";

    return sendSmtpMessage($email, $recipient, $subject, $body);
}

function getVerificationPendingState($email) {
    $pdo = getDB();
    $email = normalizeEmail($email);

    if ($email === '') {
        return [
            'exists' => false,
            'verified' => false,
            'remaining_seconds' => 0,
        ];
    }

    $stmt = $pdo->prepare("SELECT is_verified, verification_last_sent_at FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return [
            'exists' => false,
            'verified' => false,
            'remaining_seconds' => 0,
        ];
    }

    if ((int) $user['is_verified'] === 1) {
        return [
            'exists' => true,
            'verified' => true,
            'remaining_seconds' => 0,
        ];
    }

    $remaining = 0;
    if (!empty($user['verification_last_sent_at'])) {
        $lastSent = strtotime($user['verification_last_sent_at']);
        if ($lastSent !== false) {
            $elapsed = time() - $lastSent;
            if ($elapsed < VERIFICATION_RESEND_COOLDOWN_SECONDS) {
                $remaining = VERIFICATION_RESEND_COOLDOWN_SECONDS - $elapsed;
            }
        }
    }

    return [
        'exists' => true,
        'verified' => false,
        'remaining_seconds' => max(0, (int) $remaining),
    ];
}

function resendVerificationEmail($email) {
    $pdo = getDB();
    $email = normalizeEmail($email);

    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, is_verified, verification_last_sent_at FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['status' => 'generic_success', 'message' => 'If an eligible account exists, a verification email has been sent.'];
    }

    if ((int) $user['is_verified'] === 1) {
        return ['status' => 'already_verified', 'message' => 'This account is already verified. You do not need to verify it again.'];
    }

    if (!empty($user['verification_last_sent_at'])) {
        $lastSent = strtotime($user['verification_last_sent_at']);
        if ($lastSent !== false) {
            $elapsed = time() - $lastSent;
            if ($elapsed < VERIFICATION_RESEND_COOLDOWN_SECONDS) {
                return [
                    'status' => 'cooldown',
                    'message' => 'Please wait ' . (VERIFICATION_RESEND_COOLDOWN_SECONDS - $elapsed) . ' seconds before requesting another verification email.',
                ];
            }
        }
    }

    $verification = generateVerificationTokenPair();
    $expiresAt = date('Y-m-d H:i:s', time() + (VERIFICATION_EXPIRY_MINUTES * 60));
    $now = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        UPDATE users
        SET verification_token_hash = ?,
            verification_expires_at = ?,
            verification_last_sent_at = ?
        WHERE id = ? AND is_verified = 0
    ");
    $stmt->execute([$verification['hash'], $expiresAt, $now, $user['id']]);

    $mailSent = sendVerificationEmail($user['email'], $verification['plain'], $user['first_name'], $user['last_name']);

    if (!$mailSent) {
        return [
            'status' => 'error',
            'message' => 'We could not send the verification email right now. Please try again in a minute.',
        ];
    }

    return [
        'status' => 'sent',
        'message' => 'A new verification email has been sent. Please check your inbox.',
    ];
}

function verifyEmailToken($plainToken) {
    $plainToken = trim((string) $plainToken);
    if ($plainToken === '' || !preg_match('/^[a-f0-9]{64}$/', $plainToken)) {
        return ['status' => 'error', 'message' => 'Invalid verification link.'];
    }

    $pdo = getDB();
    $tokenHash = hash('sha256', $plainToken);

    $stmt = $pdo->prepare("
        SELECT id, is_verified, verification_expires_at
        FROM users
        WHERE verification_token_hash = ?
        LIMIT 1
    ");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['status' => 'error', 'message' => 'Invalid or expired verification link.'];
    }

    if ((int) $user['is_verified'] === 1) {
        return ['status' => 'success', 'message' => 'Your account is already verified.'];
    }

    if (empty($user['verification_expires_at']) || strtotime($user['verification_expires_at']) < time()) {
        return ['status' => 'expired', 'message' => 'This verification link has expired. Please request a new one.'];
    }

    $stmt = $pdo->prepare("
        UPDATE users
        SET is_verified = 1,
            verified_at = NOW(),
            verification_token_hash = NULL,
            verification_expires_at = NULL,
            verification_last_sent_at = NULL
        WHERE id = ? AND is_verified = 0
    ");
    $stmt->execute([$user['id']]);

    return ['status' => 'success', 'message' => 'Your email has been verified successfully. You can now log in.'];
}

function hasRecaptchaConfig() {
    return trim((string) RECAPTCHA_SITE_KEY) !== '' && trim((string) RECAPTCHA_SECRET_KEY) !== '';
}

function verifyRecaptchaToken($responseToken, $remoteIp = '') {
    if (!hasRecaptchaConfig()) {
        return [
            'success' => false,
            'message' => 'Security check is not configured yet.',
        ];
    }

    $responseToken = trim((string) $responseToken);
    if ($responseToken === '') {
        return [
            'success' => false,
            'message' => 'Please complete the security check and try again.',
        ];
    }

    $postFields = http_build_query([
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $responseToken,
        'remoteip' => $remoteIp,
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => $postFields,
            'timeout' => 10,
        ],
    ]);

    $verifyResponse = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    if ($verifyResponse === false) {
        return [
            'success' => false,
            'message' => 'Unable to validate the security check right now. Please try again.',
        ];
    }

    $decoded = json_decode($verifyResponse, true);
    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'Unable to validate the security check right now. Please try again.',
        ];
    }

    if (!empty($decoded['success'])) {
        return [
            'success' => true,
            'message' => '',
        ];
    }

    return [
        'success' => false,
        'message' => 'Please complete the security check and try again.',
    ];
}

function requestPasswordReset($email) {
    $pdo = getDB();
    $email = normalizeEmail($email);
    $genericMessage = "If that email address can receive password reset instructions, we'll send them shortly.";

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => 'sent', 'message' => $genericMessage];
    }

    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['status' => 'sent', 'message' => $genericMessage];
    }

    $token = generateVerificationTokenPair();
    $expiresAt = date('Y-m-d H:i:s', time() + (PASSWORD_RESET_EXPIRY_MINUTES * 60));
    $now = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        UPDATE users
        SET password_reset_token_hash = ?,
            password_reset_expires_at = ?,
            password_reset_requested_at = ?
        WHERE id = ?
    ");
    $stmt->execute([$token['hash'], $expiresAt, $now, $user['id']]);

    sendPasswordResetEmail($user['email'], $token['plain'], $user['first_name'], $user['last_name']);

    return ['status' => 'sent', 'message' => $genericMessage];
}

function getPasswordResetRecord($plainToken) {
    $plainToken = trim((string) $plainToken);
    if ($plainToken === '' || !preg_match('/^[a-f0-9]{64}$/', $plainToken)) {
        return null;
    }

    $pdo = getDB();
    $tokenHash = hash('sha256', $plainToken);

    $stmt = $pdo->prepare("SELECT id, password_reset_expires_at FROM users WHERE password_reset_token_hash = ? LIMIT 1");
    $stmt->execute([$tokenHash]);
    $user = $stmt->fetch();

    if (!$user) {
        return null;
    }

    if (empty($user['password_reset_expires_at']) || strtotime($user['password_reset_expires_at']) < time()) {
        return ['status' => 'expired', 'user_id' => (int) $user['id']];
    }

    return ['status' => 'valid', 'user_id' => (int) $user['id']];
}

function resetPasswordWithToken($plainToken, $password, $confirm) {
    $record = getPasswordResetRecord($plainToken);

    if (!$record) {
        return ['status' => 'error', 'message' => 'Invalid or expired password reset link.'];
    }

    if ($record['status'] === 'expired') {
        return ['status' => 'expired', 'message' => 'This password reset link has expired. Please request a new one.'];
    }

    $errors = validatePasswordPolicy($password);
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!empty($errors)) {
        return ['status' => 'validation_error', 'errors' => $errors];
    }

    $pdo = getDB();
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare("
        UPDATE users
        SET password = ?,
            password_reset_token_hash = NULL,
            password_reset_expires_at = NULL,
            password_reset_requested_at = NULL,
            password_reset_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$hash, $record['user_id']]);

    return ['status' => 'success', 'message' => 'Your password has been reset successfully. You can now log in.'];
}

function validatePasswordPolicy($password) {
    $errors = [];

    if (strlen($password) < 10) {
        $errors[] = 'Password must be at least 10 characters.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must include at least one uppercase letter.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must include at least one lowercase letter.';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must include at least one number.';
    }

    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Password must include at least one special character.';
    }

    return $errors;
}

/**
 * Validate registration inputs.
 */
function validateRegistration($firstName, $lastName, $email, $password, $confirm) {
    $errors = [];

    if (empty(trim($firstName))) {
        $errors[] = "First name is required.";
    }

    if (empty(trim($lastName))) {
        $errors[] = "Last name is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    $errors = array_merge($errors, validatePasswordPolicy($password));

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    return $errors;
}

function getClientIp() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $value = trim((string) $_SERVER[$key]);

            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $parts = explode(',', $value);
                $value = trim($parts[0]);
            }

            if ($value !== '') {
                return $value;
            }
        }
    }

    return 'unknown';
}

function getCurrentPageRateLimitAction() {
    $scriptName = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $strictPages = ['login.php', 'register.php', 'forgot_password.php', 'verify_pending.php'];

    if (in_array($scriptName, $strictPages, true)) {
        return 'page_' . str_replace('.php', '', $scriptName);
    }

    return 'page_general';
}

function getCurrentPageRateLimitConfig() {
    $action = getCurrentPageRateLimitAction();
    $strictActions = ['page_login', 'page_register', 'page_forgot_password', 'page_verify_pending'];

    if (in_array($action, $strictActions, true)) {
        return [
            'action' => $action,
            'max_requests' => AUTH_PAGE_RATE_LIMIT_MAX_REQUESTS,
            'window_seconds' => AUTH_PAGE_RATE_LIMIT_WINDOW_SECONDS,
        ];
    }

    return [
        'action' => $action,
        'max_requests' => GENERAL_PAGE_RATE_LIMIT_MAX_REQUESTS,
        'window_seconds' => GENERAL_PAGE_RATE_LIMIT_WINDOW_SECONDS,
    ];
}

function countRecentRateLimitRequests($action, $identifier, $windowSeconds) {
    $pdo = getDB();
    $cutoff = date('Y-m-d H:i:s', time() - (int) $windowSeconds);

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM rate_limits
        WHERE action = ?
          AND identifier = ?
          AND created_at >= ?
    ");
    $stmt->execute([$action, $identifier, $cutoff]);

    return (int) $stmt->fetchColumn();
}

function recordRateLimitRequest($action, $identifier) {
    $pdo = getDB();

    $stmt = $pdo->prepare("
        INSERT INTO rate_limits (action, identifier)
        VALUES (?, ?)
    ");
    $stmt->execute([$action, $identifier]);
}

function cleanupOldRateLimitEntries($maxAgeSeconds = 86400) {
    static $cleaned = false;

    if ($cleaned) {
        return;
    }

    $cleaned = true;

    try {
        $pdo = getDB();
        $cutoff = date('Y-m-d H:i:s', time() - (int) $maxAgeSeconds);

        $stmt = $pdo->prepare("
            DELETE FROM rate_limits
            WHERE created_at < ?
        ");
        $stmt->execute([$cutoff]);
    } catch (Throwable $e) {
        error_log('Rate limit cleanup failed: ' . $e->getMessage());
    }
}

function enforcePageRateLimit() {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = is_string($path) ? $path : '';
    $basename = basename($path);

    if (preg_match('/\.(css|js|png|jpe?g|gif|svg|webp)$/i', $path)) {
        return;
    }

    if (in_array($basename, ['403.php', '404.php', '429.php'], true)) {
        return;
    }

    cleanupOldRateLimitEntries();

    $config = getCurrentPageRateLimitConfig();
    $identifier = getClientIp();
    $requestCount = countRecentRateLimitRequests(
        $config['action'],
        $identifier,
        $config['window_seconds']
    );

    if ($requestCount >= (int) $config['max_requests']) {
        http_response_code(429);
        require_once dirname(__DIR__) . '/pages/429.php';
        exit;
    }

    recordRateLimitRequest($config['action'], $identifier);
}
