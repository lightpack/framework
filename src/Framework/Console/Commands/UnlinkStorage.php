<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;

class UnlinkStorage extends Command
{
    public function run(): int
    {
        $link = DIR_ROOT . '/public/uploads';
        
        if(!is_link($link)) {
            $this->output->line("No symlink to remove.");
            $this->output->newline();
            return self::SUCCESS;
        }

        unlink($link);

        $this->output->success("✓ Unlinked storage");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
