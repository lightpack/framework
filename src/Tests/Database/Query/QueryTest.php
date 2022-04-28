<?php

require_once __DIR__ . '/../Lucid/Product.php';

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Collection;
use Lightpack\Http\Request;
use Lightpack\Pagination\Pagination as BasePagination;
use Lightpack\Database\Lucid\Pagination as LucidPagination;
use Lightpack\Database\Pdo;
use PHPUnit\Framework\TestCase;

// Initalize container
$container = new Container();

final class QueryTest extends TestCase
{
    private $db;

    /** @var \Lightpack\Database\Query\Query */
    private $query;

    public function setUp(): void
    {
        $config = require __DIR__ . '/../tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $sql = file_get_contents(__DIR__ . '/../tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();
        $this->query = new \Lightpack\Database\Query\Query('products', $this->db);

        // Configure container
        global $container;
        $container->register('db', function() { return $this->db; });
        $container->register('request', function() { return new Request(); });

        // Set Request URI
        $_SERVER['REQUEST_URI'] = '/lightpack';
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE `products`, `options`, `owners`;";
        $this->db->query($sql);
        $this->db = null;
    }

    public function testSelectFetchAll()
    {
        // Test 1
        $products = $this->query->select('id', 'name')->all();
        $this->assertGreaterThan(0, count($products));
        $this->query->resetQuery();

        // Test 2
        $products = $this->query->select('id', 'name')->all();
        $this->assertGreaterThan(0, count($products));
        $this->query->resetQuery();

        // Test 2
        $products = $this->query->select('id', 'name')->where('color', '=', 'maroon')->all();
        $this->assertEquals(0, count($products));
        $this->assertIsArray($products);
        $this->assertEmpty($products);
        $this->assertNotNull($products);
        $this->query->resetQuery();

        // Test 3
        $products = Product::query()->all();
        $this->assertGreaterThan(0, count($products));
        $this->assertInstanceOf(Collection::class, $products);
    }

    public function testSelectFetchOne()
    {
        // Test 1
        $product = $this->query->one();
        $this->assertTrue(isset($product->id));
        $this->query->resetQuery();

         // Test 2
         $product = $this->query->one();
         $this->assertTrue(isset($product->id));
         $this->query->resetQuery();

        // Test 3
        $product = $this->query->where('color', '=', 'maroon')->one();
        $this->assertFalse($product);
        $this->query->resetQuery();

        // Test 4
        $product = Product::query()->one();
        $this->assertInstanceOf(Product::class, $product);
    }

    public function testSelectFetchColumn()
    {
        // Test 1
        $name = $this->query->column('name');
        $this->assertIsString($name);
        $this->query->resetQuery();
    }

