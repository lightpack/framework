<?php

namespace Lightpack\Routing;

class RouteDefinition
{
    private string $controller;
    private string $action;
    private array $filters = [];
    private array $params = [];
    private string $path;
    private string $route;
    private string $verb;

    public function setController(string $controller): self
    {
        $this->controller = $controller;
        return $this;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;
        return $this;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function setVerb(string $verb): self
    {
        $this->verb = $verb;
        return $this;
    }

    public function getVerb(): string
    {
        return $this->verb;
    }
}
