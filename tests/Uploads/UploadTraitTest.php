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

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $this->schema = new Schema($this->db);
        $this->databaseDriver = new DatabaseDriver($this->db);

        // create uploads table
        $this->schema->createTable('uploads', function(Table $table) {
            $table->id();
            $table->varchar('model_type');
            $table->column('model_id')->type('bigint')->attribute('unsigned');
            $table->varchar('collection')->default('default');
            $table->varchar('name');
            $table->varchar('file_name');
            $table->varchar('mime_type');
            $table->varchar('type', 25);
            $table->varchar('extension');
            $table->column('size')->type('bigint');
            $table->varchar('visibility', 25)->default('public');
            $table->column('meta')->type('json')->nullable();
            $table->timestamps();
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
        $this->schema->dropTable('uploads');
        $this->db = null;
    }
}