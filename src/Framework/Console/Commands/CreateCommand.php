<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\CommandView;

class CreateCommand implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide a command class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('#[A-Za-z0-9]#', $className)) {
            $message = "Invalid command class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = CommandView::getTemplate();
        $template = str_replace('__COMMAND_NAME__', $className, $template);
        $directory = '/app/Console';

        file_put_contents(DIR_ROOT . '/app/Commands/' . $className . '.php', $template);
        fputs(STDOUT, "✓ Command created: {$directory}/{$className}.php\n\n");
    }
}
