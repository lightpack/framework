<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;
use Lightpack\Console\Views\SeederView;

class CreateSeeder extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide the seeder class name.");
            $this->output->newline();
            return 1;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid seeder class name.");
            $this->output->newline();
            return 1;
        }

        $template = SeederView::getTemplate();
        $template = str_replace('__SEEDER_NAME__', $className, $template);
        $directory = './database/seeders';

        $filePath = DIR_ROOT . '/database/seeders/' . $className . '.php';
        if (file_exists($filePath)) {
            $this->output->error("Seeder class file already exists: {$directory}/{$className}.php");
            $this->output->newline();
            return 1;
        }
        file_put_contents($filePath, $template);
        $this->output->success("✓ Seeder class file created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return 0;
    }
}
