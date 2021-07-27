<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;

class SymlinkStorage implements ICommand
{
    public function run(array $arguments = [])
    {
        $target = DIR_ROOT . '/storage/uploads/public';
        $link = DIR_ROOT . '/public/uploads';
        
        if(is_link($link)) {
            fputs(STDOUT, "Symlink already exists.\n\n");
            return;
        }

        $success = symlink($target, $link);

        if($success) {
            fputs(STDOUT, "✓ Created symlink from \n\n{$link} \n -- to -- \n{$target}\n\n");
            return;
        }

        fputs(STDOUT, "Could not create symlink\n\n");
    }
}
