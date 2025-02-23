<?php

namespace Lightpack\Providers;

use Exception;
use Lightpack\Container\Container;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Database\DB;

class DatabaseProvider implements ProviderInterface
{
    public function register(Container $container)
    {
        $container->register('db', function ($container) {
            $config = $container->get('config');

            if ('mysql' === $config->get('db.driver')) {
                return $this->mysql($config);
            }

            $this->throwException($config);
        });

        $container->alias(DB::class, 'db');
    }

    protected function mysql($config)
    {
        return new Mysql([
            'host'      => $config->get('db.mysql.host'),
            'port'      => $config->get('db.mysql.port'),
            'username'  => $config->get('db.mysql.username'),
            'password'  => $config->get('db.mysql.password'),
            'database'  => $config->get('db.mysql.database'),
            'options'   => $config->get('db.mysql.options'),
        ]);
    }

    protected function throwException($config)
    {
        throw new Exception(
            'Only MySQL database is supported. Unsupported driver type: ' .
            $config->get('db.driver')
        );
    }
}
