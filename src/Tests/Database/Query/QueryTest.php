<?php

use PHPUnit\Framework\TestCase;

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
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE `products`, `options`, `owners`;";
        $this->db->query($sql);
        $this->db = null;
    }

    public function testSelectFetchAll()
    {
        $products = $this->query->select('id', 'name')->fetchAll();
        $this->assertGreaterThan(0, count($products));
    }

    public function testSelectFetchOne()
    {
        $product = $this->query->fetchOne();
        $this->assertTrue(isset($product->id));
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
        $this->query->where('id', '>', 2)->andWhere('color', '=', '#000');

        $this->assertEquals(
            'SELECT * FROM products WHERE id > ? AND color = ?',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 9
        $this->query->where('id', '>', 2)->andWhere('color', '=', '#000')->orWhere('color', '=', '#FFF');

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
        $this->query->whereIn('id', [23, 24, 25])->orWhereIn('color', ['#000', '#FFF']);

        $this->assertEquals(
            'SELECT * FROM products WHERE id IN (?, ?, ?) OR color IN (?, ?)',
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
    }

    public function testGetMagicMethod()
    {
        $this->assertEquals('products', $this->query->table);
        $this->assertEquals([], $this->query->bindings);
    }

    public function testInsertMethod()
    {
        $products = $this->query->fetchAll();
        $productsCountBeforeInsert = count($products);

        $this->query->insert([
            'name' => 'Product 4',
            'color' => '#CCC',
        ]);

        $products = $this->query->fetchAll();
        $productsCountAfterInsert = count($products);

        $this->assertEquals($productsCountBeforeInsert + 1, $productsCountAfterInsert);
    }

    public function testUpdateMethod()
    {
        $product = $this->query->select('id')->fetchOne();

        $this->query->where('id', '=', $product->id)->update(
            ['color' => '#09F']
        );

        $updatedProduct = $this->query->select('color')->where('id', '=', $product->id)->fetchOne();

        $this->assertEquals('#09F', $updatedProduct->color);
    }

    public function testDeleteMethod()
    {
        $product = $this->query->orderBy('id', 'DESC')->fetchOne();
        $products = $this->query->fetchAll();
        $productsCountBeforeDelete = count($products);

        $this->query->where('id', '=', $product->id)->delete();

        $products = $this->query->fetchAll();
        $productsCountAfterDelete = count($products);

        $this->assertEquals($productsCountBeforeDelete - 1, $productsCountAfterDelete);
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
}
