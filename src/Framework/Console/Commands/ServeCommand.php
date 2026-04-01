<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;

class ServeCommand extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        chdir(DIR_ROOT);

        $port = $this->args->argument(0) ?? '8000';
        $hostUrl = '127.0.0.1:' . $port;

        passthru('"' . PHP_BINARY . '"' . ' -S ' . "'$hostUrl'" . ' -t public');
        
        return 0;
    }
}
