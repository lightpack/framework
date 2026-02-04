<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;

class UnlinkStorage implements CommandInterface
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
