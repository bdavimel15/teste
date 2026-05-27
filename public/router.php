<?php
declare(strict_types=1);

$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri  = '/' . ltrim((string) $uri, '/');
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

$apiRoutes = [
    '/api/querybot.php' => __DIR__ . '/api/querybot.php',
    '/api/health.php'   => __DIR__ . '/api/health.php',
    '/api/admin.php'    => __DIR__ . '/api/admin.php',
    '/api/status.php'   => __DIR__ . '/api/status.php',
    '/admin'            => __DIR__ . '/admin.php',
    '/ping'             => __DIR__ . '/ping.php',
];

if (isset($apiRoutes[$uri])) {
    require $apiRoutes[$uri];
    return true;
}

require __DIR__ . '/index.php';
return true;
