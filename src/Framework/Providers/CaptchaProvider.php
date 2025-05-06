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
                'recaptcha' => new GoogleReCaptcha($request, $config->get('recaptcha.site_key'), $config->get('recaptcha.secret_key')),
                'turnstile' => new CloudflareTurnstile($request, $config->get('turnstile.site_key'), $config->get('turnstile.secret_key')),
                default     => new NullCaptcha($request),
            };
        });

        $container->alias(CaptchaInterface::class, 'captcha');
    }
}