    public function testCompiledSelectQuery()
    {
        // Test 1
        $this->assertEquals(
            'SELECT * FROM products',
            $this->query->getCompiledSelect()
        );
        $this->query->resetQuery();

        // Test 2
        $this->query->select('id', 'name');

        $this->assertEquals(
            'SELECT id, name FROM products',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 3
        $this->query->select('id', 'name')->orderBy('id');

        $this->assertEquals(
            'SELECT id, name FROM products ORDER BY id ASC',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 4
        $this->query->select('id', 'name')->orderBy('id', 'DESC');

        $this->assertEquals(
            'SELECT id, name FROM products ORDER BY id DESC',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 5
        $this->query->select('id', 'name')->orderBy('name', 'DESC')->orderBy('id', 'DESC');

        $this->assertEquals(
            'SELECT id, name FROM products ORDER BY name DESC, id DESC',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 6
        $this->query->select('name')->distinct();

        $this->assertEquals(
            'SELECT DISTINCT name FROM products',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 7
        $this->query->where('id', '>', 2);

        $this->assertEquals(
            'SELECT * FROM products WHERE id > ?',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 8
        $this->query->where('id', '>', 2)->where('color', '=', '#000');

        $this->assertEquals(
            'SELECT * FROM products WHERE id > ? AND color = ?',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 9
        $this->query->where('id', '>', 2)->where('color', '=', '#000')->orWhere('color', '=', '#FFF');

        $this->assertEquals(
            'SELECT * FROM products WHERE id > ? AND color = ? OR color = ?',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 10
        $this->query->whereIn('id', [23, 24, 25]);

        $this->assertEquals(
            'SELECT * FROM products WHERE id IN (?, ?, ?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 11
        $this->query->whereIn('id', [23, 24, 25])->orWhereIn('color', ['#000']);

        $this->assertEquals(
            'SELECT * FROM products WHERE id IN (?, ?, ?) OR color IN (?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 12
        $this->query->whereNotIn('id', [23, 24, 25]);

        $this->assertEquals(
            'SELECT * FROM products WHERE id NOT IN (?, ?, ?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 13
        $this->query->whereNotIn('id', [23, 24, 25])->orWhereNotIn('color', ['#000', '#FFF']);

        $this->assertEquals(
            'SELECT * FROM products WHERE id NOT IN (?, ?, ?) OR color NOT IN (?, ?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 14
        $this->query->join('options', 'products.id', 'options.product_id');

        $this->assertEquals(
            'SELECT * FROM products INNER JOIN options ON products.id = options.product_id',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 15
        $this->query->leftJoin('options', 'options.product_id', 'products.id');

        $this->assertEquals(
            'SELECT * FROM products LEFT JOIN options ON options.product_id = products.id',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 16
        $this->query->rightJoin('options', 'products.id', 'options.product_id');

        $this->assertEquals(
            'SELECT * FROM products RIGHT JOIN options ON products.id = options.product_id',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 17
        $this->query->select('products.*', 'options.name AS oname')->join('options', 'products.id', 'options.product_id');

        $this->assertEquals(
            'SELECT products.*, options.name AS oname FROM products INNER JOIN options ON products.id = options.product_id',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 18: alias
        $this->query->alias('p')->join('options AS o', 'p.id', 'o.product_id')->select('p.*', 'o.name AS oname');

        $this->assertEquals(
            'SELECT p.*, o.name AS oname FROM products AS p INNER JOIN options AS o ON p.id = o.product_id',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();
    }

    public function testGetMagicMethod()
    {
        $this->assertEquals('products', $this->query->table);
        $this->assertEquals([], $this->query->bindings);
    }

    public function testInsertMethod()
    {
        $products = $this->query->all();
        $productsCountBeforeInsert = count($products);

        $this->query->insert([
            'name' => 'Product 4',
            'color' => '#CCC',
        ]);

        $products = $this->query->all();
        $productsCountAfterInsert = count($products);

        $this->assertEquals($productsCountBeforeInsert + 1, $productsCountAfterInsert);
        $this->assertIsNumeric($this->query->lastInsertId());
    }

    public function testBulkInsertMethod()
    {
        $products = $this->query->all();
        $productsCountBeforeInsert = count($products);

        $this->query->bulkInsert([
            ['name' => 'Product 4', 'color' => '#CCC'],
            ['name' => 'Product 5', 'color' => '#CCC'],
            ['name' => 'Product 6', 'color' => '#CCC'],
        ]);

        $products = $this->query->all();
        $productsCountAfterInsert = count($products);

        $this->assertEquals($productsCountBeforeInsert + 3, $productsCountAfterInsert);

        // Test 2: Expect exception if no data is passed
        $this->expectException(Exception::class);
        $this->query->bulkInsert([]);

        // Test 3: Expect exception if data is not an array of arrays
        $this->expectException(Exception::class);
        $this->query->bulkInsert(['name' => 'Product 4', 'color' => '#CCC']);
    }

    public function testUpdateMethod()
    {
        $product = $this->query->select('id')->one();

        $this->query->where('id', '=', $product->id)->update(
            ['color' => '#09F']
        );

        $updatedProduct = $this->query->select('color')->where('id', '=', $product->id)->one();

        $this->assertEquals('#09F', $updatedProduct->color);
    }

    public function testUpdateWithMultipleWhereMethod()
    {
        $product = $this->query->select('id')->one();

        $this->query->where('id', '=', $product->id)->where('name', '=', 'Dummy')->update(
            ['color' => '#09F']
        );

        $updatedProduct = $this->query->select('color')->where('id', '=', $product->id)->one();

        $this->assertEquals('#09F', $updatedProduct->color);
    }

    public function testDeleteMethod()
    {
        $product = $this->query->orderBy('id', 'DESC')->one();
        $products = $this->query->all();
        $productsCountBeforeDelete = count($products);

        $this->query->where('id', '=', $product->id)->delete();

        $products = $this->query->all();
        $productsCountAfterDelete = count($products);

        $this->assertEquals($productsCountBeforeDelete - 1, $productsCountAfterDelete);
    }

    public function testDeleteWithMultipleWhereMethod()
    {
        $product = $this->query->select('id')->one();
        $products = $this->query->all();
        $productsCountBeforeDelete = count($products);

        $this->query->where('id', '=', $product->id)->where('name', '=', 'Foo Bar')->delete();

        $products = $this->query->all();
        $productsCountAfterDelete = count($products);

        // Because we have no product with name 'Fo Bar', the count should be the same
        $this->assertEquals($productsCountBeforeDelete, $productsCountAfterDelete);
    }

    public function testWhereLogicalGroupingOfParameters()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE (color = ? OR color = ?)';
        $this->query->where(function($q) {
            $q->where('color', '=', '#000')->orWhere('color', '=', '#FFF');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products WHERE id = ? AND (color = ? OR color = ?)';
        $this->query->where('id', '=', 1)->where(function($q) {
            $q->where('color', '=', '#000')->orWhere('color', '=', '#FFF');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereInLogicalGroupingOfParameters()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE (color IN (?, ?) OR color IN (?, ?))';
        $this->query->where(function($q) {
            $q->whereIn('color', ['#000', '#FFF'])->orWhereIn('color', ['#000', '#FFF']);
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products WHERE size IN (SELECT id FROM sizes)';
        $this->query->whereIn('size', function($q) {
                $q->select('id')->from('sizes');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM products WHERE color IN (?, ?, ?) AND size IN (SELECT id FROM sizes WHERE is_active = ?)';
        $this->query
            ->whereIn('color', ['#000', '#FFF', '#CCC'])
            ->whereIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 4
        $sql = 'SELECT * FROM products WHERE color IN (?, ?, ?) OR size IN (SELECT id FROM sizes WHERE is_active = ?)';
        $this->query
            ->whereIn('color', ['#000', '#FFF', '#CCC'])
            ->orWhereIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 5
        $sql = 'SELECT * FROM products WHERE color IN (?, ?, ?) AND size IN (SELECT id FROM sizes WHERE is_active = ?) OR size IN (SELECT id FROM sizes WHERE is_active = ?)';
        $this->query
            ->whereIn('color', ['#000', '#FFF', '#CCC'])
            ->whereIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            })
            ->orWhereIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 6
        $sql = 'SELECT * FROM products WHERE color NOT IN (?, ?, ?) AND size NOT IN (SELECT id FROM sizes WHERE is_active = ?)';
        $this->query
            ->whereNotIn('color', ['#000', '#FFF', '#CCC'])
            ->whereNotIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 7
        $sql = 'SELECT * FROM products WHERE color NOT IN (?, ?, ?) OR size NOT IN (SELECT id FROM sizes WHERE is_active = ?)';
        $this->query
            ->whereNotIn('color', ['#000', '#FFF', '#CCC'])
            ->orWhereNotIn('size', function($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereColumnMatchesSubQuery()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE size IN (SELECT id FROM sizes WHERE size = ?)';
        $this->query->where('size', 'IN', function($q) {
            $q->from('sizes')->select('id')->where('size', '=', 'XL');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereExistsSubQuery()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE EXISTS (SELECT id FROM sizes WHERE size = ?)';
        $this->query->whereExists(function($q) {
            $q->from('sizes')->select('id')->where('size', '=', 'XL');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereNotExistsSubQuery()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE NOT EXISTS (SELECT id FROM sizes WHERE size = ?)';
        $this->query->whereNotExists(function($q) {
            $q->from('sizes')->select('id')->where('size', '=', 'XL');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereRaw()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE color = ? AND size = ?';
        $this->query->whereRaw('color = ? AND size = ?', ['#000', 'XL']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products WHERE color = ? AND size = ? AND is_active = 1';
        $this->query->whereRaw('color = ? AND size = ?', ['#000', 'XL'])->whereRaw('is_active = 1');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM products WHERE (color = ? AND size = ?) OR is_active = 1';
        $this->query->whereRaw('(color = ? AND size = ?)', ['#000', 'XL'])->orWhereRaw('is_active = 1');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 4
        $sql = "SELECT * FROM products WHERE color = ? OR status = 'active'";
        $this->query->where('color', '=', '#000')->orWhereRaw("status = 'active'");
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereNullConditions()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE color IS NULL';
        $this->query->whereNull('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products WHERE color IS NOT NULL';
        $this->query->whereNotNull('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM products WHERE color IS NULL OR size IS NULL';
        $this->query->whereNull('color')->orWhereNull('size');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
        
        // Test 4
        $sql = 'SELECT * FROM products WHERE color IS NULL OR size IS NOT NULL';
        $this->query->whereNull('color')->orWhereNotNull('size');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testLimitAndOffsetQueries()
    {
        // Test 1
        $sql = 'SELECT * FROM products LIMIT 10';
        $this->query->limit(10);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products LIMIT 10 OFFSET 20';
        $this->query->limit(10)->offset(20);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testGroupByQueries()
    {
        // Test 1
        $sql = 'SELECT * FROM products GROUP BY color';
        $this->query->groupBy('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products GROUP BY color, size';
        $this->query->groupBy('color', 'size');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereBetweenConditions()
    {
        // Test 1
        $sql = 'SELECT * FROM products WHERE price BETWEEN ? AND ?';
        $this->query->whereBetween('price', [10, 20]);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM products WHERE price NOT BETWEEN ? AND ?';
        $this->query->whereNotBetween('price', [10, 20]);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM products WHERE price BETWEEN ? AND ? OR size BETWEEN ? AND ?';
        $this->query->whereBetween('price', [10, 20])->orWhereBetween('size', ['M', 'L']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 4
        $sql = 'SELECT * FROM products WHERE price NOT BETWEEN ? AND ? OR size NOT BETWEEN ? AND ?';
        $this->query->whereNotBetween('price', [10, 20])->orWhereNotBetween('size', ['M', 'L']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 5
        $sql = 'SELECT * FROM products WHERE price BETWEEN ? AND ? AND size BETWEEN ? AND ?';
        $this->query->whereBetween('price', [10, 20])->whereBetween('size', ['M', 'L']); 
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 6
        $sql = 'SELECT * FROM products WHERE price NOT BETWEEN ? AND ? AND size NOT BETWEEN ? AND ?';
        $this->query->whereNotBetween('price', [10, 20])->whereNotBetween('size', ['M', 'L']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 7: Expect exception when passing in an array with less than 2 values
        $this->expectException(Exception::class);
        $this->query->whereBetween('price', [10]);
        $this->query->resetQuery();
    }

    public function testPaginateMethod()
    {
        // Test 1
        $this->query->paginate(10, 20);
        $this->assertInstanceOf(BasePagination::class, $this->query->paginate(10, 20));
        $this->query->resetQuery();

        // Test 2
        $products = Product::query()->paginate(10, 20);
        $this->assertInstanceOf(LucidPagination::class, $products);
    }

    public function testGroupByCount()
    {
        // Test 1
        $sql = 'SELECT color, COUNT(*) AS num FROM products GROUP BY color';
        $this->query->countBy('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testSetModel()
    {
        // Test 1
        $this->query->setModel(new Product);
        $this->assertInstanceOf(Product::class, $this->query->getModel());
        $this->query->resetQuery();
    }

    public function testSetConnection()
    {
        // Test 1
        $this->query->setConnection($this->db);
        $this->assertInstanceOf(Pdo::class, $this->query->getConnection());
        $this->query->resetQuery();
    }

    public function testMagicSetterMethod()
    {
        $this->query->bindings = ['foo' => 'bar'];
        $this->query->table = 'products';
        $this->query->columns = ['*'];

        $this->assertEquals(['foo' => 'bar'], $this->query->bindings);
        $this->assertEquals('products', $this->query->table);
        $this->assertEquals(['*'], $this->query->columns);

        // It should not set unwanted properties
        $this->query->foo = 'bar';
        $this->assertNull($this->query->foo);
    }
}
