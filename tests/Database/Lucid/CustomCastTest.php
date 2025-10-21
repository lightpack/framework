<?php

require_once 'CustomCastModel.php';
require_once 'Casts/UpperCaseCast.php';

use Lightpack\Container\Container;
use PHPUnit\Framework\TestCase;

class CustomCastTest extends TestCase
{
    /** @var \Lightpack\Database\DB */
    private $db;

    protected function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();

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
        $sql = "DROP TABLE products, options, owners, users, roles, role_user, permissions, permission_role, projects, tasks, comments, articles, managers, cast_models, cast_model_relations, polymorphic_comments, polymorphic_thumbnails, posts, videos";
        $this->db->query($sql);
        $this->db = null;
    }

    public function testCustomCastOnGet()
    {
        // Insert raw data using existing cast_models table
        $this->db->query("
            INSERT INTO cast_models (string_col, integer_col) 
            VALUES ('john doe', 25)
        ");

        $model = new CustomCastModel(1);

        // Custom cast should convert to uppercase on get
        $this->assertEquals('JOHN DOE', $model->string_col);
        
        // Built-in cast should still work
        $this->assertSame(25, $model->integer_col);
        $this->assertIsInt($model->integer_col);
    }

    public function testCustomCastOnSet()
    {
        $model = new CustomCastModel();
        $model->string_col = 'JANE DOE';
        $model->integer_col = '30';
        $model->save();

        // Verify data was stored in lowercase (custom cast set method)
        $result = $this->db->query("
            SELECT string_col, integer_col FROM cast_models WHERE id = ?
        ", [$model->id])->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals('jane doe', $result['string_col']);
        $this->assertEquals(30, $result['integer_col']);
    }

    public function testCustomCastWithNullValue()
    {
        $model = new CustomCastModel();
        $model->string_col = null;
        $model->integer_col = null;
        $model->save();

        $refetched = new CustomCastModel($model->id);
        
        $this->assertNull($refetched->string_col);
        $this->assertNull($refetched->integer_col);
    }

    public function testBuiltInCastsStillWork()
    {
        // Verify that adding custom cast support didn't break built-in casts
        $model = new CustomCastModel();
        $model->string_col = 'test';
        $model->integer_col = '42';
        $model->save();

        $refetched = new CustomCastModel($model->id);
        
        // Built-in int cast should work
        $this->assertIsInt($refetched->integer_col);
        $this->assertSame(42, $refetched->integer_col);
        
        // Custom cast should work
        $this->assertEquals('TEST', $refetched->string_col);
    }
}
