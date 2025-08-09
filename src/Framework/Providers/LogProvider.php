<?php

namespace Lightpack\Providers;

use Lightpack\Logger\Logger;
use Lightpack\Logger\ILogger;
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
                    $container->get('config')->get('logs.path', DIR_STORAGE . '/logs') . '/lightpack.log',
                    $container->get('config')->get('logs.max_file_size', 10 * 1024 * 1024), // 10mb
                    $container->get('config')->get('logs.max_log_files', 10),
                );
            }

            return new Logger($logDriver);
        });

        $container->alias(Logger::class, 'logger');
        $container->alias(ILogger::class, 'logger');
    }
}
