<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;

class LinkStorage extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $target = DIR_ROOT . '/storage/uploads/public';
        $link = DIR_ROOT . '/public/uploads';
        
        if(is_link($link)) {
            $this->output->line("Symlink already exists.");
            $this->output->newline();
            return 0;
        }

        $success = symlink($target, $link);

        if($success) {
            $this->output->success('✓ Created symlink from "public/uploads" to "storage/uploads/public"');
            $this->output->newline();
            return 0;
        }

        $this->output->error("Could not create symlink");
        $this->output->newline();
        return 1;
    }
}
