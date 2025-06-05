<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Config\Config;
use Lightpack\Database\DB;
use Lightpack\Cache\Cache;
use Lightpack\Secrets\Secrets;
use Lightpack\Utils\Crypto;

class SecretsProvider
{
    public function register(Container $container)
    {
        $container->factory('secrets', function () use ($container) {
            $config = $container->get(Config::class);
            $db = $container->get(DB::class);
            $cache = $container->get(Cache::class);
            $cryptoKey = $config->get('app.secrets_key');

            if (!$cryptoKey) {
                throw new \RuntimeException('Secrets encryption key not configured.');
            }
            
            $crypto = new Crypto($cryptoKey);
            return new Secrets($db, $cache, $config, $crypto);
        });
    }
}
