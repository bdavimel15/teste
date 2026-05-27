<?php
declare(strict_types=1);

final class Config
{
    private static bool $loaded = false;
    private static array $vars  = [];

    public static function load(): void
    {
        if (self::$loaded) return;
        self::$loaded = true;

        // 1. Carrega variáveis de ambiente do sistema (Railway, Docker, etc.)
        foreach ($_ENV as $k => $v) {
            self::$vars[$k] = $v;
        }
        foreach (getenv() ?: [] as $k => $v) {
            self::$vars[$k] = $v;
        }

        // 2. Lê o .env local como fallback (sobrescreve só se a chave ainda não existir)
        $envFile = ROOT_PATH . '/.env';
        if (!is_file($envFile)) return;

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value, " \t\"'");
            // .env só preenche o que o ambiente não forneceu
            if (!isset(self::$vars[$key]) || self::$vars[$key] === '') {
                self::$vars[$key] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        self::load();
        return self::$vars[$key] ?? $default;
    }

    public static function require(string $key): string
    {
        $v = self::get($key);
        if ($v === null || $v === '') throw new RuntimeException("ENV obrigatório: {$key}");
        return $v;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        return $v === null ? $default : in_array(strtolower($v), ['1','true','yes','on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        return (int)(self::get($key) ?? $default);
    }
}
