<?php

declare(strict_types=1);

use Lightpack\Container\Container;
use PHPUnit\Framework\TestCase;
use Lightpack\Database\Lucid\Model;

class TestFilterUser extends Model
{
    protected $table = 'users';

    protected function scopeStatus($query, $value)
    {
        $query->where('status', $value);
    }

    protected function scopeType($query, $value)
    {
        $query->where('type', $value);
    }

    protected function scopeTags($query, $value)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        $query->whereIn('tag', $value);
    }
}

class ModelFilterTest extends TestCase
{
    /** @var \Lightpack\Database\DB */
    private $db;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();

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

    protected function tearDown(): void
    {
        $this->db = null;
    }

    public function testSimpleFilter()
    {
        $query = TestFilterUser::filters(['status' => 'active']);
        $this->assertEquals(
            'SELECT * FROM `users` WHERE `status` = ?',
            $query->toSql()
        );
        $this->assertEquals(['active'], $query->bindings);
    }

    public function testMultipleFilters()
    {
        $query = TestFilterUser::filters([
            'status' => 'active',
            'type' => 'admin'
        ]);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `status` = ? AND `type` = ?',
            $query->toSql()
        );
        $this->assertEquals(['active', 'admin'], $query->bindings);
    }

    public function testArrayValueFilter()
    {
        $query = TestFilterUser::filters([
            'tags' => 'php,mysql,redis'
        ]);

        $this->assertEquals(
            'SELECT * FROM `users` WHERE `tag` IN (?, ?, ?)',
            $query->toSql()
        );
        $this->assertEquals(['php', 'mysql', 'redis'], $query->bindings);
    }

    public function testInvalidFilterIsIgnored()
    {
        $query = TestFilterUser::filters([
            'nonexistent' => 'value'
        ]);

        $this->assertEquals(
            'SELECT * FROM `users`',
            $query->toSql()
        );
        $this->assertEquals([], $query->bindings);
    }

    public function testEmptyFilters()
    {
        $query = TestFilterUser::filters([]);

        $this->assertEquals(
            'SELECT * FROM `users`',
            $query->toSql()
        );
        $this->assertEquals([], $query->bindings);
    }
}
