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
                'null'      => $this->createNullCaptcha($request),
                'native'    => $this->createNativeCaptcha($request, $container->get('session'), $config),
                'recaptcha' => $this->createGoogleReCaptcha($request, $config),
                'turnstile' => $this->createCloudflareTurnstile($request, $config),
                default     => throw new \Exception("Unknown captcha driver: {$type}"),
            };
        });

        $container->alias(CaptchaInterface::class, 'captcha');
    }

    private function createNullCaptcha($request)
    {
        return new NullCaptcha($request);
    }

    private function createNativeCaptcha($request, $session, $config)
    {
        $captcha = new NativeCaptcha($request, $session);
        if ($font = $config->get('captcha.native.font')) {
            $captcha->font($font);
        }
        if ($width = $config->get('captcha.native.width')) {
            $captcha->width((int)$width);
        }
        if ($height = $config->get('captcha.native.height')) {
            $captcha->height((int)$height);
        }
        return $captcha;
    }

    private function createGoogleReCaptcha($request, $config)
    {
        return new GoogleReCaptcha(
            $request,
            $config->get('captcha.recaptcha.site_key'),
            $config->get('captcha.recaptcha.secret_key')
        );
    }

    private function createCloudflareTurnstile($request, $config)
    {
        return new CloudflareTurnstile(
            $request,
            $config->get('captcha.turnstile.site_key'),
            $config->get('captcha.turnstile.secret_key')
        );
    }
}
