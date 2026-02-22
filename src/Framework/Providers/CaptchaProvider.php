<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;
use Lightpack\Captcha\CaptchaInterface;
use Lightpack\Captcha\CaptchaManager;

class CaptchaProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('captcha.manager', function ($container) {
            return new CaptchaManager($container);
        });

        $container->register('captcha', function ($container) {
            return $container->get('captcha.manager')->driver();
        });

        $container->alias(CaptchaManager::class, 'captcha.manager');
        $container->alias(CaptchaInterface::class, 'captcha');
    }
}
