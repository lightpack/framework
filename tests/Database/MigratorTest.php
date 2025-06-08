<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Migrations\Migrator;
use Lightpack\Database\Adapters\Mysql;
use Lightpack\Database\Schema\Schema;

final class MigratorTest extends TestCase
{
    private $connection;
    private $migrator;
    private $schema;
    private $migrationsPath;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/tmp/mysql.config.php';
        $this->connection = new Mysql($config);
        $this->schema = new Schema($this->connection);
        $this->migrator = new Migrator($this->connection);
        $this->migrationsPath = sys_get_temp_dir() . '/lightpack_migrations_' . uniqid();
        mkdir($this->migrationsPath);
    }

    protected function tearDown(): void
    {
        $this->connection->query("SET FOREIGN_KEY_CHECKS = 0");
        foreach (["products", "categories"] as $table) {
            $this->schema->dropTable($table);
        }
        $this->connection->query("DROP TABLE IF EXISTS migrations");
        $this->connection->query("SET FOREIGN_KEY_CHECKS = 1");
        $this->connection = null;
        $this->schema = null;
        $this->migrator = null;
        $this->deleteDir($this->migrationsPath);
    }

    private function deleteDir($dir)
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createMigrationFile($name, $upSql, $downSql)
    {
        $filePath = $this->migrationsPath . "/$name.php";
        $code = <<<'PHP'
<?php
return new class {
    public function boot($schema, $connection) {}
    public function up() { return "%s"; }
    public function down() { return "%s"; }
};
PHP;
        $code = sprintf($code, addslashes($upSql), addslashes($downSql));
        file_put_contents($filePath, $code);
        return $filePath;
    }

    public function testMigratorCanRunAndRollbackMigrations()
    {
        // Create migration file to create products table
        $this->createMigrationFile(
            '20250101_create_products_table',
            'CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(100));',
            'DROP TABLE products;'
        );

        // Run migrations
        $migrated = $this->migrator->run($this->migrationsPath);
        $this->assertNotEmpty($migrated, 'Migrated array is empty: ' . json_encode($migrated));
        $this->assertContains('20250101_create_products_table.php', $migrated);
        $tables = $this->schema->inspectTables();
        $this->assertContains('products', $tables);
        $row = $this->connection->query('SELECT * FROM migrations WHERE migration = "20250101_create_products_table.php"')->fetch();
        $this->assertNotEmpty($row);

        // Rollback migrations
        $rolledBack = $this->migrator->rollback($this->migrationsPath);
        $this->assertContains('20250101_create_products_table.php', $rolledBack);
        $tables = $this->schema->inspectTables();
        $this->assertNotContains('products', $tables);
        $row = $this->connection->query('SELECT * FROM migrations WHERE migration = "20250101_create_products_table.php"')->fetch();
        $this->assertFalse($row);
    }

    public function testMigratorHandlesMultipleMigrationsAndBatching()
    {
        // Create two migration files
        $this->createMigrationFile(
            '20250101_create_categories_table',
            'CREATE TABLE categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100));',
            'DROP TABLE categories;'
        );
        $this->createMigrationFile(
            '20250102_create_products_table',
            'CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY, category_id INT, title VARCHAR(100));',
            'DROP TABLE products;'
        );

        // Run both migrations
        $migrated = $this->migrator->run($this->migrationsPath);
        $this->assertContains('20250101_create_categories_table.php', $migrated);
        $this->assertContains('20250102_create_products_table.php', $migrated);
        $tables = $this->schema->inspectTables();
        $this->assertContains('categories', $tables);
        $this->assertContains('products', $tables);

        // Check batch number
        $batchRow = $this->connection->query('SELECT MAX(batch) as batch FROM migrations')->fetch();
        $this->assertEquals(1, (int)$batchRow['batch']);

        // Rollback one batch
        $rolledBack = $this->migrator->rollback($this->migrationsPath);
        $this->assertContains('20250101_create_categories_table.php', $rolledBack);
        $this->assertContains('20250102_create_products_table.php', $rolledBack);
        $tables = $this->schema->inspectTables();
        $this->assertNotContains('categories', $tables);
        $this->assertNotContains('products', $tables);
    }

    public function testMigratorIsIdempotent()
    {
        // Create migration file
        $this->createMigrationFile(
            '20250101_create_products_table',
            'CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(100));',
            'DROP TABLE products;'
        );

        // Run migrations first time
        $migrated1 = $this->migrator->run($this->migrationsPath);
        $this->assertContains('20250101_create_products_table.php', $migrated1);
        // Run migrations second time (should not re-run)
        $migrated2 = $this->migrator->run($this->migrationsPath);
        $this->assertEmpty($migrated2, 'Migrator should not re-run already executed migration.');
    }
}
