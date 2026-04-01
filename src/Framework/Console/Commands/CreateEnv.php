<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\BaseCommand;

class CreateEnv extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $file = new File();

        if ($file->exists(DIR_ROOT . '/.env')) {
            $this->output->line(".env file already exists.");
            $this->output->newline();
            return 0;
        }

        (new File)->copy(DIR_ROOT . '/.env.example', DIR_ROOT . '/.env');
        $this->output->success("✓ Created .env file.");
        $this->output->newline();
        
        return 0;
    }
}
