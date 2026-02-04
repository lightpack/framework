<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Console\Views\ProviderView;

class CreateProvider implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $className = $arguments[0] ?? null;

        if (null === $className) {
            $message = "Please provide a class name for service provider.\n\n";
            fputs(STDERR, $message);
            return;
        }

        if (!preg_match('/^[\w]+$/', $className)) {
            $message = "Invalid service provider class name.\n\n";
            fputs(STDERR, $message);
            return;
        }

        $provider = strtolower(str_replace('Provider', '', $className));
        $binding = in_array('--instance', $arguments) ? 'factory' : 'register';
        $directory = './app/Providers';
        $template = ProviderView::getTemplate();
        $template = str_replace(
            ['__PROVIDER_NAME__', '__PROVIDER_ALIAS__', '__PROVIDER_BINDING__'], 
            [$className, $provider, $binding], 
            $template
        );

        file_put_contents(DIR_ROOT . '/app/Providers/' . $className . '.php', $template);
        fputs(STDOUT, "✓ Provider created: {$directory}/{$className}.php\n\n");
    }
}
