<?php
/**
 * pages/logout.php
 * Destroys the session and redirects to home.
 * No output before header() call.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth_helpers.php';

logoutUser();

$_SESSION['flash'] = ['type' => 'success', 'message' => 'You have been logged out successfully.'];
redirect(SITE_URL . '/index.php');
