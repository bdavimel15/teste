<?php

declare(strict_types=1);

/**
 * POST /api/querybot.php
 *
 * Endpoint chamado pela Zaia via HTTP Request Node.
 *
 * Agora aceita vários formatos:
 *
 * 1) JSON direto:
 *    { "action": "sales_summary", "period": "month" }
 *
 * 2) Zaia content como objeto:
 *    { "content": { "action": "sales_summary", "period": "month" } }
 *
 * 3) Zaia content como string JSON:
 *    { "content": "{\"action\":\"sales_summary\",\"period\":\"month\"}" }
 *
 * 4) Body inteiro como string JSON:
 *    "{\"action\":\"sales_summary\",\"period\":\"month\"}"
 */

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
    echo json_encode(['success' => false, 'error' => 'Método não permitido. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$requiredToken = Config::get('QUERYBOT_API_TOKEN', '');

if ($requiredToken !== '') {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';

    $sentToken = '';
    if (str_starts_with($authHeader, 'Bearer ')) {
        $sentToken = substr($authHeader, 7);
    }

    if (!hash_equals($requiredToken, $sentToken)) {
        http_response_code(401);
        Logger::error('Unauthorized', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '-']);
        echo json_encode(['success' => false, 'error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function qb_try_json_decode(mixed $value): mixed
{
    if (!is_string($value)) {
        return $value;
    }

    $trimmed = trim($value);

    if ($trimmed === '') {
        return $value;
    }

    $decoded = json_decode($trimmed, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    return $value;
}

function qb_normalize_payload(mixed $payload): array
{
    $payload = qb_try_json_decode($payload);

    // Caso o body inteiro seja uma string JSON que virou array.
    if (is_string($payload)) {
        $payload = ['message' => $payload];
    }

    if (!is_array($payload)) {
        return [];
    }

    // Se já veio certo, mantém.
    if (isset($payload['action'])) {
        return $payload;
    }

    // Zaia pode mandar dentro de content/output/text/message.
    $possibleKeys = [
        'content',
        'output',
        'text',
        'message',
        'body',
        'data',
        'result',
        'response',
    ];

    foreach ($possibleKeys as $key) {
        if (!array_key_exists($key, $payload)) {
            continue;
        }

        $candidate = qb_try_json_decode($payload[$key]);

        // content como objeto
        if (is_array($candidate)) {
            if (isset($candidate['action'])) {
                return $candidate;
            }

            // Às vezes vem content.data.action
            if (isset($candidate['data']) && is_array($candidate['data']) && isset($candidate['data']['action'])) {
                return $candidate['data'];
            }

            // Às vezes vem content.content = "{...}"
            foreach ($possibleKeys as $innerKey) {
                if (isset($candidate[$innerKey])) {
                    $inner = qb_try_json_decode($candidate[$innerKey]);
                    if (is_array($inner) && isset($inner['action'])) {
                        return $inner;
                    }
                }
            }
        }
    }

    // Se veio route/action separados de outro jeito, tenta resgatar.
    $action = $payload['action']
        ?? $payload['routeAction']
        ?? $payload['selectedAction']
        ?? null;

    if ($action) {
        $payload['action'] = $action;
        return $payload;
    }

    return $payload;
}

$raw = file_get_contents('php://input');

if ($raw === false || trim($raw) === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Body vazio. Envie JSON com o campo "action".'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$decodedRaw = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'JSON inválido: ' . json_last_error_msg(),
        'rawPreview' => mb_substr($raw, 0, 300),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = qb_normalize_payload($decodedRaw);

if (!is_array($payload)) {
    $payload = [];
}

// Defaults seguros para sales_summary sem period.
if (($payload['action'] ?? '') === 'sales_summary' && empty($payload['period'])) {
    $payload['period'] = 'today';
}

// Resposta clara quando a Zaia mandar o formato errado.
if (empty($payload['action'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Campo action não encontrado no body recebido.',
        'received' => $decodedRaw,
        'expected_examples' => [
            ['action' => 'sales_summary', 'period' => 'month'],
            ['content' => ['action' => 'sales_summary', 'period' => 'month']],
            ['content' => '{"action":"sales_summary","period":"month"}'],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

try {
    $result = QueryHandler::handle($payload);

    Logger::info('Query OK', [
        'action'  => $payload['action'] ?? 'health',
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? '-',
        'success' => $result['success'] ?? true,
    ]);
} catch (Throwable $e) {
    Logger::error('Query falhou', [
        'exception' => $e->getMessage(),
        'payload'   => $payload,
    ]);

    http_response_code(500);
    $result = ['success' => false, 'error' => 'Erro interno no servidor.'];

    if (Config::bool('APP_DEBUG')) {
        $result['debug'] = $e->getMessage();
        $result['payload'] = $payload;
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
