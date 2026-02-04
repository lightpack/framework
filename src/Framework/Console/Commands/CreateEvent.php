<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Console\Views\EventView;

class CreateEvent implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide an event listener class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid event listener class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = EventView::getTemplate();
        $template = str_replace('__EVENT_NAME__', $className, $template);
        $directory = './app/Events';

        file_put_contents(DIR_ROOT . '/app/Events/' . $className . '.php', $template);
        fputs(STDOUT, "✓ Event created: {$directory}/{$className}.php\n\n");
    }
}
