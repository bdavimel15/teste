<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'status';

if ($action === 'status') {
    try {
        Database::connection();
        $dbOk = true;
        $dbError = null;
    } catch (Throwable $e) {
        $dbOk = false;
        $dbError = $e->getMessage();
    }

    $out = [
        'success' => true,
        'backend' => 'online',
        'db' => $dbOk ? 'connected' : 'error',
        'time' => date('H:i:s'),
    ];

    if (!$dbOk && Config::bool('APP_DEBUG')) {
        $out['db_error'] = $dbError;
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

echo json_encode(['success' => false, 'error' => 'unknown action'], JSON_UNESCAPED_UNICODE);
