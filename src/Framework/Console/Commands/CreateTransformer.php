<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\Views\TransformerView;
use Lightpack\File\File;

class CreateTransformer extends Command
{
    public function run(): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide a transformer class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        $parts = explode('\\', trim($className, '/'));
        $namespace = 'App\Transformers';
        $directory = DIR_ROOT . '/app/Transformers';
        $file = new File;

        /**
         * This takes care if namespaced transformer is to be created.
         */
        if (count($parts) > 1) {
            $className = array_pop($parts);
            $namespace .= '\\' . implode('\\', $parts);
            $directory .= '/' . implode('/', $parts);
            $file->makeDir($directory);
        }

        $filepath = $directory . '/' . $className . '.php';
        $directory = substr($directory, strlen(DIR_ROOT));

        if ($file->exists($filepath)) {
            $this->output->error("{$className} already exists: .{$directory}/{$className}.php");
            $this->output->newline();
            return self::FAILURE;
        }

        $template = TransformerView::getTemplate();
        $template = str_replace(
            ['__NAMESPACE__', '__TRANSFORMER_NAME__'],
            [$namespace, $className],
            $template
        );

        $file->write($filepath, $template);
        $this->output->success("✓ Transformer created: .{$directory}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
