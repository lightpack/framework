<?php

namespace Lightpack\Config;

class Env
{
    private static array $cache = [];

    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = (string) $line;
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim((string) $value);

                // Remove quotes if present
                $value = trim($value, '"\'');
                
                // Handle special values
                $value = match (strtolower($value)) {
                    'true', '(true)' => true,
                    'false', '(false)' => false,
                    'null', '(null)' => null,
                    default => $value
                };

                // Priority: $_ENV > $_SERVER > .env file
                if (array_key_exists($key, $_ENV)) {
                    self::$cache[$key] = $_ENV[$key];
                    continue;
                }
                
                if (array_key_exists($key, $_SERVER)) {
                    self::$cache[$key] = $_SERVER[$key];
                    continue;
                }

                // Handle variable interpolation
                if (is_string($value) && str_contains($value, '${')) {
                    $value = preg_replace_callback('/\${([^}]+)}/', function($matches) {
                        return (string) (self::get($matches[1]) ?? '');
                    }, $value);
                }

                self::$cache[$key] = $value;
                putenv("{$key}=" . (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value));
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        // Priority: cache > $_ENV > $_SERVER > getenv() > default
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        
        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }
        
        // Check process environment (set via putenv())
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }

    public static function has(string $key): bool
    {
        return isset(self::$cache[$key]);
    }

    public static function set(string $key, mixed $value): void
    {
        self::$cache[$key] = $value;
        putenv("{$key}=" . (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value));
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
