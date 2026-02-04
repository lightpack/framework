<?php

namespace Lightpack\Container;

use Closure;
use Lightpack\Exceptions\BindingNotFoundException;

class Container
{
    protected $aliases = [];
    protected $services = [];
    protected $bindings = [];

    protected static $instance;

    /**
     * Get the instance of the container. If the instance does not exist, 
     * create a new instance. This makes the container a singleton.
     *
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Destroy the container instance.
     *
     * @return void
     */
    public static function destroy(): void
    {
        static::$instance = null;
    }

    /**
     * Check if a service is registered.
     *
     * @param string $id The service id.
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }

    /**
     * Get a service from the container. 
     * 
     * @param string $id
     * @return mixed
     * @throws ServiceNotFoundException
     */
    public function get(string $id): ?object
    {
        $this->throwExceptionIfServiceNotFound($id);
        $service = $this->services[$id];

        if ($service instanceof Closure) {
            return $service($this);
        }

        return $service;
    }

    /**
     * Register a service in the container. 
     * 
     * This method will return a new instance of the service every 
     * time it is called. Also, the service creation will be 
     * deferred until it is requested for the first time.
     * 
     * @param string $id
     * @param Closure $service
     * @return void
     */
    public function factory(string $id, callable $cb): void
    {
        $this->services[$id] = $cb;
    }

    /**
     * Register a service in the container. 
     * 
     * This method will register the service as a singleton. Also the 
     * service creation will be deferred until it is requested.
     * 
     * @param string $id The service id.
     * @param callable $cb The service callback.
     * 
     * @return void
     */
    public function register(string $id, callable $cb): void
    {
        $this->services[$id] = function () use ($cb) {
            static $instance;

            if ($instance === null) {
                $instance = $cb($this);

                $this->callIf($instance, '__boot');
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
        if ($this->has($id)) {
            return $this->get($id);
        }

        if (!array_key_exists($id, $this->aliases)) {
            $resolvedInstance = $this->resolveWithReflection($id);

            $this->callIf($resolvedInstance, '__boot');

            return $resolvedInstance;
        }

        // It is a type and has alias
        $alias = $this->aliases[$id];

        $this->throwExceptionIfServiceNotFound($alias);

        return $this->resolve($this->aliases[$id]);
    }

    protected function resolveWithReflection(string $id): object
    {
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
            return $this->instance($id, new $id);
        }

        // Get constructor parameters
        $parameters = $constructor->getParameters();

        // Resolve constructor parameters
        $dependencies = $this->resolveParameters($parameters);

        // Create new instance
        $instance = $reflection->newInstanceArgs($dependencies);

        // Register instance
        return $this->instance($id, $instance);
    }

    protected function resolveParameters(array $parameters): array
    {
        $parameters = $this->filterNonScalarParameters($parameters);

        return array_map(function ($parameter) {
            return $this->resolve($parameter->getType()->getName());
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

    /**
     * Bind an instance to the container.
     * 
     * @param string $id Alias for the instance
     * @param object $instance Instance to be bound
     * 
     * @return object 
     */
    public function instance(string $id, object $instance): object
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
        $this->aliases = [];
    }

    public function call(string|object $instanceName, string $instanceMethod, array $args = [])
    {
        // Resolve instance
        if (is_string($instanceName)) {
            $instance = $this->resolve($instanceName);
        } else {
            $instance = $instanceName;
        }

        // Get reflection
        $reflection = new \ReflectionClass($instance);

        // Get method
        $method = $reflection->getMethod($instanceMethod);

        // Get method parameters
        $parameters = $method->getParameters();

        // Filter parameters that are scalar
        $scalarParameters = $this->filterScalarParameters($parameters);

        // Filter parameters that are non-scalar
        $nonScalarParameters = $this->filterNonScalarParameters($parameters);

        // Resolve method parameters
        $dependencies = $this->resolveParameters($nonScalarParameters);

        // Prepare method's scalar arguments
        $arguments = [];

        foreach ($scalarParameters as $parameter) {
            $parameterName = $parameter->getName();
            $arguments[$parameterName] = $args[$parameterName] ?? $parameter->getDefaultValue();
        }

        // Merge dependencies with args
        $dependencies = array_merge($dependencies, $arguments);

        // Call method
        return $method->invokeArgs($instance, $dependencies);
    }


    /**
     * Call a method on an object or class only if the method exists.
     */
    public function callIf(string|object $instanceName, string $instanceMethod, array $args = [])
    {
        if (method_exists($instanceName, $instanceMethod)) {
            return $this->call($instanceName, $instanceMethod, $args);
        }

        return null;
    }

    protected function filterScalarParameters(array $parameters): array
    {
        return array_filter($parameters, function ($parameter) {
            return empty($parameter->getType()) || $parameter->getType()->isBuiltin();
        });
    }

    protected function filterNonScalarParameters(array $parameters): array
    {
        return array_filter($parameters, function ($parameter) {
            return $parameter->getType() && !$parameter->getType()->isBuiltin();
        });
    }

    /**
     * Register an alias for a service.
     * 
     * Multiple interfaces, abstracts, or concrete classes can be 
     * aliased against a single alias key.
     * 
     * @param string $alias Alias for the service.
     * @param string $type Service class name.
     * 
     * @return void
     * 
     * Example:
     * 
     * $container->alias(InterfaceFoo::class, 'x');
     * $container->alias(InterfaceBar::class, 'y');
     * $container->alias(X::class, 'x');
     */
    public function alias(string $type, string $alias): void
    {
        // Make sure that the alias has been registered
        $this->throwExceptionIfServiceNotFound($alias);

        $this->aliases[$type] = $alias;
    }
}
