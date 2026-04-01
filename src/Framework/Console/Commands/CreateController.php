<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\BaseCommand;
use Lightpack\Console\Views\ControllerView;

class CreateController extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide a controller class name.");
            $this->output->newline();
            return 1;
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
            $this->output->error("Invalid controller class name.");
            $this->output->newline();
            return 1;
        }

        $template = ControllerView::getTemplate();
        $template = str_replace(
            ['__NAMESPACE__', '__CONTROLLER_NAME__'],
            [$namespace, $className],
            $template
        );

        $directory = substr($directory, strlen(DIR_ROOT));

        file_put_contents($filename . '.php', $template);
        $this->output->success("✓ Controller created: .{$directory}/{$className}.php");
        $this->output->newline();
        
        return 0;
    }
}
