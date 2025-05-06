<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Providers\ProviderInterface;
use Lightpack\Captcha\CaptchaInterface;
use Lightpack\Captcha\NativeCaptcha;
use Lightpack\Captcha\GoogleReCaptcha;
use Lightpack\Captcha\CloudflareTurnstile;
use Lightpack\Captcha\NullCaptcha;

class CaptchaProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('captcha', function ($container) {
            $config = $container->get('config');
            $type = $config->get('captcha.driver');

            return match ($type) {
                'null' => new NullCaptcha(),
                'google' => new GoogleReCaptcha(
                    $config->get('captcha.google.site_key'),
                    $config->get('captcha.google.secret_key')
                ),
                'cloudflare' => new CloudflareTurnstile(
                    $config->get('captcha.cloudflare.site_key'),
                    $config->get('captcha.cloudflare.secret_key')
                ),
                'native' => (new NativeCaptcha($container->get('session')))
                    ->font($config->get('captcha.native.font'))
                    ->width($config->get('captcha.native.width'))
                    ->height($config->get('captcha.native.height')),
                default => throw new \Exception('Unknown captcha driver: ' . $type),
            };
        });

        $container->alias(CaptchaInterface::class, 'captcha');
    }
}
