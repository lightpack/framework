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
        $this->db->table('users')->insert(['id' => 99, 'name' => 'Test User']);
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
        $user->roles()->detach(2); // Ensure editor is not assigned
        $user->roles()->attach(2);
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
        $user->roles()->detach(1);
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
        $user->roles()->detach(3);
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
        $user->roles()->detach(2); // Should not error
        $this->assertFalse($user->hasRole('editor'));
        // Assign same role twice
        $user->roles()->attach(1);
        $user->roles()->attach(1);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->hasRole('admin'));
    }

    public function testRbacMethodsWithNoRolesOrPermissions()
    {
        // Create a user but do not insert any roles or permissions
        $this->db->table('users')->insert(['id' => 100, 'name' => 'Empty User']);
        $user = $this->getUserModelInstance();
        $user->find(100);
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole(1));
        $this->assertFalse($user->isSuperAdmin());
        $this->assertFalse($user->can('edit_post'));
        $this->assertFalse($user->can(10));
        $this->assertTrue($user->cannot('edit_post'));
        $this->assertTrue($user->cannot(10));
    }

    public function testFilterUsersByRoleName()
    {
        $this->seedRbacData();
        // Add another user with a different role
        $this->db->table('users')->insert(['id' => 100, 'name' => 'Other User']);
        $this->db->table('user_role')->insert([['user_id' => 100, 'role_id' => 2]]); // editor
        $users = $this->getUserModelInstance()::filters(['role' => 'admin'])->all();
        $userIds = $users->column('user_id');
        $this->assertContains(99, $userIds);
        $this->assertNotContains(100, $userIds);
    }

    public function testFilterUsersByRoleId()
    {
        $this->seedRbacData();
        $this->db->table('users')->insert(['id' => 100, 'name' => 'Other User']);
        $this->db->table('user_role')->insert([['user_id' => 100, 'role_id' => 2]]); // editor
        $users = $this->getUserModelInstance()::filters(['role' => 2])->all();
        $userIds = $users->column('user_id');
        $this->assertContains(100, $userIds);
        $this->assertNotContains(99, $userIds);
    }

    public function testFilterUsersByPermissionName()
    {
        $this->seedRbacData();
        $this->db->table('users')->insert(['id' => 100, 'name' => 'Other User']);
        $this->db->table('user_role')->insert([['user_id' => 100, 'role_id' => 2]]); // editor
        $users = $this->getUserModelInstance()::filters(['permission' => 'edit_post'])->all();
        $userIds = $users->column('user_id');
        $this->assertContains(99, $userIds);
        $this->assertNotContains(100, $userIds);
    }

    public function testFilterUsersByPermissionId()
    {
        $this->seedRbacData();
        $this->db->table('users')->insert(['id' => 100, 'name' => 'Other User']);
        $this->db->table('user_role')->insert([['user_id' => 100, 'role_id' => 2]]); // editor
        $users = $this->getUserModelInstance()::filters(['permission' => 10])->all();
        $userIds =$users->column('user_id');
        $this->assertContains(99, $userIds);
        $this->assertNotContains(100, $userIds);
    }

    public function testFilterUsersByNonExistentRoleOrPermission()
    {
        $this->seedRbacData();
        $users = $this->getUserModelInstance()::filters(['role' => 'nonexistent'])->all();
        $this->assertEmpty($users);
        $users = $this->getUserModelInstance()::filters(['permission' => 'nonexistent'])->all();
        $this->assertEmpty($users);
    }

    public function testFilterUsersByMultipleRoleAndPermission()
    {
        $this->seedRbacData();
        $this->db->table('users')->insert(['id' => 100, 'name' => 'Other User']);
        $this->db->table('user_role')->insert([['user_id' => 100, 'role_id' => 2]]); // editor
        $users = $this->getUserModelInstance()::filters(['role' => 'admin', 'permission' => 'edit_post'])->all();
        $userIds = $users->column('user_id');
        $this->assertContains(99, $userIds);
        $this->assertNotContains(100, $userIds);
    }

    public function testPermissionStillGrantedIfUserHasMultipleRolesWithSamePermission()
    {
        $this->seedRbacData();
        // Assign 'edit_post' permission to editor role as well
        $this->db->table('role_permission')->insert([
            ['role_id' => 2, 'permission_id' => 10]
        ]);
        // Assign 'editor' role to user 99
        $this->db->table('user_role')->insert([
            ['user_id' => 99, 'role_id' => 2]
        ]);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->can('edit_post'));
        // Remove admin and superadmin role, should still have permission via editor
        $user->roles()->detach(1);
        $user->roles()->detach(3);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->can('edit_post'));
        // Remove editor role, should lose permission
        $user->roles()->detach(2);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertFalse($user->can('edit_post'));
    }

    public function testAssigningRemovingNonExistentRolesOrPermissions()
    {
        $this->seedRbacData();
        $user = $this->getUserModelInstance();
        $user->find(99);
        // Try assigning a non-existent role
        $user->roles()->attach(9999); // Should not throw
        $this->assertFalse($user->hasRole(9999));
        // Try removing a non-existent role
        $user->roles()->detach(9999); // Should not throw
        $this->assertFalse($user->hasRole(9999));
        // Try assigning a non-existent permission to a real role
        $role = $this->getRoleModelInstance();
        $role->find(1);
        $role->permissions()->attach(9999); // Should not throw
        $this->assertFalse($user->can(9999));
        // Try removing a non-existent permission
        $role->permissions()->detach(9999); // Should not throw
        $this->assertFalse($user->can(9999));
    }

    public function testBulkAssignAndRemoveRoles()
    {
        $this->seedRbacData();
        $user = $this->getUserModelInstance();
        $user->find(99);
        // Remove all roles first
        $user->roles()->detach([1, 2, 3]);
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('editor'));
        $this->assertFalse($user->hasRole('superadmin'));
        // Assign multiple roles at once
        $user->roles()->attach([1, 2]);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('editor'));
        $this->assertFalse($user->hasRole('superadmin'));
        // Remove multiple roles at once
        $user->roles()->detach([1, 2]);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('editor'));
    }

    public function testBulkAssignAndRemovePermissionsForRole()
    {
        $this->seedRbacData();
        $role = $this->getRoleModelInstance();
        $role->find(1); // admin
        // Remove all permissions
        $role->permissions()->detach([10, 11]);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $user->roles()->detach(3); // remove superadmin role
        $this->assertFalse($user->can('edit_post'));
        $this->assertFalse($user->can('delete_post'));
        // Assign multiple permissions at once
        $role->permissions()->attach([10, 11]);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertTrue($user->can('edit_post'));
        $this->assertTrue($user->can('delete_post'));
        // Remove multiple permissions at once
        $role->permissions()->detach([10, 11]);
        $user = $this->getUserModelInstance();
        $user->find(99);
        $this->assertFalse($user->can('edit_post'));
        $this->assertFalse($user->can('delete_post'));
    }
}

