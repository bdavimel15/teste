<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Endpoint de polling: o frontend pergunta se a Zaia já respondeu
// Parâmetros: ?action=status&poll_zaia=1&job_id=xxx
$action = $_GET['action'] ?? 'status';

if ($action === 'status') {
    // Retorna status básico do backend + DB
    $dbOk = false;
    try { Database::connection(); $dbOk = true; } catch (Throwable) {}
    echo json_encode([
        'success' => true,
        'backend' => 'online',
        'db'      => $dbOk ? 'connected' : 'error',
        'time'    => date('H:i:s'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'error' => 'unknown action'], JSON_UNESCAPED_UNICODE);
