<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;

class ServeCommand extends Command
{
    public function run(): int
    {
        chdir(DIR_ROOT);

        $port = $this->args->argument(0) ?? '8000';
        $hostUrl = '127.0.0.1:' . $port;

        passthru('"' . PHP_BINARY . '"' . ' -S ' . "'$hostUrl'" . ' -t public');
        
        return self::SUCCESS;
    }
}
