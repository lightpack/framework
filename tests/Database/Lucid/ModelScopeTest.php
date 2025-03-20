<?php

namespace Lightpack\Tests\Database\Lucid;

use Lightpack\Database\Query\Query;
use Lightpack\Database\Lucid\Model;
use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Database\DB;

class TenantModel extends Model
{
    public function applyScope(Query $query)
    {
        $query->where('tenant_id', 1);  // Hardcode tenant_id=1 for test
    }
}

class TestModel extends TenantModel
{
    protected $table = 'users';
}

class ModelScopeTest extends TestCase
{
    private DB $db;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();

        // test data
        $this->db->table('users')->insert([
            ['name' => 'User 1', 'tenant_id' => 1, 'active' => 1],
            ['name' => 'User 2', 'tenant_id' => 1, 'active' => 1],
            ['name' => 'User 3', 'tenant_id' => 1, 'active' => 1],
            ['name' => 'User 4', 'tenant_id' => 2, 'active' => 0],
            ['name' => 'Admin', 'tenant_id' => null, 'active' => 1],
        ]);

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
        $sql = "DROP TABLE products, options, owners, users, roles, role_user, permissions, permission_role, projects, tasks, comments, articles, managers, cast_models, cast_model_relations";
        $this->db->query($sql);
    }

    public function testTenantScopeAppliedToCount()
    {
        $count = TestModel::query()->count();
        $this->assertEquals(3, $count);  // Should only count tenant_id=1
    }

    public function testTenantScopeAppliedToAll()
    {
        $users = TestModel::query()->all();
        $this->assertCount(3, $users);
        foreach ($users as $user) {
            $this->assertEquals(1, $user->tenant_id);
        }
    }

    public function testTenantScopeAppliedToWhere()
    {
        $users = TestModel::query()
            ->where('name', 'LIKE', '%user%')
            ->all();

        $this->assertCount(3, $users);
        foreach ($users as $user) {
            $this->assertEquals(1, $user->tenant_id);
        }
    }

    public function testRawSqlStillScoped()
    {
        $count = TestModel::query()
            ->select('SELECT COUNT(*) FROM users')
            ->column('COUNT(*)');

        $this->assertEquals(3, $count);
    }

    public function testTenantScopeAppliedToDelete()
    {
        // Try to delete all users
        TestModel::query()->delete();

        // Should only delete tenant_id=1
        $remaining = $this->db->table('users')
            ->select('tenant_id, COUNT(*) as count')
            ->groupBy('tenant_id')
            ->all();

        // Should still have tenant 2 and null tenant records
        $this->assertCount(2, $remaining);
        foreach ($remaining as $row) {
            $this->assertNotEquals(1, $row->tenant_id);
        }
    }

    public function testTenantScopeAppliedToUpdate()
    {
        // Try to update all users
        TestModel::query()->update(['name' => 'Changed']);

        // Check tenant 1 users are updated
        $tenant1Users = $this->db->table('users')
            ->where('tenant_id', 1)
            ->all();
        foreach ($tenant1Users as $user) {
            $this->assertEquals('Changed', $user->name);
        }

        // Check other users are unchanged
        $otherUsers = $this->db->table('users')
            ->where('tenant_id', '!=', 1)
            ->orWhereNull('tenant_id')
            ->all();
        foreach ($otherUsers as $user) {
            $this->assertNotEquals('Changed', $user->name);
        }
    }
}
