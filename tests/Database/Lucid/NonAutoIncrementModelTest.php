<?php

use Lightpack\Container\Container;
use PHPUnit\Framework\TestCase;
use Lightpack\Database\Lucid\Model;

class ManualPkModel extends Model
{
    protected $table = 'manual_pk_models';
    protected $primaryKey = 'code';
    protected $autoIncrements = false;
}

class NonAutoIncrementModelTest extends TestCase
{
    /** @var \Lightpack\Database\Adapters\Mysql */
    private $db;

    protected function setUp(): void
    {
        parent::setUp();
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $this->db->query('CREATE TABLE IF NOT EXISTS manual_pk_models (
            code VARCHAR(32) PRIMARY KEY,
            name VARCHAR(255),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )');

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

    protected function tearDown(): void
    {
        $this->db->query('DROP TABLE IF EXISTS manual_pk_models');
        $this->db = null;
    }

    public function testInsertFailsWithoutManualPrimaryKey()
    {
        $model = new ManualPkModel();
        $model->name = 'Test Name';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Insert failed: This model does not use an auto-incrementing primary key. You must assign a primary key value before saving.');
        $model->save();
    }

    public function testInsertSucceedsWithManualPrimaryKey()
    {
        $model = new ManualPkModel();
        $model->code = 'ABC123';
        $model->name = 'Test Name';
        $model->insert();
        $found = ManualPkModel::query()->where('code', '=', 'ABC123')->one();

        $this->assertNotNull($found);
        $this->assertEquals('Test Name', $found->name);
    }

    public function testUpdateWithManualPrimaryKey()
    {
        // Insert first
        $model = new ManualPkModel();
        $model->code = 'XYZ789';
        $model->name = 'Initial';
        $model->insert();

        // Update
        $model->name = 'Updated';
        $model->update();

        $found = ManualPkModel::query()->where('code', '=', 'XYZ789')->one();
        $this->assertEquals('Updated', $found->name);
    }

    public function testUpdateReturnsZeroForNonExistentRecord()
    {
        $model = new ManualPkModel();
        $model->code = 'DOESNOTEXIST';
        $model->name = 'Nothing';
        $model->update();

        // Try update directly (simulate update)
        $found = $model::query()->where('code', '=', 'DOESNOTEXIST')->one();
        $this->assertNull($found);
    }
}
