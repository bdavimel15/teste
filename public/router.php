<?php
declare(strict_types=1);

/**
 * Router para Railway/PHP built-in server.
 * Garante que /api/querybot.php carregue /public/api/querybot.php.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if ($path === '/api/querybot.php' || $path === '/api/querybot') {
    require __DIR__ . '/api/querybot.php';
    exit;
}

if ($path === '/api/admin.php' || $path === '/api/admin') {
    require __DIR__ . '/api/admin.php';
    exit;
}

if ($path === '/api/chat.php' || $path === '/api/chat') {
    require __DIR__ . '/api/chat.php';
    exit;
}

if ($path === '/api/health.php' || $path === '/api/health') {
    require __DIR__ . '/api/health.php';
    exit;
}

if ($path === '/api/status.php' || $path === '/api/status') {
    require __DIR__ . '/api/status.php';
    exit;
}

$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
