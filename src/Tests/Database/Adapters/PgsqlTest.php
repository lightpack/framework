<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PgsqlTest extends TestCase
{
    private $config = [];

    public function setUp(): void
    {
        $this->config = require __DIR__ . '/../tmp/pgsql.config.php';
    }

    public function  testConnectionException()
    {
        $this->expectException(\Exception::class);
        $this->config['username'] = 'unknown';
        $connection = new \Lightpack\Database\Adapters\Pgsql($this->config);
    }
    
    public function testCanCreateConnectionInstance()
    {
        $connection = new \Lightpack\Database\Adapters\Pgsql($this->config);
        $this->assertInstanceOf(\Lightpack\Database\Pdo::class, $connection);
    }
}