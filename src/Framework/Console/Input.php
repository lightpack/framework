<?php

namespace Lightpack\Console;

class Input
{
    protected array $arguments = [];
    protected array $options = [];
    protected array $requiredArguments = [];
    protected array $requiredOptions = [];
    protected string $scriptName = '';

    /**
     * Parses CLI arguments and options from the provided $argv array.
     * Skips the script name, then parses options and positional arguments.
     *
     * @param array $argv
     */
    public function __construct(array $argv)
    {
        $argv = array_values($argv);
        $this->extractScriptName($argv);
        $this->parseArgumentsAndOptions($argv);
    }

    /**
     * Extracts the script name from argv and removes it from the array.
     *
     * @param array &$argv
     * @return void
     */
    private function extractScriptName(array &$argv): void
    {
        if (count($argv) > 0) {
            $this->scriptName = $argv[0];
            array_shift($argv);
        }
    }

    /**
     * Parses the remaining argv array into options and positional arguments.
     *
     * @param array $argv
     * @return void
     */
    private function parseArgumentsAndOptions(array $argv): void
    {
        $argc = count($argv);
        for ($i = 0; $i < $argc; $i++) {
            $arg = $argv[$i];
            if ($this->isLongOption($arg)) {
                $this->parseLongOption($arg);
            } elseif ($this->isShortOption($arg)) {
                $i = $this->parseShortOption($argv, $i);
            } else {
                $this->arguments[] = $arg;
            }
        }
    }

    /**
     * Checks if an argument is a long option (starts with --)
     */
    private function isLongOption(string $arg): bool
    {
        return strpos($arg, '--') === 0;
    }

    /**
     * Checks if an argument is a short option (starts with - and length > 1)
     */
    private function isShortOption(string $arg): bool
    {
        return strpos($arg, '-') === 0 && strlen($arg) > 1;
    }

    /**
     * Parses a long option (e.g. --foo=bar or --flag)
     */
    private function parseLongOption(string $arg): void
    {
        $parts = explode('=', substr($arg, 2), 2);
        $key = $parts[0];
        $value = $parts[1] ?? true;
        $this->addOption($key, $value);
    }

    /**
     * Parses a short option or group of flags (e.g. -f bar, -abc)
     * Returns the new index after consuming any value.
     */
    private function parseShortOption(array $argv, int $i): int
    {
        $flags = substr($argv[$i], 1);
        if (strlen($flags) > 1) {
            foreach (str_split($flags) as $flag) {
                $this->addOption($flag, true);
            }
        } else {
            $next = ($i + 1 < count($argv)) ? $argv[$i + 1] : null;
            if ($next !== null && strpos($next, '-') !== 0) {
                $this->addOption($flags, $next);
                $i++; // skip next argument
            } else {
                $this->addOption($flags, true);
            }
        }
        return $i;
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