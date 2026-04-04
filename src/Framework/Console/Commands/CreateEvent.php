<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\Views\EventView;

class CreateEvent extends Command
{
    public function run(): int
    {
        $className = $this->args->argument(0);

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

        $template = EventView::getTemplate();
        $template = str_replace('__EVENT_NAME__', $className, $template);
        $directory = './app/Events';

        file_put_contents(DIR_ROOT . '/app/Events/' . $className . '.php', $template);
        $this->output->success("✓ Event created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
