<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\SeederView;

class CreateSeeder implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide the seeder class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid seeder class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = SeederView::getTemplate();
        $template = str_replace('__SEEDER_NAME__', $className, $template);
        $directory = './database/seeders';

        file_put_contents(DIR_ROOT . '/database/seeders/' . $className . '.php', $template);
        fputs(STDOUT, "✓ Seeder class file created: {$directory}/{$className}.php\n\n");
    }
}
