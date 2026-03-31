<?php

namespace Lightpack\Mail;

use Lightpack\Support\BaseManager;
use Lightpack\Container\Container;
use Lightpack\Mail\Drivers\SmtpDriver;
use Lightpack\Mail\Drivers\ArrayDriver;
use Lightpack\Mail\Drivers\LogDriver;
use Lightpack\Mail\Drivers\ResendDriver;

/**
 * Mail Manager - Manages multiple mail drivers
 * 
 * Allows registering custom drivers and switching between them per-mail
 */
class MailManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in mail drivers
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('smtp', function ($container) {
            return new SmtpDriver();
        });
        
        $this->register('resend', function ($container) {
            return new ResendDriver();
        });
        
        $this->register('array', function ($container) {
            return new ArrayDriver();
        });
        
        $this->register('log', function ($container) {
            return new LogDriver();
        });
    }
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = get_env('MAIL_DRIVER', 'smtp');
        $this->setDefaultDriver($default);
    }
    
    /**
     * Get mail driver instance
     */
    public function driver(?string $name = null): DriverInterface
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "Mail driver not found: {$name}";
    }
}
