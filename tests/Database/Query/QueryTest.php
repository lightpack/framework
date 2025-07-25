<?php

require_once __DIR__ . '/../Lucid/Product.php';

use Lightpack\Container\Container;
use Lightpack\Database\Lucid\Collection;
use Lightpack\Http\Request;
use Lightpack\Pagination\Pagination as BasePagination;
use Lightpack\Database\Lucid\Pagination as LucidPagination;
use Lightpack\Database\DB;
use Lightpack\Database\Query\Query;
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

        // Configure container
        $container = Container::getInstance();
        $container->register('db', function () {
            return $this->db;
        });
        $container->register('request', function () {
            return new Request();
        });

        // Set Request URI
        $_SERVER['REQUEST_URI'] = '/lightpack';
    }

    public function tearDown(): void
    {
        $sql = "DROP TABLE products, options, owners, users, roles, role_user, permissions, permission_role, projects, tasks, comments, articles, managers";
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
        $products = $this->query->select('id', 'name')->where('color', 'maroon')->all();
        $this->assertEquals(0, count($products));
        $this->assertIsArray($products);
        $this->assertEmpty($products);
        $this->assertNotNull($products);
        $this->query->resetQuery();

        // Test 4
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
        $this->assertNull($product);
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
            'SELECT * FROM `products`',
            $this->query->getCompiledSelect()
        );
        $this->query->resetQuery();

        // Test 2
        $this->query->select('id', 'name');

        $this->assertEquals(
            'SELECT `id`, `name` FROM `products`',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 3
        $this->query->select('id', 'name')->orderBy('id');

        $this->assertEquals(
            'SELECT `id`, `name` FROM `products` ORDER BY `id` ASC',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 4
        $this->query->select('id', 'name')->orderBy('id', 'DESC');

        $this->assertEquals(
            'SELECT `id`, `name` FROM `products` ORDER BY `id` DESC',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 5
        $this->query->select('id', 'name')->orderBy('name', 'DESC')->orderBy('id', 'DESC');

        $this->assertEquals(
            'SELECT `id`, `name` FROM `products` ORDER BY `name` DESC, `id` DESC',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 6
        $this->query->select('name')->distinct();

        $this->assertEquals(
            'SELECT DISTINCT `name` FROM `products`',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 7
        $this->query->where('id', '>', 2);

        $this->assertEquals(
            'SELECT * FROM `products` WHERE `id` > ?',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 8
        $this->query->where('id', '>', 2)->where('color', '=', '#000');

        $this->assertEquals(
            'SELECT * FROM `products` WHERE `id` > ? AND `color` = ?',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 9
        $this->query->where('id', '>', 2)->where('color', '=', '#000')->orWhere('color', '#FFF');

        $this->assertEquals(
            'SELECT * FROM `products` WHERE `id` > ? AND `color` = ? OR `color` = ?',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 10
        $this->query->whereIn('id', [23, 24, 25]);

        $this->assertEquals(
            'SELECT * FROM `products` WHERE `id` IN (?, ?, ?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test whereIn() for a single id
        $this->query->whereIn('id', [23]);

        $this->assertEquals(
            'SELECT * FROM `products` WHERE `id` IN (?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 11
        $this->query->whereIn('id', [23, 24, 25])->orWhereIn('color', ['#000']);

        $this->assertEquals(
            'SELECT * FROM `products` WHERE `id` IN (?, ?, ?) OR `color` IN (?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 12
        $this->query->whereNotIn('id', [23, 24, 25]);

        $this->assertEquals(
            'SELECT * FROM `products` WHERE `id` NOT IN (?, ?, ?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test whereNotIn() for a single id
        $this->query->whereNotIn('id', [23]);

        $this->assertEquals(
            'SELECT * FROM `products` WHERE `id` NOT IN (?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 13
        $this->query->whereNotIn('id', [23, 24, 25])->orWhereNotIn('color', ['#000', '#FFF']);

        $this->assertEquals(
            'SELECT * FROM `products` WHERE `id` NOT IN (?, ?, ?) OR `color` NOT IN (?, ?)',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 14
        $this->query->join('options', 'products.id', 'options.product_id');

        $this->assertEquals(
            'SELECT * FROM `products` INNER JOIN `options` ON `products`.`id` = `options`.`product_id`',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 15
        $this->query->leftJoin('options', 'options.product_id', 'products.id');

        $this->assertEquals(
            'SELECT * FROM `products` LEFT JOIN `options` ON `options`.`product_id` = `products`.`id`',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 16
        $this->query->rightJoin('options', 'products.id', 'options.product_id');

        $this->assertEquals(
            'SELECT * FROM `products` RIGHT JOIN `options` ON `products`.`id` = `options`.`product_id`',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 17
        $this->query->select('products.*', 'options.name AS oname')->join('options', 'products.id', 'options.product_id');

        $this->assertEquals(
            'SELECT `products`.*, `options`.`name` AS `oname` FROM `products` INNER JOIN `options` ON `products`.`id` = `options`.`product_id`',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();

        // Test 18: alias
        $this->query->alias('p')->join('options AS o', 'p.id', 'o.product_id')->select('p.*', 'o.name AS oname');

        $this->assertEquals(
            'SELECT `p`.*, `o`.`name` AS `oname` FROM `products` AS `p` INNER JOIN `options` AS `o` ON `p`.`id` = `o`.`product_id`',
            $this->query->getCompiledSelect()
        );

        $this->query->resetQuery();
    }

    public function testCompiledSelectForUpdate()
    {
        // Test 1
        $this->assertEquals(
            'SELECT * FROM `products` LIMIT 2 FOR UPDATE',
            $this->query->limit(2)->forUpdate()->getCompiledSelect()
        );

        $this->query->resetQuery();
    }

    public function testCompiledSelectForUpdateSkipLocked()
    {
        // Test 1
        $this->assertEquals(
            'SELECT * FROM `products` LIMIT 2 FOR UPDATE SKIP LOCKED',
            $this->query->limit(2)->forUpdate()->skipLocked()->getCompiledSelect()
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

        $this->query->insert([
            ['name' => 'Product 4', 'color' => '#CCC'],
            ['name' => 'Product 5', 'color' => '#CCC'],
            ['name' => 'Product 6', 'color' => '#CCC'],
        ]);

        $products = $this->query->all();
        $productsCountAfterInsert = count($products);

        $this->assertEquals($productsCountBeforeInsert + 3, $productsCountAfterInsert);
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
        $sql = 'SELECT * FROM `products` WHERE (`color` = ? OR `color` = ?)';
        $this->query->where(function ($q) {
            $q->where('color', '=', '#000')->orWhere('color', '=', '#FFF');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM `products` WHERE `id` = ? AND (`color` = ? OR `color` = ?)';
        $this->query->where('id', '=', 1)->where(function ($q) {
            $q->where('color', '=', '#000')->orWhere('color', '=', '#FFF');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereInLogicalGroupingOfParameters()
    {
        // Test 1
        $sql = 'SELECT * FROM `products` WHERE (`color` IN (?, ?) OR `color` IN (?, ?))';
        $this->query->where(function ($q) {
            $q->whereIn('color', ['#000', '#FFF'])->orWhereIn('color', ['#000', '#FFF']);
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM `products` WHERE `size` IN (SELECT `id` FROM `sizes`)';
        $this->query->whereIn('size', function ($q) {
            $q->select('id')->from('sizes');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM `products` WHERE `color` IN (?, ?, ?) AND `size` IN (SELECT `id` FROM `sizes` WHERE `is_active` = ?)';
        $this->query
            ->whereIn('color', ['#000', '#FFF', '#CCC'])
            ->whereIn('size', function ($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 4
        $sql = 'SELECT * FROM `products` WHERE `color` IN (?, ?, ?) OR `size` IN (SELECT `id` FROM `sizes` WHERE `is_active` = ?)';
        $this->query
            ->whereIn('color', ['#000', '#FFF', '#CCC'])
            ->orWhereIn('size', function ($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 5
        $sql = 'SELECT * FROM `products` WHERE `color` IN (?, ?, ?) AND `size` IN (SELECT `id` FROM `sizes` WHERE `is_active` = ?) OR `size` IN (SELECT `id` FROM `sizes` WHERE `is_active` = ?)';
        $this->query
            ->whereIn('color', ['#000', '#FFF', '#CCC'])
            ->whereIn('size', function ($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            })
            ->orWhereIn('size', function ($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 6
        $sql = 'SELECT * FROM `products` WHERE `color` NOT IN (?, ?, ?) AND `size` NOT IN (SELECT `id` FROM `sizes` WHERE `is_active` = ?)';
        $this->query
            ->whereNotIn('color', ['#000', '#FFF', '#CCC'])
            ->whereNotIn('size', function ($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 7
        $sql = 'SELECT * FROM `products` WHERE `color` NOT IN (?, ?, ?) OR `size` NOT IN (SELECT `id` FROM `sizes` WHERE `is_active` = ?)';
        $this->query
            ->whereNotIn('color', ['#000', '#FFF', '#CCC'])
            ->orWhereNotIn('size', function ($q) {
                $q->select('id')->from('sizes')->where('is_active', '=', 1);
            });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereColumnMatchesSubQuery()
    {
        // Test 1
        $sql = 'SELECT * FROM `products` WHERE `size` IN (SELECT `id` FROM `sizes` WHERE `size` = ?)';
        $this->query->where('size', 'IN', function ($q) {
            $q->from('sizes')->select('id')->where('size', '=', 'XL');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereExistsSubQuery()
    {
        // Test 1
        $sql = 'SELECT * FROM `products` WHERE EXISTS (SELECT `id` FROM `sizes` WHERE `size` = ?)';
        $this->query->whereExists(function ($q) {
            $q->from('sizes')->select('id')->where('size', '=', 'XL');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereNotExistsSubQuery()
    {
        // Test 1
        $sql = 'SELECT * FROM `products` WHERE NOT EXISTS (SELECT `id` FROM `sizes` WHERE `size` = ?)';
        $this->query->whereNotExists(function ($q) {
            $q->from('sizes')->select('id')->where('size', '=', 'XL');
        });
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereRaw()
    {
        // Test 1
        $sql = 'SELECT * FROM `products` WHERE color = ? AND size = ?';
        $this->query->whereRaw('color = ? AND size = ?', ['#000', 'XL']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM `products` WHERE `color` = ? AND `size` = ? AND `is_active` = 1';
        $this->query->whereRaw('`color` = ? AND `size` = ?', ['#000', 'XL'])->whereRaw('`is_active` = 1');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM `products` WHERE (`color` = ? AND `size` = ?) OR `is_active` = 1';
        $this->query->whereRaw('(`color` = ? AND `size` = ?)', ['#000', 'XL'])->orWhereRaw('`is_active` = 1');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 4
        $sql = "SELECT * FROM `products` WHERE `color` = ? OR `status` = 'active'";
        $this->query->where('color', '=', '#000')->orWhereRaw("`status` = 'active'");
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereNullConditions()
    {
        // Test 1
        $sql = 'SELECT * FROM `products` WHERE `color` IS NULL';
        $this->query->whereNull('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM `products` WHERE `color` IS NOT NULL';
        $this->query->whereNotNull('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM `products` WHERE `color` IS NULL OR `size` IS NULL';
        $this->query->whereNull('color')->orWhereNull('size');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 4
        $sql = 'SELECT * FROM `products` WHERE `color` IS NULL OR `size` IS NOT NULL';
        $this->query->whereNull('color')->orWhereNotNull('size');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereBoolean()
    {
        // Test 1
        $sql = 'SELECT * FROM `products` WHERE `published` IS TRUE';
        $this->query->from('products')->whereTrue('published');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM `products` WHERE `featured` IS FALSE';
        $this->query->from('products')->whereFalse('featured');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM `products` WHERE `published` IS TRUE OR `featured` IS FALSE';
        $this->query->from('products')->whereTrue('published')->orWhereFalse('featured');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 4
        $sql = 'SELECT * FROM `products` WHERE `published` IS TRUE AND `featured` IS FALSE';
        $this->query->from('products')->whereTrue('published')->whereFalse('featured');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 5
        $sql = 'SELECT * FROM `products` WHERE `published` IS TRUE AND `featured` IS TRUE';
        $this->query->from('products')->whereTrue('published')->whereTrue('featured');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 6
        $sql = 'SELECT * FROM `products` WHERE `published` IS FALSE OR `featured` IS TRUE';
        $this->query->from('products')->whereFalse('published')->orWhereTrue('featured');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testLimitAndOffsetQueries()
    {
        // Test 1
        $sql = 'SELECT * FROM `products` LIMIT 10';
        $this->query->limit(10);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM `products` LIMIT 10 OFFSET 20';
        $this->query->limit(10)->offset(20);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testGroupByQueries()
    {
        // Test 1
        $sql = 'SELECT * FROM `products` GROUP BY `color`';
        $this->query->groupBy('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM `products` GROUP BY `color`, `size`';
        $this->query->groupBy('color', 'size');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testWhereBetweenConditions()
    {
        // Test 1
        $sql = 'SELECT * FROM `products` WHERE `price` BETWEEN ? AND ?';
        $this->query->whereBetween('price', [10, 20]);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT * FROM `products` WHERE `price` NOT BETWEEN ? AND ?';
        $this->query->whereNotBetween('price', [10, 20]);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT * FROM `products` WHERE `price` BETWEEN ? AND ? OR `size` BETWEEN ? AND ?';
        $this->query->whereBetween('price', [10, 20])->orWhereBetween('size', ['M', 'L']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 4
        $sql = 'SELECT * FROM `products` WHERE `price` NOT BETWEEN ? AND ? OR `size` NOT BETWEEN ? AND ?';
        $this->query->whereNotBetween('price', [10, 20])->orWhereNotBetween('size', ['M', 'L']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 5
        $sql = 'SELECT * FROM `products` WHERE `price` BETWEEN ? AND ? AND `size` BETWEEN ? AND ?';
        $this->query->whereBetween('price', [10, 20])->whereBetween('size', ['M', 'L']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 6
        $sql = 'SELECT * FROM `products` WHERE `price` NOT BETWEEN ? AND ? AND `size` NOT BETWEEN ? AND ?';
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
        $products = $this->query->paginate(10);

        $this->assertInstanceOf(BasePagination::class, $this->query->paginate(10, 20));
        $this->assertCount(2, $products);

        foreach ($products as $product) {
            $this->assertIsArray($product);
        }

        $this->query->resetQuery();

        // Test 2
        $products = Product::query()->paginate(10, 20);
        $this->assertInstanceOf(LucidPagination::class, $products);
    }

    public function testGroupByCount()
    {
        // Test 1
        $sql = 'SELECT `color`, COUNT(*) AS num FROM `products` GROUP BY `color`';
        $this->query->countBy('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testSetConnection()
    {
        // Test 1
        $this->query->setConnection($this->db);
        $this->assertInstanceOf(DB::class, $this->query->getConnection());
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

    public function testInsertIgnore()
    {
        $product = $this->query->one();
        $products = $this->query->all();
        $productsCountBeforeInsert = count($products ?? []);

        // This should not insert a new product
        $this->query->insertIgnore([
            'id' => $product->id,
            'name' => 'Product 4',
            'color' => '#CCC',
        ]);

        // This should insert a new product
        $this->query->insertIgnore([
            'name' => 'Product 3',
            'color' => '#CCC',
        ]);

        $products = $this->query->all();
        $productsCountAfterInsert = count($products ?? []);

        $this->assertEquals($productsCountBeforeInsert + 1, $productsCountAfterInsert);
        $this->assertIsNumeric($this->query->lastInsertId());
    }

    public function testQueryChunkMethod()
    {
        // Make sure we have no records
        $this->query->delete();

        foreach (range(1, 25) as $item) {
            $records[] = ['name' => 'Product name', 'color' => '#CCC'];
        }

        $this->query->insert($records);

        // Process chunk query
        $chunkedRecords = [];

        $this->query->chunk(5, function ($records) use (&$chunkedRecords) {
            if (count($chunkedRecords) == 4) {
                return false;
            }

            $chunkedRecords[] = $records;
        });

        // Assertions
        $this->assertCount(4, $chunkedRecords);

        foreach ($chunkedRecords as $records) {
            $this->assertCount(5, $records);
        }
    }

    public function testQueryChunkWithEmptyTable()
    {
        // Make sure we have no records
        $this->query->delete();

        // Process chunk query on empty table
        $callbackExecuted = false;

        $this->query->chunk(5, function ($records) use (&$callbackExecuted) {
            $callbackExecuted = true;
        });

        // Callback should not execute on empty table
        $this->assertFalse($callbackExecuted);
    }

    public function testQueryChunkWithNonStandardChunkSize()
    {
        // Make sure we have no records
        $this->query->delete();

        // Insert 10 records
        foreach (range(1, 10) as $item) {
            $records[] = ['name' => 'Product name', 'color' => '#CCC'];
        }

        $this->query->insert($records);

        // Process chunk query with chunk size 3
        $chunkedRecords = [];

        $this->query->chunk(3, function ($records) use (&$chunkedRecords) {
            $chunkedRecords[] = $records;
        });

        // Should have 4 chunks: 3, 3, 3, and 1
        $this->assertCount(4, $chunkedRecords);
        $this->assertCount(3, $chunkedRecords[0]);
        $this->assertCount(3, $chunkedRecords[1]);
        $this->assertCount(3, $chunkedRecords[2]);
        $this->assertCount(1, $chunkedRecords[3]);
    }

    public function testQueryChunkWithOrderBy()
    {
        // Make sure we have no records
        $this->query->delete();

        // Insert records with different names
        foreach (range(1, 5) as $item) {
            $records[] = ['name' => 'Product ' . $item, 'color' => '#CCC'];
        }

        $this->query->insert($records);

        // Process chunk query with ordering
        $names = [];

        $this->query->orderBy('name', 'DESC')->chunk(5, function ($records) use (&$names) {
            foreach ($records as $record) {
                $names[] = $record->name;
            }
        });

        $this->assertEquals('Product 5', $names[0]);
        $this->assertEquals('Product 4', $names[1]);
        $this->assertEquals('Product 3', $names[2]);
        $this->assertEquals('Product 2', $names[3]);
        $this->assertEquals('Product 1', $names[4]);
    }

    public function testQueryChunkWithZeroChunkSize()
    {
        // Make sure we have no records
        $this->query->delete();

        // Insert a few records
        foreach (range(1, 5) as $item) {
            $records[] = ['name' => 'Product ' . $item, 'color' => '#CCC'];
        }

        $this->query->insert($records);

        // Process chunk query with zero chunk size (should default to some reasonable behavior)
        $callbackExecuted = false;

        $this->expectException(\InvalidArgumentException::class);
        $this->query->chunk(0, fn() => '');
    }

    public function testQueryChunkWithExactlyOneChunkSize()
    {
        // Make sure we have no records
        $this->query->delete();

        // Insert exactly 5 records
        foreach (range(1, 5) as $item) {
            $records[] = ['name' => 'Product ' . $item, 'color' => '#CCC'];
        }

        $this->query->insert($records);

        // Process chunk query with chunk size exactly matching record count
        $chunkedRecords = [];

        $this->query->chunk(5, function ($records) use (&$chunkedRecords) {
            $chunkedRecords[] = $records;
        });

        // Should have exactly 1 chunk with 5 records
        $this->assertCount(1, $chunkedRecords);
        $this->assertCount(5, $chunkedRecords[0]);
    }

    public function testQueryChunkWithWhereCondition()
    {
        // Make sure we have no records
        $this->query->delete();

        // Insert records with different colors
        foreach (range(1, 10) as $item) {
            $color = $item <= 5 ? 'red' : 'blue';
            $records[] = ['name' => 'Product ' . $item, 'color' => $color];
        }

        $this->query->insert($records);

        $this->query->where('color', 'red')->chunk(2, function ($records) {
            foreach ($records as $record) {
                $this->assertEquals('red', $record->color);
            }
        });
    }

    public function testItProducesCorrectSyntaxForAggregateQueries()
    {
        // Test 1
        $sql = 'SELECT SUM(`price`) AS sum FROM `products`';
        $this->query->sum('price');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();

        // Test 2
        $sql = 'SELECT AVG(`price`) AS avg FROM `products`';
        $this->assertEquals(150, $this->query->avg('price'));
        $this->query->resetQuery();

        // Test 3
        $sql = 'SELECT MIN(`price`) AS min FROM `products`';
        $this->assertEquals(100, $this->query->min('price'));
        $this->query->resetQuery();

        // Test 4
        $sql = 'SELECT MAX(`price`) AS max FROM `products`';
        $this->assertEquals(200, $this->query->max('price'));
        $this->query->resetQuery();
    }

    public function testExistsMethod()
    {
        // Test when records exist
        $exists = $this->query->exists();
        $this->assertTrue($exists);
        $this->query->resetQuery();

        // Test when no records exist
        $notExists = $this->query->where('color', '=', 'non-existent-color')->notExists();
        $this->assertTrue($notExists);
        $this->query->resetQuery();

        // Test with complex where conditions
        $exists = $this->query->where('id', '>', 0)->where('color', '!=', 'non-existent-color')->exists();
        $this->assertTrue($exists);
        $this->query->resetQuery();
    }

    public function testBasicHavingClause()
    {
        $sql = 'SELECT `color`, COUNT(*) AS num FROM `products` GROUP BY `color` HAVING `num` > ?';
        $this->query->columns = ['color', 'COUNT(*) AS num'];
        $this->query->groupBy('color')->having('num', '>', 1);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testMultipleHavingClausesWithAndOr()
    {
        $sql = 'SELECT `color`, COUNT(*) AS num FROM `products` GROUP BY `color` HAVING `num` > ? AND `num` < ? OR `num` = ?';
        $this->query->columns = ['color', 'COUNT(*) AS num'];
        $this->query->groupBy('color')
            ->having('num', '>', 1)
            ->having('num', '<', 10)
            ->orHaving('num', '=', 5);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testHavingRawClause()
    {
        $sql = 'SELECT `color`, COUNT(*) AS num FROM `products` GROUP BY `color` HAVING `num` > 1';
        $this->query->columns = ['color', 'COUNT(*) AS num'];
        $this->query->groupBy('color')->havingRaw('`num` > 1');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testHavingRawWithBindings()
    {
        $sql = 'SELECT `color`, COUNT(*) AS num FROM `products` GROUP BY `color` HAVING `num` > ?';
        $this->query->columns = ['color', 'COUNT(*) AS num'];
        $this->query->groupBy('color')->havingRaw('`num` > ?', [2]);
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals([2], $this->query->bindings);
        $this->query->resetQuery();
    }

    public function testHavingWithSumAvgMinMax()
    {
        $sql = 'SELECT `color`, SUM(`price`) AS sum, AVG(`price`) AS avg, MIN(`price`) AS min, MAX(`price`) AS max FROM `products` GROUP BY `color` HAVING `sum` > ? AND `avg` < ?';
        $this->query->columns = ['color', 'SUM(`price`) AS sum', 'AVG(`price`) AS avg', 'MIN(`price`) AS min', 'MAX(`price`) AS max'];
        $this->query->groupBy('color')
            ->having('sum', '>', 100)
            ->having('avg', '<', 200);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testHavingWithWhereAndGroupBy()
    {
        $sql = 'SELECT `color`, COUNT(*) AS num FROM `products` WHERE `color` != ? GROUP BY `color` HAVING COUNT(*) > ?';
        $this->query->columns = ['color', 'COUNT(*) AS num'];
        $this->query->where('color', '!=', 'maroon')->groupBy('color')->having('COUNT(*)', '>', 1);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testHavingWithAlias()
    {
        $sql = 'SELECT `color`, SUM(`price`) AS total FROM `products` GROUP BY `color` HAVING `total` > ?';
        $this->query->columns = ['color', 'SUM(`price`) AS total'];
        $this->query->groupBy('color')->having('total', '>', 100);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testHavingInAndNotIn()
    {
        $sql = 'SELECT `color`, COUNT(*) AS num FROM `products` GROUP BY `color` HAVING `num` IN (?, ?, ?) AND `num` NOT IN (?, ?)';
        $this->query->columns = ['color', 'COUNT(*) AS num'];
        $this->query->groupBy('color')
            ->having('num', 'IN', [1, 2, 3])
            ->having('num', 'NOT IN', [4, 5]);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testHavingBooleanAndNull()
    {
        $sql = 'SELECT `color`, COUNT(*) AS num FROM `products` GROUP BY `color` HAVING COUNT(*) IS TRUE AND COUNT(*) IS NOT NULL';
        $this->query->columns = ['color', 'COUNT(*) AS num'];
        $this->query->groupBy('color')
            ->having('COUNT(*)', 'IS TRUE')
            ->having('COUNT(*)', 'IS NOT NULL');
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testHavingWithMultipleAggregates()
    {
        $sql = 'SELECT `color`, SUM(`price`) AS sum, COUNT(*) AS num FROM `products` GROUP BY `color` HAVING `sum` > ? AND `num` > ?';
        $this->query->columns = ['color', 'SUM(`price`) AS sum', 'COUNT(*) AS num'];
        $this->query->groupBy('color')->having('sum', '>', 100)->having('num', '>', 1);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testHavingWithOrderByLimitOffset()
    {
        $sql = 'SELECT `color`, COUNT(*) AS num FROM `products` GROUP BY `color` HAVING COUNT(*) > ? ORDER BY `color` ASC LIMIT 5 OFFSET 2';
        $this->query->columns = ['color', 'COUNT(*) AS num'];
        $this->query->groupBy('color')->having('COUNT(*)', '>', 1)->orderBy('color')->limit(5)->offset(2);
        $this->assertEquals($sql, $this->query->toSql());
        $this->query->resetQuery();
    }

    public function testSelectRawWithBindings()
    {
        // Test 1: Simple parameterized select raw
        $sql = 'SELECT ?, ? FROM `products`';
        $this->query->selectRaw('?, ?', [1, 2]);
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals([1, 2], $this->query->bindings);
        $this->query->resetQuery();

        // Test 2: Function with alias and binding
        $sql = 'SELECT SUM(price) > ? AS expensive FROM `products`';
        $this->query->selectRaw('SUM(price) > ? AS expensive', [100]);
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals([100], $this->query->bindings);
        $this->query->resetQuery();

        // Test 3: Mixing selectRaw and select columns
        $sql = 'SELECT SUM(price) > ? AS expensive, ?, `name` FROM `products`';
        $this->query->selectRaw('SUM(price) > ? AS expensive', [100])->select('name')->selectRaw('?', ['rawval']);
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals([100, 'rawval'], $this->query->bindings);
        $this->query->resetQuery();

        // Test 4: Multiple selectRaw expressions
        $sql = 'SELECT ?, ?, `color` FROM `products`';
        $this->query->selectRaw('?', ['a'])->selectRaw('?', ['b'])->select('color');
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals(['a', 'b'], $this->query->bindings);
        $this->query->resetQuery();

        // Test 5: selectRaw and select with aggregate
        $sql = 'SELECT ?, COUNT(*) AS num FROM `products`';
        $this->query->selectRaw('?', ['xyz'])->select('COUNT(*) AS num');
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals(['xyz'], $this->query->bindings);
        $this->query->resetQuery();
    }

    // full text search

    public function testFullTextSearch()
    {
        // Test 1: Basic full-text search SQL and bindings
        $sql = "SELECT * FROM `products` WHERE MATCH(`title`, `body`) AGAINST (? IN BOOLEAN MODE)";
        $this->query->search('foo bar', ['title', 'body'])->from('products');
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals(['foo bar'], $this->query->bindings);
        $this->query->resetQuery();

        // Test 2: Search with additional where clause
        $sql = "SELECT * FROM `products` WHERE MATCH(`title`, `body`) AGAINST (? IN BOOLEAN MODE) AND `status` = ?";
        $this->query->search('baz', ['title', 'body'])->where('status', 'published')->from('products');
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals(['baz', 'published'], $this->query->bindings);
        $this->query->resetQuery();

        // Test 3: Search with order and limit
        $sql = "SELECT * FROM `products` WHERE MATCH(`title`) AGAINST (? IN BOOLEAN MODE) ORDER BY `created_at` DESC LIMIT 5";
        $this->query->search('apple', ['title'])->from('products')->orderBy('created_at', 'DESC')->limit(5);
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals(['apple'], $this->query->bindings);
        $this->query->resetQuery();

        // Test 4: Search with boolean operators in term
        $sql = "SELECT * FROM `products` WHERE MATCH(`title`, `body`) AGAINST (? IN BOOLEAN MODE)";
        $this->query->search('+foo -bar', ['title', 'body'])->from('products');
        $this->assertEquals($sql, $this->query->toSql());
        $this->assertEquals(['+foo -bar'], $this->query->bindings);
        $this->query->resetQuery();
    }
}
