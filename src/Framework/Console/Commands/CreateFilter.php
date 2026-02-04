<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Console\Views\FilterView;

class CreateFilter implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide a filter class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid filter class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = FilterView::getTemplate();
        $template = str_replace('__FILTER_NAME__', $className, $template);
        $directory = './app/Filters';

        file_put_contents(DIR_ROOT . '/app/Filters/' . $className . '.php', $template);
        fputs(STDOUT, "✓ Filter created: {$directory}/{$className}.php\n\n");
    }
}
