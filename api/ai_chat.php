<?php
/**
 * AI Chat API Endpoint
 * POST /api/ai_chat
 * Returns JSON response from configured AI provider
 *
 * Table: ai_conversations
 *   - Each row = one message (user or assistant)
 *   - session_token groups a conversation
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/helpers.php';

header('Content-Type: application/json; charset=utf-8');

// Auth check
auth_start_session();
if (!auth_check()) {
    http_response_code(401);
    echo json_encode(['error' => 'Tidak dibenarkan.']);
    exit;
}

// CSRF via header
$token  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$stored = $_SESSION['csrf_token'] ?? '';
if (!$stored || !hash_equals($stored, $token)) {
    // Allow JSON body CSRF fallback
    $raw_body  = file_get_contents('php://input');
    $body_data = json_decode($raw_body, true) ?: [];
    $token     = $body_data['csrf_token'] ?? '';
    if (!$stored || !hash_equals($stored, $token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF tidak sah.']);
        exit;
    }
} else {
    $raw_body  = file_get_contents('php://input');
    $body_data = json_decode($raw_body, true) ?: [];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Kaedah tidak dibenarkan.']);
    exit;
}

$message      = trim($body_data['message'] ?? '');
$session_token = trim($body_data['session_token'] ?? '');

if (!$message || mb_strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'Mesej tidak sah (1–2000 karakter).']);
    exit;
}

$db      = getDB();
$user_id = auth_id();
$role    = auth_role();

// Generate session token for new conversation
if (!$session_token) {
    $session_token = bin2hex(random_bytes(16));
}

// Fetch conversation history (last 20 messages in this session)
$hist_stmt = $db->prepare("SELECT message_role, message FROM ai_conversations WHERE session_token=? AND user_id=? ORDER BY created_at DESC LIMIT 20");
$hist_stmt->execute([$session_token, $user_id]);
$history_rows = array_reverse($hist_stmt->fetchAll() ?: []);

// System prompt from settings
$system_prompt = get_setting('ai_system_prompt', 'Anda adalah pembantu AI untuk platform Kasih Gold Easy. Bantu pengguna memahami produk emas, simpanan, dan cara menggunakan platform ini. Berikan jawapan yang ringkas, tepat, dan mesra. Gunakan Bahasa Melayu.');

// AI config
$ai_provider = get_setting('ai_provider', 'anthropic');
$ai_model    = get_setting('ai_model', 'claude-haiku-4-5-20251001');
$ai_api_key  = get_setting('ai_api_key', '');

// Save user message
$db->prepare("INSERT INTO ai_conversations (user_id, session_token, actor_role, message_role, message, created_at) VALUES (?,?,?,?,?,NOW())")
   ->execute([$user_id, $session_token, $role, 'user', $message]);

$reply = '';

if (empty($ai_api_key)) {
    $reply = ai_fallback_response($message);
} else {
    try {
        $messages = [];
        foreach ($history_rows as $h) {
            $messages[] = ['role' => $h['message_role'], 'content' => $h['message']];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        if ($ai_provider === 'anthropic') {
            $payload = json_encode([
                'model'      => $ai_model,
                'max_tokens' => 1024,
                'system'     => $system_prompt,
                'messages'   => $messages,
            ]);
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $ai_api_key,
                    'anthropic-version: 2023-06-01',
                ],
                CURLOPT_TIMEOUT => 30,
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);
            if ($err) throw new \RuntimeException('cURL: ' . $err);
            $data  = json_decode($response, true);
            $reply = $data['content'][0]['text'] ?? '';
        } else {
            // OpenAI-compatible
            $payload = json_encode([
                'model'    => $ai_model,
                'messages' => array_merge(
                    [['role'=>'system','content'=>$system_prompt]],
                    $messages
                ),
                'max_tokens' => 1024,
            ]);
            $ch = curl_init('https://api.openai.com/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $ai_api_key,
                ],
                CURLOPT_TIMEOUT => 30,
            ]);
            $response = curl_exec($ch);
            $err      = curl_error($ch);
            curl_close($ch);
            if ($err) throw new \RuntimeException('cURL: ' . $err);
            $data  = json_decode($response, true);
            $reply = $data['choices'][0]['message']['content'] ?? '';
        }
    } catch (\Throwable $e) {
        error_log('AI Chat error: ' . $e->getMessage());
        $reply = ai_fallback_response($message);
    }
}

if (!$reply) $reply = ai_fallback_response($message);

// Save assistant reply
$db->prepare("INSERT INTO ai_conversations (user_id, session_token, actor_role, message_role, message, created_at) VALUES (?,?,?,?,?,NOW())")
   ->execute([$user_id, $session_token, $role, 'assistant', $reply]);

echo json_encode([
    'ok'            => true,
    'reply'         => $reply,
    'session_token' => $session_token,
]);

// ── Fallback responses (no API key configured) ────────────────────────────────
function ai_fallback_response(string $msg): string {
    $m = mb_strtolower($msg);
    if (str_contains($m, 'harga') || str_contains($m, 'price')) {
        $price = get_active_gold_price();
        if ($price) return 'Harga emas semasa ialah RM ' . number_format((float)$price['price_per_g'], 2) . '/g. Harga ini ditetapkan oleh admin dan boleh berubah dari semasa ke semasa.';
        return 'Harga emas terkini belum tersedia. Sila semak lagi kemudian.';
    }
    if (str_contains($m, 'beli') || str_contains($m, 'buy')) {
        return 'Untuk membeli emas, pergi ke menu "Beli Emas", masukkan jumlah RM, buat bayaran, dan muat naik bukti. Mata emas akan dikreditkan selepas admin mengesahkan.';
    }
    if (str_contains($m, 'pindah') || str_contains($m, 'transfer')) {
        return 'Pindahkan mata emas melalui menu "Pindah Mata". Masukkan e-mel atau kod rujukan penerima dan jumlah mata.';
    }
    if (str_contains($m, 'rujukan') || str_contains($m, 'referral')) {
        return 'Program rujukan 3 peringkat: Level 1 (5%), Level 2 (3%), Level 3 (1%). Kongsi pautan rujukan anda!';
    }
    if (str_contains($m, 'mata') || str_contains($m, 'point')) {
        return '1 mata emas = 0.01 gram emas. Formula: Mata = (RM ÷ Harga/g) × 100.';
    }
    if (str_contains($m, 'syariat') || str_contains($m, 'halal') || str_contains($m, 'shariah')) {
        return 'Kasih Gold Easy direka mengikut prinsip Muamalat Islam. Simpanan emas fizikal bebas riba. Rujuk ulama untuk kepastian lanjut.';
    }
    if (str_contains($m, 'terima kasih') || str_contains($m, 'thank')) {
        return 'Sama-sama! Jika ada pertanyaan lain, saya sedia membantu.';
    }
    return 'Terima kasih atas pertanyaan anda. Untuk maklumat lanjut, hubungi sokongan kami melalui WhatsApp. Boleh saya bantu dengan perkara lain?';
}
