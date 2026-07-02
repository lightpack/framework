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
                config('app.lang.default', 'en'),
                config('app.lang.path', DIR_ROOT . '/lang'),
                config('app.lang.fallback', 'en')
            );
        });

        $container->alias(Lang::class, 'lang');
    }
}
