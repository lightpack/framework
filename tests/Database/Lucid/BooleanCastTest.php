<?php

use Lightpack\Database\Lucid\Model;
use Lightpack\Container\Container;
use PHPUnit\Framework\TestCase;

class BooleanCastTestModel extends Model
{
    protected $table = 'products';
    protected $casts = [
        'is_active' => 'bool',
        'is_featured' => 'bool',
        'in_stock' => 'bool',
    ];
}

class BooleanCastTest extends TestCase
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

    public function testBooleanCastStoresTrueAsOne(): void
    {
        $product = new BooleanCastTestModel();
        $product->name = 'Test Product';
        $product->is_active = true;
        $product->save();

        // Verify it's stored as 1 in database
        $row = $this->db->query("SELECT is_active FROM products WHERE id = ?", [$product->id])->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, $row['is_active']);
        $this->assertIsInt($row['is_active']);
    }

    public function testBooleanCastStoresFalseAsZero(): void
    {
        $product = new BooleanCastTestModel();
        $product->name = 'Test Product';
        $product->is_active = false;
        $product->save();

        // Verify it's stored as 0 in database
        $row = $this->db->query("SELECT is_active FROM products WHERE id = ?", [$product->id])->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(0, $row['is_active']);
        $this->assertIsInt($row['is_active']);
    }

    public function testBooleanCastRetrievesOneAsTrue(): void
    {
        // Insert directly with value 1
        $this->db->query("INSERT INTO products (name, is_active) VALUES (?, ?)", ['Test Product', 1]);
        $id = $this->db->lastInsertId();

        $product = new BooleanCastTestModel($id);
        
        $this->assertTrue($product->is_active);
        $this->assertIsBool($product->is_active);
    }

    public function testBooleanCastRetrievesZeroAsFalse(): void
    {
        // Insert directly with value 0
        $this->db->query("INSERT INTO products (name, is_active) VALUES (?, ?)", ['Test Product', 0]);
        $id = $this->db->lastInsertId();

        $product = new BooleanCastTestModel($id);
        
        $this->assertFalse($product->is_active);
        $this->assertIsBool($product->is_active);
    }

    public function testCheckboxFormBehaviorChecked(): void
    {
        // Simulate checked checkbox: sends 'on' or '1'
        $checkboxValue = 'on';
        
        $product = new BooleanCastTestModel();
        $product->name = 'Test Product';
        $product->is_active = $checkboxValue; // Will be cast to bool
        $product->save();

        // Verify it's stored as 1
        $row = $this->db->query("SELECT is_active FROM products WHERE id = ?", [$product->id])->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, $row['is_active']);

        // Verify it retrieves as true
        $retrieved = new BooleanCastTestModel($product->id);
        $this->assertTrue($retrieved->is_active);
    }

    public function testCheckboxFormBehaviorUnchecked(): void
    {
        // Simulate unchecked checkbox: sends nothing, defaults to false
        $checkboxValue = false;
        
        $product = new BooleanCastTestModel();
        $product->name = 'Test Product';
        $product->is_active = $checkboxValue;
        $product->save();

        // Verify it's stored as 0
        $row = $this->db->query("SELECT is_active FROM products WHERE id = ?", [$product->id])->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(0, $row['is_active']);

        // Verify it retrieves as false
        $retrieved = new BooleanCastTestModel($product->id);
        $this->assertFalse($retrieved->is_active);
    }

    public function testMultipleBooleanFields(): void
    {
        $product = new BooleanCastTestModel();
        $product->name = 'Test Product';
        $product->is_active = true;
        $product->is_featured = false;
        $product->in_stock = true;
        $product->save();

        $retrieved = new BooleanCastTestModel($product->id);
        
        $this->assertTrue($retrieved->is_active);
        $this->assertFalse($retrieved->is_featured);
        $this->assertTrue($retrieved->in_stock);
    }

    public function testBooleanUpdateFromTrueToFalse(): void
    {
        $product = new BooleanCastTestModel();
        $product->name = 'Test Product';
        $product->is_active = true;
        $product->save();

        $this->assertTrue($product->is_active);

        // Update to false
        $product->is_active = false;
        $product->save();

        // Verify in database
        $row = $this->db->query("SELECT is_active FROM products WHERE id = ?", [$product->id])->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(0, $row['is_active']);

        // Verify retrieval
        $retrieved = new BooleanCastTestModel($product->id);
        $this->assertFalse($retrieved->is_active);
    }

    public function testBooleanUpdateFromFalseToTrue(): void
    {
        $product = new BooleanCastTestModel();
        $product->name = 'Test Product';
        $product->is_active = false;
        $product->save();

        $this->assertFalse($product->is_active);

        // Update to true
        $product->is_active = true;
        $product->save();

        // Verify in database
        $row = $this->db->query("SELECT is_active FROM products WHERE id = ?", [$product->id])->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, $row['is_active']);

        // Verify retrieval
        $retrieved = new BooleanCastTestModel($product->id);
        $this->assertTrue($retrieved->is_active);
    }

    public function testBooleanWithStringValues(): void
    {
        $product = new BooleanCastTestModel();
        $product->name = 'Test Product';
        
        // Test truthy string values
        $product->is_active = '1';
        $product->save();
        $this->assertTrue((new BooleanCastTestModel($product->id))->is_active);

        // Test falsy string values
        $product->is_active = '0';
        $product->save();
        $this->assertFalse((new BooleanCastTestModel($product->id))->is_active);

        // Test empty string (falsy)
        $product->is_active = '';
        $product->save();
        $this->assertFalse((new BooleanCastTestModel($product->id))->is_active);
    }

    public function testBooleanWithNumericValues(): void
    {
        $product = new BooleanCastTestModel();
        $product->name = 'Test Product';
        
        // Test numeric 1
        $product->is_active = 1;
        $product->save();
        $this->assertTrue((new BooleanCastTestModel($product->id))->is_active);

        // Test numeric 0
        $product->is_active = 0;
        $product->save();
        $this->assertFalse((new BooleanCastTestModel($product->id))->is_active);
    }

    public function testInvalidCastTypeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown cast type: 'boolean'");
        
        // Create a model with invalid cast type
        $model = new class extends Model {
            protected $table = 'products';
            protected $casts = ['is_active' => 'boolean'];  // Invalid - should be 'bool'
        };
        
        $model->name = 'Test Product';
        $model->is_active = true;
        $model->save();  // Should throw exception
    }
}
