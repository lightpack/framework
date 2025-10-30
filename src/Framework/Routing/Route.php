<?php

namespace Lightpack\Routing;

class Route
{
    private string $controller;
    private string $action;
    private array $filters = [];
    private array $params = [];
    private string $path;
    private string $uri;
    private string $name;
    private array $pattern = [];
    private string $host = '';
    private array $bindings = [];

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
    public function filter(string|array $filter): self
    {
        if (is_string($filter)) {
            $filter = [$filter];
        }

        $this->filters = array_merge($this->filters, $filter);
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
    public function setUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @return string The route URI pattern.
     */
    public function getUri(): string
    {
        return $this->uri;
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

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function hasName(): bool
    {
        return isset($this->name);
    }

    public function pattern(array $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function getPattern(): array
    {
        return $this->pattern;
    }

    public function host(string $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Bind a route parameter to a model class.
     * 
     * Phase 1: Basic binding with model class
     * Phase 3: Custom field support
     * 
     * @param string $param Route parameter name (e.g., 'id', 'user_id')
     * @param string $model Fully qualified model class name
     * @param string|null $field Database column to query (default: model's primary key)
     * @return self
     * @throws \Exception If model class doesn't exist or isn't a Model subclass
     */
    public function bind(string $param, string $model, ?string $field = null): self
    {
        // Validate model class exists
        if (!class_exists($model)) {
            throw new \Exception("Model class not found: {$model}");
        }
        
        // Validate it's a Model subclass
        if (!is_subclass_of($model, \Lightpack\Database\Lucid\Model::class)) {
            throw new \Exception("{$model} must extend Lightpack\\Database\\Lucid\\Model");
        }
        
        // Validate parameter exists in route URI
        if (!str_contains($this->uri, ":{$param}") && !str_contains($this->uri, ":{$param}?")) {
            throw new \Exception("Route parameter :{$param} not found in route {$this->uri}");
        }
        
        // Store binding configuration
        $this->bindings[$param] = [
            'model' => $model,
            'field' => $field,
        ];
        
        return $this;
    }

    /**
     * Get all model bindings for this route.
     * 
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Check if route has any model bindings.
     * 
     * @return bool
     */
    public function hasBindings(): bool
    {
        return !empty($this->bindings);
    }
}
