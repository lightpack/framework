<?php

namespace Lightpack\Utils;

use Lightpack\Container\Container;

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
    public function to(...$params): string
    {
        $queryString = '';

        if (is_array($queryParams = end($params))) {
            $queryString = $this->buildQueryString($queryParams);
            array_pop($params);
        }

        // Remove empty values from the array.
        $params = array_filter($params, function ($value) {
            return trim($value) ? true : false;
        });

        // Trim whitespace and slashes from URL params
        array_walk($params, fn (&$el) => $el = trim($el, '/ '));

        return '/' . implode('/', $params) . $queryString;
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
        // trim whitespace and slashes from the file path
        $file = trim($file, '/ ');
        $file = $file ? '/' . $file : '';

        return get_env('ASSET_URL', '/assets') . $file;
    }

    public function route(string $routeName, ...$params)
    {
        if(is_array(end($params))) {
            $queryParams = array_pop($params);
        }

        /** @var \Lightpack\Routing\Route */
        $route = Container::getInstance()->get('route')->getByName($routeName);

        if (!$route) {
            throw new \Exception("Route with name '$routeName' not found.");
        }

        $uri = explode('/', trim($route->getUri(), '/ '));
        $uriPatterns = array_filter($uri, fn($val) => strpos($val, ':') === 0);

        if (count($uriPatterns) !== count($params)) {
            throw new \Exception("Invalid number of parameters for route '$routeName'. Expected " . count($uriPatterns) . " but got " . count($params));
        }

        foreach($uri as $key => $value) {
            if(strpos($value, ':') === 0) {
                $uri[$key] = array_shift($params);
            }
        }

        $uri[] = $queryParams ?? [];

        return $this->to(...$uri);
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
        $params = array_filter($params, function ($value) {
            return trim($value) ? true : false;
        });

        $queryString = http_build_query($params);

        return $queryString ? '?' . $queryString : '';
    }
}
