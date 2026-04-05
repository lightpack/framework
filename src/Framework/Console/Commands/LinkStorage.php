<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;

class LinkStorage extends Command
{
    public function run(): int
    {
        $target = DIR_ROOT . '/storage/uploads/public';
        $link = DIR_ROOT . '/public/uploads';
        
        if(is_link($link)) {
            $this->output->line("Symlink already exists.");
            $this->output->newline();
            return self::SUCCESS;
        }

        $success = symlink($target, $link);

        if($success) {
            $this->output->success('✓ Created symlink from "public/uploads" to "storage/uploads/public"');
            $this->output->newline();
            return self::SUCCESS;
        }

        $this->output->error("Could not create symlink");
        $this->output->newline();
        return self::FAILURE;
    }
}
