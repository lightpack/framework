<?php

namespace Lightpack\Container;

use Closure;
use Lightpack\Exceptions\BindingNotFoundException;

class Container
{
    private $services = [];
    private $bindings = [];

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

    public function bind(string $contract, string $implementation)
    {
        $this->bindings[$contract] = $implementation;
    }

    public function resolve(string $id): object
    {
        // If already resolved, return it
        if ($this->has($id)) {
            return $this->get($id);
        }

        // Get reflection class
        $reflection = new \ReflectionClass($id);

        // Is it an interface or abstract class?
        if ($reflection->isInterface() || $reflection->isAbstract()) {
            return $this->resolveImplementation($id);
        }

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

    protected function resolveImplementation(string $id): object
    {
        $implementation = $this->bindings[$id] ?? null;

        if ($implementation == null) {
            throw new BindingNotFoundException(
                sprintf('No binding found for `%s`', $id)
            );
        }

        return $this->resolve($implementation);
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function reset(): void
    {
        $this->services = [];
        $this->bindings = [];
    }

    public function call(string|object $instanceName, string $instanceMethod, array $args = [])
    {
        // Resolve instance
        if(is_string($instanceName)) {
            $instance = $this->resolve($instanceName);
        } else {
            $instance = $instanceName;
        }

        // Get reflection
        $reflection = new \ReflectionClass($instance);

        // Get method parameters
        $parameters = $reflection->getMethod($instanceMethod)->getParameters();

        // Filter parameters that are scalar
        $parameters = array_filter($parameters, function ($parameter) {
            return $parameter->getType() && !$parameter->getType()->isBuiltin();
        });

        // Resolve method parameters
        $dependencies = $this->resolveParameters($parameters);

        // Merge dependencies with args
        $dependencies = array_merge($dependencies, $args);

        // Call method
        return $reflection->getMethod($instanceMethod)->invokeArgs($instance, $dependencies);
    }
}
