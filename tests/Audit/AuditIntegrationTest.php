<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\Audit\Audit;
use Lightpack\Audit\AuditLog;
use Lightpack\Audit\AuditTrait;
use Lightpack\Container\Container;

class AuditIntegrationTest extends TestCase
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

        $this->schema = new Schema($this->db);
        $this->schema->createTable('audit_logs', function(Table $table) {
            $table->id();
            $table->column('user_id')->type('bigint')->nullable();
            $table->varchar('action', 50);
            $table->varchar('audit_type', 150);
            $table->column('audit_id')->type('bigint')->nullable();
            $table->column('old_values')->type('text')->nullable();
            $table->column('new_values')->type('text')->nullable();
            $table->column('message')->type('text')->nullable();
            $table->varchar('url', 255)->nullable();
            $table->varchar('ip_address', 45)->nullable();
            $table->varchar('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        $this->schema->dropTable('audit_logs');
        $this->db = null;
    }

    protected function getDummyModelInstance() {
        return new class {
            use AuditTrait;
            public $id = 101;
            public $table = 'dummy';
            public function toArray() { return ['id' => $this->id, 'foo' => 'bar']; }
        };
    }

    public function testUserEventAuditLog()
    {
        $data = [
            'user_id'    => 1,
            'action'     => 'create',
            'old_values' => null,
            'new_values' => ['id' => 1, 'foo' => 'bar'],
            'message'    => 'Created by user',
        ];
        $log = Audit::log(array_merge($data, [
            'audit_type' => 'dummy',
            'audit_id' => 1,
        ]));
        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals('create', $log->action);
        $this->assertEquals('dummy', $log->audit_type);
        $this->assertEquals(1, $log->audit_id);
        $this->assertEquals('Created by user', $log->message);
    }

    public function testSystemEventAuditLog()
    {
        $log = Audit::log([
            'user_id'    => null,
            'action'     => 'system_cleanup',
            'audit_type' => 'Token',
            'audit_id'   => 202,
            'old_values' => ['id' => 202, 'token' => 'abc'],
            'new_values' => null,
            'message'    => 'Expired token removed by cron',
            'user_agent' => 'system/cronjob',
        ]);
        $this->assertNull($log->user_id);
        $this->assertEquals('system_cleanup', $log->action);
        $this->assertEquals('Token', $log->audit_type);
        $this->assertEquals(202, $log->audit_id);
        $this->assertEquals('Expired token removed by cron', $log->message);
        $this->assertEquals('system/cronjob', $log->user_agent);
    }

    public function testAuditLogWithTrait()
    {
        $model = $this->getDummyModelInstance();
        $old = ['id' => $model->id, 'foo' => 'baz'];
        $log = $model->audit([
            'action'     => 'update',
            'user_id'    => 2,
            'old_values' => $old,
            'new_values' => $model->toArray(),
            'message'    => 'Updated by admin',
        ]);
        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals('dummy', $log->audit_type);
        $this->assertEquals($model->id, $log->audit_id);
        $this->assertEquals('update', $log->action);
        $this->assertEquals('Updated by admin', $log->message);
        $this->assertEquals($model->toArray(), $log->new_values);
    }

    public function testMultipleEntityAuditLogs()
    {
        // Simulate deleting a role and cascading to permissions
        $role = ['id' => 10, 'name' => 'editor'];
        $permissions = [
            ['id' => 21, 'name' => 'edit_post'],
            ['id' => 22, 'name' => 'delete_post'],
        ];
        $roleLog = Audit::log([
            'user_id'    => 1,
            'action'     => 'delete',
            'audit_type' => 'Role',
            'audit_id'   => $role['id'],
            'old_values' => $role,
            'message'    => 'Role deleted by admin',
        ]);
        $this->assertEquals('Role', $roleLog->audit_type);
        $this->assertEquals('delete', $roleLog->action);
        foreach ($permissions as $perm) {
            $permLog = Audit::log([
                'user_id'    => 1,
                'action'     => 'cascade_delete',
                'audit_type' => 'Permission',
                'audit_id'   => $perm['id'],
                'old_values' => $perm,
                'message'    => 'Permission unlinked/deleted due to role deletion',
            ]);
            $this->assertEquals('Permission', $permLog->audit_type);
            $this->assertEquals('cascade_delete', $permLog->action);
        }
    }

    public function testAuditLogQuerying()
    {
        Audit::log([
            'user_id'    => 1,
            'action'     => 'login',
            'audit_type' => 'User',
            'audit_id'   => 5,
            'message'    => 'User logged in',
        ]);
        Audit::log([
            'user_id'    => 2,
            'action'     => 'login',
            'audit_type' => 'User',
            'audit_id'   => 6,
            'message'    => 'User logged in',
        ]);
        $logs = AuditLog::query()->where('action', 'login')->all();
        $this->assertCount(2, $logs);
        $userLogs = AuditLog::query()->where('audit_type', 'User')->where('audit_id', 5)->all();
        $this->assertCount(1, $userLogs);
        $this->assertEquals(5, $userLogs[0]->audit_id);
    }

    public function testAuditLogWithOnlyRequiredFields()
    {
        $log = Audit::log([
            'action'     => 'minimal',
            'audit_type' => 'Minimal',
            'audit_id'   => 1,
        ]);
        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals('minimal', $log->action);
        $this->assertEquals('Minimal', $log->audit_type);
        $this->assertEquals(1, $log->audit_id);
        $this->assertNull($log->user_id);
        $this->assertNull($log->old_values);
        $this->assertNull($log->new_values);
        $this->assertNull($log->message);
    }

    public function testAuditLogWithNonArrayOldNewValuesThrows()
    {
        // Should throw for string/int instead of array
        $this->expectException(\InvalidArgumentException::class);
        Audit::log([
            'action'     => 'nonarray',
            'audit_type' => 'Test',
            'audit_id'   => 2,
            'old_values' => 'string_value', // Not an array
            'new_values' => 12345,          // Not an array
        ]);
    }


    public function testAuditTraitFailsWithoutIdOrTable()
    {
        // No id property
        $modelNoId = new class {
            use AuditTrait;
            public $table = 'notable';
        };
        try {
            $modelNoId->audit(['action' => 'fail']);
            $this->fail('Expected exception for missing id');
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
        // No table property
        $modelNoTable = new class {
            use AuditTrait;
            public $id = 1;
        };
        try {
            $modelNoTable->audit(['action' => 'fail']);
            $this->fail('Expected exception for missing table');
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }
}