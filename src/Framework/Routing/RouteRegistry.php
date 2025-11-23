<?php

namespace Lightpack\Routing;

use Lightpack\Container\Container;
use Lightpack\Utils\Url;

class RouteRegistry
{
    private $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
        'OPTIONS' => [],
    ];
    private $placeholders = [
        ':any' => '.*',
        ':seg' => '[^/]+',
        ':num' => '[0-9]+',
        ':slug' => '[a-zA-Z0-9-]+',
        ':alpha' => '[a-zA-Z]+',
        ':alnum' => '[a-zA-Z0-9]+',
    ];

    private $options = [
        'prefix' => '',
        'filter' => [],
        'host' => '',
    ];

    private $names = [];

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('GET', $this->buildUri($uri), $controller, $action);
    }

    public function post(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('POST', $this->buildUri($uri), $controller, $action);
    }

    public function put(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('PUT', $this->buildUri($uri), $controller, $action);
    }

    public function patch(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('PATCH', $this->buildUri($uri), $controller, $action);
    }

    public function delete(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('DELETE', $this->buildUri($uri), $controller, $action);
    }

    public function options(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('OPTIONS', $this->buildUri($uri), $controller, $action);
    }

    /**
     * Combines prefix and uri, ensuring exactly one slash between them.
     * Handles cases where either/both have or lack leading/trailing slashes.
     */
    protected function buildUri(string $uri): string
    {
        $prefix = $this->options['prefix'] ?? '';

        // Remove all leading/trailing slashes, backslashes, and whitespace
        $prefix = trim($prefix, " \/");
        $uri = trim($uri, " \/");

        // If both are empty, return '/'
        if ($prefix === '' && $uri === '') {
            return '/';
        }

        // If only prefix is empty
        if ($prefix === '') {
            return '/' . $uri;
        }

        // If only uri is empty
        if ($uri === '') {
            return $prefix === '' ? '/' : $prefix;
        }
        
        // Otherwise join with single slash
        return $prefix . '/' . $uri;
    }

    public function paths(string $method): array
    {
        return $this->routes[$method] ?? [];
    }

    public function group(array $options, callable $callback): void
    {
        $oldOptions = $this->options;
        // Merge prefix
        $options['prefix'] = ($oldOptions['prefix'] ?? '') . ($options['prefix'] ?? '');
        // Merge filters (cumulative array, unique)
        if (isset($options['filter']) && isset($oldOptions['filter'])) {
            $options['filter'] = array_unique(array_merge((array)$oldOptions['filter'], (array)$options['filter']));
        } elseif (isset($oldOptions['filter'])) {
            $options['filter'] = array_unique((array)$oldOptions['filter']);
        }
        // Inherit host if not set
        if (!isset($options['host']) && isset($oldOptions['host'])) {
            $options['host'] = $oldOptions['host'];
        }
        // Merge all options
        $merged = $oldOptions;
        foreach ($options as $key => $value) {
            $merged[$key] = $value;
        }
        $this->options = $merged;
        $callback($this);
        $this->options = $oldOptions;
    }

    public function map(array $verbs, string $route, string $controller, string $action = 'index'): void
    {
        foreach ($verbs as $verb) {
            if (false === \array_key_exists(strtoupper($verb), $this->routes)) {
                throw new \Exception('Unsupported HTTP request method: ' . $verb);
            }

            $this->{$verb}($route, $controller, $action);
        }
    }

    public function any(string $uri, string $controller, string $action = 'index'): void
    {
        $verbs = \array_keys($this->routes);

        foreach ($verbs as $verb) {
            $this->{$verb}($uri, $controller, $action);
        }
    }

    public function matches(string $path): bool|Route
    {
        $originalPath = $path;
        $routes = $this->getRoutesForCurrentRequest();

        foreach ($routes as $routeUri => $route) {
            ['params' => $params, 'regex' => $regex] = $this->compileRegexWithParams($routeUri, $route->getPattern(), (bool)$route->getHost());

            if ($route->getHost()) {
                $path = $this->container->get('request')->host() . '/' . trim($originalPath, '/');
            } else {
                $path = $originalPath;
            }

            if (preg_match('@^' . $regex . '$@', $path, $matches)) {
                \array_shift($matches);
                
                // Trim slashes from matches
                $matches = array_map(function($match) {
                    return trim($match, '/');
                }, $matches);

                $routeParams = [];

                if($params) {
                    foreach($params as $key => $param) {
                        $routeParams[$param] = $matches[$key] ?? null;
                    }
                } else {
                    $routeParams = $matches;
                }
                
                /** @var Route */
                $route = $this->routes[$this->container->get('request')->method()][$routeUri];
                $route->setParams($routeParams);
                
                $route->setPath($path);

                return $route;
            }
        }

        return false;
    }

    public function getByName(string $name): ?Route
    {
        return $this->names[$name] ?? null;
    }

    public function bootRouteNames(): void
    {
        foreach ($this->routes as $routes) {
            foreach ($routes as $route) {
                $this->setRouteName($route);
            }
        }
    }

    private function add(string $method, string $uri, string $controller, string $action): Route
    {
        if (trim($uri) === '') {
            throw new \Exception('Empty route path');
        }

        $route = new Route();
        $route->setController($controller)->setAction($action)->filter($this->options['filter'])->setUri($uri)->setVerb($method);

        if ($this->options['host'] ?? false) {
            $uri = $this->options['host'] . '/' . trim($uri, '/');
            $route->host($this->options['host']);
            $route->setUri($uri);
        }

        $this->routes[$method][$uri] = $route;

        return $route;
    }

    private function regex(string $path): string
    {
        $search = \array_keys($this->placeholders);
        $replace = \array_values($this->placeholders);

        return str_replace($search, $replace, $path);
    }

    private function compileRegexWithParams(string $routePattern, array $pattern, bool $hasHost = false): array
    {
        $params = [];
        $parts = [];
        $fragments = explode('/', $routePattern);
        $isFirstFragment = true;

        foreach ($fragments as $fragment) {
            if (strpos($fragment, ':') === 0) {
                $param = substr($fragment, 1);
                $isOptional = false;

                if (substr($param, -1) === '?') {
                    $param = substr($param, 0, -1);
                    $isOptional = true;
                }

                // For host parameters (containing dots), extract just the parameter name
                // e.g., :subdomain.example.com -> param name is 'subdomain', literal part is '.example.com'
                $literalPart = '';
                if ($isFirstFragment && $hasHost && strpos($param, '.') !== false) {
                    $paramParts = explode('.', $param, 2);
                    $param = $paramParts[0];
                    $literalPart = '\\.' . str_replace('.', '\\.', $paramParts[1]);
                }

                $params[] = $param;
                $registeredPattern = $pattern[$param] ?? ':seg';
                $registeredPattern = $this->placeholders[$registeredPattern] ?? $registeredPattern;

                if ($isOptional) {
                    $parts[] = '(\/' . $registeredPattern . ')?';
                } else {
                    // For host-based routes, first fragment shouldn't have leading slash
                    $separator = ($isFirstFragment && $hasHost) ? '' : '/';
                    $parts[] = $separator . '(' . $registeredPattern . ')' . $literalPart;
                }
            } else {
                // For host-based routes, first fragment shouldn't have leading slash
                // Also escape dots for literal matching in host part
                $separator = ($isFirstFragment && $hasHost) ? '' : '/';
                $fragmentToAdd = ($isFirstFragment && $hasHost) ? str_replace('.', '\\.', $fragment) : $fragment;
                $parts[] = $separator . $fragmentToAdd;
            }
            $isFirstFragment = false;
        }

        $compiledRegex = trim(implode('', $parts), '/');
        // For host-based routes, don't add leading slash to regex
        if (!$hasHost) {
            $compiledRegex = '/' . $compiledRegex;
        }

        return [
            'params' => $params,
            'regex' => $compiledRegex,
        ];
    }

    private function getRoutesForCurrentRequest()
    {
        $requestMethod = $this->container->get('request')->method();
        $requestMethod = trim($requestMethod);
        $routes = $this->routes[$requestMethod] ?? [];

        return $routes;
        // return \array_keys($routes);
    }

    private function setRouteName(Route $route): void
    {
        if (false === $route->hasName()) {
            return;
        }

        $name = $route->getName();

        if (isset($this->names[$name])) {
            throw new \Exception('Duplicate route name: ' . $name);
        }

        $this->names[$name] = $route;
    }

    /**
     * Generate a URL for a named route with parameters.
     * 
     * This method resolves a named route and generates its URL by replacing
     * route parameters with provided values. Extra parameters are appended
     * as query string.
     * 
     * @param string $routeName The name of the route
     * @param array $params Route parameters and query string parameters
     * @return string The generated URL
     * @throws \Exception If route is not found or required parameters are missing
     * 
     * @example
     * // Route: /users/:id/posts/:slug?
     * route()->url('user.posts', ['id' => 1, 'slug' => 'hello'])
     * // Returns: /users/1/posts/hello
     * 
     * route()->url('user.posts', ['id' => 1, 'page' => 2])
     * // Returns: /users/1/posts?page=2
     */
    public function url(string $routeName, array $params = []): string
    {
        $route = $this->getByName($routeName);

        if (!$route) {
            throw new \Exception("Route with name '$routeName' not found.");
        }

        $uri = explode('/', trim($route->getUri(), '/ '));

        // We do not want the subdomain while resolving route urls
        if ($route->getHost() !== '') {
            unset($uri[0]);
        }

        $uriPatterns = array_filter($uri, fn($val) => strpos($val, ':') === 0);
        $lastCharacterForEndParam = substr(end($uriPatterns), -1);
        $minimumRequiredParams = $lastCharacterForEndParam == '?' ? count($uriPatterns) - 1 : count($uriPatterns);

        if ($minimumRequiredParams > count($params)) {
            throw new \Exception("Invalid number of parameters for route '$routeName'. Expected " . count($uriPatterns) . " but got " . count($params));
        }

        foreach ($uri as $key => $value) {
            if (strpos($value, ':') === 0) {
                $isOptionalParam = substr($value, -1) == '?';
                $value = trim($value, ':?');

                if (!$isOptionalParam && !isset($params[$value])) {
                    throw new \Exception("Undefined parameter [:{$value}] for route '{$routeName}'");
                }

                $uri[$key] = $params[$value] ?? null;
                unset($params[$value]);
            }
        }

        $uri[] = $params ?? [];

        return (new Url)->to(...$uri);
    }

    /**
     * Generate a signed URL for a named route.
     * 
     * Signed URLs contain a cryptographic signature that prevents tampering.
     * They also include an expiration timestamp for time-limited access.
     * 
     * @param string $routeName The name of the route
     * @param array $params Route parameters and query string parameters
     * @param int $expiration Expiration time in seconds (default: 3600)
     * @return string The signed URL with signature and expiration
     * @throws \Exception If route is not found or required parameters are missing
     * 
     * @example
     * route()->sign('download', ['file' => 'report.pdf'], 3600)
     * // Returns: /download/report.pdf?signature=abc123&expires=1234567890
     */
    public function sign(string $routeName, array $params = [], int $expiration = 3600): string
    {
        $url = $this->url($routeName, $params);
        $expirationTime = time() + $expiration;
        $stringToSign = $url . $expirationTime;

        $crypto = $this->container->get('crypto');
        $encryptedSignature = $crypto->hash($stringToSign);

        $separator = str_contains($url, '?') ? '&' : '?';

        // Append the encrypted signature and expiration timestamp
        $url .= $separator . 'signature=' . urlencode($encryptedSignature);
        $url .= '&expires=' . $expirationTime;

        return $url;
    }
}
