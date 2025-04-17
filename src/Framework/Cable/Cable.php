<?php

namespace Lightpack\Cable;

/**
 * Cable - Real-time communication for Lightpack
 * 
 * This class provides a simple API for real-time communication
 * using a driver-based architecture similar to other Lightpack
 * components.
 */
class Cable
{
    /**
     * @var DriverInterface
     */
    protected $driver;
    
    /**
     * @var string
     */
    protected $channel;
    
    /**
     * Create a new Cable instance
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }
    
    /**
     * Target a specific channel
     */
    public function to(string $channel): self
    {
        $clone = clone $this;
        $clone->channel = $channel;
        return $clone;
    }
    
    /**
     * Emit an event to the channel
     */
    public function emit(string $event, array $payload = []): void
    {
        if (empty($this->channel)) {
            throw new \RuntimeException('No channel specified. Use to() method first.');
        }
        
        $this->driver->emit($this->channel, $event, $payload);
    }
    
    /**
     * Update DOM directly
     */
    public function update(string $selector, string $html): void
    {
        $this->emit('dom-update', [
            'selector' => $selector,
            'html' => $html
        ]);
    }
    
    /**
     * Get the driver instance
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }
    
    /**
     * Get messages from a channel
     */
    public function getMessages(?string $channel = null, ?int $lastId = null): array
    {
        $channel = $channel ?? $this->channel;
        
        if (empty($channel)) {
            throw new \RuntimeException('No channel specified. Use to() method first or provide a channel.');
        }
        
        return $this->driver->getMessages($channel, $lastId);
    }
    
    /**
     * Clean up old messages
     */
    public function cleanup(int $olderThanSeconds = 86400): void
    {
        $this->driver->cleanup($olderThanSeconds);
    }
}
