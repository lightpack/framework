<?php

namespace Lightpack\Logger;

use Lightpack\Support\ProviderInterface;
use Lightpack\Logger\Logger;
use Lightpack\Logger\LoggerInterface;
use Lightpack\Logger\LogManager;
use Lightpack\Container\Container;

class LogProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('logger.manager', function ($container) {
            return new LogManager($container);
        });

        $container->register('logger', function ($container) {
            return $container->get('logger.manager')->driver();
        });

        $container->alias(LogManager::class, 'logger.manager');
        $container->alias(Logger::class, 'logger');
        $container->alias(LoggerInterface::class, 'logger');
    }
}
