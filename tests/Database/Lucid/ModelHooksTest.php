<?php

namespace Lightpack\Tests\Database\Lucid;

use Lightpack\Database\Query\Query;
use Lightpack\Database\Lucid\Model;
use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;

class TestModel extends Model 
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
    private $testModel;

    protected function setUp(): void 
    {
        parent::setUp();

        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $db->query($sql);
        $stmt->closeCursor();
        $this->testModel = $db->model(TestModel::class);

        // Configure container
        $container = Container::getInstance();

        $container->register('db', function () use($db) {
            return $db;
        });

        $container->register('logger', function () {
            return new class {
                public function error($message, $context = []) {}
                public function critical($message, $context = []) {}
            };
        });
    }

    public function testFetchHooks() 
    {
        $this->testModel->find(1, false);
        $hooks = $this->testModel->getHooksCalled();
        
        $this->assertContains('beforeFetch', $hooks);
        $this->assertContains('afterFetch', $hooks);
    }

    public function testSaveHooks() 
    {
        $this->testModel->name = 'Test User';
        $this->testModel->save();
        $hooks = $this->testModel->getHooksCalled();
        
        $this->assertContains('beforeSave', $hooks);
        $this->assertContains('afterSave', $hooks);
    }

    public function testDeleteHooks() 
    {
        $this->testModel->name = 'Test User';
        $this->testModel->save();

        $this->testModel->delete();
        $hooks = $this->testModel->getHooksCalled();
        
        $this->assertContains('beforeDelete', $hooks);
        $this->assertContains('afterDelete', $hooks);
    }

    public function testHookOrder() 
    {
        $this->testModel->name = 'Test User';
        $this->testModel->save();
        $hooks = $this->testModel->getHooksCalled();
        
        $saveIndex = array_search('beforeSave', $hooks);
        $afterSaveIndex = array_search('afterSave', $hooks);
        
        $this->assertLessThan($afterSaveIndex, $saveIndex, 'beforeSave should be called before afterSave');
    }

    public function testQueryModificationInBeforeHook() 
    {
        $this->testModel->find(1, false);

        $hooks = $this->testModel->getHooksCalled();
        $this->assertContains('beforeFetch', $hooks);
    }
}
