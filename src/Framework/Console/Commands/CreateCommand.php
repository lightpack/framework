<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Console\Views\CommandView;

class CreateCommand implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide a command class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid command class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = CommandView::getTemplate();
        $template = str_replace('__COMMAND_NAME__', $className, $template);
        $directory = './app/Commands';

        file_put_contents(DIR_ROOT . '/app/Commands/' . $className . '.php', $template);
        fputs(STDOUT, "✓ Command created: {$directory}/{$className}.php\n\n");
    }
}
