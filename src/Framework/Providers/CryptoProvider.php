<?php

namespace Lightpack\Providers;

use Lightpack\Config\Config;
use Lightpack\Container\Container;
use Lightpack\Utils\Crypto;

class CryptoProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('crypto', function ($container) {
            $config = $container->get('config');
            $this->ensureAppKeyIsSet($config);

            return new Crypto($config->get('app.key'));
        });

        $container->alias(Crypto::class, 'crypto');
    }

    private function ensureAppKeyIsSet(Config $config)
    {
        if (!$config->get('app.key')) {
            throw new \Exception('Encryption key has not been set in .env: APP_KEY');
        }
    }
}
