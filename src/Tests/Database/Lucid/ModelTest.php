<?php

require_once 'Product.php';

use PHPUnit\Framework\TestCase;
use \Lightpack\Database\Lucid\Model;
use Lightpack\Exceptions\RecordNotFoundException;

final class ModelTest extends TestCase
{
    private $db;
    private $model;

    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';

        $this->db = new \Lightpack\Database\Adapters\Mysql($config); 
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();
        $this->product = $this->db->model(Product::class);
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE `products`, `options`, `owners`;";
        $this->db->query($sql);
        $this->db = null;
    }
    
    public function testModelInstance()
    {
        $this->assertInstanceOf(Model::class, $this->product);
    }

    public function testModelSaveInsertMethod()
    {
        $products = $this->db->table('products')->all();
        $productsCountBeforeSave = count($products);

        $this->product->name = 'Dummy Product';
        $this->product->color = '#CCC';
        $this->product->save();

        $products = $this->db->table('products')->all();
        $productsCountAfterSave = count($products);

        $this->assertEquals($productsCountBeforeSave + 1, $productsCountAfterSave);
    }

    public function testModelSaveUpdateMethod()
    {
        $product = $this->db->table('products')->one();
        $products = $this->db->table('products')->all();
        $productsCountBeforeSave = count($products);

        $this->product->find($product->id);
        $this->product->name = 'ACME Product';
        $this->product->save();

        $products = $this->db->table('products')->all();
        $productsCountAfterSave = count($products);

        $this->assertEquals($productsCountBeforeSave, $productsCountAfterSave);
    }

    public function testModelDeleteMethod()
    {
        $product = $this->db->table('products')->one();
        $products = $this->db->table('products')->all();
        $productsCountBeforeDelete = count($products);

        $this->product->find($product->id);
        $this->product->delete();

        $products = $this->db->table('products')->all();
        $productsCountAfterDelete = count($products);

        $this->assertEquals($productsCountBeforeDelete - 1, $productsCountAfterDelete);
    }

    public function testModelDeleteWithIdMethod()
    {
        $productsCountBeforeDelete = Product::query()->count();
        $product = Product::query()->one();
        (new Product)->delete($product->id);
        $productsCountAfterDelete = Product::query()->count();

        $this->assertEquals($productsCountBeforeDelete - 1, $productsCountAfterDelete);
    }

    public function testModelDeleteWhenIdNotSet()
    {
        $this->assertNull($this->product->delete());
    }

    public function testModelHasOneRelation()
    {
        $this->db->table('products')->insert(['name' => 'Dummy Product', 'color' => '#CCC']);
        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $owner = $this->db->table('owners')->where('product_id', '=', $product->id)->one();
        
        if(!isset($owner->id)) {
            $this->db->table('owners')->insert(['product_id' => $product->id, 'name' => 'Bob']);
        }

        $this->product->find($product->id);
        $productOwner = $this->product->owner;
        $this->assertNotNull($productOwner->id);
    }

    public function testModelHasManyRelation()
    {
        $this->db->table('products')->insert(['name' => 'Dummy Product', 'color' => '#CCC']);
        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $this->db->table('options')->insert(['product_id' => $product->id, 'name' => 'Size', 'value' => 'XL']);
        $this->db->table('options')->insert(['product_id' => $product->id, 'name' => 'Color', 'value' => '#000']);
        
        $this->product->find($product->id);
        $productOptions = $this->product->options;
        $this->assertEquals(2, count($productOptions));
    }

    public function testModelBelongsToRelation()
    {
        $this->db->table('products')->insert(['name' => 'Dummy Product', 'color' => '#CCC']);
        $product = $this->db->table('products')->orderBy('id', 'DESC')->one();
        $owner = $this->db->table('owners')->where('product_id', '=', $product->id)->one();
        
        if(!isset($owner->id)) {
            $this->db->table('owners')->insert(['product_id' => $product->id, 'name' => 'Bob']);
            $owner = $this->db->table('owners')->where('product_id', '=', $product->id)->one();
        }

        $ownerModel = $this->db->model(Owner::class);
        $ownerModel->find($owner->id);
        $ownerProduct = $ownerModel->product;

        $this->assertNotNull($ownerProduct->id);
    }

    public function testLastInsertId()
    {
        $product = new Product();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->save();
        $lastInsertId = $product->lastInsertId();
        
        // Fetch the latest product
        $product = Product::query()->orderBy('id', 'DESC')->one();
        $this->assertTrue($product->id == $lastInsertId);
    }

    public function testModelToArrayMethod()
    {
        $product = product::query()->one();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->save();
        $productArray = $product->toArray();

        $this->assertTrue(is_array($productArray));
        $this->assertTrue(isset($productArray['id']));
        $this->assertTrue(isset($productArray['name']));
        $this->assertTrue(isset($productArray['color']));
        $this->assertTrue($productArray['id'] == $product->id);
        $this->assertTrue($productArray['name'] == $product->name);
        $this->assertTrue($productArray['color'] == $product->color);
    }

    public function testModelSetAttribute()
    {
        $product = new Product();
        $product->setAttribute('name', 'Dummy Product');
        $product->setAttribute('color', '#CCC');
        $product->save();

        $product = Product::query()->orderBy('id', 'DESC')->one();

        $this->assertEquals('Dummy Product', $product->name);
        $this->assertEquals('#CCC', $product->color);
    }

    public function testModelGetAttributeMethod()
    {
        $product = product::query()->one();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->save();

        $this->assertEquals($product->name, $product->getAttribute('name'));
        $this->assertEquals($product->color, $product->getAttribute('color'));
        $this->assertEquals($product->name, $product->getAttribute('name', 'default'));
        $this->assertNull($product->getAttribute('non_existing_attribute'));
    }

    public function testHasAttributeMethod()
    {
        $product = product::query()->one();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->save();

        $this->assertTrue($product->hasAttribute('name'));
        $this->assertTrue($product->hasAttribute('color'));
        $this->assertFalse($product->hasAttribute('non_existing_attribute'));
    }

    public function testModelGetAttributesMethod()
    {
        $product = product::query()->one();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->save();
        $productAttributes = $product->getAttributes();

        $this->assertTrue(is_object($productAttributes));
        $this->assertTrue(isset($productAttributes->id));
        $this->assertTrue(isset($productAttributes->name));
        $this->assertTrue(isset($productAttributes->color));
        $this->assertTrue($productAttributes->id == $product->id);
        $this->assertTrue($productAttributes->name == $product->name);
        $this->assertTrue($productAttributes->color == $product->color);
    }

    public function testSaveAndRefresh()
    {
        $product = new Product();
        $product->name = 'Dummy Product';
        $product->color = '#CCC';
        $product->saveAndRefresh();
        $latestProduct = Product::query()->orderBy('id', 'DESC')->one();

        $this->assertTrue($product->id == $latestProduct->id);
        $this->assertTrue($product->name == $latestProduct->name);
        $this->assertTrue($product->color == $latestProduct->color);
    }

    public function testContructThrowsRecordNotFoundException()
    {
        $this->expectException(RecordNotFoundException::class);
        new Product('non_existing_id');
    }

    public function testRecordNotFoundException()
    {
        $this->expectException(RecordNotFoundException::class);
        $product = new Product();
        $product->find(1);
    }
}

