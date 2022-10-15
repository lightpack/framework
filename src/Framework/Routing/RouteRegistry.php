<?php

namespace Lightpack\Routing;

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
        ':any' => '(.*)',
        ':seg' => '([^/]+)',
        ':num' => '([0-9]+)',
        ':slug' => '([a-zA-Z0-9-]+)',
        ':alpha' => '([a-zA-Z]+)',
        ':alnum' => '([a-zA-Z0-9]+)',
    ];
    private $options = [
        'prefix' => '',
        'filters' => [],
    ];
    private $request;

    public function __construct(\Lightpack\Http\Request $request)
    {
        $this->request = $request;
    }

    public function get(string $path, string $controller, string $action = 'index'): Route
    {
        return $this->add('GET', $this->options['prefix'] . $path, $controller, $action);
    }

    public function post(string $path, string $controller, string $action = 'index'): Route
    {
        return $this->add('POST', $this->options['prefix'] . $path, $controller, $action);
    }

    public function put(string $path, string $controller, string $action = 'index'): Route
    {
        return $this->add('PUT', $this->options['prefix'] . $path, $controller, $action);
    }

    public function patch(string $path, string $controller, string $action = 'index'): Route
    {
        return $this->add('PATCH', $this->options['prefix'] . $path, $controller, $action);
    }

    public function delete(string $path, string $controller, string $action = 'index'): Route
    {
        return $this->add('DELETE', $this->options['prefix'] . $path, $controller, $action);
    }

    public function options(string $path, string $controller, string $action = 'index'): Route
    {
        return $this->add('OPTIONS', $this->options['prefix'] . $path, $controller, $action);
    }

    public function paths(string $method): array
    {
        return $this->routes[$method] ?? [];
    }

    public function group(array $options, callable $callback): void
    {
        $oldOptions = $this->options;
        $this->options = \array_merge($oldOptions, $options);
        $this->options['prefix'] = $oldOptions['prefix'] . $this->options['prefix'];
        $callback($this);
        $this->options = $oldOptions;
    }

    public function map(array $verbs, string $route, string $controller, string $action = 'index', array $filters = []): void
    {
        foreach ($verbs as $verb) {
            if (false === \array_key_exists($verb, $this->routes)) {
                throw new \Exception('Unsupported HTTP request method: ' . $verb);
            }

            $this->{$verb}($route, $controller, $action, $filters);
        }
    }

    public function any(string $path, string $controller, string $action = 'index', array $filters = []): void
    {
        $verbs = \array_keys($this->routes);

        foreach ($verbs as $verb) {
            $this->{$verb}($path, $controller, $action, $filters);
        }
    }

    public function matches(string $path): false|Route
    {
        $routes = $this->getRoutesForCurrentRequest();

        foreach ($routes as $routeUri) {
            if (preg_match('@^' . $this->regex($routeUri) . '$@', $path, $matches)) {
                \array_shift($matches);

                /** @var Route */
                $route = $this->routes[$this->request->method()][$routeUri];
                $route->setParams($matches);
                $route->setPath($path);
                $route->setRouteUri($routeUri);
                $route->setVerb($this->request->method());

                return $route;
            }
        }

        return false;
    }

    private function add(string $method, string $path, string $controller, string $action): Route
    {

        if (trim($path) === '') {
            throw new \Exception('Empty route path');
        }

        $route = new Route();
        $route->setController($controller)->setAction($action)->setFilters($this->options['filters']);
        $this->routes[$method][$path] = $route;

        return $route;
    }

    private function regex(string $path): string
    {
        $search = \array_keys($this->placeholders);
        $replace = \array_values($this->placeholders);

        return str_replace($search, $replace, $path);
    }

    private function getRoutesForCurrentRequest()
    {
        $requestMethod = $this->request->method();
        $requestMethod = trim($requestMethod);
        $routes = $this->routes[$requestMethod] ?? [];
        return \array_keys($routes);
    }
}
