<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\BaseCommand;
use Lightpack\Console\Views\ProviderView;

class CreateProvider extends BaseCommand
{
    public function run(array $arguments = []): int
    {
        $className = $this->args->argument(0);

        if (null === $className) {
            $this->output->error("Please provide a class name for service provider.");
            $this->output->newline();
            return 1;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $this->output->error("Invalid service provider class name.");
            $this->output->newline();
            return 1;
        }

        $provider = strtolower(str_replace('Provider', '', $className));
        $binding = $this->args->has('instance') ? 'factory' : 'register';
        $directory = './app/Providers';
        $template = ProviderView::getTemplate();
        $template = str_replace(
            ['__PROVIDER_NAME__', '__PROVIDER_ALIAS__', '__PROVIDER_BINDING__'], 
            [$className, $provider, $binding], 
            $template
        );

        file_put_contents(DIR_ROOT . '/app/Providers/' . $className . '.php', $template);
        $this->output->success("✓ Provider created: {$directory}/{$className}.php");
        $this->output->newline();
        
        return 0;
    }
}
