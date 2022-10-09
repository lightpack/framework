<?php

use Lightpack\Container\Container;
use Lightpack\Utils\Url;
use PHPUnit\Framework\TestCase;

require 'MockFilter.php';

final class FilterTest extends TestCase
{
    private $filter;

    public function setUp(): void
    {
        $this->request = new \Lightpack\Http\Request();
        $this->response = new \Lightpack\Http\Response(new Url);
        $this->filter = new \Lightpack\Filters\Filter(Container::getInstance(), $this->request, $this->response);
    }

    public function testFilterBeforeMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $this->filter->register('/users', MockFilter::class);
        $result = $this->filter->processBeforeFilters('/users');
        $this->assertTrue($this->request->post('framework') == 'Lightpack');
    }

    public function testFilterBeforeMethodReturnsResponse()
    {
        $_SERVER['REQUEST_METHOD'] = 'get';
        $this->filter->register('/users', MockFilter::class);
        $result = $this->filter->processBeforeFilters('/users');
        $this->assertInstanceOf(\Lightpack\Http\Response::class, $result);
    }

    public function testFilterAfterMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'get';
        $this->filter->register('/users', MockFilter::class);
        $result = $this->filter->processAfterFilters('/users');
        $this->assertInstanceOf(\Lightpack\Http\Response::class, $result);
        $this->assertTrue($result->getBody() == 'hello');
    }
    
    public function __testFilterBeforeMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $request = new \Lightpack\Http\Request();
        $this->mockFilter->before($request);
        $this->assertTrue($request->post('framework') == 'Lightpack');
    }
}