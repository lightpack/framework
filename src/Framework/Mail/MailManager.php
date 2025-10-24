<?php

namespace Lightpack\Mail;

use Exception;

/**
 * Mail Manager - Manages multiple mail drivers
 * 
 * Allows registering custom drivers and switching between them per-mail
 */
class MailManager
{
    private array $drivers = [];
    private string $defaultDriver = 'smtp';

    /**
     * Register a mail driver
     */
    public function registerDriver(string $name, DriverInterface $driver): self
    {
        $this->drivers[$name] = $driver;
        return $this;
    }

    /**
     * Set the default driver
     */
    public function setDefaultDriver(string $name): self
    {
        if (!isset($this->drivers[$name])) {
            throw new Exception("Mail driver '{$name}' is not registered.");
        }

        $this->defaultDriver = $name;
        return $this;
    }

    /**
     * Get a specific driver
     */
    public function driver(?string $name = null): DriverInterface
    {
        $name = $name ?? $this->defaultDriver;

        if (!isset($this->drivers[$name])) {
            throw new Exception("Mail driver '{$name}' is not registered.");
        }

        return $this->drivers[$name];
    }

    /**
     * Get the default driver
     */
    public function getDefaultDriver(): DriverInterface
    {
        return $this->driver($this->defaultDriver);
    }

    /**
     * Get all registered driver names
     */
    public function getDriverNames(): array
    {
        return array_keys($this->drivers);
    }

    /**
     * Create a new batch mail instance
     * 
     * Usage:
     * app('mail')->batch()
     *     ->add(fn($m) => $m->to('user1@example.com')->subject('Hi')->body('...'))
     *     ->add(fn($m) => $m->to('user2@example.com')->subject('Hi')->body('...'))
     *     ->send();
     */
    public function batch(): BatchMail
    {
        return new BatchMail($this);
    }
}
