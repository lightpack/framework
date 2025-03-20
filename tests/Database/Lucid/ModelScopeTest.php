<?php

namespace Lightpack\Tests\Database\Lucid;

use Lightpack\Database\Query\Query;
use Lightpack\Database\Lucid\Model;
use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Database\DB;

class TenantModel extends Model {
    public function applyScope(Query $query) {
        $query->where('tenant_id', 1);  // Hardcode tenant_id=1 for test
    }
}

class TestModel extends TenantModel {
    protected $table = 'users';
}

class ModelScopeTest extends TestCase {
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

    public function testTenantScopeAppliedToCount() {
        $count = TestModel::query()->count();
        $this->assertEquals(3, $count);  // Should only count tenant_id=1
    }

    public function testTenantScopeAppliedToAll() {
        $users = TestModel::query()->all();
        $this->assertCount(3, $users);
        foreach($users as $user) {
            $this->assertEquals(1, $user->tenant_id);
        }
    }

    public function testTenantScopeAppliedToWhere() {
        $users = TestModel::query()
            ->where('name', 'LIKE', '%user%')
            ->all();
        
        $this->assertCount(3, $users);
        foreach($users as $user) {
            $this->assertEquals(1, $user->tenant_id);
        }
    }

    public function testRawSqlStillScoped() {
        $count = TestModel::query()
            ->select('SELECT COUNT(*) FROM users')
            ->column('COUNT(*)');
            
        $this->assertEquals(3, $count);
    }
}
