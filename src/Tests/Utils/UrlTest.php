<?php

declare(strict_types=1);

use Lightpack\Container\Container;
use Lightpack\Http\Request;
use Lightpack\Routing\RouteRegistry;
use Lightpack\Utils\Url;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\once;

final class UrlTest extends TestCase
{
    /** @var Url */
    private $url;

    public function setUp(): void
    {
        $this->url = new Url();
    }

    public function tearDown(): void
    {
        unset($this->url);
    }

    public function testUrlToMethod()
    {
        $this->assertEquals('/', $this->url->to('/'));
        $this->assertEquals('/', $this->url->to('//'));
        $this->assertEquals('/users', $this->url->to('users'));
        $this->assertEquals('/users/profile', $this->url->to('users/profile'));
        $this->assertEquals('/users/profile', $this->url->to('users', 'profile'));
        $this->assertEquals('/users/profile', $this->url->to('users', 'profile'));
        $this->assertEquals('/users/profile', $this->url->to('/users', ' profile '));
        $this->assertEquals('/users/profile', $this->url->to('users', 'profile', '', null));
        $this->assertEquals('/?sort=asc&status=active', $this->url->to(['sort' => 'asc', 'status' => 'active']));
        $this->assertEquals('/users?sort=asc&status=active', $this->url->to('users', ['sort' => 'asc', 'status' => 'active']));
        $this->assertEquals('/users', $this->url->to('users', ['sort' => '', 'status' => null]));
        $this->assertEquals('/users?member=gold', $this->url->to('users', ['sort' => '', 'status' => null, 'member' => 'gold']));
    }

    public function testUrlAssetMethod()
    {
        $this->assertEquals('/assets', $this->url->asset(''));
        $this->assertEquals('/assets/styles.css', $this->url->asset('styles.css'));
        $this->assertEquals('/assets/styles.css', $this->url->asset('/styles.css'));
        $this->assertEquals('/assets/styles.css', $this->url->asset(' styles.css '));
        $this->assertEquals('/assets/css/styles.css', $this->url->asset('css/styles.css'));
        $this->assertEquals('/assets/css/styles.css', $this->url->asset('/css/styles.css'));
    }

    public function testUrlRouteMethod()
    {
        $testRoutes = [
            ['name' => 'foo', 'uri' => '/foo', 'params' => [], 'expected' => '/foo'],
            ['name' => 'foo.num', 'uri' => '/foo/:num', 'params' => [23], 'expected' => '/foo/23'],
            ['name' => 'foo.num.bar', 'uri' => '/foo/:num/bar', 'params' => [23], 'expected' => '/foo/23/bar'],
            ['name' => 'foo.num.bar.baz', 'uri' => '/foo/:num/bar/:slug', 'params' => [23, 'baz'], 'expected' => '/foo/23/bar/baz'],
            ['name' => 'foo.num.bar.baz', 'uri' => '/foo/:num/bar/:slug', 'params' => [23, 'baz', ['p' => 1, 'r' => 2]], 'expected' => '/foo/23/bar/baz?p=1&r=2'],
        ];

        // Add routes
        $routeRegistery = new RouteRegistry(new Request());
        $routeRegistery->get('/foo', 'DummyController')->name('foo');
        $routeRegistery->get('/foo/:num', 'DummyController')->name('foo.num');
        $routeRegistery->get('/foo/:num/bar', 'DummyController')->name('foo.num.bar');
        $routeRegistery->get('/foo/:num/bar/:slug', 'DummyController')->name('foo.num.bar.baz');
        $routeRegistery->bootRouteNames();

        // Set container
        Container::getInstance()->instance('route', $routeRegistery);

        // Test routes
        $this->assertEquals('/foo', $this->url->route('foo'));
        $this->assertEquals('/foo/23', $this->url->route('foo.num', 23));
        $this->assertEquals('/foo/23/bar', $this->url->route('foo.num.bar', 23));
        $this->assertEquals('/foo/23/bar/baz', $this->url->route('foo.num.bar.baz', 23, 'baz'));
        $this->assertEquals('/foo/23/bar/baz?p=1&r=2', $this->url->route('foo.num.bar.baz', 23, 'baz', ['p' => 1, 'r' => 2]));
    }
}
