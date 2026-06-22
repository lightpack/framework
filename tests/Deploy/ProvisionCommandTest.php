<?php

declare(strict_types=1);

use Lightpack\Deploy\Commands\ProvisionCommand;
use PHPUnit\Framework\TestCase;

final class ProvisionCommandTest extends TestCase
{
    public function testGatherParamsWithAllCliFlags(): void
    {
        $args = [
            '--init-user=root',
            '--php=8.4',
            '--db-name=myapp',
            '--db-user=myapp',
            '--timezone=America/New_York',
        ];
        $command = new ProvisionCommand($args);

        $method = new \ReflectionMethod($command, 'gatherParams');
        $method->setAccessible(true);

        $envConfig = ['host' => '1.2.3.4', 'path' => '/var/www/myapp'];
        $params = $method->invoke($command, 'production', $envConfig);

        $this->assertEquals('root', $params['init_user']);
        $this->assertEquals('8.4', $params['php_version']);
        $this->assertEquals('myapp', $params['db_name']);
        $this->assertEquals('myapp', $params['db_user']);
        $this->assertEquals('America/New_York', $params['timezone']);
        $this->assertEquals('production', $params['name']);
    }

    public function testGatherParamsDerivesDefaultsFromPath(): void
    {
        $args = [
            '--init-user=root',
            '--php=8.3',
            '--db-name=shop_app',
            '--db-user=shop_app',
            '--timezone=UTC',
        ];
        $command = new ProvisionCommand($args);

        $method = new \ReflectionMethod($command, 'gatherParams');
        $method->setAccessible(true);

        $envConfig = ['host' => '1.2.3.4', 'path' => '/var/www/shop-app'];
        $params = $method->invoke($command, 'production', $envConfig);

        $this->assertEquals('shop_app', $params['db_name']);
        $this->assertEquals('shop_app', $params['db_user']);
    }
}
