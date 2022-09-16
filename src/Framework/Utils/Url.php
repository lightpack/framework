<?php

namespace Lightpack\Utils;

use Lightpack\Http\Request;

class Url
{
    /**
     * @var Request
     */
    protected $request;

    public function __construct(Request $request)
    {   
        $this->request = $request;
    }

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
        if (is_array($params = end($fragments))) {
            $queryString = $this->buildQueryString($params);
            array_pop($fragments);
        }

        $url = [
            get_env('APP_URL'), 
            $this->request->basepath(), 
            ...$fragments,
        ];

        // Trim slashes from URL fragments
        array_walk($url, fn(&$el) => $el = trim($el, '/'));

        return implode('/', $url) . ($queryString ?? '');
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

        $queryString = http_build_query($params);

        return $queryString ? '?' . $queryString : '';
    }
}
