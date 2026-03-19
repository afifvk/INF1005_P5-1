<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth_helpers.php';
require_once dirname(__DIR__) . '/includes/gemini_helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
if (!verifyCsrfToken((string) $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request token. Please refresh and try again.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput ?: '', true);
if (!is_array($data)) {
    $data = $_POST;
}

$message = trim((string) ($data['message'] ?? ''));
$history = $data['history'] ?? [];

if ($message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please enter a message.']);
    exit;
}

$result = geminiGenerateChatReply($message, is_array($history) ? $history : []);
if (!$result['success']) {
    http_response_code(500);
    echo json_encode($result);
    exit;
}

echo json_encode($result);
