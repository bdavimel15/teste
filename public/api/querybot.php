<?php

declare(strict_types=1);

/**
 * POST /api/querybot.php
 *
 * Endpoint chamado pela Zaia via HTTP Request Node.
 *
 * Aceita:
 * 1) JSON direto com action:
 *    { "action": "sales_summary", "period": "month" }
 *
 * 2) Content como objeto/string:
 *    { "content": { "action": "top_products", "limit": 5 } }
 *    { "content": "{\"action\":\"top_products\",\"limit\":5}" }
 *
 * 3) Texto simples vindo da Zaia:
 *    { "content": "quais produtos mais venderam?" }
 *    { "mensagem": "clientes que compraram picanha" }
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

function qb_find_text(mixed $payload): string
{
    $payload = qb_try_json_decode($payload);

    if (is_string($payload)) {
        return trim($payload);
    }

    if (!is_array($payload)) {
        return '';
    }

    $keys = [
        'message', 'mensagem', 'texto', 'text', 'prompt', 'pergunta',
        'query', 'content', 'body', 'input', 'descricao', 'description',
    ];

    foreach ($keys as $key) {
        if (!array_key_exists($key, $payload)) {
            continue;
        }

        $value = qb_try_json_decode($payload[$key]);

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (is_array($value)) {
            $inner = qb_find_text($value);
            if ($inner !== '') {
                return $inner;
            }
        }
    }

    return '';
}

function qb_infer_payload_from_text(string $text): array
{
    $t = mb_strtolower(trim($text));

    if ($t === '') {
        return [];
    }

    // Health/diagnóstico
    if (str_contains($t, 'diagn') || str_contains($t, 'health') || str_contains($t, 'status')) {
        return ['action' => 'health'];
    }

    // Produto específico comprado por cliente
    if (preg_match('/clientes?\s+que\s+compraram\s+(.+)/iu', $text, $m)) {
        $product = trim($m[1], " \t\n\r\0\x0B?.!,");
        if ($product !== '') {
            return ['action' => 'customers_by_product', 'product' => $product, 'limit' => 20];
        }
    }

    // Produtos mais vendidos / ranking
    if (
        str_contains($t, 'produto') &&
        (str_contains($t, 'mais vendido') || str_contains($t, 'top') || str_contains($t, 'ranking') || str_contains($t, 'vendeu mais'))
    ) {
        $limit = 5;
        if (preg_match('/(?:top|primeir[oa]s?)\s+(\d+)/iu', $text, $m)) {
            $limit = max(1, min(20, (int)$m[1]));
        }
        return ['action' => 'top_products', 'limit' => $limit];
    }

    // Listar produtos
    if (str_contains($t, 'listar produtos') || str_contains($t, 'lista de produtos') || $t === 'produtos') {
        return ['action' => 'products_list', 'limit' => 20];
    }

    // Estoque baixo
    if (str_contains($t, 'estoque baixo') || str_contains($t, 'baixo estoque')) {
        return ['action' => 'low_stock', 'threshold' => 10];
    }

    // Clientes
    if (str_contains($t, 'quantos clientes') || str_contains($t, 'total de clientes') || $t === 'clientes') {
        return ['action' => 'customers_count'];
    }

    // Pedidos recentes
    if (str_contains($t, 'últimos pedidos') || str_contains($t, 'ultimos pedidos') || str_contains($t, 'pedidos recentes')) {
        return ['action' => 'recent_orders', 'limit' => 10];
    }

    // Vendas/faturamento
    if (str_contains($t, 'venda') || str_contains($t, 'faturamento') || str_contains($t, 'receita') || str_contains($t, 'pedido')) {
        $period = 'today';

        if (str_contains($t, 'ontem')) {
            $period = 'yesterday';
        } elseif (str_contains($t, 'semana') || str_contains($t, '7 dias')) {
            $period = 'week';
        } elseif (str_contains($t, 'mês') || str_contains($t, 'mes') || str_contains($t, '30 dias')) {
            $period = 'month';
        }

        return ['action' => 'sales_summary', 'period' => $period];
    }

    return [];
}

function qb_normalize_payload(mixed $payload): array
{
    $payload = qb_try_json_decode($payload);

    if (is_string($payload)) {
        $textPayload = qb_infer_payload_from_text($payload);
        return $textPayload ?: ['message' => $payload];
    }

    if (!is_array($payload)) {
        return [];
    }

    // Se já veio certo, mantém.
    if (isset($payload['action']) && is_string($payload['action']) && trim($payload['action']) !== '') {
        return $payload;
    }

    // Zaia pode mandar action dentro de content/output/text/message/data/result/response.
    $possibleKeys = [
        'content',
        'output',
        'text',
        'message',
        'mensagem',
        'texto',
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

        if (is_array($candidate)) {
            if (isset($candidate['action'])) {
                return $candidate;
            }

            if (isset($candidate['data']) && is_array($candidate['data']) && isset($candidate['data']['action'])) {
                return $candidate['data'];
            }

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

    $action = $payload['action']
        ?? $payload['routeAction']
        ?? $payload['selectedAction']
        ?? null;

    if ($action) {
        $payload['action'] = $action;
        return $payload;
    }

    // Fallback: se veio texto natural, interpreta para action.
    $text = qb_find_text($payload);
    $inferred = qb_infer_payload_from_text($text);

    if ($inferred) {
        $inferred['_original_text'] = $text;
        return $inferred;
    }

    return $payload;
}

$raw = file_get_contents('php://input');

if ($raw === false || trim($raw) === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Body vazio. Envie JSON com action ou texto em content/mensagem.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$decodedRaw = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    // Se veio texto puro, tenta interpretar.
    $payload = qb_normalize_payload($raw);
} else {
    $payload = qb_normalize_payload($decodedRaw);
}

if (!is_array($payload)) {
    $payload = [];
}

if (($payload['action'] ?? '') === 'sales_summary' && empty($payload['period'])) {
    $payload['period'] = 'today';
}

if (empty($payload['action'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Campo action não encontrado e não foi possível interpretar o texto recebido.',
        'received' => $decodedRaw ?? $raw,
        'expected_examples' => [
            ['action' => 'sales_summary', 'period' => 'month'],
            ['action' => 'top_products', 'limit' => 5],
            ['action' => 'customers_by_product', 'product' => 'Picanha'],
            ['content' => 'clientes que compraram picanha'],
            ['content' => 'produtos mais vendidos'],
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
