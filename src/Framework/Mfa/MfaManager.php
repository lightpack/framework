<?php

namespace Lightpack\Mfa;

use Lightpack\Support\BaseManager;
use Lightpack\Container\Container;
use Lightpack\Mfa\Drivers\NullDriver;
use Lightpack\Mfa\Drivers\EmailDriver;
use Lightpack\Mfa\Drivers\SmsDriver;
use Lightpack\Mfa\Drivers\BackupCodeDriver;
use Lightpack\Mfa\Drivers\TotpDriver;
use Lightpack\Utils\Otp;

class MfaManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in MFA drivers (factors)
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('null', function ($container) {
            return new NullDriver();
        });
        
        $this->register('email', function ($container) {
            return new EmailDriver(
                $container->get('cache'),
                $container->get('config'),
                new Otp()
            );
        });
        
        $this->register('sms', function ($container) {
            return new SmsDriver(
                $container->get('cache'),
                $container->get('config'),
                new Otp(),
                $container->get('sms')
            );
        });
        
        $this->register('totp', function ($container) {
            return new TotpDriver();
        });
        
        $this->register('backup_code', function ($container) {
            return new BackupCodeDriver();
        });
    }
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = $this->container->get('config')->get('mfa.default');
        if ($default) {
            $this->setDefaultDriver($default);
        }
    }
    
    /**
     * Get MFA driver (factor) instance
     */
    public function driver(?string $name = null): MfaInterface
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    /**
     * Get all registered driver names
     */
    public function getDriverNames(): array
    {
        return array_keys($this->factories);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "MFA driver not found: {$name}";
    }
}
