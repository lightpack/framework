<?php

use Lightpack\Container\Container;
use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;

class RbacTraitIntegrationTest extends TestCase
{
    /** @var \Lightpack\Database\Adapters\Mysql */
    private $db;
    /** @var Schema */
    private $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $config = require __DIR__ . '/../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);

        // configure container
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

        // Create RBAC tables
        $this->schema = new Schema($this->db);
        $this->schema->createTable('users', function(Table $table) {
            $table->id();
            $table->varchar('name');
            $table->text('rbac_cache')->nullable();
            $table->timestamps();
        });
        $this->schema->createTable('roles', function(Table $table) {
            $table->id();
            $table->varchar('name');
            $table->timestamps();
        });
        $this->schema->createTable('permissions', function(Table $table) {
            $table->id();
            $table->varchar('name');
            $table->timestamps();
        });
        $this->schema->createTable('user_role', function(Table $table) {
            $table->column('user_id')->type('bigint')->attribute('unsigned');
            $table->column('role_id')->type('bigint')->attribute('unsigned');
        });
        $this->schema->createTable('role_permission', function(Table $table) {
            $table->column('role_id')->type('bigint')->attribute('unsigned');
            $table->column('permission_id')->type('bigint')->attribute('unsigned');
        });
    }

    protected function tearDown(): void
    {
        $this->schema->dropTable('role_permission');
        $this->schema->dropTable('user_role');
        $this->schema->dropTable('permissions');
        $this->schema->dropTable('roles');
        $this->schema->dropTable('users');
        $this->db = null;
    }

    protected function getUserModelInstance() {
        return new class extends \Lightpack\Database\Lucid\Model {
            use \Lightpack\Rbac\RbacTrait;
            protected $table = 'users';
            protected $primaryKey = 'id';
            public $timestamps = true;
        };
    }

    protected function getRoleModelInstance() {
        return new class extends \Lightpack\Rbac\Models\Role {};
    }

    protected function getPermissionModelInstance() {
        return new class extends \Lightpack\Rbac\Models\Permission {};
    }

    protected function seedRbacData()
    {
        // Insert roles
        $this->db->table('roles')->insert([
            ['id' => 1, 'name' => 'admin'],
            ['id' => 2, 'name' => 'editor'],
            ['id' => 3, 'name' => 'superadmin'],
        ]);
        // Insert permissions
        $this->db->table('permissions')->insert([
            ['id' => 10, 'name' => 'edit_post'],
            ['id' => 11, 'name' => 'delete_post'],
        ]);
        // Insert user
        $this->db->table('users')->insert(['id' => 99, 'name' => 'Test User', 'rbac_cache' => null]);
        // Assign roles to user
        $this->db->table('user_role')->insert([
            ['user_id' => 99, 'role_id' => 1],
            ['user_id' => 99, 'role_id' => 3],
        ]);
        // Assign permissions to roles
        $this->db->table('role_permission')->insert([
            ['role_id' => 1, 'permission_id' => 10],
            ['role_id' => 3, 'permission_id' => 11],
        ]);
    }

    public function testAssignRole()
    {
        $this->seedRbacData();
        $user = $this->getUserModelInstance();
        $user->find(99);
        $user->removeRole(2); // Ensure editor is not assigned
        $user->assignRole(2);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->hasRole('editor'));
    }

    public function testRemoveRole()
    {
        $this->seedRbacData();
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->hasRole('admin'));
        $user->removeRole(1);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertFalse($user->hasRole('admin'));
    }

    public function testHasRoleByNameAndId()
    {
        $this->seedRbacData();
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole(3));
        $this->assertFalse($user->hasRole('editor'));
    }

    public function testSuperAdminRole()
    {
        $this->seedRbacData();
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->isSuperAdmin());
        $user->removeRole(3);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertFalse($user->isSuperAdmin());
    }

    public function testCanCheckPermissionByNameAndId()
    {
        $this->seedRbacData();
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->can('edit_post'));
        $this->assertTrue($user->can(11));
        $this->assertFalse($user->can('nonexistent_permission'));
    }

    public function testCacheInvalidationOnRoleChange()
    {
        $this->seedRbacData();
        $user = $this->getUserModelInstance();
        $user->find(99);
        $user->hasRole('admin'); // populate cache
        $user->removeRole(1);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertFalse($user->hasRole('admin'));
        $user->assignRole(1);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->hasRole('admin'));
    }

    public function testAssignPermissionToRole()
    {
        $this->seedRbacData();
        $this->db->table('role_permission')->insert([
            ['role_id' => 1, 'permission_id' => 11]
        ]);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->can('delete_post'));
    }

    public function testRemovePermissionFromRole()
    {
        $this->seedRbacData();
        $role = $this->getRoleModelInstance();
        $role->find(1);
        $role->permissions()->detach(10);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertFalse($user->can('edit_post'));
    }

    public function testEdgeCases()
    {
        $this->seedRbacData();
        $user = $this->getUserModelInstance();
        $user->find(99);
        // Remove a role not assigned
        $user->removeRole(2); // Should not error
        $this->assertFalse($user->hasRole('editor'));
        // Assign same role twice
        $user->assignRole(1);
        $user->assignRole(1);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->hasRole('admin'));
    }
}
