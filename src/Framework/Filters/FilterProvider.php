<?php

namespace Lightpack\Filters;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class FilterProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('filter', function ($container) {
            return new Filter(
                $container,
                $container->get('request'),
                $container->get('response')
            );
        });
    }
}
