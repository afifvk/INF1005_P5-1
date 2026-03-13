<?php
/**
 * Application Configuration
 */
require_once __DIR__ . '/../includes/env_helpers.php';
loadEnv(dirname(__DIR__) . '/.env');

define('SITE_NAME', 'Tea');
define('SITE_URL', 'http://35.212.189.249');
define('SITE_VERSION', '1.0.0');

// Mail configuration
define('MAIL_FROM_NAME', 'Tea Support');
define('MAIL_FROM_ADDRESS', 'Uniw2500518@gmail.com');
define('VERIFICATION_EXPIRY_MINUTES', 30);
define('VERIFICATION_RESEND_COOLDOWN_SECONDS', 60);
define('PASSWORD_RESET_EXPIRY_MINUTES', 30);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'Uniw2500518@gmail.com');
define('SMTP_PASSWORD', 'lcyzcxanmmycbpca');
define('SMTP_ENCRYPTION', 'tls');

// Google reCAPTCHA v2
define('RECAPTCHA_SITE_KEY', '6Le6SIIsAAAAAKSab4IdtBdfnBNS4_LlpY3MdirQ');
define('RECAPTCHA_SECRET_KEY', '6Le6SIIsAAAAAIn7BBHUTow9T8-sWKFP_oxnprKQ');


// Gemini chatbot
// Keep the website on HTTP if needed; the server will call the Gemini API over HTTPS.
define('ENABLE_GEMINI_CHATBOT', true);
//define('GEMINI_API_KEY', 'AIzaSyCK-hZO3GTN2xC2QEwSn2XXkdQmSRH9E4w');
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY'));
define('GEMINI_MODEL', 'gemini-2.5-flash');
define('GEMINI_CHATBOT_MAX_HISTORY_TURNS', 6);

// Session hardening
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function regenerateSession(): void {
    session_regenerate_id(true);
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}
