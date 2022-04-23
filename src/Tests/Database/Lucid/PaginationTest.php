<?php

require_once 'Product.php';

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
}
