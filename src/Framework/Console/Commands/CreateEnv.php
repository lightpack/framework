<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\ICommand;

class CreateEnv implements ICommand
{
    public function run(array $arguments = [])
    {
        $file = new File();

        if ($file->exists(DIR_ROOT . '/.env')) {
            fputs(STDOUT, ".env file already exists.\n\n");
            return;
        }

        (new File)->copy(DIR_ROOT . '/.env.example', DIR_ROOT . '/.env');
        fputs(STDOUT, "✓ Created .env file.\n\n");
    }
}
