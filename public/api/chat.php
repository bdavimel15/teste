<?php
declare(strict_types=1);

error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido. Use POST.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$zaiaUrl = Config::get('ZAIA_WEBHOOK_URL', '');

if (!$zaiaUrl) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'ZAIA_WEBHOOK_URL não configurado no .env.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: '{}', true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'JSON inválido.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$message = trim((string)($payload['message'] ?? $payload['content'] ?? $payload['question'] ?? ''));

if ($message === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Mensagem vazia.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$body = json_encode([
    'message' => $message,
    'content' => $message,
    'question' => $message,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init($zaiaUrl);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_CONNECTTIMEOUT => 15,
]);

$responseBody = curl_exec($ch);
$curlError = curl_error($ch);
$statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

// PHP 8.5+: curl_close() é deprecated/não faz efeito.
// Não usar para evitar warning poluindo o JSON.

if ($responseBody === false || $curlError) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'Falha ao chamar a Zaia: ' . $curlError
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($statusCode < 200 || $statusCode >= 300) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'Zaia retornou HTTP ' . $statusCode,
        'raw' => (string)$responseBody,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$decoded = json_decode((string)$responseBody, true);

if (!is_array($decoded)) {
    echo json_encode([
        'success' => true,
        'reply' => trim((string)$responseBody),
        'agent' => 'Sistema',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = isset($decoded['data']) && is_array($decoded['data'])
    ? $decoded['data']
    : $decoded;

$reply = $data['reply']
    ?? $data['rawReply']
    ?? $data['raw_reply']
    ?? $data['message']
    ?? $data['content']
    ?? $data['answer']
    ?? $data['response']
    ?? $data['text']
    ?? '';

$agent = $data['agent']
    ?? $data['agentName']
    ?? $data['agent_name']
    ?? $data['selectedAgent']
    ?? $data['responder']
    ?? $data['agente']
    ?? 'Sistema';

$tokens = $data['tokens']
    ?? $data['remainingTokens']
    ?? $data['remaining_tokens']
    ?? null;

echo json_encode([
    'success' => true,
    'reply' => trim((string)$reply),
    'agent' => (string)$agent,
    'tokens' => is_numeric($tokens) ? (int)$tokens : null,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
