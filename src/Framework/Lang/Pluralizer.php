<?php

namespace Lightpack\Lang;

/**
 * Minimal plural form resolver without ICU dependencies.
 *
 * Supports languages with complex plural rules (Arabic, Russian, Polish, etc.)
 * by mapping (count, locale) to a form index.
 *
 * Translation strings can prefix each plural form with {index}:
 *
 *     '{0} no items|{1} one item|{2} :count items'
 *
 * If no {index} prefixes are found, falls back to simple singular/plural:
 *
 *     ':count item|:count items'
 */
class Pluralizer
{
    /**
     * Plural form callbacks indexed by locale.
     *
     * Each callback receives the count and returns the form index to use.
     */
    private array $rules = [
        // English and similar (singular/plural only)
        'en' => [self::class, 'english'],
        'es' => [self::class, 'english'],
        'fr' => [self::class, 'english'],
        'de' => [self::class, 'english'],
        'it' => [self::class, 'english'],
        'pt' => [self::class, 'english'],
        'hi' => [self::class, 'english'],
        'ja' => [self::class, 'zero'],   // No grammatical plural
        'ko' => [self::class, 'zero'],
        'zh' => [self::class, 'zero'],

        // Slavic languages (1, few, many)
        'ru' => [self::class, 'russian'],
        'uk' => [self::class, 'russian'],
        'pl' => [self::class, 'polish'],
        'cs' => [self::class, 'russian'],
        'sk' => [self::class, 'russian'],

        // Arabic (6 forms)
        'ar' => [self::class, 'arabic'],
    ];

    /**
     * Get the plural form index for a given count and locale.
     */
    public function form(int $count, string $locale): int
    {
        $locale = strtolower($locale);

        $rule = $this->rules[$locale] ?? [self::class, 'english'];

        return $rule($count);
    }

    /**
     * Register or override a plural rule for a locale.
     */
    public function setRule(string $locale, callable $rule): void
    {
        $this->rules[strtolower($locale)] = $rule;
    }

    /**
     * English-style: singular vs plural.
     * Form 0 = plural (0, 2+), Form 1 = singular (1).
     */
    private static function english(int $count): int
    {
        return $count === 1 ? 1 : 0;
    }

    /**
     * Languages with no grammatical plural (always form 0).
     */
    private static function zero(int $count): int
    {
        return 0;
    }

    /**
     * Russian/Ukrainian/Czech/Slovak:
     * 1 → form 1 (one)
     * 2-4 → form 2 (few)
     * 0, 5-20, 25-30, etc. → form 0 (many)
     * 21, 31, 41 → form 1
     * 22-24, 32-34 → form 2
     */
    private static function russian(int $count): int
    {
        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return 1; // one
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return 2; // few
        }

        return 0; // many
    }

    /**
     * Polish:
     * 1 → form 1 (one)
     * 2-4, 22-24, 32-34 → form 2 (few)
     * 0, 5-21, 25-31, etc. → form 0 (many)
     */
    private static function polish(int $count): int
    {
        if ($count === 1) {
            return 1; // one
        }

        $mod10 = $count % 10;
        $mod100 = $count % 100;

        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
            return 2; // few
        }

        return 0; // many
    }

    /**
     * Arabic (6 forms):
     * 0 → form 0 (zero)
     * 1 → form 1 (one)
     * 2 → form 2 (two)
     * 3-10 → form 3 (few)
     * 11-99 → form 4 (many)
     * 100+ → form 5 (other)
     */
    private static function arabic(int $count): int
    {
        if ($count === 0) {
            return 0; // zero
        }

        if ($count === 1) {
            return 1; // one
        }

        if ($count === 2) {
            return 2; // two
        }

        $mod100 = $count % 100;

        if ($mod100 >= 3 && $mod100 <= 10) {
            return 3; // few
        }

        if ($mod100 >= 11 && $mod100 <= 99) {
            return 4; // many
        }

        return 5; // other (100, 101, 200, etc.)
    }
}
