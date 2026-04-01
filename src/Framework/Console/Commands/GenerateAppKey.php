<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;
use Lightpack\File\File;
use Lightpack\Utils\Str;

class GenerateAppKey extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $file = new File();
        $filepath = realpath(DIR_ROOT . '/.env');

        if (!$file->exists($filepath)) {
            $this->output->error("No env file found.");
            $this->output->newline();
            return 1;
        }

        $contents = $file->read($filepath);
        $newKey = (new Str)->random(32);
        $pattern = "/^APP_KEY=.*$/m";
        $replacement = "APP_KEY=" . $newKey;
        $modifiedContents = preg_replace($pattern, $replacement, $contents);
        
        (new File)->write($filepath, $modifiedContents);

        $this->output->success("✓ Generated APP_KEY: {$newKey}.");
        $this->output->newline();
        
        return 0;
    }
}
