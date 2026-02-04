<?php

namespace Lightpack\Filters;

use Lightpack\Http\Request;
use Lightpack\Http\Response;
use Lightpack\Container\Container;

class Filter
{
    private $filters = [];

    public function __construct(
        private Container $container,
        private Request $request,
        private Response $response
    ) {
        // ...
    }

    public function setResponse(Response $response): void
    {
        $this->response = &$response;
    }

    public function register(string $route, string $filter, array $params = []): void
    {
        $this->ensureFilterIsValid($filter);
        $this->filters[$route] = $this->filters[$route] ?? [];
        $this->filters[$route][] = [$this->container->resolve($filter), $params];
    }

    public function processBeforeFilters(string $route)
    {
        foreach (($this->filters[$route] ?? []) as $filterArray) {
            [$filter, $params] = $filterArray;

            $result = $filter->before($this->request, $params);

            if ($result instanceof Response) {
                return $result;
            }
        }
    }

    public function processAfterFilters(string $route)
    {
        foreach (($this->filters[$route] ?? []) as $filterArray) {
            [$filter, $params] = $filterArray;
            $result = $filter->after($this->request, $this->response, $params);

            if ($result instanceof Response) {
                $this->response = $result;
            }
        }

        return $this->response;
    }

    private function ensureFilterIsValid(string $filter): void
    {
        if (!class_exists($filter)) {
            throw new \Exception("Filter class {$filter} does not exist.");
        }

        if (!in_array(FilterInterface::class, class_implements($filter))) {
            throw new \Exception("Filter class {$filter} must implement interface " . FilterInterface::class);
        }
    }
}
