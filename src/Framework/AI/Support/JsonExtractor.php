<?php
namespace Lightpack\AI\Support;

class JsonExtractor
{
    public static function extract(string $text): ?string
    {
        if (preg_match('/(\[.*\])/s', $text, $matches)) {
            return $matches[0];
        }
        
        if (preg_match_all('/\{.*?\}/s', $text, $matches) && count($matches[0]) > 1) {
            return '[' . implode(',', $matches[0]) . ']';
        }
        
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            return $matches[0];
        }
        
        return null;
    }
    
    public static function decode(string $text): ?array
    {
        $json = self::extract($text) ?? $text;
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}
