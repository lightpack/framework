<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;

class ServeCommand implements ICommand
{
    public function run(array $arguments = [])
    {
        chdir(DIR_ROOT);

        $hostUrl = get_env('APP_URL', '127.0.0.1:8000');

        $hostUrl = str_replace(['http://', 'https://'], '', $hostUrl);

        $hostUrl = trim($hostUrl, '/');

        passthru('"' . PHP_BINARY . '"' . ' -S ' . "'$hostUrl'" . ' -t public');
    }
}
