<?php

namespace Lightpack\Utils;

class Pipeline
{
    /**
     * The object being passed through the pipeline.
     */
    private $passable;

    /**
     * The array of pipes.
     */
    private array $pipes = [];

    /**
     * Create a new Pipeline instance.
     *
     * @param mixed $passable
     */
    public function __construct($passable)
    {
        $this->passable = $passable;
    }

    /**
     * Set the pipes the passable should be piped through.
     *
     * @param array $pipes
     * @return self
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * Run the pipeline and return the result.
     *
     * @return mixed
     */
    public function run()
    {
        $result = $this->passable;

        foreach ($this->pipes as $pipe) {
            $pipeInstance = $this->resolvePipe($pipe);
            $result = $pipeInstance($result);
        }

        return $result;
    }

    /**
     * Resolve the pipe instance.
     * Handles class name strings, callables, and objects.
     *
     * @param mixed $pipe
     * @return callable
     * @throws \InvalidArgumentException
     */
    protected function resolvePipe($pipe)
    {
        // Already an object with __invoke
        if (is_object($pipe) && method_exists($pipe, '__invoke')) {
            return $pipe;
        }
        
        // Already a callable (closure, function name, etc.)
        if (is_callable($pipe)) {
            return $pipe;
        }
        
        // Class name string - resolve from container with DI
        if (is_string($pipe) && class_exists($pipe)) {
            return app($pipe);
        }
        
        throw new \InvalidArgumentException(
            "Invalid pipe: " . (is_string($pipe) ? $pipe : gettype($pipe))
        );
    }
}
