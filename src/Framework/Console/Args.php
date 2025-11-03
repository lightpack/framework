<?php

namespace Lightpack\Console;

/**
 * Simple argument parser for console commands.
 * Provides convenient access to command-line arguments.
 */
class Args
{
    private array $args;
    private array $positional = [];
    private array $options = [];

    public function __construct(array $args)
    {
        $this->args = $args;
        $this->parse();
    }

    /**
     * Get an option value by name (e.g., --table=users)
     */
    public function get(string $name, $default = null)
    {
        return $this->options[$name] ?? $default;
    }

    /**
     * Check if a flag exists (e.g., --help, --force)
     */
    public function has(string $flag): bool
    {
        return isset($this->options[$flag]) && $this->options[$flag] === true;
    }

    /**
     * Get the first positional argument (non-option)
     */
    public function first(): ?string
    {
        return $this->positional[0] ?? null;
    }

    /**
     * Get all positional arguments
     */
    public function positional(): array
    {
        return $this->positional;
    }

    /**
     * Get all options
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * Get raw arguments array
     */
    public function all(): array
    {
        return $this->args;
    }

    private function parse(): void
    {
        foreach ($this->args as $arg) {
            // Option with value: --key=value (including empty values)
            // [^=]+ requires at least one non-= character for the option name
            if (preg_match('/^--([^=]+)=(.*)$/', $arg, $matches)) {
                $this->options[$matches[1]] = $matches[2];
            }
            // Flag: --help, --force
            // [^=]+ ensures flag name doesn't start with = (e.g., --=value is invalid)
            elseif (preg_match('/^--([^=].*)$/', $arg, $matches)) {
                $this->options[$matches[1]] = true;
            }
            // Positional argument (anything else, including malformed options)
            else {
                $this->positional[] = $arg;
            }
        }
    }
}
