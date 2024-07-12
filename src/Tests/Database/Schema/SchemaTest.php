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
            $table->column('status')->type('varchar')->length(55);
            $table->column('sku')->type('varchar')->length(55);
            $table->index('status');
            $table->unique('sku');
        });

        // Query indexes
        $resultTitle = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'title'")->fetch();
        $resultSlug = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'slug'")->fetch();
        $resultStatus = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'status'")->fetch();
        $resultSku = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'sku'")->fetch();

        // Assert that the index is added
        $this->assertEquals('title', $resultTitle['Column_name']);
        $this->assertEquals('slug', $resultSlug['Column_name']);
        $this->assertEquals('status', $resultStatus['Column_name']);
        $this->assertEquals('sku', $resultSku['Column_name']);
    }

    public function testSchemaCanAddCompositeIndexWhenCreatingTableColumns()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('title')->type('varchar')->length(55);
            $table->column('status')->type('varchar')->length(55);
            $table->column('slug')->type('varchar')->length(55);
            $table->column('sku')->type('varchar')->length(55);
            $table->index(['title', 'status']);
            $table->unique(['slug', 'sku']);
        });

        // Query indexes
        $resultTitle = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'title'")->fetch();
        $resultStatus = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'status'")->fetch();
        $resultSlug = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'slug'")->fetch();
        $resultSku = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'sku'")->fetch();

        // Assert that the index is added
        $this->assertEquals('title', $resultTitle['Column_name']);
        $this->assertEquals('status', $resultStatus['Column_name']);
        $this->assertEquals('slug', $resultSlug['Column_name']);
        $this->assertEquals('sku', $resultSku['Column_name']);
    }


    public function testSchemaCanAlterTableAddCompositeIndex()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('title')->type('varchar')->length(55);
            $table->column('status')->type('varchar')->length(55);
            $table->column('slug')->type('varchar')->length(55);
            $table->column('sku')->type('varchar')->length(55);
        });

        // Add index
        $this->schema->alterTable('products')->index(['title', 'status']);
        $this->schema->alterTable('products')->unique(['slug', 'sku']);

        // Query indexes
        $resultTitle = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'title'")->fetch();
        $resultStatus = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'status'")->fetch();
        $resultSlug = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'slug'")->fetch();
        $resultSku = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'sku'")->fetch();

        // Assert that the index is added
        $this->assertEquals('title', $resultTitle['Column_name']);
        $this->assertEquals('status', $resultStatus['Column_name']);
        $this->assertEquals('slug', $resultSlug['Column_name']);
        $this->assertEquals('sku', $resultSku['Column_name']);
    }

    public function testSchemaCanAlterTableDropIndex()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('title')->type('varchar')->length(55)->index();
            $table->column('slug')->type('varchar')->length(55)->unique();
        });

        // Drop the index
        $this->schema->alterTable('products')->dropIndex('title_index');
        $this->schema->alterTable('products')->dropUnique('slug_unique');

        // Query indexes
        $resultTitle = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'title'")->fetch();
        $resultSlug = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'slug'")->fetch();

        // Assert that the index is dropped
        $this->assertFalse($resultTitle);
        $this->assertFalse($resultSlug);
    }

    public function testSchemaCanAlterTableDropCompositeIndex()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('title')->type('varchar')->length(55);
            $table->column('status')->type('varchar')->length(55);
            $table->column('slug')->type('varchar')->length(55);
            $table->column('sku')->type('varchar')->length(55);
            $table->index(['title', 'status']);
            $table->unique(['slug', 'sku']);
        });

        // Drop the index
        $this->schema->alterTable('products')->dropIndex('title_status_index');
        $this->schema->alterTable('products')->dropUnique('slug_sku_unique');

        // Query indexes
        $resultTitle = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'title'")->fetch();
        $resultStatus = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'status'")->fetch();
        $resultSlug = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'slug'")->fetch();
        $resultSku = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'sku'")->fetch();

        // Assert that the index is dropped
        $this->assertFalse($resultTitle);
        $this->assertFalse($resultStatus);
        $this->assertFalse($resultSlug);
        $this->assertFalse($resultSku);
    }

    public function testSchemaCanDropPrimaryKey()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
        });

        // First we need to modify the id column to int type to avoid issue: "there can be only one auto column and it must be defined as a key"
        $this->schema->alterTable('products')->modify(function (Table $table) {
            $table->column('id')->type('int');
        });

        $this->schema->alterTable('products')->dropPrimary();

        // Query primary key
        $result = $this->connection->query("SHOW INDEX FROM products WHERE Key_name = 'PRIMARY'")->fetch();

        // Assert that the primary key is dropped
        $this->assertFalse($result);
    }

    public function testSchemaCanDropPrimaryColumn()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('title')->type('varchar')->length(55);
        });

        // Drop the primary column
        $this->schema->alterTable('products')->dropColumn('id');

        // Query if column is dropped
        $result = $this->connection->query("SHOW COLUMNS FROM products WHERE Field = 'id'")->fetch();

        // Assert that the column is dropped
        $this->assertFalse($result);
    }

    public function testSchemaCanAddFullTextIndex()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('title')->type('text')->fullText();
            $table->column('description')->type('text');
            $table->fullText('description');
        });

        // Query indexes
        $resultTitle = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'title'")->fetch();
        $resultDescription = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'description'")->fetch();

        // Assert that the index is added
        $this->assertEquals('title', $resultTitle['Column_name']);
        $this->assertEquals('description', $resultDescription['Column_name']);
    }

    public function testSchemaCanDropFullTextIndex()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->column('id')->type('int')->increments();
            $table->column('title')->type('text')->fullText();
            $table->column('description')->type('text')->fullText();
        });

        // Drop the index
        $this->schema->alterTable('products')->dropFullText('title_fulltext', 'description_fulltext');

        // Query indexes
        $resultTitle = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'title'")->fetch();
        $resultDescription = $this->connection->query("SHOW INDEX FROM products WHERE Column_name = 'description'")->fetch();

        // Assert that the index is dropped
        $this->assertFalse($resultTitle);
        $this->assertFalse($resultDescription);
    }

    public function testSchemaInspectColumns()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->id();
            $table->varchar('title', 125);
            $table->varchar('email', 125)->nullable();
        });

        // Query columns
        $columns = $this->schema->inspectColumns('products');

        // Assert that the columns are created
        $this->assertEquals('id', $columns[0]);
        $this->assertEquals('title', $columns[1]);
        $this->assertEquals('email', $columns[2]);
    }

    public function testSchemaInspectColumn()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->id();
            $table->varchar('title', 125);
            $table->varchar('email', 125)->nullable();
        });

        // Query column
        $column = $this->schema->inspectColumn('products', 'title');

        // Assert that the column is created
        $this->assertEquals('title', $column['Field']);

        // test for null
        $column = $this->schema->inspectColumn('products', 'description');
        $this->assertNull($column);
    }

    public function testSchemaInspectIndexes()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->id();
            $table->varchar('title', 125)->index();
            $table->varchar('email', 125)->unique();
        });

        // Query indexes
        $indexes = $this->schema->inspectIndexes('products');

        // Assert that the indexes are created
        $this->assertCount(3, $indexes);
        $this->assertContains('PRIMARY', $indexes);
        $this->assertContains('title_index', $indexes);
        $this->assertContains('email_unique', $indexes);
    }

    public function testSchemaInspectIndex()
    {
        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->id();
            $table->varchar('title', 125)->index();
            $table->varchar('email', 125)->unique();
        });

        // Query index
        $index = $this->schema->inspectIndex('products', 'title_index');

        // Assert that the index is created
        $this->assertEquals('title_index', $index['Key_name']);

        // test for null
        $index = $this->schema->inspectIndex('products', 'email_index');
        $this->assertNull($index);
    }

    public function testSchemaInspectForeignKeys()
    {
        // Create categories table
        $this->schema->createTable('categories', function (Table $table) {
            $table->id();
            $table->varchar('title', 125);
        });

        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->id();
            $table->column('category_id')->type('bigint')->attribute('unsigned');
            $table->foreignKey('category_id')->references('id')->on('categories');
        });

        // Query foreign keys
        $foreignKeys = $this->schema->inspectForeignKeys('products');

        // Assert that the foreign keys are created
        $this->assertCount(1, $foreignKeys);
        $this->assertContains('products_ibfk_1', $foreignKeys[0]);
    }

    public function testSchemaInspectForeignKey()
    {
        // Create categories table
        $this->schema->createTable('categories', function (Table $table) {
            $table->id();
            $table->varchar('title', 125);
        });

        // Create products table
        $this->schema->createTable('products', function (Table $table) {
            $table->id();
            $table->column('category_id')->type('bigint')->attribute('unsigned');
            $table->foreignKey('category_id')->references('id')->on('categories');
        });

        // Query foreign key
        $foreignKey = $this->schema->inspectForeignKey('products', 'products_ibfk_1');

        // Assert that the foreign key is created
        $this->assertEquals('products_ibfk_1', $foreignKey['CONSTRAINT_NAME']);

        // test for null
        $foreignKey = $this->schema->inspectForeignKey('products', 'products_ibfk_2');
        $this->assertNull($foreignKey);
    }
}