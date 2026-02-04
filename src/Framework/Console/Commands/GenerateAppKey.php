<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\File\File;
use Lightpack\Utils\Str;

class GenerateAppKey implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $file = new File();
        $filepath = realpath(DIR_ROOT . '/.env');

        if (!$file->exists($filepath)) {
            fputs(STDOUT, "[Error] No env file found.\n");
            return;
        }

        $contents = $file->read($filepath);
        $newKey = (new Str)->random(32);
        $pattern = "/^APP_KEY=.*$/m";
        $replacement = "APP_KEY=" . $newKey;
        $modifiedContents = preg_replace($pattern, $replacement, $contents);
        
        (new File)->write($filepath, $modifiedContents);

        fputs(STDOUT, "✓ Generated APP_KEY: {$newKey}.\n\n");
    }
}
