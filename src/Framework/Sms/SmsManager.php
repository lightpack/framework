<?php

namespace Lightpack\Sms;

use Lightpack\Support\BaseManager;
use Lightpack\Container\Container;
use Lightpack\Sms\Providers\LogSmsProvider;
use Lightpack\Sms\Providers\NullSmsProvider;
use Lightpack\Sms\Providers\TwilioProvider;

class SmsManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in SMS drivers
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('null', function ($container) {
            return new Sms(new NullSmsProvider());
        });
        
        $this->register('log', function ($container) {
            return new Sms(new LogSmsProvider(
                $container->get('logger')
            ));
        });
        
        $this->register('twilio', function ($container) {
            $config = $container->get('config');
            return new Sms(new TwilioProvider(
                $container->get('logger'),
                $config->get('sms.twilio', [])
            ));
        });
    }
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = $this->container->get('config')->get('sms.provider', 'null');
        $this->setDefaultDriver($default);
    }
    
    /**
     * Get SMS driver instance
     */
    public function driver(?string $name = null): Sms
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "SMS driver not found: {$name}";
    }
}
