<?php

namespace Lightpack\Utils;

class Url
{
    /**
     * Generate URL with support for query params.
     * 
     * It takes any number of string texts and concatenates them to
     * generate the URL. To append query params, pass an array as
     * key-value pairs, and it will be converted to a query string.
     *
     * For example:
     * Url::to('users', ['sort' => 'asc', 'status' => 'active']);
     * That  will produce: /users?sort=asc&status=active 
     */
    public function to(...$fragments): string
    {
        $queryString = '';

        if (is_array($params = end($fragments))) {
            $queryString = $this->buildQueryString($params);
            array_pop($fragments);
        }

        // Remove empty values from the array.
        $fragments = array_filter($fragments, function($value) {
            return trim($value) ? true : false;
        });

        // Trim slashes from URL fragments
        array_walk($fragments, fn(&$el) => $el = trim($el, '/'));

        return '/' . implode('/', $fragments) . $queryString;
    }

    /**
     * ------------------------------------------------------------
     * Generates URL for assets in /public/assets folder.
     * ------------------------------------------------------------
     * 
     * Usage: 
     * 
     * Url::asset('css/styles.css');
     * Url::asset('img/favicon.png');
     * Url::asset('js/scripts.js');
     */
    public function asset(string $file): ?string
    {
        $url = trim($this->request->basepath(), '/') . '/' . trim($file, '/');

        return get_env('ASSET_URL', 'assets') . $url;
    }

    /**
     * Builds a query string from an array of key-value pairs.
     */
    protected static function buildQueryString(array $params): string
    {
        if (empty($params)) {
            return '';
        }

        // Remove empty values from the array.
        $params = array_filter($params, function($value) {
            return trim($value) ? true : false;
        });

        $queryString = http_build_query($params);

        return $queryString ? '?' . $queryString : '';
    }
}
