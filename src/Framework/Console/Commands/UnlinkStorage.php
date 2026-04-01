<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;

class UnlinkStorage extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $link = DIR_ROOT . '/public/uploads';
        
        if(!is_link($link)) {
            $this->output->line("No symlink to remove.");
            $this->output->newline();
            return 0;
        }

        unlink($link);

        $this->output->success("✓ Unlinked storage");
        $this->output->newline();
        
        return 0;
    }
}
