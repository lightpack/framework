<?php

require_once 'Owner.php';
require_once 'Option.php';
require_once 'Product.php';

use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Database\Adapters\Mysql;

class StrictModeTest extends TestCase
{
    /** @var \Lightpack\Database\DB */
    private $db;

    protected function setUp(): void
    {
        parent::setUp();

        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();

        // Configure container
        $container = Container::getInstance();
        $container->register('db', function () {
            return $this->db;
        });
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE products, options, owners";
        $this->db->query($sql);
        $this->db = null;
    }

    public function test_strict_mode_prevents_lazy_loading()
    {
        // Create test data
        $this->db->table('products')->insert(['name' => 'Test Product', 'color' => '#000']);
        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $this->db->table('owners')->insert(['product_id' => $product->id, 'name' => 'John']);

        // Create a strict mode product model
        $strictProduct = new class extends Product {
            protected $strictMode = true;
        };

        $strictProduct->setConnection($this->db);
        $strictProduct = $strictProduct->find($product->id);

        // Should throw exception when accessing non-eager loaded relation
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Strict Mode: Relation 'owner' must be eager loaded");
        $strictProduct->owner;
    }

    public function test_strict_mode_prevents_non_whitelisted_relations()
    {
        // Create test data
        $this->db->table('products')->insert(['name' => 'Test Product', 'color' => '#000']);
        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $this->db->table('owners')->insert(['product_id' => $product->id, 'name' => 'John']);
        $this->db->table('options')->insert(['product_id' => $product->id, 'name' => 'Size', 'value' => 'XL']);

        // Create a strict mode product model with whitelisted relation
        $strictProduct = new class extends Product {
            protected $strictMode = true;
            protected $allowedLazyRelations = ['options'];
        };

        $strictProduct->setConnection($this->db);
        $strictProduct = $strictProduct->find($product->id);

        // Should throw exception for non-whitelisted relation
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Strict Mode: Relation 'owner' must be eager loaded");
        $strictProduct->owner;
    }

    public function test_strict_mode_allows_whitelisted_relations()
    {
        // Create test data
        $this->db->table('products')->insert(['name' => 'Test Product', 'color' => '#000']);
        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $this->db->table('owners')->insert(['product_id' => $product->id, 'name' => 'John']);
        $this->db->table('options')->insert(['product_id' => $product->id, 'name' => 'Size', 'value' => 'XL']);

        // Create a strict mode product model with whitelisted relation
        $strictProduct = new class extends Product {
            protected $strictMode = true;
            protected $allowedLazyRelations = ['options'];
        };

        $strictProduct->setConnection($this->db);
        $strictProduct = $strictProduct->find($product->id);

        // Should allow whitelisted relation
        $this->assertNotNull($strictProduct->options);
        $this->assertCount(1, $strictProduct->options);
    }

    public function test_strict_mode_works_with_eager_loading()
    {
        // Create test data
        $this->db->table('products')->insert(['name' => 'Test Product', 'color' => '#000']);
        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $this->db->table('owners')->insert(['product_id' => $product->id, 'name' => 'John']);

        // Create a strict mode product model
        $strictProduct = new class extends Product {
            protected $strictMode = true;
        };

        $strictProduct->setConnection($this->db);

        // Should work fine with eager loading
        $strictProduct = $strictProduct->query()->with('owner')->where('id', '=', $product->id)->one();
        
        $this->assertNotNull($strictProduct->owner);
        $this->assertEquals('John', $strictProduct->owner->name);
    }

    public function test_strict_mode_works_with_collection_load()
    {
        // Create test data
        $this->db->table('products')->insert(['name' => 'Test Product 1', 'color' => '#000']);
        $this->db->table('products')->insert(['name' => 'Test Product 2', 'color' => '#000']);
        foreach($this->db->table('products')->all() as $product) {
            $this->db->table('owners')->insert(['product_id' => $product->id, 'name' => 'Owner ' . $product->id]);
        }

        // Create a strict mode product model
        $strictProduct = new class extends Product {
            protected $strictMode = true;
        };

        $strictProduct->setConnection($this->db);

        // Get products without eager loading
        $products = $strictProduct->query()->all();

        // Should throw exception initially
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Strict Mode: Relation 'owner' must be eager loaded");
        $products[0]->owner;

        // Should work after explicitly loading the relation
        $products->load('owner');
        foreach($products as $product) {
            $this->assertNotNull($product->owner);
            $this->assertEquals('Owner ' . $product->id, $product->owner->name);
        }
    }

    public function test_strict_mode_with_multiple_relations()
    {
        // Create test data
        $this->db->table('products')->insert(['name' => 'Test Product', 'color' => '#000']);
        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $this->db->table('owners')->insert(['product_id' => $product->id, 'name' => 'John']);
        $this->db->table('options')->insert(['product_id' => $product->id, 'name' => 'Size', 'value' => 'XL']);
        $this->db->table('options')->insert(['product_id' => $product->id, 'name' => 'Color', 'value' => 'Red']);

        // Create a strict mode product model
        $strictProduct = new class extends Product {
            protected $strictMode = true;
        };

        $strictProduct->setConnection($this->db);

        // Should work with multiple eager loaded relations
        $strictProduct = $strictProduct->query()->with(['owner', 'options'])->where('id', '=', $product->id)->one();

        $this->assertNotNull($strictProduct->owner);
        $this->assertNotNull($strictProduct->options);
        $this->assertEquals('John', $strictProduct->owner->name);
        $this->assertCount(2, $strictProduct->options);
    }
}
