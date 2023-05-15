<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\File\File;
use Lightpack\Utils\Str;

class GenerateAppKey implements ICommand
{
    public function run(array $arguments = [])
    {
        $file = new File();
        $filepath = DIR_ROOT . '/env.php';

        if (!$file->exists($filepath)) {
            fputs(STDOUT, "[Error] No env.php file found.\n");
            fputs(STDOUT, "Please run the command below to create one:\n\n");
            fputs(STDOUT, "php lucy create:env\n\n");
            return;
        }

        $contents = $file->read($filepath);
        $newKey = (new Str)->random(32);
        $pattern = "/('APP_KEY'\s*=>\s*')([^']*)(')/";
        $replacement = '${1}' . $newKey . '${3}';
        $modifiedContents = preg_replace($pattern, $replacement, $contents);
        
        (new File)->write($filepath, $modifiedContents);

        fputs(STDOUT, "✓ Generated APP_KEY: {$newKey}.\n\n");
    }
}
