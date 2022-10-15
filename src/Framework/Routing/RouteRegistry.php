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
        'filter' => [],
    ];

    private $names = [];

    private $request;

    public function __construct(\Lightpack\Http\Request $request)
    {
        $this->request = $request;
    }

    public function get(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('GET', $this->options['prefix'] . $uri, $controller, $action);
    }

    public function post(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('POST', $this->options['prefix'] . $uri, $controller, $action);
    }

    public function put(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('PUT', $this->options['prefix'] . $uri, $controller, $action);
    }

    public function patch(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('PATCH', $this->options['prefix'] . $uri, $controller, $action);
    }

    public function delete(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('DELETE', $this->options['prefix'] . $uri, $controller, $action);
    }

    public function options(string $uri, string $controller, string $action = 'index'): Route
    {
        return $this->add('OPTIONS', $this->options['prefix'] . $uri, $controller, $action);
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

    public function map(array $verbs, string $route, string $controller, string $action = 'index'): void
    {
        foreach ($verbs as $verb) {
            if (false === \array_key_exists($verb, $this->routes)) {
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
        $this->routes[$method][$uri] = $route;

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

    private function setRouteName(Route $route): void
    {
        if(false === $route->hasName()) {
            return;
        }

        $name = $route->getName();

        if (isset($this->names[$name])) {
            throw new \Exception('Duplicate route name: ' . $name);
        }

        $this->names[$name] = $route;
    }
}
