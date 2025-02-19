<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Validation\Validator;

class ValidationProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->factory('validator', function ($container) {
            $data = array_merge($container->get('request')->input(), $_FILES);

            return (new Validator)->setInput($data);
        });

        $container->alias(Validator::class, 'validator');
    }
}
