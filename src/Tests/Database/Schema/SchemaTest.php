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
        $this->schema->dropTable('items'); // used in testSchemaCanRenameTable
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
        $this->schema->alterTable('products')->add(function (Table $table) {
            $table->column('description')->type('text');
        });
        
        // Assert that the column was added
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
        $this->schema->alterTable('products')->modify(function (Table $table) {
            $table->column('description')->type('varchar')->length(150);
        });

        // Assert if column modified successfully, we should get its type 
        $this->assertEquals('varchar(150)', $this->schema->inspectColumn('products', 'description')['Type']);
    }

    public function testSchemaCanTruncateTable()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
        });

        // Truncate the table
        $this->schema->truncateTable('products');

        // Assert that the table is empty
        $this->assertEquals(0, $this->connection->query("SELECT COUNT(*) AS count FROM products")->fetch()['count']);
    }

    public function testSchemaCanDropTable()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
        });

        // Drop the table
        $this->schema->dropTable('products');

        // Assert that the table is dropped
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

        // Query if foreign key is added
        $result = $this->connection->query("SHOW CREATE TABLE products")->fetch();
        $this->assertStringContainsString('FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)', $result['Create Table']);

        // Query created foreign key name
        $result = $this->connection->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'products' AND COLUMN_NAME = 'category_id'")->fetch();
        $this->assertEquals('products_ibfk_1', $result['CONSTRAINT_NAME']);
    }

    public function testSchemaCanDropForeignKey()
    {
        // Create categories table
        $this->schema->createTable('categories', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('title')->type('varchar')->length(55);
        });

        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('child_id')->type('int');
            $table->column('category_id')->type('int');
            $table->column('title')->type('varchar')->length(55);
            $table->foreignKey('category_id')->references('id')->on('categories');
            $table->foreignKey('child_id')->references('id')->on('products');
        });

        // Drop the foreign key
        $this->schema->alterTable('products')->dropForeign('products_ibfk_1', 'products_ibfk_2');

        // Assert that the foreign key is dropped
        $result = $this->connection->query("SHOW CREATE TABLE products")->fetch();
        $this->assertStringNotContainsString('FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)', $result['Create Table']);
        $this->assertStringNotContainsString('FOREIGN KEY (`child_id`) REFERENCES `products` (`id`)', $result['Create Table']);
    }

    public function testSchemaCanRenameTable()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
        });

        // Rename the table
        $this->schema->renameTable('products', 'items');

        // Assert that the table is renamed
        $this->assertFalse(in_array('products', $this->schema->inspectTables()));
        $this->assertTrue(in_array('items', $this->schema->inspectTables()));
    }

    public function testSchemaCanRenameColumn()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('title')->type('varchar')->length(55);
        });

        // Rename the column
        $this->schema->alterTable('products')->renameColumn('title', 'name');

        // Assert that the column is renamed
        $this->assertFalse(in_array('title', $this->schema->inspectColumns('products')));
        $this->assertTrue(in_array('name', $this->schema->inspectColumns('products')));
    }

    public function testSchemaCanAddIndexWhenCreatingTable()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('title')->type('varchar')->length(55)->index();
            $table->column('slug')->type('varchar')->length(55)->unique();
        });

        // Assert that the index is added
        $result = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'title'")->fetch();
        $this->assertEquals('title', $result['Column_name']);

        // Assert that the unique index is added
        $result = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'slug'")->fetch();
        $this->assertEquals('slug', $result['Column_name']);
    }
}