<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Container\Container;
use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ExistsRuleTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);

        // Register DB in container
        $container = Container::getInstance();
        $container->register('db', function () {
            return $this->db;
        });

        // Create test tables
        $this->db->query("
            CREATE TABLE IF NOT EXISTS test_categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                status VARCHAR(50) DEFAULT 'active'
            )
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS test_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL,
                status VARCHAR(50) DEFAULT 'active'
            )
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS test_products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                sku VARCHAR(50) NOT NULL,
                category_id INT NOT NULL,
                warehouse_id INT NOT NULL
            )
        ");

        // Insert test data
        $this->db->table('test_categories')->insert([
            ['name' => 'Electronics', 'status' => 'active'],
            ['name' => 'Books', 'status' => 'active'],
            ['name' => 'Archived', 'status' => 'inactive']
        ]);

        $this->db->table('test_users')->insert([
            ['email' => 'admin@example.com', 'role' => 'admin', 'status' => 'active'],
            ['email' => 'user@example.com', 'role' => 'user', 'status' => 'active'],
            ['email' => 'banned@example.com', 'role' => 'user', 'status' => 'banned']
        ]);
    }

    protected function tearDown(): void
    {
        $this->db->query("DROP TABLE IF EXISTS test_categories");
        $this->db->query("DROP TABLE IF EXISTS test_users");
        $this->db->query("DROP TABLE IF EXISTS test_products");
        $this->db = null;

        parent::tearDown();
    }

    public function testExistsPassesWhenValueExists(): void
    {
        $validator = new Validator();
        $validator->field('category_id')->exists('test_categories', 'id');

        $validator->setInput(['category_id' => 1]);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testExistsFailsWhenValueDoesNotExist(): void
    {
        $validator = new Validator();
        $validator->field('category_id')->exists('test_categories', 'id');

        $validator->setInput(['category_id' => 999]);
        $this->assertTrue($validator->validate()->fails());
        $this->assertStringContainsString('does not exist', $validator->getError('category_id'));
    }

    public function testExistsWithDefaultColumn(): void
    {
        $validator = new Validator();
        $validator->field('id')->exists('test_categories');
        $validator->setInput(['id' => 1]);
        $this->assertTrue($validator->validate()->passes());

        $validator = new Validator();
        $validator->field('id')->exists('test_categories');
        $validator->setInput(['id' => 999]);
        $this->assertTrue($validator->validate()->fails());
    }

    public function testExistsWithWhereCondition(): void
    {
        // Category 1 is active - should pass
        $validator = new Validator();
        $validator->field('category_id')->exists('test_categories', 'id', where: ['status' => 'active']);
        $validator->setInput(['category_id' => 1]);
        $this->assertTrue($validator->validate()->passes());

        // Category 3 is inactive - should fail
        $validator = new Validator();
        $validator->field('category_id')->exists('test_categories', 'id', where: ['status' => 'active']);
        $validator->setInput(['category_id' => 3]);
        $this->assertTrue($validator->validate()->fails());
    }

    public function testExistsWithMultipleWhereConditions(): void
    {
        // User 1 is admin and active - should pass
        $validator = new Validator();
        $validator->field('user_id')->exists('test_users', 'id', where: [
            'role' => 'admin',
            'status' => 'active'
        ]);
        $validator->setInput(['user_id' => 1]);
        $this->assertTrue($validator->validate()->passes());

        // User 2 is user (not admin) - should fail
        $validator = new Validator();
        $validator->field('user_id')->exists('test_users', 'id', where: [
            'role' => 'admin',
            'status' => 'active'
        ]);
        $validator->setInput(['user_id' => 2]);
        $this->assertTrue($validator->validate()->fails());
    }

    public function testExistsSkipsValidationForEmptyValue(): void
    {
        // Empty value should pass (use required() to enforce presence)
        $validator = new Validator();
        $validator->field('category_id')->exists('test_categories', 'id');
        $validator->setInput(['category_id' => '']);
        $this->assertTrue($validator->validate()->passes());

        $validator = new Validator();
        $validator->field('category_id')->exists('test_categories', 'id');
        $validator->setInput(['category_id' => null]);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testExistsWithRequiredRule(): void
    {
        // Empty value should fail (required)
        $validator = new Validator();
        $validator
            ->field('category_id')
            ->required()
            ->exists('test_categories', 'id');
        $validator->setInput(['category_id' => '']);
        $this->assertTrue($validator->validate()->fails());
        $this->assertStringContainsString('required', $validator->getError('category_id'));

        // Non-existent value should fail (exists)
        $validator = new Validator();
        $validator
            ->field('category_id')
            ->required()
            ->exists('test_categories', 'id');
        $validator->setInput(['category_id' => 999]);
        $this->assertTrue($validator->validate()->fails());
        $this->assertStringContainsString('does not exist', $validator->getError('category_id'));

        // Valid value should pass
        $validator = new Validator();
        $validator
            ->field('category_id')
            ->required()
            ->exists('test_categories', 'id');
        $validator->setInput(['category_id' => 1]);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testExistsWithZeroValue(): void
    {
        // Insert a record with ID 0 (if supported)
        $this->db->query("INSERT INTO test_categories (id, name) VALUES (0, 'Zero Category')");

        $validator = new Validator();
        $validator->field('category_id')->exists('test_categories', 'id');

        $validator->setInput(['category_id' => 0]);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testExistsWithStringValue(): void
    {
        $validator = new Validator();
        $validator->field('email')->exists('test_users', 'email');
        $validator->setInput(['email' => 'admin@example.com']);
        $this->assertTrue($validator->validate()->passes());

        $validator = new Validator();
        $validator->field('email')->exists('test_users', 'email');
        $validator->setInput(['email' => 'nonexistent@example.com']);
        $this->assertTrue($validator->validate()->fails());
    }

    public function testExistsChainedWithOtherRules(): void
    {
        // Invalid type
        $validator = new Validator();
        $validator
            ->field('category_id')
            ->required()
            ->int()
            ->exists('test_categories', 'id', where: ['status' => 'active']);
        $validator->setInput(['category_id' => 'abc']);
        $this->assertTrue($validator->validate()->fails());

        // Valid type but doesn't exist
        $validator = new Validator();
        $validator
            ->field('category_id')
            ->required()
            ->int()
            ->exists('test_categories', 'id', where: ['status' => 'active']);
        $validator->setInput(['category_id' => 999]);
        $this->assertTrue($validator->validate()->fails());

        // Exists but inactive
        $validator = new Validator();
        $validator
            ->field('category_id')
            ->required()
            ->int()
            ->exists('test_categories', 'id', where: ['status' => 'active']);
        $validator->setInput(['category_id' => 3]);
        $this->assertTrue($validator->validate()->fails());

        // Valid and active
        $validator = new Validator();
        $validator
            ->field('category_id')
            ->required()
            ->int()
            ->exists('test_categories', 'id', where: ['status' => 'active']);
        $validator->setInput(['category_id' => 1]);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testExistsWithCustomMessage(): void
    {
        $validator = new Validator();
        $validator
            ->field('category_id')
            ->exists('test_categories', 'id')
            ->message('The selected category is invalid');

        $validator->setInput(['category_id' => 999]);
        $this->assertTrue($validator->validate()->fails());
        $this->assertEquals('The selected category is invalid', $validator->getError('category_id'));
    }

    public function testMultipleExistsRules(): void
    {
        $validator = new Validator();
        $validator
            ->field('category_id')->exists('test_categories', 'id')
            ->field('user_id')->exists('test_users', 'id');

        $validator->setInput([
            'category_id' => 999,
            'user_id' => 888
        ]);

        $this->assertTrue($validator->validate()->fails());
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('category_id', $errors);
        $this->assertArrayHasKey('user_id', $errors);
    }

    public function testExistsWithCompositeColumns(): void
    {
        // Insert test data
        $this->db->table('test_products')->insert([
            ['sku' => 'PROD-001', 'category_id' => 1, 'warehouse_id' => 1],
            ['sku' => 'PROD-001', 'category_id' => 1, 'warehouse_id' => 2],
            ['sku' => 'PROD-002', 'category_id' => 2, 'warehouse_id' => 1]
        ]);

        // Exact match exists
        $validator = new Validator();
        $validator->field('sku')->exists('test_products', ['sku', 'category_id', 'warehouse_id']);
        $validator->setInput([
            'sku' => 'PROD-001',
            'category_id' => 1,
            'warehouse_id' => 1
        ]);
        $this->assertTrue($validator->validate()->passes());

        // SKU exists but different warehouse
        $validator = new Validator();
        $validator->field('sku')->exists('test_products', ['sku', 'category_id', 'warehouse_id']);
        $validator->setInput([
            'sku' => 'PROD-001',
            'category_id' => 1,
            'warehouse_id' => 999
        ]);
        $this->assertTrue($validator->validate()->fails());
    }

    public function testExistsWithNestedFields(): void
    {
        $validator = new Validator();
        $validator->field('order.category_id')->exists('test_categories', 'id');
        $validator->setInput([
            'order' => [
                'category_id' => 1
            ]
        ]);
        $this->assertTrue($validator->validate()->passes());

        $validator = new Validator();
        $validator->field('order.category_id')->exists('test_categories', 'id');
        $validator->setInput([
            'order' => [
                'category_id' => 999
            ]
        ]);
        $this->assertTrue($validator->validate()->fails());
    }
}
