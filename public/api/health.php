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

try {
    $db = Database::connection();
    $dbStatus = 'connected';
    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $dbError = null;
} catch (Throwable $e) {
    $dbStatus = 'error';
    $tables = [];
    $dbError = $e->getMessage();
}

$out = [
    'success'  => true,
    'status'   => 'online',
    'db'       => $dbStatus,
    'tables'   => $tables,
    'php'      => PHP_VERSION,
    'timezone' => date_default_timezone_get(),
    'time'     => date('Y-m-d H:i:s'),
];

if ($dbError && Config::bool('APP_DEBUG')) {
    $out['db_error'] = $dbError;
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
