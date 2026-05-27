<?php

declare(strict_types=1);

/**
 * POST /api/querybot.php
 *
 * Endpoint chamado pela Zaia via HTTP Request Node.
 * Recebe JSON, valida Bearer token, executa a ação e retorna JSON.
 *
 * Fluxo correto:
 *   Frontend → Zaia → aqui → Zaia → Frontend
 *
 * Headers esperados (enviados pela Zaia):
 *   Content-Type:  application/json
 *   Authorization: Bearer <QUERYBOT_API_TOKEN>
 *
 * Body esperado (mínimo):
 *   { "action": "sales_summary", "period": "today" }
 *
 * Ações disponíveis:
 *   health, sales_summary, top_products, customers_count,
 *   low_stock, recent_orders
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Apenas POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido. Use POST.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Autenticação por Bearer Token ────────────────────────────────
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

// ── Lê e valida o corpo JSON ─────────────────────────────────────
$raw = file_get_contents('php://input');

if ($raw === false || trim($raw) === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Body vazio. Envie JSON com o campo "action".'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode($raw, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'JSON inválido: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── Processa a ação ──────────────────────────────────────────────
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
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
