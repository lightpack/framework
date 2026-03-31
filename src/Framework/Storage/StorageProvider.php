<?php

namespace Lightpack\Storage;

use Lightpack\Support\ProviderInterface;
use Lightpack\Container\Container;
use Lightpack\Storage\StorageInterface;
use Lightpack\Storage\StorageManager;

class StorageProvider implements ProviderInterface
{
    public function register(Container $container): void
    {
        $container->register('storage.manager', function ($container) {
            return new StorageManager($container);
        });

        $container->register('storage', function ($container) {
            return $container->get('storage.manager')->driver();
        });

        $container->alias(StorageManager::class, 'storage.manager');
        $container->alias(StorageInterface::class, 'storage');
    }
}
