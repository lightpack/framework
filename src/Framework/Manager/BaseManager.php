<?php

namespace Lightpack\Manager;

use Lightpack\Container\Container;
use Closure;
use Exception;

abstract class BaseManager
{
    protected Container $container;
    protected array $instances = [];
    protected array $factories = [];
    protected ?string $defaultDriver = null;
    
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Register a driver factory
     */
    public function register(string $name, Closure $factory): self
    {
        $this->factories[$name] = $factory;
        return $this;
    }
    
    /**
     * Extend with custom driver (alias for register)
     */
    public function extend(string $name, Closure $factory): self
    {
        return $this->register($name, $factory);
    }
    
    /**
     * Set default driver
     */
    public function setDefaultDriver(string $name): void
    {
        $this->defaultDriver = $name;
    }
    
    /**
     * Get default driver name
     */
    public function getDefaultDriver(): ?string
    {
        return $this->defaultDriver;
    }
    
    /**
     * Resolve a driver instance (lazy loaded, cached)
     */
    protected function resolve(string $name): object
    {
        if (!isset($this->instances[$name])) {
            if (!isset($this->factories[$name])) {
                throw new Exception($this->getErrorMessage($name));
            }
            
            $this->instances[$name] = $this->factories[$name]($this->container);
        }
        
        return $this->instances[$name];
    }
    
    /**
     * Get error message for missing driver
     */
    abstract protected function getErrorMessage(string $name): string;
}
