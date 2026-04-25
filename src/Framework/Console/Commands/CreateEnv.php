<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\Command;

class CreateEnv extends Command
{
    public function run()
    {
        $file = new File();

        if ($file->exists(DIR_ROOT . '/.env')) {
            $this->output->error(".env file already exists.");
            $this->output->newline();
            return self::SUCCESS;
        }

        (new File)->copy(DIR_ROOT . '/.env.example', DIR_ROOT . '/.env');
        $this->output->success("✓ Created .env file.");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
