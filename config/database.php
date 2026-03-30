<?php
/**
 * Database Configuration & PDO Connection
 * Uses PDO with prepared statements — prevents SQL injection.
 * Credentials should be moved to environment variables in production.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'store_db');
define('DB_USER', 'inf1005-sqldev');       // Change in production
define('DB_PASS', 'P5-1-password');           // Change in production
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO connection.
 * PDO throws exceptions on error rather than silent failures.
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Throw on error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Arrays by default
            PDO::ATTR_EMULATE_PREPARES   => false,                    // Real prepared stmts
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET time_zone = '+08:00'");
        } catch (PDOException $e) {
            // Never expose raw DB errors to users — log them instead
            error_log("DB Connection Error: " . $e->getMessage());
            die("A database error occurred. Please try again later.");
        }
    }

    return $pdo;
}
