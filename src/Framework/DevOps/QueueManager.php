<?php

namespace Lightpack\DevOps;

/**
 * Manages a background queue worker process.
 *
 * Uses a PID file at storage/worker.pid to track the daemon.
 * On Unix systems, uses posix_kill for signal-based lifecycle management.
 * Falls back gracefully on systems without POSIX extensions.
 */
class QueueManager
{
    private string $pidFile;
    private string $consolePath;

    public function __construct()
    {
        $this->pidFile = DIR_ROOT . '/storage/worker.pid';
        $this->consolePath = DIR_ROOT . '/console';
    }

    /**
     * Start the background worker daemon.
     *
     * @param array $options Worker options (queue, sleep, cooldown)
     * @return array{success: bool, message: string, pid?: int}
     */
    public function start(array $options = []): array
    {
        if ($this->isRunning()) {
            $pid = $this->getPid();
            return [
                'success' => false,
                'message' => "Worker already running (PID: {$pid}). Use queue:restart to reload.",
            ];
        }

        $command = $this->buildWorkerCommand($options);

        if (!function_exists('posix_kill')) {
            return [
                'success' => false,
                'message' => 'POSIX extension not available. Cannot start background daemon on this system. Use "php console jobs:run" instead.',
            ];
        }

        // Use shell backgrounding to detach the process
        $shellCommand = sprintf(
            '(%s) > /dev/null 2>&1 & echo $!',
            $command
        );

        $pid = (int) trim((string) shell_exec($shellCommand));

        if ($pid <= 0) {
            return [
                'success' => false,
                'message' => 'Failed to start worker process.',
            ];
        }

        // Give it a moment to start, then verify
        usleep(200000); // 200ms

        if (!$this->isProcessAlive($pid)) {
            return [
                'success' => false,
                'message' => "Worker started but exited immediately (PID: {$pid}). Check logs for errors.",
            ];
        }

        $this->writePid($pid);

        return [
            'success' => true,
            'message' => "Worker started (PID: {$pid}).",
            'pid' => $pid,
        ];
    }

    /**
     * Stop the background worker daemon.
     *
     * @return array{success: bool, message: string}
     */
    public function stop(): array
    {
        $pid = $this->getPid();

        if ($pid === null) {
            return [
                'success' => false,
                'message' => 'No worker PID file found. Worker is not running.',
            ];
        }

        if (!$this->isProcessAlive($pid)) {
            $this->clearPid();
            return [
                'success' => false,
                'message' => "Worker PID file exists but process {$pid} is not running. Cleaned up stale PID file.",
            ];
        }

        if (!function_exists('posix_kill')) {
            return [
                'success' => false,
                'message' => 'POSIX extension not available. Cannot stop daemon on this system.',
            ];
        }

        // Send SIGTERM (graceful shutdown)
        posix_kill($pid, SIGTERM);

        // Wait up to 5 seconds for graceful exit
        $waited = 0;
        while ($this->isProcessAlive($pid) && $waited < 50) {
            usleep(100000); // 100ms
            $waited++;
        }

        // Force kill if still running
        if ($this->isProcessAlive($pid)) {
            posix_kill($pid, SIGKILL);
            usleep(500000);
        }

        $this->clearPid();

        return [
            'success' => true,
            'message' => "Worker stopped (was PID: {$pid}).",
        ];
    }

    /**
     * Restart the background worker daemon.
     *
     * @param array $options Worker options
     * @return array{success: bool, message: string, pid?: int}
     */
    public function restart(array $options = []): array
    {
        $this->stop();
        // Brief pause to ensure port/file locks are released
        usleep(500000);
        return $this->start($options);
    }

    /**
     * Get the current status of the worker.
     *
     * @return array{running: bool, pid: ?int}
     */
    public function status(): array
    {
        $pid = $this->getPid();

        if ($pid === null) {
            return [
                'running' => false,
                'pid' => null,
            ];
        }

        if (!$this->isProcessAlive($pid)) {
            $this->clearPid();
            return [
                'running' => false,
                'pid' => null,
            ];
        }

        return [
            'running' => true,
            'pid' => $pid,
        ];
    }

    /**
     * Check if the worker is currently running.
     */
    public function isRunning(): bool
    {
        $status = $this->status();
        return $status['running'];
    }

    private function buildWorkerCommand(array $options): string
    {
        $command = PHP_BINARY . ' ' . escapeshellarg($this->consolePath) . ' jobs:run --no-watch';

        if (!empty($options['queue'])) {
            $command .= ' --queue=' . escapeshellarg($options['queue']);
        }

        if (isset($options['sleep'])) {
            $command .= ' --sleep=' . (int) $options['sleep'];
        }

        if (isset($options['cooldown'])) {
            $command .= ' --cooldown=' . (int) $options['cooldown'];
        }

        return $command;
    }

    private function getPid(): ?int
    {
        if (!file_exists($this->pidFile)) {
            return null;
        }

        $content = @file_get_contents($this->pidFile);
        $pid = (int) trim((string) $content);

        return $pid > 0 ? $pid : null;
    }

    private function writePid(int $pid): void
    {
        $dir = dirname($this->pidFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->pidFile, (string) $pid);
    }

    private function clearPid(): void
    {
        if (file_exists($this->pidFile)) {
            @unlink($this->pidFile);
        }
    }

    private function isProcessAlive(int $pid): bool
    {
        if (!function_exists('posix_kill')) {
            return false;
        }

        return posix_kill($pid, 0);
    }

}
