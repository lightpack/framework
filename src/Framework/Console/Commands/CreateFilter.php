<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\FilterView;

class CreateFilter implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide a filter class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('#[A-Za-z0-9]#', $className)) {
            $message = "Invalid filter class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = FilterView::getTemplate();

        $template = str_replace('__FILTER_NAME__', $className, $template);

        file_put_contents(DIR_ROOT . '/app/Filters/' . $className . '.php', $template);
        fputs(STDOUT, "Filter created in /app/Filters\n\n");
    }
}
