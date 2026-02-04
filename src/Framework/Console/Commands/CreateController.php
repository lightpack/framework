<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\CommandInterface;
use Lightpack\Console\Views\ControllerView;

class CreateController implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide a controller class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $parts = explode('\\', trim($className, '/'));
        $namespace = 'App\Controllers';
        $directory = DIR_ROOT . '/app/Controllers';

        /**
         * This takes care if namespaced controller is to be created.
         */
        if (count($parts) > 1) {
            $className = array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
            $directory .= '/' . implode('/', $parts);
            (new File)->makeDir($directory);
        }

        $filename = $directory . '/' . $className;

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid controller class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = ControllerView::getTemplate();
        $template = str_replace(
            ['__NAMESPACE__', '__CONTROLLER_NAME__'],
            [$namespace, $className],
            $template
        );

        $directory = substr($directory, strlen(DIR_ROOT));

        file_put_contents($filename . '.php', $template);
        fputs(STDOUT, "✓ Controller created: .{$directory}/{$className}.php\n\n");
    }
}
