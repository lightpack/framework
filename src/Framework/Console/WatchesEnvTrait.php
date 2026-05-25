<?php

namespace Lightpack\Console;

use Lightpack\Utils\Process;

trait WatchesEnvTrait
{
    private function runWatched(array $command): void
    {
        $envFile = DIR_ROOT . DIRECTORY_SEPARATOR . '.env';
        $lastMtime = $this->getEnvMtime($envFile);
        $process = new Process;

        while (true) {
            $child = $process->spawn($command, $this->buildEnv($envFile));
            $restarting = false;

            while ($child->isRunning()) {
                clearstatcache(true, $envFile);
                $currentMtime = $this->getEnvMtime($envFile);

                if ($currentMtime !== $lastMtime) {
                    $lastMtime = $currentMtime;
                    $restarting = true;
                    $child->terminate();
                    $child->wait();
                    $this->output->newline();
                    $this->output->warningLabel();
                    $this->output->warning(' .env changed — restarting...');
                    $this->output->newline();
                    sleep(1);
                    break;
                }

                usleep(500000);
            }

            if (! $restarting) {
                return;
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
