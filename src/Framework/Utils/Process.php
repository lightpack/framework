<?php

namespace Lightpack\Utils;

use RuntimeException;
use InvalidArgumentException;

class Process
{
    const STDIN = 0;
    const STDOUT = 1;
    const STDERR = 2;

    private $resource = null;
    private string $output = '';
    private string $error = '';
    private ?int $exitCode = null;
    private ?string $workingDirectory = null;
    private array $pipes = [];
    private int $timeout = 60;
    private bool $isRunning = false;
    private array $descriptorSpec = [
        self::STDIN => ['pipe', 'r'],
        self::STDOUT => ['pipe', 'w'],
        self::STDERR => ['pipe', 'w'],
    ];

    public function setTimeout(int $seconds): self
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Timeout must be greater than 0 seconds');
        }
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Execute a command with optional callback for processing output line by line.
     * When callback is provided, it's memory efficient for handling large outputs.
     *
     * @param string|array $command The command to execute
     * @param callable|null $callback Optional callback to process output line by line
     *                               Signature: function(string $line, string $type): void
     *                               where $type is 'stdout' or 'stderr'
     */
    public function execute(string|array $command, ?callable $callback = null): void
    {
        $this->executeProcess($command, $callback);
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    public function failed(): bool
    {
        return $this->exitCode !== 0;
    }

    public function setDirectory(string $directory): self
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException('Invalid working directory: ' . $directory);
        }

        $this->workingDirectory = $directory;
        return $this;
    }

    public function getDirectory(): string
    {
        return $this->workingDirectory ?? getcwd();
    }

    private function validateCommand(string|array $command): void
    {
        if (is_array($command)) {
            if (empty($command)) {
                throw new InvalidArgumentException('Command array cannot be empty');
            }
            foreach ($command as $arg) {
                if (!is_string($arg) && (!is_numeric($arg) || $arg === '')) {
                    throw new InvalidArgumentException('Command argument cannot be empty');
                }
            }
        } elseif (!is_string($command) || trim($command) === '') {
            throw new InvalidArgumentException('Command string cannot be empty');
        }
    }

    private function executeProcess(string|array $command, ?callable $callback = null): void 
    {
        $this->validateCommand($command);

        if ($this->isRunning) {
            throw new RuntimeException('Process is already running');
        }

        try {
            $this->reset();
            $this->startProcess($command);
            $this->isRunning = true;

            if ($callback) {
                $this->processWithCallback($callback);
            } else {
                $this->waitForCompletion();
            }

            $this->closePipes();
            $this->closeProcess();
        } catch (\Exception $e) {
            $this->cleanup();
            throw $e;
        } finally {
            $this->isRunning = false;
        }
    }

    private function processWithCallback(callable $callback): void
    {
        $startTime = time();
        while (true) {
            $status = proc_get_status($this->resource);
            
            if (!$status['running']) {
                $this->exitCode = $status['exitcode'];
                $this->processRemainingOutput($callback);
                break;
            }

            // Process stdout
            if (isset($this->pipes[self::STDOUT])) {
                while (!feof($this->pipes[self::STDOUT]) && ($line = fgets($this->pipes[self::STDOUT])) !== false) {
                    $callback($line, 'stdout');
                    // $this->output .= $line;
                }
            }

            // Process stderr
            if (isset($this->pipes[self::STDERR])) {
                while (!feof($this->pipes[self::STDERR]) && ($line = fgets($this->pipes[self::STDERR])) !== false) {
                    $callback($line, 'stderr');
                    // $this->error .= $line;
                }
            }

            $this->checkTimeout($startTime);
            usleep(1000);
        }
    }

    private function processRemainingOutput(callable $callback): void
    {
        // Process remaining stdout
        if (isset($this->pipes[self::STDOUT])) {
            while (!feof($this->pipes[self::STDOUT]) && ($line = fgets($this->pipes[self::STDOUT])) !== false) {
                $callback($line, 'stdout');
                $this->output .= $line;
            }
        }

        // Process remaining stderr
        if (isset($this->pipes[self::STDERR])) {
            while (!feof($this->pipes[self::STDERR]) && ($line = fgets($this->pipes[self::STDERR])) !== false) {
                $callback($line, 'stderr');
                $this->error .= $line;
            }
        }
    }

    private function reset(): void
    {
        $this->output = '';
        $this->error = '';
        $this->exitCode = null;
        $this->pipes = [];
    }

    private function startProcess(string|array $command): void
    {
        $escapedCommand = $this->escapeCommand($command);
        $this->resource = proc_open($escapedCommand, $this->descriptorSpec, $this->pipes, $this->getDirectory());

        if (!is_resource($this->resource)) {
            throw new RuntimeException('Failed to start the process');
        }

        // Set pipes to non-blocking mode
        foreach ([self::STDOUT, self::STDERR] as $pipe) {
            if (isset($this->pipes[$pipe])) {
                stream_set_blocking($this->pipes[$pipe], false);
            }
        }
    }

    private function closePipes(): void
    {
        foreach ($this->pipes as $pipe) {
            if (!is_resource($pipe)) {
                continue;
            }

            if (get_resource_type($pipe) === 'stream') {
                $type = array_search($pipe, $this->pipes, true);
                
                // Read remaining output
                if ($type === self::STDOUT || $type === self::STDERR) {
                    $content = '';
                    while (!feof($pipe)) {
                        $content .= fread($pipe, 8192);
                    }
                    if ($type === self::STDOUT) {
                        $this->output .= $content;
                    } else {
                        $this->error .= $content;
                    }
                }
            }
            fclose($pipe);
        }
        $this->pipes = [];
    }

    private function closeProcess(): void
    {
        if (!is_resource($this->resource)) {
            return;
        }

        $this->exitCode = proc_close($this->resource);
        $this->resource = null;
    }

    private function cleanup(): void
    {
        if (is_resource($this->resource)) {
            proc_terminate($this->resource, 9); // SIGKILL
            $this->closePipes();
            proc_close($this->resource);
            $this->resource = null;
        }
        $this->isRunning = false;
    }

    private function checkTimeout(int $startTime): void
    {
        if (time() - $startTime > $this->timeout) {
            throw new RuntimeException("Process timed out after {$this->timeout} seconds");
        }
    }

    private function waitForCompletion(): void
    {
        $startTime = time();
        $lastOutputCheck = 0;
        $outputCheckInterval = 0.1; // seconds

        while (true) {
            try {
                $status = proc_get_status($this->resource);
                
                if (!$status['running']) {
                    $this->exitCode = $status['exitcode'];
                    break;
                }

                // Read output periodically to prevent pipe buffer from filling up
                $now = microtime(true);
                if ($now - $lastOutputCheck >= $outputCheckInterval) {
                    $this->readPendingOutput();
                    $lastOutputCheck = $now;
                }

                $this->checkTimeout($startTime);
                usleep(1000);
            } catch (\Exception $e) {
                $this->cleanup();
                throw $e;
            }
        }
    }

    private function readPendingOutput(): void
    {
        foreach ([self::STDOUT, self::STDERR] as $type) {
            if (!isset($this->pipes[$type]) || !is_resource($this->pipes[$type])) {
                continue;
            }

            $content = fread($this->pipes[$type], 8192);
            if ($content === false || $content === '') {
                continue;
            }

            if ($type === self::STDOUT) {
                $this->output .= $content;
            } else {
                $this->error .= $content;
            }
        }
    }

    private function escapeCommand(string|array $command): string
    {
        if (is_string($command)) {
            if (str_contains($command, '|') || str_contains($command, ';') || str_contains($command, '&&')) {
                // For shell operators, use sh -c
                return '/bin/sh -c ' . escapeshellarg($command);
            }
            return escapeshellcmd($command);
        }

        return implode(' ', array_map(function($arg) {
            if (empty($arg) && $arg !== '0') {
                throw new InvalidArgumentException('Command argument cannot be empty');
            }
            return escapeshellarg((string)$arg);
        }, $command));
    }
}