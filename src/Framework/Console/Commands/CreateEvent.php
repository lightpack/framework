<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\Views\EventView;

class CreateEvent extends Command
{
    public function run(): int
    {
        $className = $this->args->argument(0);
        $force = $this->args->has('force');

        if (null === $className) {
            $this->output->error("Please provide an event listener class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid event listener class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        $directory = './app/Events';
        $filePath = DIR_ROOT . '/app/Events/' . $className . '.php';

        if (file_exists($filePath) && !$force) {
            $this->output->newline();
            $this->output->error("Event already exists: {$directory}/{$className}.php");
            $this->output->newline();
            $this->output->line("Use --force to overwrite.");
            $this->output->newline();
            return self::FAILURE;
        }

        $template = EventView::getTemplate();
        $template = str_replace('__EVENT_NAME__', $className, $template);

        file_put_contents($filePath, $template);
        $this->output->success("✓ Event created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
