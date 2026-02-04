<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Console\Views\TransformerView;
use Lightpack\File\File;

class CreateTransformer implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide a transformer class name.\n\n";
            fputs(STDERR, $message);
            return;
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
            $message = "{$className} already exists: .{$directory}/{$className}.php\n\n";
            fputs(STDERR, $message);
            return;
        }

        $template = TransformerView::getTemplate();
        $template = str_replace(
            ['__NAMESPACE__', '__TRANSFORMER_NAME__'],
            [$namespace, $className],
            $template
        );

        $file->write($filepath, $template);
        fputs(STDOUT, "✓ Transformer created: .{$directory}/{$className}.php\n\n");
    }
}
