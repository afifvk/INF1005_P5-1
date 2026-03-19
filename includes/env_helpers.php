<?php
/**
 * Environment variable helper.
 * Loads a .env file and provides a typed accessor.
 */

function loadEnv(string $filePath): void {
    if (!file_exists($filePath)) {
        error_log('env_helper: .env file not found at ' . $filePath);
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and blank lines
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Must contain '='
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip surrounding quotes (single or double)
        if (
            strlen($value) >= 2 &&
            (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            )
        ) {
            $value = substr($value, 1, -1);
        }

        if ($key === '') {
            continue;
        }

        // Don't overwrite already-set environment variables
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

/**
 * Get an env value, with an optional default fallback.
 *
 * Usage:
 *   env('GEMINI_API_KEY')            // returns value or null
 *   env('GEMINI_API_KEY', 'default') // returns value or 'default'
 */
function env(string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    // Cast common string booleans
    return match (strtolower((string) $value)) {
        'true', '1', 'yes'  => true,
        'false', '0', 'no'  => false,
        'null', 'none', ''  => null,
        default             => $value,
    };
}