<?php

namespace Lightpack\Console;

class Input
{
    protected array $arguments = [];
    protected array $options = [];
    protected array $requiredArguments = [];
    protected array $requiredOptions = [];
    protected string $scriptName = '';

    public function __construct(array $argv)
    {
        $argv = array_values($argv);
        if (count($argv) > 0) {
            $this->scriptName = $argv[0];
            $argv = array_slice($argv, 1); // skip script name
        }

        $argc = count($argv);
        for ($i = 0; $i < $argc; $i++) {
            $arg = $argv[$i];
            if (strpos($arg, '--') === 0) {
                // Long option
                $parts = explode('=', substr($arg, 2), 2);
                $key = $parts[0];
                $value = $parts[1] ?? true;
                $this->addOption($key, $value);
            } elseif (strpos($arg, '-') === 0 && strlen($arg) > 1) {
                // Short option(s)
                $flags = substr($arg, 1);
                if (strlen($flags) > 1) {
                    // Multiple flags, e.g. -abc
                    foreach (str_split($flags) as $flag) {
                        $this->addOption($flag, true);
                    }
                } else {
                    // Single flag, may have value
                    $next = ($i + 1 < $argc) ? $argv[$i + 1] : null;
                    if ($next !== null && strpos($next, '-') !== 0) {
                        $this->addOption($flags, $next);
                        $i++; // skip next argument
                    } else {
                        $this->addOption($flags, true);
                    }
                }
            } else {
                $this->arguments[] = $arg;
            }
        }
    }

    protected function addOption(string $key, $value): void
    {
        if (isset($this->options[$key])) {
            if (is_array($this->options[$key])) {
                $this->options[$key][] = $value;
            } else {
                $this->options[$key] = [$this->options[$key], $value];
            }
        } else {
            $this->options[$key] = $value;
        }
    }

    public function getArgument(int $index)
    {
        return $this->arguments[$index] ?? null;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOption(string $name)
    {
        return $this->options[$name] ?? null;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function requireArgument(int $index, string $description = ''): void
    {
        $this->requiredArguments[$index] = $description;
    }

    public function requireOption(string $name, string $description = ''): void
    {
        $this->requiredOptions[$name] = $description;
    }

    public function validate(): array
    {
        $errors = [];
        foreach ($this->requiredArguments as $index => $desc) {
            if (!isset($this->arguments[$index])) {
                $errors[] = "Missing required argument #{$index}" . ($desc ? ": {$desc}" : '');
            }
        }
        foreach ($this->requiredOptions as $name => $desc) {
            if (!isset($this->options[$name])) {
                $errors[] = "Missing required option --{$name}" . ($desc ? ": {$desc}" : '');
            }
        }
        return $errors;
    }

    public function getUsage(string $command = ''): string
    {
        $usage = "Usage: php {$this->scriptName} {$command}";
        foreach ($this->requiredArguments as $index => $desc) {
            $usage .= " <arg{$index}>";
        }
        foreach ($this->requiredOptions as $name => $desc) {
            $usage .= " [--{$name}=value]";
        }
        return $usage;
    }
}