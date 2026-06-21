<?php

namespace Lightpack\Deploy;

use Lightpack\Utils\Process;

/**
 * Shared process execution for deployment service classes.
 *
 * Provides a uniform execute() wrapper around Process and a helper
 * to expand '~' in SSH key paths. Used by Deployer and Provisioner
 * to eliminate duplication between service classes.
 */
trait RunsProcessTrait
{
    private function resolveKeyPath(string $key): string
    {
        if (str_starts_with($key, '~')) {
            $home = $_SERVER['HOME'] ?? getenv('HOME') ?? getenv('USERPROFILE') ?? '';
            return str_replace('~', $home, $key);
        }

        return $key;
    }

    /**
     * Execute a command and stream output to stdout in real-time.
     *
     * @param string|array $command
     * @return array{success: bool, exit_code: int, output: string}
     */
    private function execute(string|array $command, int $timeout = 300): array
    {
        $process = new Process();
        $output = '';

        $process
            ->setTimeout($timeout)
            ->execute($command, function (string $line, string $type) use (&$output) {
                $output .= $line;
                echo $line;
                flush();
            });

        $exitCode = $process->getExitCode() ?? -1;

        return [
            'success'   => $exitCode === 0,
            'exit_code' => $exitCode,
            'output'    => $output,
        ];
    }
}
