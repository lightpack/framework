<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Config\Config;
use Lightpack\Database\DB;
use Lightpack\Secrets\Secrets;
use Lightpack\Utils\Crypto;

class SecretsProvider
{
    public function register(Container $container)
    {
        $container->factory('secrets', function () use ($container) {
            $db = $container->get(DB::class);
            $crypto = $container->get('crypto');

            return new Secrets($db, $crypto);
        });
    }
}
