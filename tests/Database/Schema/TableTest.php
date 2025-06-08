<?php

use Lightpack\Database\Schema\Column;
use Lightpack\Database\Schema\Compilers\CreateTable;
use Lightpack\Database\Schema\Compilers\ModifyColumn;
use Lightpack\Database\Schema\Compilers\RenameColumn;
use Lightpack\Database\Schema\Table;
use PHPUnit\Framework\TestCase;

final class TableTest extends TestCase
{
    /** @var \Lightpack\Database\DB */
    private $connection;

    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';

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

    public function testCompilerCanCreateCharColumn()
    {
        $table = new Table('test', $this->connection);
        $table->char('flag', 2);
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`flag` CHAR(2) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateYearColumn()
    {
        $table = new Table('test', $this->connection);
        $table->year('graduation_year');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`graduation_year` YEAR NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateTinytextColumn()
    {
        $table = new Table('test', $this->connection);
        $table->tinytext('shortnote');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`shortnote` TINYTEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateMediumtextColumn()
    {
        $table = new Table('test', $this->connection);
        $table->mediumtext('content');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`content` MEDIUMTEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateLongtextColumn()
    {
        $table = new Table('test', $this->connection);
        $table->longtext('bigcontent');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`bigcontent` LONGTEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateBigintColumn()
    {
        $table = new Table('test', $this->connection);
        $table->bigint('bigid');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`bigid` BIGINT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateSmallintColumn()
    {
        $table = new Table('test', $this->connection);
        $table->smallint('smallid');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`smallid` SMALLINT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateTinyintColumn()
    {
        $table = new Table('test', $this->connection);
        $table->tinyint('tinyflag');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`tinyflag` TINYINT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateDateColumn()
    {
        $table = new Table('test', $this->connection);
        $table->date('published_on');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`published_on` DATE NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateTimeColumn()
    {
        $table = new Table('test', $this->connection);
        $table->time('alarm');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`alarm` TIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateTimestampColumn()
    {
        $table = new Table('test', $this->connection);
        $table->timestamp('created_at');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`created_at` TIMESTAMP NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateJsonColumn()
    {
        $table = new Table('test', $this->connection);
        $table->json('meta');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`meta` JSON NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateIpAddressColumn()
    {
        $table = new Table('test', $this->connection);
        $table->ipAddress('ip');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`ip` VARCHAR(45) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateMacAddressColumn()
    {
        $table = new Table('test', $this->connection);
        $table->macAddress('mac');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`mac` VARCHAR(17) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }

    public function testCompilerCanCreateMorphsColumns()
    {
        $table = new Table('test', $this->connection);
        $table->morphs('attachable');
        $sql = (new CreateTable)->compile($table);
        $expected = 'CREATE TABLE IF NOT EXISTS test (`attachable_id` BIGINT UNSIGNED NOT NULL, `attachable_type` VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        $this->assertEquals($expected, $sql);
    }
}
