<?php

namespace Lightpack\Validation;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

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
