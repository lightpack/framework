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

                // Handle variable interpolation
                if (is_string($value) && str_contains($value, '${')) {
                    $value = preg_replace_callback('/\${([^}]+)}/', function($matches) {
                        return (string) (self::get($matches[1]) ?? '');
                    }, $value);
                }

                self::$cache[$key] = $value;
                putenv("{$key}=" . (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value));

                if(!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                }
                
                if(!isset($_SERVER[$key])) {
                    $_SERVER[$key] = $value;
                }
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$cache[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset(self::$cache[$key]);
    }
}
