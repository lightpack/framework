<?php

namespace Lightpack\Tests\Database\Lucid;

use Lightpack\Database\Query\Query;
use Lightpack\Database\Lucid\Model;
use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Database\DB;

class TestHookModel extends Model 
{
    protected $table = 'users';
    private $hooksCalled = [];

    public function getHooksCalled(): array 
    {
        return $this->hooksCalled;
    }

    protected function beforeSave() 
    {
        $this->hooksCalled[] = 'beforeSave';
    }

    protected function afterSave() 
    {
        $this->hooksCalled[] = 'afterSave';
    }

    protected function beforeDelete() 
    {
        $this->hooksCalled[] = 'beforeDelete';
    }

    protected function afterDelete() 
    {
        $this->hooksCalled[] = 'afterDelete';
    }

    protected function beforeInsert()
    {
        $this->hooksCalled[] = 'beforeInsert';
    }

    protected function afterInsert()
    {
        $this->hooksCalled[] = 'afterInsert';
    }

    protected function beforeUpdate()
    {
        $this->hooksCalled[] = 'beforeUpdate';
    }

    protected function afterUpdate()
    {
        $this->hooksCalled[] = 'afterUpdate';
    }
}

class ModelHooksTest extends TestCase 
{
    private ?DB $db;
    private TestHookModel $TestHookModel;

    protected function setUp(): void 
    {
        parent::setUp();

        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();
        $this->TestHookModel = $this->db->model(TestHookModel::class);

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
        $this->db = null;
    }

    public function testSaveHooks() 
    {
        $this->TestHookModel->name = 'Test User';
        $this->TestHookModel->save();
        $hooks = $this->TestHookModel->getHooksCalled();
        
        $this->assertContains('beforeSave', $hooks);
        $this->assertContains('afterSave', $hooks);
    }

    public function testDeleteHooks() 
    {
        $this->TestHookModel->name = 'Test User';
        $this->TestHookModel->save();

        $this->TestHookModel->delete();
        $hooks = $this->TestHookModel->getHooksCalled();
        
        $this->assertContains('beforeDelete', $hooks);
        $this->assertContains('afterDelete', $hooks);
    }

    public function testHookModelOrder() 
    {
        $this->TestHookModel->name = 'Test User';
        $this->TestHookModel->save();
        $hooks = $this->TestHookModel->getHooksCalled();
        
        $saveIndex = array_search('beforeSave', $hooks);
        $afterSaveIndex = array_search('afterSave', $hooks);
        
        $this->assertLessThan($afterSaveIndex, $saveIndex, 'beforeSave should be called before afterSave');
    }

    public function testInsertHooks()
    {
        $model = new TestHookModel();
        $model->name = 'Insert User';
        $model->insert();
        $hooks = $model->getHooksCalled();
        $this->assertContains('beforeInsert', $hooks);
        $this->assertContains('afterInsert', $hooks);
        $this->assertLessThan(array_search('afterInsert', $hooks), array_search('beforeInsert', $hooks), 'beforeInsert should be called before afterInsert');
    }

    public function testUpdateHooks()
    {
        $model = new TestHookModel();
        $model->name = 'Update User';
        $model->save();
        $model->getHooksCalled(); // Clear hooks from save
        $model->name = 'Updated User';
        $model->update();
        $hooks = $model->getHooksCalled();
        $this->assertContains('beforeUpdate', $hooks);
        $this->assertContains('afterUpdate', $hooks);
        $this->assertLessThan(array_search('afterUpdate', $hooks), array_search('beforeUpdate', $hooks), 'beforeUpdate should be called before afterUpdate');
    }
}
