<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\Views\SeederView;

class CreateSeeder extends Command
{
    public function run(): int
    {
        $className = $this->args->argument(0);
        $force = $this->args->has('force');

        if (null === $className) {
            $this->output->error("Please provide the seeder class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid seeder class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        $directory = './database/seeders';
        $filePath = DIR_ROOT . '/database/seeders/' . $className . '.php';

        if (file_exists($filePath) && !$force) {
            $this->output->newline();
            $this->output->error("Seeder already exists: {$directory}/{$className}.php");
            $this->output->newline();
            $this->output->line("Use --force to overwrite.");
            $this->output->newline();
            return self::FAILURE;
        }

        $template = SeederView::getTemplate();
        $template = str_replace('__SEEDER_NAME__', $className, $template);

        file_put_contents($filePath, $template);
        $this->output->success("✓ Seeder created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
