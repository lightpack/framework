<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Cache\Drivers\DatabaseDriver;
use Lightpack\Container\Container;
use Lightpack\Database\DB;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;

final class DatabaseDriverTest extends TestCase
{
    private ?DB $db;
    private Schema $schema;
    private DatabaseDriver $databaseDriver;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $this->schema = new Schema($this->db);
        $this->databaseDriver = new DatabaseDriver($this->db);

        // create cache table
        $this->schema->createTable('cache', function(Table $table) {
            $table->varchar('`key`', 255)->primary();
            $table->column('value')->type('longtext');
            $table->column('expires_at')->type('int')->attribute('UNSIGNED');
            $table->index('expires_at', 'idx_cache_expiry');
        });

        // Configure container
        $container = Container::getInstance();

        $container->register('db', function () {
            return $this->db;
        });

        $container->register('logger', function () {
            return new class {
                public function error($message, $context = []) {}
                public function critical($message, $context = []) {}
            };
        });
    }

    public function tearDown(): void
    {
        $this->schema->dropTable('cache');
        $this->db = null;
    }

    public function testCanStoreAndRetrieveItem()
    {
        $this->databaseDriver->set('name', 'Lightpack', time() + 60);
        $this->assertTrue($this->databaseDriver->has('name'));
        $this->assertEquals('Lightpack', $this->databaseDriver->get('name'));
    }

    public function testCanDeleteItem()
    {
        $this->databaseDriver->set('name', 'Lightpack', time() + 60);
        $this->assertTrue($this->databaseDriver->has('name'));
        
        $this->databaseDriver->delete('name');
        $this->assertFalse($this->databaseDriver->has('name'));
    }

    public function testExpiredItemReturnsNull()
    {
        $this->databaseDriver->set('expired', 'value', time() - 1);
        $this->assertNull($this->databaseDriver->get('expired'));
    }

    public function testPreserveTtlKeepsOriginalExpiry()
    {
        $expiry = time() + 60;
        $this->databaseDriver->set('key', 'original', $expiry);
        
        // Update with preserveTtl
        $this->databaseDriver->set('key', 'updated', time() + 3600, true);
        
        // Get raw entry to check expiry
        $entry = $this->db->table('cache')->where('key', 'key')->one();
        $this->assertEquals($expiry, $entry->expires_at);
    }

    public function testFlushClearsAllItems()
    {
        $this->databaseDriver->set('key1', 'value1', time() + 60);
        $this->databaseDriver->set('key2', 'value2', time() + 60);
        
        $this->databaseDriver->flush();
        
        $this->assertFalse($this->databaseDriver->has('key1'));
        $this->assertFalse($this->databaseDriver->has('key2'));
    }

    public function testCanStoreAndRetrieveComplexData()
    {
        $data = [
            'array' => [1, 2, 3],
            'object' => (object)['foo' => 'bar'],
            'null' => null,
            'bool' => true,
        ];
        
        $this->databaseDriver->set('complex', $data, time() + 60);
        $retrieved = $this->databaseDriver->get('complex');
        
        $this->assertEquals($data, $retrieved);
    }

    public function testNonExistentKeyReturnsNull()
    {
        $this->assertNull($this->databaseDriver->get('nonexistent'));
    }

    public function testHasReturnsFalseForExpiredItems()
    {
        $this->databaseDriver->set('expired', 'value', time() - 1);
        $this->assertFalse($this->databaseDriver->has('expired'));
    }
}