<?php

namespace Lightpack\Utils;

class Str
{
    private static ?Inflector $inflector = null;

    private function getInflector(): Inflector 
    {
        if (self::$inflector === null) {
            self::$inflector = new Inflector();
        }
        return self::$inflector;
    }

    /**
     * This method returns the singular form of the passed string.
     */
    public function singularize(string $subject): string
    {
        return $this->getInflector()->singularize($subject);
    }

    /**
     * This method returns the plural form of the passed string.
     */
    public function pluralize(string $subject): string
    {
        return $this->getInflector()->pluralize($subject);
    }

    /**
     * This method returns the plural form of the passed string only if
     * the passed $number > 1. 
     * 
     * For example: pluralizeIf(2, 'role') returns 'roles',
     */
    public function pluralizeIf(int $number, string $subject): string
    {
        return $this->getInflector()->pluralizeIf($number, $subject);
    }

    /**
     * This method will return the camel-cased version of the passed string.
     * 
     * For example: camelize('parent class') returns 'ParentClass'.
     */
    public function camelize(string $subject): string
    {
        return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $subject)));
    }

    /**
     * This method will return the equivalent variable name version of 
     * the passed string. This is useful for converting a camel-cased 
     * string to a variable name.
     * 
     * For example: variable('ParentClass') returns 'parentClass'.
     */
    public function variable(string $subject): string
    {
        return lcfirst(static::camelize($subject));
    }

    /**
     * This method will return the lowercased underscored version of the 
     * passed string.
     * 
     * For example: underscore('ParentClass') returns 'parent_class'.
     */
    public function underscore(string $subject): string
    {
        $subject = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $subject));

        return str_replace([' ', '-'], '_', $subject);
    }

    /**
     * This method will return the lowercased dashed (hyphenated) version of 
     * the passed string.
     * 
     * For example: dasherize('Parent Class') returns 'parent-class'.
     */
    public function dasherize(string $subject): string
    {
        $subject = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $subject));

        return str_replace([' ', '_'], '-', $subject);
    }

    /**
     * This method will return the human readable version of the passed 
     * string with the first word capitalized.
     * 
     * Examples: 
     * - humanize('lazy_brown_fox') returns 'Lazy brown fox'
     * - humanize('lazyBrownFox') returns 'Lazy brown fox'
     */
    public function humanize(string $subject): string
    {
        // Convert camelCase to space-separated words
        $subject = preg_replace('/(?<!^)[A-Z]/', ' $0', $subject);
        
        // Replace underscores and hyphens with spaces
        $subject = str_replace(['_', '-'], ' ', $subject);
        
        // Collapse multiple spaces and convert to lowercase
        $subject = strtolower(trim(preg_replace('/\s+/', ' ', $subject)));
        
        // Capitalize first word only
        return ucfirst($subject);
    }

    /**
     * This method will capitalize the the first character of each word. 
     * This is specially useful for headlines and titles.
     * 
     * Examples:
     * - headline('lazy_brown_fox') returns 'Lazy Brown Fox'
     * - headline('lazyBrownFox') returns 'Lazy Brown Fox'
     */
    public function headline(string $subject): string
    {
        // Convert camelCase to space-separated words
        $subject = preg_replace('/(?<!^)[A-Z]/', ' $0', $subject);
        
        // Replace underscores and hyphens with spaces
        $subject = str_replace(['_', '-'], ' ', $subject);
        
        // Collapse multiple spaces and capitalize each word
        return ucwords(trim(preg_replace('/\s+/', ' ', $subject)));
    }

    /**
     * This method will return the plural form of the passed string making 
     * it suitable for use as a table name in databases.
     * 
     * For example: tableize('User') returns 'users'.
     */
    public function tableize(string $subject): string
    {
        return $this->getInflector()->pluralize($this->underscore($subject));
    }

    /**
     * This method will return the singular form of the passed string making 
     * it suitable for use as a class name in PHP.
     * 
     * For example: classify('users') returns 'User'.
     */
    public function classify(string $subject): string
    {
        return $this->camelize($this->getInflector()->singularize($subject));
    }

    /**
     * This method will return the plural form of the passed string making 
     * it suitable for use as a foreign key in databases.
     * 
     * For example: foreignKey('User') returns 'user_id'.
     */
    public function foreignKey(string $subject): string
    {
        return $this->underscore($this->getInflector()->singularize($subject)) . '_id';
    }

    /**
     * This method will return the ordinal form of the passed string.
     * 
     * For example: ordinalize(1) returns '1st'.
     */
    public function ordinalize(int $number): string
    {
        if (in_array(($number % 100), range(11, 13))) {
            return $number . 'th';
        }

        switch (($number % 10)) {
            case 1:
                return $number . 'st';
            case 2:
                return $number . 'nd';
            case 3:
                return $number . 'rd';
            default:
                return $number . 'th';
        }
    }

    /**
     * Convert a string to a URL-friendly ASCII slug.
     *
     * Example: slug('Hello World') returns 'hello-world'
     *
     * - Only '-' or '_' are allowed as separators; any other value falls back to '-'.
     * - Attempts best-effort transliteration for all languages.
     * - Tries to return a safe ASCII slug.
     *
     * @param string $subject   The input string.
     * @param string $separator The word separator, '-' or '_'.
     * @return string           The generated slug.
     */
    public function slug(string $subject, string $separator = '-'): string
    {
        if (empty($subject)) {
            return '';
        }

        // Only allow '-' or '_' as separator; fallback to '-'
        if ($separator !== '-' && $separator !== '_') {
            $separator = '-';
        }

        // Ensure UTF-8 encoding
        $encoding = mb_detect_encoding($subject, 'UTF-8, ISO-8859-1', true);
        $subject = mb_convert_encoding($subject, 'UTF-8', $encoding);

        // Try transliteration (ICU), then iconv, then strip non-ASCII
        if (function_exists('transliterator_transliterate')) {
            $subject = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $subject);
        } elseif (function_exists('iconv')) {
            $iconv = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $subject);
            if ($iconv !== false) {
                $subject = $iconv;
            }
        } else {
            // Remove all non-ASCII (best effort)
            $subject = preg_replace('/[^\x00-\x7F]/u', '', $subject);
        }

        // Replace all types of spaces, punctuation, etc. with a single space
        $subject = preg_replace('/[\pZ\pC\pM\pP\pS]+/u', ' ', $subject);
        // Remove anything that's not alphanumeric or space
        $subject = preg_replace('/[^a-zA-Z0-9\s]/', '', $subject);
        // Convert to lowercase and trim
        $subject = mb_strtolower(trim($subject));
        // Replace spaces with separator
        $slug = preg_replace('/\s+/', $separator, $subject);
        // Remove leading/trailing separators (if any)
        $slug = trim($slug, $separator);
        return $slug;
    }

    /**
     * This method will return the slug form of the passed string useful
     * for human friendly URLs.
     * 
     * @deprecated Use slug() instead
     */
    public function slugify(string $subject, string $separator = '-'): string
    {
        return $this->slug($subject, $separator);
    }

    /**
     * This method will check if the passed subject string starts with 
     * the passed prefix.
     * 
     * For example: startsWith('/admin/products/23', '/admin') returns true.
     */
    public function startsWith(string $subject, string $prefix): bool
    {
        return substr($subject, 0, strlen($prefix)) === $prefix;
    }

    /**
     * This method will check if the passed subject string ends with 
     * the passed suffix.
     * 
     * For example: endsWith('/admin/products/23', '23') returns true.
     */
    public function endsWith(string $subject, string $suffix): bool
    {
        return substr($subject, -strlen($suffix)) === $suffix;
    }

    /**
     * This method will check if the passed subject constains the passed
     * substring anywhere in it.
     * 
     * For example: contains('/admin/products/23', 'products') returns true.
     */
    public function contains(string $subject, string $needle): bool
    {
        return strpos($subject, $needle) !== false;
    }

    /**
     * This method will generate a random string of the passed length.
     */
    public function random(int $length = 16): string
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('Length must be at least 1');
        }

        $bytes = random_bytes((int) ceil($length / 2));
        return substr(bin2hex($bytes), 0, $length);
    }

    /**
     * This method will mask the passed subject string with the passed 
     * mask. This is specially useful for obfuscatng sensitive data such
     * as credit card numbers, emails, phones, etc.
     * 
     * For example: mask('MYSECRET', '*', 2) returns 'MY****'.
     */
    public function mask(string $subject, string $mask = '*', int $start = 0): string
    {
        $length = strlen($subject);
        $masked = substr($subject, 0, $start);

        for ($i = $start; $i < $length; $i++) {
            $masked .= $mask;
        }

        return $masked;
    }

    /**
     * Truncate a string to a specified length with optional append string.
     */
    public function truncate(string $subject, int $length, string $append = '...'): string 
    {
        if (mb_strlen($subject, 'UTF-8') <= $length) {
            return $subject;
        }
        
        return rtrim(mb_substr($subject, 0, $length, 'UTF-8')) . $append;
    }

    /**
     * Limit a string by word count with optional append string.
     */
    public function limit(string $subject, int $words = 100, string $append = '...'): string 
    {
        $wordArray = str_word_count($subject, 1, '0123456789');
        if (count($wordArray) <= $words) {
            return $subject;
        }
        
        return implode(' ', array_slice($wordArray, 0, $words)) . $append;
    }

    /**
     * Pad a string to a certain length.
     */
    public function pad(string $subject, int $length, string $pad = ' ', int $type = STR_PAD_RIGHT): string 
    {
        return str_pad($subject, $length, $pad, $type);
    }

    /**
     * Convert string to title case with proper UTF-8 support.
     */
    public function title(string $subject): string 
    {
        return mb_convert_case($subject, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Convert string to uppercase with proper UTF-8 support.
     */
    public function upper(string $subject): string 
    {
        return mb_strtoupper($subject, 'UTF-8');
    }

    /**
     * Convert string to lowercase with proper UTF-8 support.
     */
    public function lower(string $subject): string 
    {
        return mb_strtolower($subject, 'UTF-8');
    }

    /**
     * Escape HTML entities in a string.
     */
    public function escape(string $subject): string 
    {
        return htmlspecialchars($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Check if string is a valid email address.
     */
    public function isEmail(string $subject): bool 
    {
        return filter_var($subject, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if string is a valid URL.
     */
    public function isUrl(string $subject): bool 
    {
        return filter_var($subject, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if string is a valid IP address (IPv4 or IPv6).
     */
    public function isIp(string $subject): bool 
    {
        return filter_var($subject, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Check if string is a valid hex color code.
     */
    public function isHex(string $subject): bool 
    {
        return (bool) preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $subject);
    }

    /**
     * Check if string is a valid UUID (v4).
     */
    public function isUuid(string $subject): bool 
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', strtolower($subject));
    }

    /**
     * Check if string is a valid domain name.
     */
    public function isDomain(string $subject): bool 
    {
        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/', strtolower($subject));
    }

    /**
     * Check if string is valid base64 encoded.
     */
    public function isBase64(string $subject): bool 
    {
        if (empty($subject)) {
            return false;
        }
        
        // Check if string contains only valid base64 characters
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $subject)) {
            return false;
        }
        
        // Attempt to decode and verify
        $decoded = base64_decode($subject, true);
        return $decoded !== false && base64_encode($decoded) === $subject;
    }

    /**
     * Check if string represents a valid MIME type format.
     */
    public function isMimeType(string $subject): bool 
    {
        return (bool) preg_match('/^[a-z]+\/[a-z0-9\-\+\.]+$/i', $subject);
    }

    /**
     * Check if string represents a valid file path format.
     */
    public function isPath(string $subject): bool 
    {
        // Check for basic path format
        if (!preg_match('/^[a-zA-Z0-9\/_\-\.]+$/', $subject)) {
            return false;
        }
        
        // Check for directory traversal attempts
        if (strpos($subject, '../') !== false || strpos($subject, '..\\') !== false) {
            return false;
        }
        
        return true;
    }

    /**
     * Check if string is valid JSON.
     */
    public function isJson(string $subject): bool 
    {
        if (empty($subject)) {
            return false;
        }
        
        try {
            json_decode($subject, true, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }

    /**
     * Get the filename from a file path.
     * 
     * Example: filename('/path/to/file.txt') returns 'file.txt'
     */
    public function filename(string $path): string 
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Get the filename without extension from a file path.
     * 
     * Example: stem('/path/to/file.txt') returns 'file'
     */
    public function stem(string $path): string 
    {
        $filename = $this->filename($path);
        $pos = strpos($filename, '.');
        if ($pos === false) {
            return $filename;
        }
        return substr($filename, 0, $pos);
    }

    /**
     * Get the file extension from a file path.
     * 
     * Example: ext('/path/to/file.txt') returns 'txt'
     */
    public function ext(string $path): string 
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the directory name from a file path.
     * 
     * Example: dir('/path/to/file.txt') returns '/path/to'
     */
    public function dir(string $path): string 
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Remove all HTML tags.
     * 
     * For example: strip('<p>Hello World</p>') returns 'Hello World'
     */
    public function strip(string $subject): string 
    {
        // First remove script and style tags with their content
        $subject = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $subject);
        $subject = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $subject);
        
        // Then remove remaining HTML tags
        return strip_tags($subject);
    }

    /**
     * Keep only alphanumeric characters.
     * 
     * Example: alphanumeric('Hello, World! 123') returns 'HelloWorld123'
     */
    public function alphanumeric(string $subject): string 
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $subject);
    }

    /**
     * Keep only alphabetic characters.
     * 
     * Example: alpha('Hello123') returns 'Hello'
     */
    public function alpha(string $subject): string 
    {
        return preg_replace('/[^a-zA-Z]/', '', $subject);
    }

    /**
     * Keep only numeric characters.
     * 
     * Example: number('Price: $123.45') returns '12345'
     */
    public function number(string $subject): string 
    {
        return preg_replace('/[^0-9]/', '', $subject);
    }

    /**
     * Replace multiple whitespace characters with a single space.
     * 
     * Example: collapse('Hello    World') returns 'Hello World'
     */
    public function collapse(string $subject): string 
    {
        return preg_replace('/\s+/', ' ', trim($subject));
    }

    /**
     * Generate initials from a name.
     * 
     * Example: initials('John Doe') returns 'JD'
     */
    public function initials(string $name): string 
    {
        $words = explode(' ', $this->collapse($name));
        $initials = '';
        
        foreach ($words as $word) {
            if ($word !== '') {
                $initials .= mb_substr($word, 0, 1);
            }
        }
        
        return mb_strtoupper($initials);
    }

    /**
     * Create an excerpt of text.
     * 
     * Example: excerpt('This is a very long text that needs to be shortened', 20) returns 'This is a very...'
     */
    public function excerpt(string $text, int $length = 100, string $end = '...'): string 
    {
        // If text is shorter than length, return as is
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        // Get the substring
        $excerpt = mb_substr($text, 0, $length);
        
        // Find the last space
        $lastSpace = mb_strrpos($excerpt, ' ');
        
        // If there's a space, cut at the space to avoid cutting words
        if ($lastSpace !== false) {
            $excerpt = mb_substr($excerpt, 0, $lastSpace);
        }
        
        return $excerpt . $end;
    }

    /**
     * Generate a numeric one-time password (OTP) of specified length.
     * 
     * Example: otp(6) returns '123456'
     * 
     * @param int $length The length of OTP to generate (default: 6)
     * @return string The generated OTP
     * @throws \InvalidArgumentException If length is less than 1
     */
    public function otp(int $length = 6): string 
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('OTP length must be at least 1');
        }

        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;

        return (string) random_int($min, $max);
    }
}
