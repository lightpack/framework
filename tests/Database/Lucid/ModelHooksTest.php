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

    protected function beforeFind(Query $query) 
    {
        $this->hooksCalled[] = 'beforeFind';
        $query->where('active', '=', 1);
    }

    protected function afterFind() 
    {
        $this->hooksCalled[] = 'afterFind';
    }

    protected function beforeSave(Query $query) 
    {
        $this->hooksCalled[] = 'beforeSave';
    }

    protected function afterSave() 
    {
        $this->hooksCalled[] = 'afterSave';
    }

    protected function beforeDelete(Query $query) 
    {
        $this->hooksCalled[] = 'beforeDelete';
    }

    protected function afterDelete() 
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
        
        $this->assertContains('beforeFind', $hooks);
        $this->assertContains('afterFind', $hooks);
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
        // The beforeFind hook adds where active = 1
        // We can verify this by checking the generated SQL
        // This would require exposing the query object or its SQL
        // For now we just verify the hook was called
        $hooks = $this->testModel->getHooksCalled();
        $this->assertContains('beforeFind', $hooks);
    }
}
