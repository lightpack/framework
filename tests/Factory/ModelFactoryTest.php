<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Factory\ModelFactory;
use Lightpack\Database\Lucid\Model;

class TestFactoryModel extends Model
{
    protected $table = 'test_model_factory';
    protected $primaryKey = 'code';
    protected $autoIncrements = false;
}

class DummyTestFactory extends ModelFactory
{
    protected function template(): array
    {
        return [
            'code' => uniqid('code_'),
            'name' => 'Default Name',
        ];
    }

    protected function for(): string
    {
        return TestFactoryModel::class;
    }
}

class ModelFactoryTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        parent::setUp();
        $config = require __DIR__ . '/../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $this->db->query('CREATE TABLE IF NOT EXISTS test_model_factory (
            code VARCHAR(32) PRIMARY KEY,
            name VARCHAR(255),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )');

        $container = Container::getInstance();
        $container->register('db', fn() => $this->db);
        $container->register('logger', fn() => new class {
            public function error($message, $context = []) {}
            public function critical($message, $context = []) {}
        });
    }

    protected function tearDown(): void
    {
        $this->db->query('DROP TABLE IF EXISTS test_model_factory');
        $this->db = null;
    }

    public function testModelFactorySavesSingleModel()
    {
        $factory = new DummyTestFactory;
        $model = $factory->save(['code' => 'TEST1', 'name' => 'Factory Name']);
        $found = TestFactoryModel::query()->where('code', '=', 'TEST1')->one();

        $this->assertNotNull($found);
        $this->assertEquals('Factory Name', $found->name);
    }

    public function testModelFactorySavesBatchModels()
    {
        $factory = new DummyTestFactory;
        $models = $factory->batch(2)->save([
            'name' => 'Batch Name'
        ]);

        $this->assertCount(2, $models);
        foreach ($models as $model) {
            $found = TestFactoryModel::query()->where('code', '=', $model->code)->one();
            $this->assertNotNull($found);
            $this->assertEquals('Batch Name', $found->name);
        }
    }
}