<?php

namespace Lightpack\Providers;

use Lightpack\Http\Response;
use Lightpack\Container\Container;
use Lightpack\Filters\Filter;

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
