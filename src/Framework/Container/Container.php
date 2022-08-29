<?php

namespace Lightpack\Container;

use Closure;

class Container
{
    private $services = [];

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }

    public function get(string $id): ?object
    {
        $this->throwExceptionIfServiceNotFound($id);
        $service = $this->services[$id];

        if ($service instanceof Closure) {
            return $service($this);
        }

        return $service;
    }

    public function factory(string $id, callable $cb): void
    {
        $this->services[$id] = $cb;
    }

    public function register(string $id, callable $cb): void
    {
        $this->services[$id] = function () use ($cb) {
            static $instance;

            if ($instance == null) {
                $instance = $cb($this);
            }

            return $instance;
        };
    }

    private function throwExceptionIfServiceNotFound(string $id): void
    {
        if (!$this->has($id)) {
            throw new \Lightpack\Exceptions\ServiceNotFoundException(
                sprintf(
                    'Service `%s` is not registered',
                    $id
                )
            );
        }
    }

    public function resolve(string $id): object
    {
        // If already resolved, return it
        if ($this->has($id)) {
            return $this->get($id);
        }

        // Get reflection class
        $reflection = new \ReflectionClass($id);

        // Get constructor
        $constructor = $reflection->getConstructor();

        // If no constructor, return new instance
        if ($constructor == null) {
            return $this->registerInstance($id, new $id);
        }

        // Get constructor parameters
        $parameters = $constructor->getParameters();

        // Resolve constructor parameters
        $dependencies = $this->resolveParameters($parameters);

        // Create new instance
        $instance = $reflection->newInstanceArgs($dependencies);

        // Register instance
        return $this->registerInstance($id, $instance);
    }

    protected function resolveParameters(array $parameters): array
    {
        return array_map(function ($parameter) {
            return $this->resolve($parameter->getClass()->getName());
        }, $parameters);
    }

    protected function resolveParameter(\ReflectionParameter $parameter): ?object
    {
        $type = $parameter->getType();

        if (true === $type->isBuiltin()) {
            return null;
        }

        return $this->resolve($type->getName());
    }

    protected function registerInstance(string $id, object $instance): object
    {
        $this->register($id, function () use ($instance) {
            return $instance;
        });

        return $instance;
    }
}
