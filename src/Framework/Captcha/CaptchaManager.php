<?php

namespace Lightpack\Captcha;

use Lightpack\Manager\BaseManager;
use Lightpack\Container\Container;
use Lightpack\Captcha\NativeCaptcha;
use Lightpack\Captcha\GoogleReCaptcha;
use Lightpack\Captcha\CloudflareTurnstile;
use Lightpack\Captcha\NullCaptcha;

class CaptchaManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in captcha drivers
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('null', function ($container) {
            return new NullCaptcha($container->get('request'));
        });
        
        $this->register('native', function ($container) {
            $config = $container->get('config');
            $captcha = new NativeCaptcha(
                $container->get('request'),
                $container->get('session')
            );
            
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
        });
        
        $this->register('recaptcha', function ($container) {
            $config = $container->get('config');
            return new GoogleReCaptcha(
                $container->get('request'),
                $config->get('captcha.recaptcha.site_key'),
                $config->get('captcha.recaptcha.secret_key')
            );
        });
        
        $this->register('turnstile', function ($container) {
            $config = $container->get('config');
            return new CloudflareTurnstile(
                $container->get('request'),
                $config->get('captcha.turnstile.site_key'),
                $config->get('captcha.turnstile.secret_key')
            );
        });
    }
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = $this->container->get('config')->get('captcha.driver');
        $this->setDefaultDriver($default);
    }
    
    /**
     * Get captcha driver instance
     */
    public function driver(?string $name = null): CaptchaInterface
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "Captcha driver not found: {$name}";
    }
}
