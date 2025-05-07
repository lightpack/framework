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
            $request = $container->get('request');
            $config = $container->get('config');
            $type = $config->get('captcha.driver');

            return match ($type) {
                'null'      => new NullCaptcha($request),
                'native'    => new NativeCaptcha($request, $container->get('session')),
                'recaptcha' => new GoogleReCaptcha($request, $config->get('captcha.recaptcha.site_key'), $config->get('captcha.recaptcha.secret_key')),
                'turnstile' => new CloudflareTurnstile($request, $config->get('captcha.turnstile.site_key'), $config->get('captcha.turnstile.secret_key')),
                default     => throw new \Exception("Unknown captcha driver: {$type}"),
            };
        });

        $container->alias(CaptchaInterface::class, 'captcha');
    }
}
