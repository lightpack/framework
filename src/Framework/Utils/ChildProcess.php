<?php

namespace Lightpack\Utils;

use RuntimeException;

/**
 * Represents a long-running process spawned with inherited stdio.
 *
 * Created exclusively via Process::spawn(). The child process inherits the
 * parent's STDIN/STDOUT/STDERR, making it suitable for interactive processes
 * like dev servers that must stream output directly to the terminal.
 *
 * Cleanup is handled at three levels:
 *   1. pcntl_signal (SIGINT/SIGTERM) — graceful Ctrl+C on Unix/macOS
 *   2. register_shutdown_function   — fallback for crashes and fatal exits
 *   3. __destruct                   — safety net when the instance is GC'd
 */
class ChildProcess
{
    private $resource;
    private ?int $exitCode = null;
    private ?int $processId = null;

    public function __construct($resource)
    {
        if (! is_resource($resource)) {
            throw new RuntimeException('Invalid process resource');
        }

        $this->resource = $resource;
        $status = proc_get_status($this->resource);
        $this->processId = $status['pid'] ?? null;

        $this->registerSignalHandlers();
    }

    /**
     * Returns true while the child process is alive.
     *
     * Captures the exit code the first time running = false is observed,
     * because proc_get_status only reports an accurate exitcode once.
     */
    public function isRunning(): bool
    {
        if (! is_resource($this->resource)) {
            return false;
        }

        $status = proc_get_status($this->resource);

        if (! $status['running'] && $this->exitCode === null) {
            $this->exitCode = $status['exitcode'];
        }

        return (bool) $status['running'];
    }

    /**
     * Returns the OS-level process ID, or null if unavailable.
     */
    public function pid(): ?int
    {
        return $this->processId;
    }

    /**
     * Returns the exit code, or null if the process is still running.
     * Always call wait() first for a reliable value.
     */
    public function exitCode(): ?int
    {
        return $this->exitCode;
    }

    /**
     * Blocks until the process exits and returns the exit code.
     */
    public function wait(): int
    {
        while ($this->isRunning()) {
            usleep(5000);
        }

        $this->close();

        return $this->exitCode ?? -1;
    }

    /**
     * Sends a signal to the process. Defaults to SIGTERM (15).
     * Use kill() for SIGKILL when the process must be force-stopped.
     */
    public function terminate(int $signal = 15): void
    {
        if (is_resource($this->resource)) {
            proc_terminate($this->resource, $signal);
        }
    }

    /**
     * Sends SIGKILL (9) — forcible, unblockable termination.
     */
    public function kill(): void
    {
        $this->terminate(9);
    }

    public function __destruct()
    {
        if (is_resource($this->resource)) {
            proc_terminate($this->resource, 9);
            proc_close($this->resource);
        }
    }

    private function close(): void
    {
        if (! is_resource($this->resource)) {
            return;
        }

        $code = proc_close($this->resource);
        $this->resource = null;

        if ($this->exitCode === null) {
            $this->exitCode = $code;
        }
    }

    private function registerSignalHandlers(): void
    {
        register_shutdown_function(function () {
            if (is_resource($this->resource)) {
                proc_terminate($this->resource, 9);
                proc_close($this->resource);
            }
        });

        if (! function_exists('pcntl_signal')) {
            return;
        }

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }

        $cleanup = function () {
            if (is_resource($this->resource)) {
                proc_terminate($this->resource, 9);
                proc_close($this->resource);
                $this->resource = null;
            }
            exit(0);
        };

        pcntl_signal(SIGINT, $cleanup);
        pcntl_signal(SIGTERM, $cleanup);
    }
}
