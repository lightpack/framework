<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;

class ServeCommand implements CommandInterface
{
    public function run(array $arguments = [])
    {
        chdir(DIR_ROOT);

        $port = $arguments[0] ?? '8000';
        $hostUrl = '127.0.0.1:' . $port;

        passthru('"' . PHP_BINARY . '"' . ' -S ' . "'$hostUrl'" . ' -t public');
    }
}
