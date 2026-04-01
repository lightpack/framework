<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;
use Lightpack\Console\Views\EventView;

class CreateEvent extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide an event listener class name.");
            $this->output->newline();
            return 1;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid event listener class name.");
            $this->output->newline();
            return 1;
        }

        $template = EventView::getTemplate();
        $template = str_replace('__EVENT_NAME__', $className, $template);
        $directory = './app/Events';

        file_put_contents(DIR_ROOT . '/app/Events/' . $className . '.php', $template);
        $this->output->success("✓ Event created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return 0;
    }
}
