<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\CommandInterface;
use Lightpack\Console\Views\RequestView;

class CreateRequest implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide a form request class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $parts = explode('\\', trim($className, '/'));
        $namespace = 'App\Requests';
        $directory = DIR_ROOT . '/app/Requests';

        // Make directory if not exists
        (new File)->makeDir($directory);

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
            $message = "Invalid form request class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = RequestView::getTemplate();
        $template = str_replace(
            ['__NAMESPACE__', '__REQUEST_NAME__'],
            [$namespace, $className],
            $template
        );

        $directory = substr($directory, strlen(DIR_ROOT));

        file_put_contents($filename . '.php', $template);
        fputs(STDOUT, "✓ request created: .{$directory}/{$className}.php\n\n");
    }
}
