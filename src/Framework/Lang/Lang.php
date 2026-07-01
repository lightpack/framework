<?php

namespace Lightpack\Lang;

class Lang
{
    protected string $locale;
    protected string $fallback;
    protected string $path;
    protected array $loaded = [];

    public function __construct(?string $locale = null, ?string $path = null, ?string $fallback = null)
    {
        $this->locale = $locale ?? $this->getConfig('lang.default', 'en');
        $this->fallback = $fallback ?? $this->getConfig('lang.fallback', 'en');
        $this->path = $path ?? $this->getConfig('lang.path', DIR_ROOT . '/app/Lang');
    }

    /**
     * Set the current locale.
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Get the current locale.
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Get a translation by key.
     *
     * Supports dot notation: 'messages.hello'
     * Supports placeholders: :name, :count
     *
     * @param string $key Translation key
     * @param array $replace Placeholder replacements
     * @param string|null $locale Optional locale override
     * @return string
     */
    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;

        // Parse file.key notation
        [$file, $item] = $this->parseKey($key);

        // Load translations for this file and locale
        $translations = $this->load($file, $locale);

        $value = $translations[$item] ?? null;

        // Fallback to default locale if not found
        if ($value === null && $locale !== $this->fallback) {
            $fallbackTranslations = $this->load($file, $this->fallback);
            $value = $fallbackTranslations[$item] ?? null;
        }

        // Return key as-is if no translation found
        if ($value === null) {
            return $key;
        }

        return $this->replacePlaceholders($value, $replace);
    }

    /**
     * Get a translation with pluralization support.
     *
     * Uses pipe syntax: '{count} item|{count} items'
     *
     * @param string $key Translation key
     * @param int $count The count to determine plural form
     * @param array $replace Placeholder replacements (count is auto-injected)
     * @return string
     */
    public function choice(string $key, int $count, array $replace = []): string
    {
        $locale = $this->locale;

        [$file, $item] = $this->parseKey($key);
        $translations = $this->load($file, $locale);
        $value = $translations[$item] ?? null;

        if ($value === null && $locale !== $this->fallback) {
            $fallbackTranslations = $this->load($file, $this->fallback);
            $value = $fallbackTranslations[$item] ?? null;
        }

        if ($value === null) {
            return $key;
        }

        $replace['count'] = $count;

        // Handle pipe syntax for pluralization
        if (is_string($value) && str_contains($value, '|')) {
            $parts = explode('|', $value);
            $value = $count === 1 ? trim($parts[0]) : trim($parts[1] ?? $parts[0]);
        }

        return $this->replacePlaceholders($value, $replace);
    }

    /**
     * Check if a translation exists.
     */
    public function has(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->locale;
        [$file, $item] = $this->parseKey($key);
        $translations = $this->load($file, $locale);

        if (isset($translations[$item])) {
            return true;
        }

        if ($locale !== $this->fallback) {
            $fallbackTranslations = $this->load($file, $this->fallback);
            return isset($fallbackTranslations[$item]);
        }

        return false;
    }

    /**
     * Safely get config value with fallback.
     */
    protected function getConfig(string $key, string $default): string
    {
        try {
            if (function_exists('config')) {
                return config($key, $default);
            }
        } catch (\Exception $e) {
            // Container not available (e.g. unit tests)
        }

        return $default;
    }

    /**
     * Parse a dot-notated key into file and item.
     */
    protected function parseKey(string $key): array
    {
        $segments = explode('.', $key, 2);

        if (count($segments) === 1) {
            // If no dot, use 'messages' as default file
            return ['messages', $segments[0]];
        }

        return [$segments[0], $segments[1]];
    }

    /**
     * Load translations from file.
     */
    protected function load(string $file, string $locale): array
    {
        $cacheKey = "{$locale}.{$file}";

        if (isset($this->loaded[$cacheKey])) {
            return $this->loaded[$cacheKey];
        }

        $path = $this->path . '/' . $locale . '/' . $file . '.php';

        if (file_exists($path)) {
            $this->loaded[$cacheKey] = require $path;
        } else {
            $this->loaded[$cacheKey] = [];
        }

        return $this->loaded[$cacheKey];
    }

    /**
     * Replace :placeholder values in translation string.
     */
    protected function replacePlaceholders(string $value, array $replace): string
    {
        foreach ($replace as $key => $replacement) {
            $value = str_replace(':' . $key, (string) $replacement, $value);
        }

        return $value;
    }
}
