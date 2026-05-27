<?php

declare(strict_types=1);

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

require_once ROOT_PATH . '/src/Config.php';
require_once ROOT_PATH . '/src/Logger.php';
require_once ROOT_PATH . '/src/Database.php';
require_once ROOT_PATH . '/src/QueryHandler.php';

Config::load();

// Configura timezone da aplicação
$tz = Config::get('APP_TIMEZONE', 'America/Sao_Paulo');
date_default_timezone_set($tz);

// Exibe erros em modo debug, oculta em produção
if (Config::bool('APP_DEBUG')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}
