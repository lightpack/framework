<?php

namespace Lightpack\Console\Commands;

use Lightpack\File\File;
use Lightpack\Console\Command;
use Lightpack\Console\Views\FactoryView;

class CreateFactory extends Command
{
    public function run()
    {
        $className = $this->args->argument(0);
        $force = $this->args->has('force');
        $model = $this->args->get('model');

        if (null === $className) {
            $this->output->error("Please provide the factory class name.");

            return self::FAILURE;
        }

        if (! preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid factory class name.");

            return self::FAILURE;
        }

        $directory = './database/factories';
        $filePath = DIR_ROOT . '/database/factories/' . $className . '.php';

        if (file_exists($filePath) && ! $force) {
            $this->output->newline();
            $this->output->error("Factory already exists: {$directory}/{$className}.php");
            $this->output->line("Use --force to overwrite.");
            $this->output->newline();

            return self::FAILURE;
        }

        if (! is_dir(DIR_ROOT . '/database/factories')) {
            (new File)->makeDir(DIR_ROOT . '/database/factories');
        }

        if ($model) {
            $template = FactoryView::getModelFactoryTemplate();
            $modelClass = $this->resolveModelClass($model);
            $modelShort = $this->resolveModelShort($model);
            $template = str_replace(
                ['__FACTORY_NAME__', '__MODEL_CLASS__', '__MODEL_SHORT__'],
                [$className, $modelClass, $modelShort],
                $template
            );
        } else {
            $template = FactoryView::getTemplate();
            $template = str_replace('__FACTORY_NAME__', $className, $template);
        }

        file_put_contents($filePath, $template);
        $this->output->success("✓ Factory created: {$directory}/{$className}.php");

        return self::SUCCESS;
    }

    private function resolveModelClass(string $model): string
    {
        $parts = explode('/', str_replace('\\', '/', $model));
        $shortName = array_pop($parts);

        return 'App\\Models\\' . $shortName;
    }

    private function resolveModelShort(string $model): string
    {
        $model = str_replace('/', '\\', $model);
        $parts = explode('\\', $model);

        return array_pop($parts);
    }
}
