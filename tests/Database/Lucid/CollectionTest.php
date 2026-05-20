<?php

use Lightpack\Database\Lucid\Collection;
use Lightpack\Database\Lucid\Model;
use PHPUnit\Framework\TestCase;

class CollectionItem extends Model
{
    protected $table = 'collection_items';
    protected $timestamps = false;
}

final class CollectionTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function make(array $data): CollectionItem
    {
        $item = new CollectionItem;
        foreach ($data as $key => $value) {
            $item->setAttribute($key, $value);
        }

        return $item;
    }

    private function collect(array $rows): Collection
    {
        return new Collection(array_map([$this, 'make'], $rows));
    }

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function testEmptyCollectionCreation()
    {
        $c = new Collection([]);
        $this->assertCount(0, $c);
        $this->assertTrue($c->isEmpty());
    }

    public function testSingleModelWrappedInCollection()
    {
        $item = $this->make(['id' => 1, 'name' => 'Solo']);
        $c = new Collection($item);
        $this->assertCount(1, $c);
    }

    public function testCountableInterface()
    {
        $c = $this->collect([['id' => 1], ['id' => 2], ['id' => 3]]);
        $this->assertEquals(3, count($c));
    }

    // -------------------------------------------------------------------------
    // ids / isEmpty / isNotEmpty
    // -------------------------------------------------------------------------

    public function testIds()
    {
        $c = $this->collect([['id' => 10], ['id' => 20], ['id' => 30]]);
        $this->assertEquals([10, 20, 30], $c->ids());
    }

    public function testIsEmpty()
    {
        $this->assertTrue((new Collection([]))->isEmpty());
        $this->assertFalse($this->collect([['id' => 1]])->isEmpty());
    }

    public function testIsNotEmpty()
    {
        $this->assertFalse((new Collection([]))->isNotEmpty());
        $this->assertTrue($this->collect([['id' => 1]])->isNotEmpty());
    }

    // -------------------------------------------------------------------------
    // find / first / last
    // -------------------------------------------------------------------------

    public function testFindByPrimaryKey()
    {
        $c = $this->collect([['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']]);
        $this->assertEquals('B', $c->find(2)->name);
    }

    public function testFindReturnsDefaultWhenMissing()
    {
        $c = $this->collect([['id' => 1]]);
        $this->assertNull($c->find(99));

        $fallback = $this->make(['id' => 0]);
        $this->assertSame($fallback, $c->find(99, $fallback));
    }

    public function testFirstWithNoConditions()
    {
        $c = $this->collect([['id' => 1, 'name' => 'First'], ['id' => 2, 'name' => 'Second']]);
        $this->assertEquals(1, $c->first()->id);
    }

    public function testFirstWithConditions()
    {
        $c = $this->collect([
            ['id' => 1, 'status' => 'active'],
            ['id' => 2, 'status' => 'inactive'],
            ['id' => 3, 'status' => 'active'],
        ]);
        $this->assertEquals(1, $c->first(['status' => 'active'])->id);
        $this->assertNull($c->first(['status' => 'pending']));
    }

    public function testFirstReturnsNullOnEmpty()
    {
        $this->assertNull((new Collection([]))->first());
    }

    public function testLast()
    {
        $c = $this->collect([['id' => 1], ['id' => 2], ['id' => 3]]);
        $this->assertEquals(3, $c->last()->id);
    }

    public function testLastReturnsNullOnEmpty()
    {
        $this->assertNull((new Collection([]))->last());
    }

    // -------------------------------------------------------------------------
    // column
    // -------------------------------------------------------------------------

    public function testColumn()
    {
        $c = $this->collect([
            ['id' => 1, 'email' => 'a@test.com'],
            ['id' => 2, 'email' => 'b@test.com'],
        ]);
        $this->assertEquals(['a@test.com', 'b@test.com'], $c->column('email'));
    }

    public function testColumnSkipsFalsyValues()
    {
        $c = $this->collect([
            ['id' => 1, 'status' => 'active'],
            ['id' => 2, 'status' => null],
            ['id' => 3, 'status' => ''],
            ['id' => 4, 'status' => 'inactive'],
        ]);
        $this->assertEquals(['active', 'inactive'], $c->column('status'));
    }

    // -------------------------------------------------------------------------
    // any / asMap
    // -------------------------------------------------------------------------

    public function testAny()
    {
        $c = new Collection([
            $this->make(['id' => 1]),
            $this->make(['id' => 2, 'admin' => true]),
        ]);
        $this->assertTrue($c->any('admin'));
        $this->assertFalse($c->any('superuser'));
    }

    public function testAsMap()
    {
        $c = $this->collect([
            ['id' => 1, 'slug' => 'foo'],
            ['id' => 2, 'slug' => 'bar'],
        ]);
        $map = $c->asMap('slug');
        $this->assertArrayHasKey('foo', $map);
        $this->assertEquals(1, $map['foo']->id);
    }

    // -------------------------------------------------------------------------
    // filter / map / each
    // -------------------------------------------------------------------------

    public function testFilter()
    {
        $c = $this->collect([
            ['id' => 1, 'active' => true],
            ['id' => 2, 'active' => false],
            ['id' => 3, 'active' => true],
        ]);
        $filtered = $c->filter(fn ($item) => $item->active);
        $this->assertEquals([1, 3], $filtered->ids());
    }

    public function testFilterReturnsNewInstance()
    {
        $c = $this->collect([['id' => 1], ['id' => 2]]);
        $filtered = $c->filter(fn ($i) => $i->id === 1);
        $this->assertNotSame($c, $filtered);
        $this->assertCount(2, $c); // original unchanged
    }

    public function testMap()
    {
        $c = $this->collect([['id' => 1, 'name' => 'alice'], ['id' => 2, 'name' => 'bob']]);
        $names = $c->map(fn ($i) => strtoupper($i->name));
        $this->assertEquals(['ALICE', 'BOB'], $names->getItems());
    }

    public function testEach()
    {
        $c = $this->collect([['id' => 1, 'visited' => false], ['id' => 2, 'visited' => false]]);
        $c->each(fn ($i) => $i->setAttribute('visited', true));
        foreach ($c as $item) {
            $this->assertTrue($item->visited);
        }
    }

    public function testEachReturnsSelf()
    {
        $c = $this->collect([['id' => 1]]);
        $this->assertSame($c, $c->each(fn ($i) => null));
    }

    // -------------------------------------------------------------------------
    // exclude
    // -------------------------------------------------------------------------

    public function testExcludeByPrimaryKey()
    {
        $c = $this->collect([['id' => 1], ['id' => 2], ['id' => 3]]);
        $this->assertEquals([1, 3], $c->exclude(2)->ids());
    }

    public function testExcludeMultipleKeys()
    {
        $c = $this->collect([['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]]);
        $this->assertEquals([2, 4], $c->exclude([1, 3])->ids());
    }

    public function testExcludeReturnsNewInstance()
    {
        $c = $this->collect([['id' => 1], ['id' => 2]]);
        $this->assertNotSame($c, $c->exclude(1));
        $this->assertCount(2, $c); // original unchanged
    }

    // -------------------------------------------------------------------------
    // sort
    // -------------------------------------------------------------------------

    public function testSortAscending()
    {
        $c = $this->collect([['id' => 1, 'price' => 30], ['id' => 2, 'price' => 10], ['id' => 3, 'price' => 20]]);
        $sorted = $c->sort('price');
        $this->assertEquals([2, 3, 1], $sorted->ids());
    }

    public function testSortDescending()
    {
        $c = $this->collect([['id' => 1, 'price' => 30], ['id' => 2, 'price' => 10], ['id' => 3, 'price' => 20]]);
        $sorted = $c->sort('price', 'desc');
        $this->assertEquals([1, 3, 2], $sorted->ids());
    }

    public function testSortStringValues()
    {
        $c = $this->collect([['id' => 1, 'name' => 'Charlie'], ['id' => 2, 'name' => 'Alice'], ['id' => 3, 'name' => 'Bob']]);
        $sorted = $c->sort('name');
        $this->assertEquals([2, 3, 1], $sorted->ids());
    }

    public function testSortDoesNotMutateOriginal()
    {
        $c = $this->collect([['id' => 1, 'price' => 30], ['id' => 2, 'price' => 10]]);
        $sorted = $c->sort('price');
        $this->assertEquals([1, 2], $c->ids()); // original unchanged
        $this->assertEquals([2, 1], $sorted->ids());
    }

    public function testSortByEqualValuesPreservesOrder()
    {
        $c = $this->collect([['id' => 1, 'score' => 5], ['id' => 2, 'score' => 5], ['id' => 3, 'score' => 5]]);
        $sorted = $c->sort('score');
        $this->assertEquals([1, 2, 3], $sorted->ids());
    }

    // -------------------------------------------------------------------------
    // Array access interface
    // -------------------------------------------------------------------------

    public function testArrayAccessRead()
    {
        $c = $this->collect([['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B']]);
        $this->assertEquals(1, $c[0]->id);
        $this->assertEquals(2, $c[1]->id);
    }

    public function testArrayAccessIsset()
    {
        $c = $this->collect([['id' => 1]]);
        $this->assertTrue(isset($c[0]));
        $this->assertFalse(isset($c[99]));
    }

    public function testArrayAccessWrite()
    {
        $c = new Collection([]);
        $c[] = $this->make(['id' => 5, 'name' => 'New']);
        $this->assertCount(1, $c);
        $this->assertEquals(5, $c[0]->id);
    }

    public function testArrayAccessWriteThrowsForNonModel()
    {
        $this->expectException(\InvalidArgumentException::class);
        $c = new Collection([]);
        $c[] = 'not a model';
    }

    public function testArrayAccessUnset()
    {
        $c = $this->collect([['id' => 1], ['id' => 2]]);
        unset($c[0]);
        $this->assertCount(1, $c);
    }

    // -------------------------------------------------------------------------
    // Iteration / JsonSerializable
    // -------------------------------------------------------------------------

    public function testIterable()
    {
        $c = $this->collect([['id' => 1], ['id' => 2]]);
        $ids = [];
        foreach ($c as $item) {
            $ids[] = $item->id;
        }
        $this->assertEquals([1, 2], $ids);
    }

    public function testJsonSerialize()
    {
        $c = $this->collect([['id' => 1], ['id' => 2]]);
        $json = json_encode($c);
        $this->assertIsString($json);
        $decoded = json_decode($json, true);
        $this->assertCount(2, $decoded);
    }

    // -------------------------------------------------------------------------
    // toArray / getItems
    // -------------------------------------------------------------------------

    public function testToArray()
    {
        $c = $this->collect([['id' => 1, 'name' => 'A']]);
        $arr = $c->toArray();
        $this->assertIsArray($arr);
        $this->assertIsArray($arr[0]);
        $this->assertEquals(1, $arr[0]['id']);
    }

    public function testGetItems()
    {
        $c = $this->collect([['id' => 1], ['id' => 2]]);
        $items = $c->getItems();
        $this->assertIsArray($items);
        $this->assertCount(2, $items);
        $this->assertInstanceOf(CollectionItem::class, $items[0]);
    }
}
