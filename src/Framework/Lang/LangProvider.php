<?php

namespace Lightpack\Lang;

use Lightpack\Container\Container;
use Lightpack\Support\ProviderInterface;

class LangProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('lang', function ($container) {
            return new Lang(
                config('lang.default', 'en'),
                config('lang.path', DIR_ROOT . '/app/Lang'),
                config('lang.fallback', 'en')
            );
        });

        $container->alias(Lang::class, 'lang');
    }
}
