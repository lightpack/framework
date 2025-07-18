<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;

class ServeCommand implements ICommand
{
    public function run(array $arguments = [])
    {
        chdir(DIR_ROOT);

        $port = $arguments[0] ?? '8000';
        $hostUrl = '127.0.0.1:' . $port;

        passthru('"' . PHP_BINARY . '"' . ' -S ' . "'$hostUrl'" . ' -t public');
    }
}
