<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\Views\TransformerView;
use Lightpack\File\File;

class CreateTransformer extends Command
{
    public function run()
    {
        $className = $this->args->argument(0);
        $force = $this->args->has('force');

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
        $displayPath = substr($directory, strlen(DIR_ROOT));

        if ($file->exists($filepath) && !$force) {
            $this->output->newline();
            $this->output->error("Transformer already exists: .{$displayPath}/{$className}.php");
            $this->output->newline();
            $this->output->line("Use --force to overwrite.");
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
        $this->output->success("✓ Transformer created: .{$displayPath}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
