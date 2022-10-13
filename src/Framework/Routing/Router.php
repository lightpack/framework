<?php

namespace Lightpack\Routing;

use Lightpack\Http\Request;
use Lightpack\Routing\Route;

/**
 * Responsible to determine the correct controller/action to execute.
 */
class Router
{
    private $route;

    /**
     * @var RouteDefinition
     */
    private $routeDefinition;

    public function __construct(Request $request, Route $route)
    {
        $this->route = $route;
        $this->parse($request->path());
    }

    public function hasRouteDefinition(): bool
    {
        return $this->routeDefinition !== null;
    }

    public function getRouteDefinition(): RouteDefinition
    {
        return $this->routeDefinition;
    }

    private function parse(string $path): void
    {
        if ($routeDefinition = $this->route->matches($path)) {
            $this->routeDefinition = $routeDefinition;
        }
    }
}
