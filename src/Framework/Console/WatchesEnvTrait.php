<?php

namespace Lightpack\Console;

trait WatchesEnvTrait
{
    private function runWatched(array $command): void
    {
        $envFile = DIR_ROOT . DIRECTORY_SEPARATOR . '.env';
        $lastMtime = $this->getEnvMtime($envFile);

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }

        while (true) {
            $pipes = [];
            $process = proc_open(
                $command,
                [STDIN, STDOUT, STDERR],
                $pipes,
                null,
                $this->buildEnv($envFile)
            );

            if ($process === false) {
                break;
            }

            if (function_exists('pcntl_signal')) {
                pcntl_signal(SIGINT, function () use ($process) {
                    proc_terminate($process);
                    proc_close($process);
                    exit(0);
                });
            }

            while (true) {
                if (!proc_get_status($process)['running']) {
                    proc_close($process);
                    return;
                }

                clearstatcache(true, $envFile);
                $currentMtime = $this->getEnvMtime($envFile);

                if ($currentMtime !== $lastMtime) {
                    $lastMtime = $currentMtime;
                    proc_terminate($process);
                    proc_close($process);
                    $this->output->newline();
                    $this->output->warningLabel();
                    $this->output->warning(' .env changed — restarting...');
                    $this->output->newline();
                    sleep(1);
                    break;
                }

                usleep(500000);
            }
        }
    }

    private function buildEnv(string $file): array
    {
        $env = getenv();

        if (!file_exists($file)) {
            return $env;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = trim(trim($value), '"\'');
        }

        return $env;
    }

    private function getEnvMtime(string $file): int
    {
        return file_exists($file) ? (int) filemtime($file) : 0;
    }
}
