<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;
use Lightpack\Console\Views\ProviderView;

class CreateProvider extends Command
{
    public function run(): int
    {
        $className = $this->args->argument(0);
        $force = $this->args->has('force');

        if (null === $className) {
            $this->output->error("Please provide a class name for service provider.");
            $this->output->newline();
            return self::FAILURE;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid service provider class name.");
            $this->output->newline();
            return self::FAILURE;
        }

        $provider = strtolower(str_replace('Provider', '', $className));
        $binding = $this->args->has('instance') ? 'factory' : 'register';
        $directory = './app/Providers';
        $filePath = DIR_ROOT . '/app/Providers/' . $className . '.php';

        if (file_exists($filePath) && !$force) {
            $this->output->newline();
            $this->output->error("Provider already exists: {$directory}/{$className}.php");
            $this->output->newline();
            $this->output->line("Use --force to overwrite.");
            $this->output->newline();
            return self::FAILURE;
        }

        $template = ProviderView::getTemplate();
        $template = str_replace(
            ['__PROVIDER_NAME__', '__PROVIDER_ALIAS__', '__PROVIDER_BINDING__'], 
            [$className, $provider, $binding], 
            $template
        );

        file_put_contents($filePath, $template);
        $this->output->success("✓ Provider created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return self::SUCCESS;
    }
}
