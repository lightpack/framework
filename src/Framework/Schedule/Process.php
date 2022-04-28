<?php

namespace Lightpack\Schedule;

class Process
{
    public function __construct(string $command, array $args = [])
    {
        $this->command = $command;
        $this->args = $args;
    }

    public function run()
    {
        $command = $this->command . ' ' . implode(' ', $this->args);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (is_resource($process)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);

            fclose($pipes[1]);
            fclose($pipes[2]);

            $status = proc_get_status($process);

            while ($status['running']) {
                usleep(100000);
                $status = proc_get_status($process);
            }

            proc_close($process);

            if ($status['exitcode'] != 0) {
                throw new \Exception($stderr);
            }
        }

        return $stdout;
    }
}