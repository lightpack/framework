<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Validation\Validator;

class ValidationProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->factory('validator', function ($container) {
            return new Validator;
        });

        $container->alias(Validator::class, 'validator');
    }
}
