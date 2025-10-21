<?php

namespace Lightpack\Providers;

use Lightpack\View\Template;
use Lightpack\Container\Container;

class TemplateProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('template', function ($container) {
            // Use DIR_VIEWS constant if defined, otherwise null (will use default)
            $viewsPath = defined('DIR_VIEWS') ? DIR_VIEWS : null;
            return new Template($viewsPath);
        });

        $container->alias(Template::class, 'template');
    }
}
