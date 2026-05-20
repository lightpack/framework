<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\File\File;

class CreateEnv extends Command
{
    public function run()
    {
        $file = new File;

        if ($file->exists(DIR_ROOT . '/.env')) {
            $this->output->error(".env file already exists.");

            return self::SUCCESS;
        }

        (new File)->copy(DIR_ROOT . '/.env.example', DIR_ROOT . '/.env');
        $this->output->success("✓ Created .env file.");

        return self::SUCCESS;
    }
}
