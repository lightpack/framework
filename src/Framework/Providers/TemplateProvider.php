<?php

namespace Lightpack\Providers;

use Lightpack\View\Template;
use Lightpack\Container\Container;

class TemplateProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('template', function ($container) {
            return new Template();
        });

        $container->alias(Template::class, 'template');
    }
}
