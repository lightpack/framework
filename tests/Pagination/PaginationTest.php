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
}
