<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$db = null;
try {
    $db = Database::connection();
    $dbStatus = 'connected';
} catch (Throwable) {
    $dbStatus = 'error';
}

echo json_encode([
    'success'  => true,
    'status'   => 'online',
    'db'       => $dbStatus,
    'php'      => PHP_VERSION,
    'timezone' => date_default_timezone_get(),
    'time'     => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE);
