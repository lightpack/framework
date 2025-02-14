<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Storage\LocalStorage;
use Lightpack\Storage\StorageInterface;

class StorageProvider implements ProviderInterface
{
    public function register(Container $container): void
    {
        $container->register('storage', function($container) {
            return new LocalStorage();
        });

        $container->alias(StorageInterface::class, 'storage');
    }
}
