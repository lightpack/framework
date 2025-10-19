<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Container\Container;
use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class DbUniqueRuleTest extends TestCase
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
            CREATE TABLE IF NOT EXISTS test_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                username VARCHAR(255) NOT NULL,
                organization_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS test_posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(255) NOT NULL,
                category_id INT NOT NULL,
                title VARCHAR(255) NOT NULL
            )
        ");
    }

    protected function tearDown(): void
    {
        // Clean up test tables
        $this->db->query("DROP TABLE IF EXISTS test_users");
        $this->db->query("DROP TABLE IF EXISTS test_posts");
        $this->db = null;

        parent::tearDown();
    }

    public function testDbUniquePassesWhenValueDoesNotExist(): void
    {
        $validator = new Validator();
        $validator->field('email')->dbUnique('test_users', 'email');

        $validator->setInput(['email' => 'new@example.com']);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testDbUniqueFailsWhenValueExists(): void
    {
        // Insert test data
        db()->table('test_users')->insert([
            'email' => 'existing@example.com',
            'username' => 'testuser'
        ]);

        $validator = new Validator();
        $validator->field('email')->dbUnique('test_users', 'email');

        $validator->setInput(['email' => 'existing@example.com']);
        $this->assertTrue($validator->validate()->fails());
        $this->assertStringContainsString('already been taken', $validator->getError('email'));
    }

    public function testDbUniqueIgnoresSpecificId(): void
    {
        // Insert test data
        db()->table('test_users')->insert([
            'email' => 'user@example.com',
            'username' => 'testuser'
        ]);
        $id = $this->db->lastInsertId();

        // Should pass when ignoring the same record
        $validator = new Validator();
        $validator->field('email')->dbUnique('test_users', 'email', ignoreId: $id);

        $validator->setInput(['email' => 'user@example.com']);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testDbUniqueFailsWhenDifferentRecordExists(): void
    {
        // Insert two records
        db()->table('test_users')->insert([
            'email' => 'user1@example.com',
            'username' => 'user1'
        ]);
        $id1 = $this->db->lastInsertId();

        db()->table('test_users')->insert([
            'email' => 'user2@example.com',
            'username' => 'user2'
        ]);

        // Should fail when trying to use user2's email while updating user1
        $validator = new Validator();
        $validator->field('email')->dbUnique('test_users', 'email', ignoreId: $id1);

        $validator->setInput(['email' => 'user2@example.com']);
        $this->assertTrue($validator->validate()->fails());
    }

    public function testDbUniqueWithCompositeColumns(): void
    {
        // Insert test data - same email in different organizations
        db()->table('test_users')->insert([
            'email' => 'user@example.com',
            'username' => 'user1',
            'organization_id' => 1
        ]);

        // Should pass - same email but different organization
        $validator = new Validator();
        $validator->field('email')->dbUnique('test_users', ['email', 'organization_id']);

        $validator->setInput([
            'email' => 'user@example.com',
            'organization_id' => 2
        ]);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testDbUniqueCompositeFailsWhenCombinationExists(): void
    {
        // Insert test data
        db()->table('test_users')->insert([
            'email' => 'user@example.com',
            'username' => 'user1',
            'organization_id' => 1
        ]);

        // Should fail - same email and organization
        $validator = new Validator();
        $validator->field('email')->dbUnique('test_users', ['email', 'organization_id']);

        $validator->setInput([
            'email' => 'user@example.com',
            'organization_id' => 1
        ]);
        $this->assertTrue($validator->validate()->fails());
        $this->assertStringContainsString('combination', $validator->getError('email'));
    }

    public function testDbUniqueWithSlugAndCategory(): void
    {
        // Insert test data - same slug in different categories
        db()->table('test_posts')->insert([
            'slug' => 'hello-world',
            'category_id' => 1,
            'title' => 'Hello World'
        ]);

        // Should pass - same slug but different category
        $validator = new Validator();
        $validator->field('slug')->dbUnique('test_posts', ['slug', 'category_id']);

        $validator->setInput([
            'slug' => 'hello-world',
            'category_id' => 2
        ]);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testDbUniqueCompositeWithIgnoreId(): void
    {
        // Insert test data
        db()->table('test_posts')->insert([
            'slug' => 'hello-world',
            'category_id' => 1,
            'title' => 'Hello World'
        ]);
        $id = $this->db->lastInsertId();

        // Should pass - updating the same record
        $validator = new Validator();
        $validator->field('slug')->dbUnique('test_posts', ['slug', 'category_id'], ignoreId: $id);

        $validator->setInput([
            'slug' => 'hello-world',
            'category_id' => 1
        ]);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testDbUniqueWithCustomIdColumn(): void
    {
        // Create table with custom ID column
        db()->query("
            CREATE TABLE IF NOT EXISTS test_custom (
                uuid VARCHAR(36) PRIMARY KEY,
                code VARCHAR(50) NOT NULL
            )
        ");

        db()->table('test_custom')->insert([
            'uuid' => 'abc-123',
            'code' => 'CODE001'
        ]);

        // Should pass when ignoring with custom ID column
        $validator = new Validator();
        $validator->field('code')->dbUnique('test_custom', 'code', ignoreId: 'abc-123', idColumn: 'uuid');

        $validator->setInput(['code' => 'CODE001']);
        $this->assertTrue($validator->validate()->passes());

        db()->query("DROP TABLE IF EXISTS test_custom");
    }

    public function testDbUniqueWithDefaultColumnName(): void
    {
        // When no column specified, should use current field name
        db()->table('test_users')->insert([
            'email' => 'test@example.com',
            'username' => 'testuser'
        ]);

        $validator = new Validator();
        $validator->field('email')->dbUnique('test_users');

        $validator->setInput(['email' => 'test@example.com']);
        $this->assertTrue($validator->validate()->fails());
    }

    public function testDbUniqueChainedWithOtherRules(): void
    {
        $validator = new Validator();
        $validator
            ->field('email')
            ->required()
            ->email()
            ->dbUnique('test_users', 'email');

        // Test with invalid email
        $validator->setInput(['email' => 'invalid-email']);
        $this->assertTrue($validator->validate()->fails());
        $this->assertStringContainsString('valid email', $validator->getError('email'));

        // Test with valid but existing email
        db()->table('test_users')->insert([
            'email' => 'existing@example.com',
            'username' => 'testuser'
        ]);

        // Create new validator instance for second test
        $validator = new Validator();
        $validator
            ->field('email')
            ->required()
            ->email()
            ->dbUnique('test_users', 'email');

        $validator->setInput(['email' => 'existing@example.com']);
        $this->assertTrue($validator->validate()->fails());
        $this->assertStringContainsString('already been taken', $validator->getError('email'));
    }

    public function testDbUniqueWithCustomMessage(): void
    {
        db()->table('test_users')->insert([
            'email' => 'taken@example.com',
            'username' => 'testuser'
        ]);

        $validator = new Validator();
        $validator
            ->field('email')
            ->dbUnique('test_users', 'email')
            ->message('This email is already registered');

        $validator->setInput(['email' => 'taken@example.com']);
        $this->assertTrue($validator->validate()->fails());
        $this->assertEquals('This email is already registered', $validator->getError('email'));
    }

    public function testDbUniqueMultipleFields(): void
    {
        db()->table('test_users')->insert([
            'email' => 'user@example.com',
            'username' => 'takenuser'
        ]);

        $validator = new Validator();
        $validator
            ->field('email')
            ->dbUnique('test_users', 'email')
            ->field('username')
            ->dbUnique('test_users', 'username');

        $validator->setInput([
            'email' => 'user@example.com',
            'username' => 'takenuser'
        ]);

        $this->assertTrue($validator->validate()->fails());
        $errors = $validator->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('username', $errors);
    }
}
