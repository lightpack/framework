<?php

require_once 'Product.php';
require_once 'Owner.php';
require_once 'Product.php';
require_once 'Option.php';

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Pagination;
use Lightpack\Http\Request;
use PHPUnit\Framework\TestCase;

// Initalize container
$container = new Container();

final class PaginationTest extends TestCase
{
    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config); 
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();
        $this->productsCollection = $this->db->model(Product::class)->query()->all();

        // Configure container
        global $container;
        $container->register('request', function() { return new Request(); });

        // Set Request URI
        $_SERVER['REQUEST_URI'] = '/lightpack';
    }

    public function testContructor()
    {
        $pagination = new Pagination($this->productsCollection);

        $this->assertEquals($this->productsCollection->count(), $pagination->count());
        $this->assertInstanceOf(Traversable::class, $pagination->items());
        $this->assertInstanceOf(JsonSerializable::class, $pagination);
        $this->assertInstanceOf(Countable::class, $pagination);
        $this->assertInstanceOf(IteratorAggregate::class, $pagination);
        $this->assertInstanceOf(Traversable::class, $pagination->getIterator());
    }

    public function testIsJsonSerializable()
    {
        $pagination = new Pagination($this->productsCollection);
        $json = $pagination->jsonSerialize();

        $this->assertArrayHasKey('total', $json);
        $this->assertArrayHasKey('per_page', $json);
        $this->assertArrayHasKey('current_page', $json);
        $this->assertArrayHasKey('last_page', $json);
        $this->assertArrayHasKey('path', $json);
        $this->assertArrayHasKey('links', $json);
        $this->assertArrayHasKey('items', $json);

        $this->assertEquals($this->productsCollection->count(), $json['total']);
        $this->assertEquals(10, $json['per_page']);
        $this->assertEquals(1, $json['current_page']);
        $this->assertEquals(1, $json['last_page']);
        $this->assertEquals('/lightpack', $json['path']);
        $this->assertEquals(['next' => null, 'prev' => null], $json['links']);
        $this->assertEquals($this->productsCollection->count(), count($json['items']));
    }

    public function testLoadMethod()
    {
        // insert options for the latest product
        $product = $this->db->table('products')->orderBy('id', 'desc')->one();
        $options = $this->db->table('options')->bulkInsert([
            ['product_id' => $product->id, 'name' => 'Option 1', 'value' => 'V1'],
            ['product_id' => $product->id, 'name' => 'Option 2', 'value' => 'V2'],
            ['product_id' => $product->id, 'name' => 'Option 3', 'value' => 'V3'],
        ]);

        // Query all products as collection
        $products = Product::query()->all();
        $pagination = new Pagination($products);
        $pagination->load('options');

        // fetch the last product in collection
        $product = $pagination->items()->last();

        // Assertions
        $this->assertTrue($product->hasAttribute('options'));
        $this->assertEquals(3, $this->productsCollection->last()->options->count());
        $this->assertEquals(3, $product->options->count());
    }
}
