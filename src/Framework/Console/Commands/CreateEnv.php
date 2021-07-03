<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\ICommand;

class CreateEnv implements ICommand
{
    public function run(array $arguments = [])
    {
        $file = new File();

        if ($file->exists(DIR_ROOT . '/env.php')) {
            fputs(STDOUT, "env.php file already exists.\n\n");
            return;
        }

        (new File)->copy(DIR_ROOT . '/env.example.php', DIR_ROOT . '/env.php');
        fputs(STDOUT, "✓ Created env.php file.\n\n");
    }
}
