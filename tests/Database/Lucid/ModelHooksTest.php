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

    public function beforeFetch(Query $query) 
    {
        $this->hooksCalled[] = 'beforeFetch';
        $query->where('active', '=', 1);
    }

    public function afterFetch() 
    {
        $this->hooksCalled[] = 'afterFetch';
    }

    public function beforeSave(Query $query) 
    {
        $this->hooksCalled[] = 'beforeSave';
    }

    public function afterSave() 
    {
        $this->hooksCalled[] = 'afterSave';
    }

    public function beforeDelete(Query $query) 
    {
        $this->hooksCalled[] = 'beforeDelete';
    }

    public function afterDelete() 
    {
        $this->hooksCalled[] = 'afterDelete';
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
}
