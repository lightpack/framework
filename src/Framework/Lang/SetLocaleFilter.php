<?php

namespace Lightpack\Lang;

use Lightpack\Filters\FilterInterface;
use Lightpack\Http\Request;
use Lightpack\Http\Response;

/**
 * Filter to automatically set the application locale based on
 * URL path segment, session, or Accept-Language header.
 *
 * Example usage in routes:
 *
 *     $route->group(['filter' => ['locale']], function($route) {
 *         // All routes in this group will have locale auto-detected
 *     });
 *
 * Or with explicit prefix:
 *
 *     $route->group(['prefix' => ':seg', 'filter' => ['locale']], function($route) {
 *         // First segment is treated as locale
 *     });
 */
class SetLocaleFilter implements FilterInterface
{
    public function before(Request $request, array $params = [])
    {
        $lang = lang();
        $supported = config('lang.supported', ['en']);
        $default = config('lang.default', 'en');

        // 1. Check URL path segment (e.g. /hi/about)
        $segment = $request->segments(0);
        if ($segment && in_array($segment, $supported)) {
            $lang->setLocale($segment);
            return;
        }

        // 2. Check session
        $sessionLocale = session()->get('locale');
        if ($sessionLocale && in_array($sessionLocale, $supported)) {
            $lang->setLocale($sessionLocale);
            return;
        }

        // 3. Check Accept-Language header
        $header = $request->header('Accept-Language');
        if ($header) {
            $locale = $this->parseAcceptLanguage($header, $supported);
            if ($locale) {
                $lang->setLocale($locale);
                return;
            }
        }

        // 4. Fall back to default
        $lang->setLocale($default);
    }

    public function after(Request $request, Response $response, array $params = []): Response
    {
        return $response;
    }

    /**
     * Parse Accept-Language header and find best match.
     */
    private function parseAcceptLanguage(string $header, array $supported): ?string
    {
        $locales = [];
        $parts = explode(',', $header);

        foreach ($parts as $part) {
            $part = trim($part);
            if (str_contains($part, ';')) {
                [$locale, $quality] = explode(';', $part, 2);
                $locale = trim($locale);
                $quality = (float) str_replace('q=', '', trim($quality));
            } else {
                $locale = $part;
                $quality = 1.0;
            }

            // Normalize: en-US -> en
            $locale = strtolower(explode('-', $locale)[0]);
            $locales[$locale] = $quality;
        }

        // Sort by quality descending
        arsort($locales);

        foreach (array_keys($locales) as $locale) {
            if (in_array($locale, $supported)) {
                return $locale;
            }
        }

        return null;
    }
}
