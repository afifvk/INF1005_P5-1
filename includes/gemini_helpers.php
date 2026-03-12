<?php
/**
 * Gemini chatbot helpers.
 */

require_once __DIR__ . '/../includes/product_helpers.php';

function geminiChatEnabled(): bool {
    return defined('ENABLE_GEMINI_CHATBOT')
        && ENABLE_GEMINI_CHATBOT === true
        && defined('GEMINI_API_KEY')
        && trim((string) GEMINI_API_KEY) !== ''
        && GEMINI_API_KEY !== 'PASTE_YOUR_GEMINI_API_KEY_HERE';
}

function buildGeminiStoreContext(): string {
    try {
        $products = getAllProducts();
    } catch (Throwable $e) {
        error_log('Gemini context load failed: ' . $e->getMessage());
        $products = [];
    }

    $lines = [];
    foreach (array_slice($products, 0, 12) as $product) {
        $name = trim((string) ($product['name'] ?? ''));
        $description = trim((string) ($product['description'] ?? ''));
        $price = isset($product['price']) ? number_format((float) $product['price'], 2) : '';
        $stock = isset($product['stock']) ? (int) $product['stock'] : 0;

        if ($name === '') {
            continue;
        }

        $lines[] = '- ' . $name
            . ($price !== '' ? ' ($' . $price . ')' : '')
            . ' — ' . ($description !== '' ? $description : 'No description available.')
            . ' Stock: ' . $stock . '.';
    }

    if (empty($lines)) {
        return 'No live product catalog data is available right now.';
    }

    return "Current product catalog:
" . implode("
", $lines);
}

function sanitizeGeminiChatHistory($history): array {
    if (!is_array($history)) {
        return [];
    }

    $maxTurns = defined('GEMINI_CHATBOT_MAX_HISTORY_TURNS') ? (int) GEMINI_CHATBOT_MAX_HISTORY_TURNS : 6;
    $history = array_slice($history, -$maxTurns * 2);
    $clean = [];

    foreach ($history as $item) {
        if (!is_array($item)) {
            continue;
        }

        $role = strtolower(trim((string) ($item['role'] ?? '')));
        $text = trim((string) ($item['text'] ?? ''));

        if (!in_array($role, ['user', 'model'], true) || $text === '') {
            continue;
        }

        $clean[] = [
            'role' => $role,
            'parts' => [
                ['text' => mb_substr($text, 0, 1500)],
            ],
        ];
    }

    return $clean;
}

function geminiGenerateChatReply(string $message, array $history = []): array {
    if (!geminiChatEnabled()) {
        return [
            'success' => false,
            'message' => 'Gemini chatbot is not configured yet.',
        ];
    }

    $message = trim($message);
    if ($message === '') {
        return [
            'success' => false,
            'message' => 'Please enter a message.',
        ];
    }

    $message = mb_substr($message, 0, 2000);
    $context = buildGeminiStoreContext();
    $contents = sanitizeGeminiChatHistory($history);
    $contents[] = [
        'role' => 'user',
        'parts' => [
            ['text' => $message],
        ],
    ];

    $payload = [
        'systemInstruction' => [
            'parts' => [[
                'text' => "You are the customer support chatbot for the Tea ecommerce website. "
                    . "Help visitors with product questions, store navigation, tea suggestions, recommendations, account help, checkout basics, and general support. "
                    . "Be concise, friendly, and practical. Do not invent products, policies, prices, or stock. "
                    . "If the answer is not in the provided catalog context or the user's message, say you are not sure and suggest checking the Products page or contacting support.

"
                    . $context,
            ]],
        ],
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 300,
        ],
    ];

    $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode((string) GEMINI_MODEL) . ':generateContent';

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . GEMINI_API_KEY,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $rawResponse = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($rawResponse === false || $curlError !== '') {
        error_log('Gemini cURL error: ' . $curlError);
        return [
            'success' => false,
            'message' => 'The chatbot is temporarily unavailable. Please try again shortly.',
        ];
    }

    $decoded = json_decode($rawResponse, true);
    if (!is_array($decoded)) {
        error_log('Gemini invalid JSON response: ' . $rawResponse);
        return [
            'success' => false,
            'message' => 'The chatbot returned an invalid response.',
        ];
    }

    if ($httpCode >= 400 || isset($decoded['error'])) {
        $apiMessage = (string) ($decoded['error']['message'] ?? 'Unknown API error.');
        error_log('Gemini API error: HTTP ' . $httpCode . ' - ' . $apiMessage);
        return [
            'success' => false,
            'message' => 'The chatbot could not answer right now. Please try again later.',
        ];
    }

    $parts = $decoded['candidates'][0]['content']['parts'] ?? [];
    $replyChunks = [];
    foreach ($parts as $part) {
        if (!empty($part['text'])) {
            $replyChunks[] = trim((string) $part['text']);
        }
    }

    $reply = trim(implode("

", array_filter($replyChunks)));
    if ($reply === '') {
        $reply = 'Sorry, I could not generate a reply just now.';
    }

    return [
        'success' => true,
        'reply' => $reply,
    ];
}
