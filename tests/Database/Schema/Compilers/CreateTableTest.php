<?php

use Lightpack\Database\Schema\Column;
use Lightpack\Database\Schema\Compilers\CreateTable;
use Lightpack\Database\Schema\Compilers\ModifyColumn;
use Lightpack\Database\Schema\Compilers\RenameColumn;
use Lightpack\Database\Schema\Table;
use PHPUnit\Framework\TestCase;

final class CreateTableTest extends TestCase
{
    /** @var \Lightpack\Database\DB */
    private $connection;

    public function setUp(): void
    {
        $config = require __DIR__ . '/../../tmp/mysql.config.php';

        $this->connection = new Lightpack\Database\Adapters\Mysql($config);
    }

    public function tearDown(): void
    {
        $this->connection = null;
    }

    public function testCompilerCanCreateTable(): void
    {
        $table = new Table('products', $this->connection);

        $table->column('id')->type('int')->increments()->primary();
        
        $sql = (new CreateTable)->compile($table);
        
        $expected = 'CREATE TABLE IF NOT EXISTS products (`id` INT AUTO_INCREMENT NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        $this->assertTrue($expected === $sql, 'Output: ' . $sql);
    }

    public function testCompilerCanAddForeignKey(): void
    {
        $table = new Table('products', $this->connection);

        $table->column('id')->type('int')->increments()->primary();
        $table->column('category_id')->type('int');
        $table->column('title')->type('varchar')->length(55);
        $table->column('description')->type('varchar')->length(55)->nullable();
        $table->foreignKey('category_id')->references('id')->on('categories');
        
        $sql = (new CreateTable)->compile($table);
        
        $expected = 'CREATE TABLE IF NOT EXISTS products (`id` INT AUTO_INCREMENT NOT NULL, `category_id` INT NOT NULL, `title` VARCHAR(55) NOT NULL, `description` VARCHAR(55) NULL, PRIMARY KEY (`id`), FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanRenameColumnSql()
    {
        $sql = (new RenameColumn)->compile('products', 'title', 'heading');

        $expected = 'ALTER TABLE products RENAME COLUMN title TO heading;';

        $this->assertEquals($expected, $sql);
    }

    public function testcompilerCanChangeColumnSql()
    {
        $table = new Table('products', $this->connection);

        $table->column('id')->type('int')->increments()->index(Column::INDEX_PRIMARY);
        
        $sql = (new ModifyColumn)->compile($table);
        
        $expected = 'ALTER TABLE products CHANGE id `id` INT AUTO_INCREMENT NOT NULL;';

        $this->assertEquals($expected, $sql);
    }
}
