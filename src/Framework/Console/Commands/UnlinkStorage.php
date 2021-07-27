<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;

class UnlinkStorage implements ICommand
{
    public function run(array $arguments = [])
    {
        $link = DIR_ROOT . '/public/uploads';
        
        if(!is_link($link)) {
            return;
        }

        unlink($link);

        fputs(STDOUT, "✓ Unlinked storage\n\n");
    }
}
