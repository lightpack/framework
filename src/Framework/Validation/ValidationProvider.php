<?php

namespace Lightpack\Validation;

use Lightpack\Support\ProviderInterface;
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
