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
                'text' => "You are the customer support chatbot for a Tea ecommerce website. "
                    . "Help visitors with product questions, store navigation, tea suggestions, recommendations, account help, checkout basics, and general support. "
                    . "Be concise, friendly, and practical. Do not invent products, policies, prices, or stock. "
                    . "If the answer is not in the provided catalog or the user's message, say you are not sure and suggest checking the Products page or contacting support. "
                    . "\n\nSHIPPING POLICY: Free shipping on orders over \$50. Orders under \$50 ship for \$3.99. "
                    . "\n\nTEA CATALOG:\n"
                    . "- Morning Rush Black Tea: Bold, high-caffeine black tea. Great for energy and focus in the morning.\n"
                    . "- Slow Sunday Herbal: Caffeine-free herbal blend. Relaxing, good for unwinding or evenings.\n"
                    . "- Afternoon Floral Lift: Light floral blend with mild caffeine. Refreshing afternoon pick-me-up.\n"
                    . "- Zen Master Blend: Calming herbal mix with chamomile and lavender. Good for stress relief and sleep.\n"
                    . "- Afternoon Chicken: Savory broth-style tea. Unique umami flavor, caffeine-free.\n"
                    . "- Morning Chicken: Savory, broth-style morning tea. Caffeine-free, warming and hearty.\n"
                    . "- Dragon Well Green Tea: Classic Chinese green tea. Light, grassy, antioxidant-rich. Supports metabolism.\n"
                    . "- Earl Grey Black Tea: Black tea with bergamot. Aromatic, medium caffeine. A classic.\n"
                    . "- Peppermint Herbal Tea: Caffeine-free. Aids digestion, refreshing and cooling.\n"
                    . "- Darjeeling First Flush: Delicate, floral black tea from India. Light-bodied, low-to-medium caffeine.\n"
                    . "- Matcha Ceremonial Grade: Finely ground Japanese green tea. High antioxidants, sustained energy, supports metabolism.\n"
                    . "- Chamomile Honey: Caffeine-free herbal tea. Soothing, great for sleep and relaxation.\n"
                    . "- Oolong Milk Tea: Creamy, semi-oxidized oolong. Medium caffeine. Often associated with metabolism support.\n"
                    . "- Rooibos Vanilla: Caffeine-free South African red tea with vanilla. Naturally sweet, antioxidant-rich.\n"
                    . "- Sencha Classic: Traditional Japanese green tea. Grassy and refreshing, moderate caffeine, rich in antioxidants.\n"
                    . "\n\nRECOMMENDATION GUIDANCE:\n"
                    . "- Weight loss / metabolism: Matcha Ceremonial Grade, Dragon Well Green Tea, Oolong Milk Tea, Sencha Classic.\n"
                    . "- Energy / caffeine: Morning Rush Black Tea (highest), Matcha, Earl Grey, Darjeeling.\n"
                    . "- Caffeine-free: Slow Sunday Herbal, Zen Master Blend, Afternoon Chicken, Morning Chicken, Peppermint Herbal, Chamomile Honey, Rooibos Vanilla.\n"
                    . "- Sleep / relaxation: Zen Master Blend, Chamomile Honey, Slow Sunday Herbal.\n"
                    . "- Digestion: Peppermint Herbal Tea.\n"
                    . "- Antioxidants: Matcha, Sencha, Dragon Well, Rooibos Vanilla.\n"
                    . "Always recommend 2-3 specific teas when a customer asks for a suggestion, briefly explaining why each fits their need.\n\n"
                    . $context,
            ]],
        ],
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 800,
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
