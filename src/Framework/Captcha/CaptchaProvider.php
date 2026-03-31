<?php

namespace Lightpack\Captcha;

use Lightpack\Support\ProviderInterface;
use Lightpack\Container\Container;
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
