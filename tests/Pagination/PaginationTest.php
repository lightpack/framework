<?php

use Lightpack\Container\Container;
use Lightpack\Http\Request;
use Lightpack\Pagination\Pagination;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function setUp(): void
    {
        $container = Container::getInstance();
        $container->register('request', function () {
            return new Request;
        });

        $_SERVER['REQUEST_URI'] = '/products';
        $_GET = [];
    }

    public function tearDown(): void
    {
        $_GET = [];
    }

    public function testIsEmptyReturnsTrueWithEmptyItems()
    {
        $pagination = new Pagination([], 0, 10, 1);

        $this->assertTrue($pagination->isEmpty());
        $this->assertFalse($pagination->isNotEmpty());
    }

    public function testIsEmptyReturnsFalseWithItems()
    {
        $pagination = new Pagination(['a', 'b', 'c'], 3, 10, 1);

        $this->assertFalse($pagination->isEmpty());
        $this->assertTrue($pagination->isNotEmpty());
    }

    public function testIsEmptyReturnsTrueWithNullItems()
    {
        $pagination = new Pagination(null, 0, 10, 1);

        $this->assertTrue($pagination->isEmpty());
        $this->assertFalse($pagination->isNotEmpty());
    }

    public function testCanIterateWithForeach()
    {
        $pagination = new Pagination(['a', 'b', 'c'], 3, 10, 1);
        $items = [];

        foreach ($pagination as $item) {
            $items[] = $item;
        }

        $this->assertEquals(['a', 'b', 'c'], $items);
    }

    public function testLastPageIsIntegerNotFloat()
    {
        $pagination = new Pagination([], 25, 10, 1);

        // ceil() returns float; this should be int
        $this->assertSame(3, $pagination->lastPage());
    }

    public function testOnlyFiltersQueryParamsByKey()
    {
        $_GET = ['category' => 'books', 'sort' => 'price'];

        $pagination = new Pagination([], 100, 10, 1);
        $pagination->only(['category']);

        $url = $pagination->url(2);

        // Bug: array_filter passes VALUE to callback, not KEY.
        // So 'books' is checked against ['category'] -> false.
        // All $_GET values get stripped, leaving only page=2.
        $this->assertStringContainsString('category=books', $url);
        $this->assertStringNotContainsString('sort=price', $url);
    }

    public function testUrlHandlesExistingQueryDelimiter()
    {
        $pagination = new Pagination([], 100, 10, 1);

        // Inject a path that already contains ?
        $reflection = new ReflectionClass($pagination);
        $prop = $reflection->getProperty('path');
        $prop->setAccessible(true);
        $prop->setValue($pagination, '/search?q=test');

        $url = $pagination->url(2);

        // Bug: blindly appends ?page=2 producing /search?q=test?page=2
        $this->assertStringContainsString('&page=2', $url);
        $this->assertStringNotContainsString('?test?page=2', $url);
    }
}
