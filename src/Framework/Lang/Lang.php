<?php

namespace Lightpack\Lang;

use Lightpack\Utils\Arr;

class Lang
{
    protected Arr $arr;
    protected Pluralizer $pluralizer;
    protected string $locale;
    protected string $fallback;
    protected string $path;
    protected array $loaded = [];

    public function __construct(?string $locale = null, ?string $path = null, ?string $fallback = null)
    {
        $this->arr = new Arr;
        $this->pluralizer = new Pluralizer;
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
     * Register a custom plural rule for a locale.
     *
     * The callable receives an int $count and must return the form index (int).
     */
    public function setLocaleRule(string $locale, callable $rule): void
    {
        $this->pluralizer->setRule($locale, $rule);
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

        [$file, $item] = $this->parseKey($key);

        $value = $this->resolve($file, $item, $locale);

        if (!is_string($value)) {
            return $key;
        }

        return $this->replacePlaceholders($value, $replace);
    }

    /**
     * Get a translation with pluralization support.
     *
     * Simple format:  ':count item|:count items'
     * Indexed format: '{0} :count items|{1} :count item'
     *
     * @param string $key Translation key
     * @param int $count The count to determine plural form
     * @param array $replace Placeholder replacements (count is auto-injected)
     * @param string|null $locale Optional locale override
     * @return string
     */
    public function choice(string $key, int $count, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->locale;

        [$file, $item] = $this->parseKey($key);

        $value = $this->resolve($file, $item, $locale);

        if (!is_string($value)) {
            return $key;
        }

        $replace['count'] = $count;

        if (str_contains($value, '|')) {
            $parts = array_map('trim', explode('|', $value));
            $value = $this->resolvePluralForm($parts, $count, $locale);
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

        if ($this->arr->has($item, $translations)) {
            return true;
        }

        if ($locale !== $this->fallback) {
            $fallbackTranslations = $this->load($file, $this->fallback);
            return $this->arr->has($item, $fallbackTranslations);
        }

        return false;
    }

    /**
     * Resolve a translation value with automatic fallback to the fallback locale.
     *
     * Returns null if not found in either locale.
     */
    private function resolve(string $file, string $item, string $locale): mixed
    {
        $value = $this->arr->get($item, $this->load($file, $locale));

        if ($value === null && $locale !== $this->fallback) {
            $value = $this->arr->get($item, $this->load($file, $this->fallback));
        }

        return $value;
    }

    /**
     * Resolve which plural form to use from pipe-separated parts.
     *
     * Supports two formats:
     * - Simple: ':count item|:count items' (no {n} prefix)
     * - Indexed: '{0} :count items|{1} :count item' (uses Pluralizer by locale)
     */
    private function resolvePluralForm(array $parts, int $count, string $locale): string
    {
        $indexed = [];
        $hasIndexed = false;
        $simple = [];

        foreach ($parts as $i => $part) {
            if (preg_match('/^\{(\d+)\}\s*/', $part, $matches)) {
                $indexed[(int) $matches[1]] = substr($part, strlen($matches[0]));
                $hasIndexed = true;
            } else {
                $simple[$i] = $part;
            }
        }

        if ($hasIndexed) {
            $form = $this->pluralizer->form($count, $locale);
            return $indexed[$form] ?? end($indexed);
        }

        return $count === 1 ? ($simple[0] ?? '') : ($simple[1] ?? $simple[0] ?? '');
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
