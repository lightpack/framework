<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Validator\Validator;

class ValidationProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->factory('validator', function ($container) {
            $data = $container->get('request')->input();
            $data = array_merge($data, $container->get('request')->files()->get());

            return (new Validator)->setInput($container->get('request')->input());
        });

        $container->alias(Validator::class, 'validator');
    }
}
