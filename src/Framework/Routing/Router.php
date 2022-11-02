<?php

namespace Lightpack\Routing;

use Lightpack\Http\Request;

/**
 * Responsible to determine the correct controller/action to execute.
 */
class Router
{
    /**
     * @var RouteRegistry
     */
    private $routeRegistry;

    /**
     * @var Route
     */
    private $route;

    public function __construct(Request $request, RouteRegistry $routeRegistry)
    {
        $this->routeRegistry = $routeRegistry;
        $this->parse($request->path());
    }

    public function hasRoute(): bool
    {
        return $this->route !== null;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function parse(string $path): void
    {
        if ($route = $this->routeRegistry->matches($path)) {
            $this->route = $route;
        }
    }
}
