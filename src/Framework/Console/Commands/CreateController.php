<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\ICommand;
use Lightpack\Console\Views\ControllerView;

class CreateController implements ICommand
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide a controller class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('#[A-Za-z0-9]#', $className)) {
            $message = "Invalid controller class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = ControllerView::getTemplate();
        $template = str_replace('__CONTROLLER_NAME__', $className, $template);

        file_put_contents(DIR_ROOT . '/app/Controllers/' . $className . '.php', $template);
        fputs(STDOUT, "Controller created in /app/Controllers\n\n");
    }
}
