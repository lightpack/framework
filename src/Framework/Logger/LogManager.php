<?php

namespace Lightpack\Logger;

use Lightpack\Manager\BaseManager;
use Lightpack\Container\Container;
use Lightpack\Logger\Drivers\FileLogger;
use Lightpack\Logger\Drivers\DailyFileLogger;
use Lightpack\Logger\Drivers\NullLogger;

class LogManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in log drivers
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('null', function ($container) {
            return new Logger(new NullLogger);
        });
        
        $this->register('file', function ($container) {
            $config = $container->get('config');
            $driver = new FileLogger(
                $config->get('logs.path', DIR_STORAGE . '/logs') . '/lightpack.log',
                $config->get('logs.max_file_size', 10 * 1024 * 1024), // 10mb
                $config->get('logs.max_log_files', 10)
            );
            
            return new Logger($driver);
        });
        
        $this->register('daily', function ($container) {
            $config = $container->get('config');
            $driver = new DailyFileLogger(
                $config->get('logs.path', DIR_STORAGE . '/logs'),
                $config->get('logs.days_to_keep', 7)
            );
            
            return new Logger($driver);
        });
    }
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = get_env('LOG_DRIVER', 'null');
        $this->setDefaultDriver($default);
    }
    
    /**
     * Get logger driver instance
     */
    public function driver(?string $name = null): Logger
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "Log driver not found: {$name}";
    }
}
