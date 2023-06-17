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
        // If absolute URL, return it.
        if (filter_var($params[0], FILTER_VALIDATE_URL)) {
            return $params[0];
        }

        $queryString = '';

        if (is_array($queryParams = end($params))) {
            $queryString = $this->buildQueryString($queryParams);
            array_pop($params);
        }

        // Remove empty values from the array.
        $params = array_filter($params, function ($value) {
            return $value && trim($value) ? true : false;
        });

        // Trim whitespace and slashes from URL params
        array_walk($params, fn (&$el) => $el = trim($el, '/ '));

        $url = '/' . implode('/', $params) . $queryString;

        if (get_env('APP_URL')) {
            $url = rtrim(get_env('APP_URL'), '/') . $url;
        }

        return rtrim($url, '/') ?: '/';
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

        if (get_env('ASSET_URL')) {
            return rtrim(get_env('ASSET_URL'), '/') . $file;
        }

        return '/assets' . $file;
    }

    public function route(string $routeName, array $params = [])
    {
        // if (is_array(end($params))) {
        //     $queryParams = array_pop($params);
        // }

        /** @var \Lightpack\Routing\Route */
        $route = Container::getInstance()->get('route')->getByName($routeName);

        if (!$route) {
            throw new \Exception("Route with name '$routeName' not found.");
        }

        $uri = explode('/', trim($route->getUri(), '/ '));
        $uriPatterns = array_filter($uri, fn ($val) => strpos($val, ':') === 0);

        if (count($uriPatterns) > count($params)) {
            throw new \Exception("Invalid number of parameters for route '$routeName'. Expected " . count($uriPatterns) . " but got " . count($params));
        }

        foreach ($uri as $key => $value) {
            if (strpos($value, ':') === 0) {
                $value = trim($value, ':');
                $uri[$key] = $params[$value];
                unset($params[$value]);
            } 
        }

        $uri[] = $params ?? [];

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
            return $value && trim($value) ? true : false;
        });

        $queryString = http_build_query($params);

        return $queryString ? '?' . $queryString : '';
    }

    /**
     * Generate a signed URL for a given route.
     *
     * @param string $route The route name.
     * @param array $params The route params.
     * @param int $expiration Expiration time in seconds (default: 3600)
     * @return string
     */
    public function sign(string $route, array $params = [], int $expiration = 3600): string
    {
        $url = $this->route($route, $params);
        $expirationTime = time() + $expiration;
        $stringToSign = $url . $expirationTime;

        $crypto = Container::getInstance()->get('crypto');
        $encryptedSignature = $crypto->hash($stringToSign);

        // Append the encrypted signature and expiration timestamp as query parameters
        $url .= '&signature=' . urlencode($encryptedSignature);
        $url .= '&expires=' . $expirationTime;

        return $url;
    }

    public function verify(string $url, array $ignoredParameters = []): bool
    {
        // Extract the signature and expiration time from the URL
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['query'])) {
            return false; // URL has no query parameters
        }

        parse_str($parsedUrl['query'], $queryParams);
        if (!isset($queryParams['signature']) || !isset($queryParams['expires'])) {
            return false; // URL is missing signature or expires parameter
        }

        $signature = urldecode($queryParams['signature']);
        $expires = (int) $queryParams['expires'];

        // Remove the signature and expires parameters from the query string
        unset($queryParams['signature'], $queryParams['expires']);

        // Remove ignored parameters from the query string
        foreach ($ignoredParameters as $ignoredParam) {
            unset($queryParams[$ignoredParam]);
        }

        // Reconstruct the URL without the signature, expires, and ignored parameters
        $urlWithoutSignature = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'];
        if (!empty($queryParams)) {
            $urlWithoutSignature .= '?' . http_build_query($queryParams);
        }

        // Recreate the expected signature using the same signing algorithm and secret key
        $stringToSign = $urlWithoutSignature . $expires;
        $crypto = Container::getInstance()->get('crypto');
        $expectedSignature = $crypto->hash($stringToSign);

        // Compare the expected signature with the extracted signature
        if ($signature !== $expectedSignature) {
            return false; // Signature doesn't match, URL has been tampered with
        }

        // Check the expiration time
        if ($expires < time()) {
            return false; // URL has expired
        }

        return true; // URL is valid and correct
    }
}
