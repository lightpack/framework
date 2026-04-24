<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\Command;
use Lightpack\Console\Views\ControllerView;

class CreateController extends Command
{
    public function run()
    {
        $className = $this->args->argument(0);
        $force = $this->args->has('force');

        if (null === $className) {
            $this->output->error("Please provide a controller class name.");
            $this->output->newline();
            return self::FAILURE;
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
            return self::FAILURE;
        }

        $filePath = $filename . '.php';
        $displayPath = substr($directory, strlen(DIR_ROOT));

        if (file_exists($filePath) && !$force) {
            $this->output->newline();
            $this->output->error("Controller already exists: .{$displayPath}/{$className}.php");
            $this->output->newline();
            $this->output->line("Use --force to overwrite.");
            $this->output->newline();
            return self::FAILURE;
        }

        $template = ControllerView::getTemplate();
        $template = str_replace(
            ['__NAMESPACE__', '__CONTROLLER_NAME__'],
            [$namespace, $className],
            $template
        );

        file_put_contents($filePath, $template);
        $this->output->success("✓ Controller created: .{$displayPath}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
