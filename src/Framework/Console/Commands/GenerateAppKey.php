<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\File\File;
use Lightpack\Utils\Str;

class GenerateAppKey extends Command
{
    public function run(): int
    {
        $file = new File();
        $filepath = realpath(DIR_ROOT . '/.env');

        if (!$file->exists($filepath)) {
            $this->output->error("No env file found.");
            $this->output->newline();
            return self::FAILURE;
        }

        $contents = $file->read($filepath);
        $newKey = (new Str)->random(32);
        $pattern = "/^APP_KEY=.*$/m";
        $replacement = "APP_KEY=" . $newKey;
        $modifiedContents = preg_replace($pattern, $replacement, $contents);
        
        (new File)->write($filepath, $modifiedContents);

        $this->output->success("✓ Generated APP_KEY: {$newKey}.");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
