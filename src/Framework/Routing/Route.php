<?php

namespace Lightpack\Routing;

class Route
{
    private string $controller;
    private string $action;
    private array $filters = [];
    private array $params = [];
    private string $path;
    private string $routeUri;

    /**
     * @var string HTTP method
     */
    private string $verb;

    /**
     * @var string $controller Controller class name.
     */
    public function setController(string $controller): self
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return string Controller class name.
     */
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * @param string $action The controller action to execute.
     * @return Route
     */
    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string The controller action to execute.
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param array $filters Array of route filters.
     * @return Route
     */
    public function setFilters(array $filters): self
    {
        $this->filters = array_merge($this->filters, $filters);
        $this->filters = array_unique($this->filters);
        
        return $this;
    }

    /**
     * @return array Array of route filters.
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array $params Array of matched route parameters
     * @return Route
     */
    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    /**
     * @return array $params Array of matched route parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param string $path The request path to match against.
     * @return Route
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return string The request path to match against.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $route The route URI pattern.
     */
    public function setRouteUri(string $routeUri): self
    {
        $this->routeUri = $routeUri;
        return $this;
    }

    /**
     * @return string The route URI pattern.
     */
    public function getRouteUri(): string
    {
        return $this->routeUri;
    }

    /**
     * @param string $verb HTTP method.
     */
    public function setVerb(string $verb): self
    {
        $this->verb = $verb;
        return $this;
    }

    /**
     * @return string HTTP method.
     */
    public function getVerb(): string
    {
        return $this->verb;
    }
}
