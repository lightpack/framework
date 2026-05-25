<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\WatchesEnvTrait;

class ServeCommand extends Command
{
    use WatchesEnvTrait;

    public function run()
    {
        chdir(DIR_ROOT);

        $host = '127.0.0.1';
        $requestedPort = (int) $this->args->get('port', 8000);

        $this->output->newline();

        if ($requestedPort < 1 || $requestedPort > 65535) {
            return $this->abort("Port must be between 1 and 65535.");
        }

        [$port, $error] = $this->findAvailablePort($host, $requestedPort);

        if ($port === null) {
            return $this->abort($error);
        }

        if ($port !== $requestedPort) {
            $this->alert("Port {$requestedPort} is in use. Using port {$port} instead.");
        }

        $this->printServerInfo($host, $port);

        $this->runWatched([PHP_BINARY, '-S', "{$host}:{$port}", '-t', 'public']);

        return self::SUCCESS;
    }

    private function abort(string $message): int
    {
        $this->output->errorLabel();
        $this->output->error(" {$message}");

        return self::FAILURE;
    }

    private function alert(string $message): void
    {
        $this->output->warningLabel();
        $this->output->warning(" {$message}");
        $this->output->newline();
    }

    private function printServerInfo(string $host, int $port): void
    {
        $this->printBanner();
        $this->output->newline();
        $this->output->successLabel('SERVER');
        $this->output->success(" http://{$host}:{$port}");
        $this->output->newline();
        $this->output->line('Press Ctrl+C to stop the server.');
        $this->output->newline();
    }

    private function printBanner(): void
    {
        $art = <<<'ART'
 _     _       _     _   ____   _    ____ _  __
| |   (_) __ _| |__ | |_|  _ \ / \  / ___| |/ /
| |   | |/ _` | '_ \| __| |_) / _ \| |   | ' / 
| |___| | (_| | | | | |_|  __/ ___ \ |___| . \ 
|_____|_|\__, |_| |_|\__|_| /_/   \_\____|_|\_\
         |___/
ART;

        foreach (explode("\n", $art) as $line) {
            $this->output->info($line);
        }
    }

    private function findAvailablePort(string $host, int $startPort): array
    {
        $maxPort = min($startPort + 100, 65535);

        for ($port = $startPort; $port <= $maxPort; $port++) {
            [$canBind, $errstr] = $this->tryBind($host, $port);

            if ($canBind) {
                return [$port, null];
            }

            if (stripos($errstr, 'permission') !== false) {
                return [null, "Permission denied on port {$port}."];
            }
        }

        return [null, "No available port found between {$startPort} and {$maxPort}."];
    }

    private function tryBind(string $host, int $port): array
    {
        set_error_handler(fn () => true);
        $socket = stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);
        restore_error_handler();

        if ($socket === false) {
            return [false, $errstr];
        }

        fclose($socket);

        return [true, null];
    }
}
