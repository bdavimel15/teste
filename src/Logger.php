<?php
declare(strict_types=1);

final class Logger
{
    private static ?string $logPath = null;

    public static function init(): void
    {
        $path = Config::get('LOG_PATH', '../logs/querybot.log');
        if (!str_starts_with($path, '/')) $path = ROOT_PATH . '/' . $path;
        self::$logPath = $path;
        $dir = dirname($path);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }

    public static function write(string $level, string $message, array $ctx = []): void
    {
        if (self::$logPath === null) self::init();
        $tz  = Config::get('APP_TIMEZONE', 'America/Sao_Paulo');
        $now = (new DateTimeImmutable('now', new DateTimeZone($tz)))->format('Y-m-d H:i:s');
        $entry = array_merge(['ts' => $now, 'level' => strtoupper($level), 'msg' => $message], $ctx);
        file_put_contents(self::$logPath, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $m, array $c = []): void  { self::write('INFO',  $m, $c); }
    public static function error(string $m, array $c = []): void { self::write('ERROR', $m, $c); }
    public static function debug(string $m, array $c = []): void { if (Config::bool('APP_DEBUG')) self::write('DEBUG', $m, $c); }
}
