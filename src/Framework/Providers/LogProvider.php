<?php

namespace Lightpack\Providers;

use Psr\Log\LoggerInterface;
use Lightpack\Logger\Logger;
use Lightpack\Logger\Drivers\FileLogger;
use Lightpack\Logger\Drivers\NullLogger;
use Lightpack\Container\Container;

class LogProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('logger', function ($container) {
            $logDriver = new NullLogger;

            if ('file' === get_env('LOG_DRIVER')) {
                $logDriver = new FileLogger(
                    $container->get('config')->get('storage.logs') . '/logs.txt'
                );
            }

            return new Logger($logDriver);
        });

        $container->alias(Logger::class, 'logger');
        $container->alias(LoggerInterface::class, 'logger');
    }
}
