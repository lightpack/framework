<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Table;
use Lightpack\Database\Schema\Schema;

final class SchemaTest extends TestCase
{
    /** @var \Lightpack\Database\DB */
    private $connection;

    /** @var \Lightpack\Database\Schema\Schema */
    private $schema;

    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';

        $this->connection = new Lightpack\Database\Adapters\Mysql($config);

        $this->schema = new Schema($this->connection);
    }

    public function tearDown(): void
    {
        $this->connection->query("SET FOREIGN_KEY_CHECKS = 0");
        $this->schema->dropTable('products');
        $this->schema->dropTable('categories');
        $this->connection->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function testSchemaCanCreateTable()
    {
        $this->schema->createTable('products', function (Table $table) {
            $table->id();
            $table->varchar('title', 125);
            $table->varchar('email', 125)->nullable();
        });

        $this->assertTrue(in_array('products', $this->schema->inspectTables()));
    }

    public function testSchemaCanAlterTableAddColumn()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
        });

        // Add new column
        $table = new Table('products', $this->connection);
        $table->alterContext()->add(function(Table $table) {
            $table->column('description')->type('text');
        });

        $this->assertTrue(in_array('description', $this->schema->inspectColumns('products')));
    }

    public function testSchemaCanAlterTableModifyColumn()
    {
        // First drop the table if exists
        $this->schema->dropTable('products');

        // First create the table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('description')->type('text');
        });

        // Now lets modify the description column
        $table = new Table('products', $this->connection);

        $table->alterContext()->modify(function(Table $table) {
            $table->column('description')->type('varchar')->length(150);
        });

        // If column modified successfully, we should get its type 
        $descriptionColumnInfo = $this->schema->inspectColumn('products', 'description');

        $this->assertEquals($descriptionColumnInfo['Type'], 'varchar(150)');
    }

    public function testSchemaCanTruncateTable()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
        });

        // Truncate the table
        $this->schema->truncateTable('products');

        $count = $this->connection->query("SELECT COUNT(*) AS count FROM products")->fetch();

        $this->assertEquals(0, $count['count']);
    }

    public function testSchemaCanDropTable()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
        });

        // Drop the table
        $this->schema->dropTable('products');

        $this->assertFalse(in_array('products', $this->schema->inspectTables()));
    }

    public function testSchemaCanAddForeignKey()
    {
        // Create categories table
        $this->schema->createTable('categories', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('title')->type('varchar')->length(55);
        });

        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('category_id')->type('int');
            $table->column('title')->type('varchar')->length(55);
            $table->foreignKey('category_id')->references('id')->on('categories');
        });

        $this->assertTrue(in_array('products', $this->schema->inspectTables()));
    }
}
