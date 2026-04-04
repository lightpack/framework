<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\Command;
use Lightpack\Console\Views\RequestView;

class CreateRequest extends Command
{
    public function run(): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide a form request class name.");
            $this->output->newline();
            return self::FAILURE;
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
            $this->output->error("Invalid form request class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        $template = RequestView::getTemplate();
        $template = str_replace(
            ['__NAMESPACE__', '__REQUEST_NAME__'],
            [$namespace, $className],
            $template
        );

        $directory = substr($directory, strlen(DIR_ROOT));

        file_put_contents($filename . '.php', $template);
        $this->output->success("✓ Request created: .{$directory}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
