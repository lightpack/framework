<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private const HTTP_GET = 'GET';

    /**
     * @var \Lightpack\Routing\Router
     */
    private $router;

    /**
     * @var \Lightpack\Routing\RouteRegistry
     */
    private $routeRegistry;

    public function setUp(): void
    {
        $basepath = '/lightpack';
        $_SERVER['REQUEST_URI'] = $basepath . '/users/23';
        $_SERVER['REQUEST_METHOD'] = self::HTTP_GET;

        $request = new \Lightpack\Http\Request($basepath);
        $this->routeRegistry = new \Lightpack\Routing\RouteRegistry($request);
        $this->router = new \Lightpack\Routing\Router($request, $this->routeRegistry);
    }

    public function testRouterCanParseUrl()
    {
        $this->routeRegistry->get('/users/:num/role/:alpha/:any', 'UserController', 'index');
        $this->router->parse('/users/23/role/admin/hello/world');

        $this->assertEquals(
            'UserController',
            $this->router->getRoute()->getController(),
            'Router should parse controller: UserController'
        );

        $this->assertEquals(
            'index',
            $this->router->getRoute()->getAction(),
            'Router should parse action: index'
        );

        $this->assertSame(
            ['23', 'admin', 'hello/world'],
            $this->router->getRoute()->getParams(),
            'Router should parse params correctly'
        );
    }

    public function testRouterCanParseMultipleUrls()
    {
        // 'path' => ['method', 'route', 'Controller', 'action', $routeMeta]
        // example: '/news/23' => ['GET', 'route', 'News@index', ['controller' => 'News', 'action' => 'index', 'path' => '/news/23', 'route' => '/news/:num', 'params' => ['23']]]
        $routes = [
            '/' => ['GET', '/', 'News', 'handle', []],
            '/news' => ['GET', '/news', 'News', 'handle', []],
            '/news/order/asc' => ['POST', '/news/order/:alpha', 'News', 'handle', ['asc']],
            '/news/23/category/politics' => ['PUT', '/news/:num/category/:slug', 'News', 'handle', ['23', 'politics']],
            '/news/v2.0/latest/politics' => ['PATCH', '/news/:seg/:seg/:alpha', 'News', 'handle', ['v2.0', 'latest', 'politics']],
            '/news/author/bob-walter/id-23' => ['DELETE', '/news/:alpha/:any', 'News', 'handle', ['author', 'bob-walter/id-23']],
            '/news/way2go/id-23' => ['GET', '/news/:alnum/:any', 'News', 'handle', ['way2go', 'id-23']],
        ];

        foreach ($routes as $path => $config) {
            // Prepare data
            $method = $config[0];
            $route = $config[1];
            $controller = $config[2];
            $action = $config[3];
            $params = $config[4];
            $method = self::HTTP_GET;

            // Initialize setup
            $this->routeRegistry->{$method}($route, $controller, $action);
            $this->router->parse($path);

            // Assertions
            $this->assertEquals($path, $this->router->getRoute()->getPath(), "Router should parse path: {$path}");
            $this->assertEquals($route, $this->router->getRoute()->getUri(), "Router should parse route uri: {$route}");
            $this->assertEquals($controller, $this->router->getRoute()->getController(), "Router should parse controller: {$controller}");
            $this->assertEquals($action, $this->router->getRoute()->getAction(), "Router should parse action: {$action}");
            $this->assertEquals($params, $this->router->getRoute()->getParams(), "Router should parse params correctly");
            $this->assertEquals($method, $this->router->getRoute()->getVerb(), "Router should parse method: {$method}");
        }
    }

    public function testRouterCanParseUrlMeta()
    {
        $this->routeRegistry->get('/news/:num/author/:slug', 'News', 'index')->filter(['auth', 'csrf']);
        $this->router->parse('/news/23/author/bob');

        $route = $this->router->getRoute();
        $actual = [
            'method' => $route->getVerb(),
            'controller' => $route->getController(),
            'action' => $route->getAction(),
            'route' => $route->getUri(),
            'path' => $route->getPath(),
            'params' => $route->getParams(),
            'filters' => $route->getFilters(),
        ];

        $meta = [
            'method' => 'GET',
            'controller' => 'News',
            'action' => 'index',
            'route' => '/news/:num/author/:slug',
            'path' => '/news/23/author/bob',
            'params' => ['23', 'bob'],
            'filters' => ['auth', 'csrf']
        ];

        foreach ($meta as $key => $value) {
            $this->assertTrue(isset($actual[$key]), "Router should have parsed meta key: {$key}");
            $this->assertEquals($value, $actual[$key], "Router should have parsed correctly meta value for key: {$key}");
        }
    }

    public function testRouterCanParseBadUrlMeta()
    {
        $this->routeRegistry->get('/news/:slug', 'News', 'index');
        $this->router->parse('/news//23');

        // should be false
        $this->assertFalse($this->router->hasRoute(), "Router should have returned fale for bad url requests");
    }

    public function testRouterCanParseRegexUrl()
    {
        $this->routeRegistry->get('/news/([0-9]+)/slug/([a-zA-Z]+)', 'News', 'index');
        $this->router->parse('/news/23/slug/politics');

        $this->assertEquals(['23', 'politics'], $this->router->getRoute()->getParams());
    }

    public function testRouterCanParseComplexUrl()
    {
        $this->routeRegistry->get('/news/id-([0-9]+)/slug/political-([a-zA-Z]+)', 'News', 'index');
        $this->router->parse('/news/id-23/slug/political-agenda');
        $this->assertEquals(['23', 'agenda'], $this->router->getRoute()->getParams());
    }

    public function testRouterCanParseGroupOptions()
    {
        $this->routeRegistry->group(
            ['prefix' => '/admin', 'filter' => 'auth'],
            function ($route) {
                $route->get('/users/:num', 'UserController', 'index')->filter(['csrf', 'honeypot']);
            }
        );

        $this->router->parse('/admin/users/23');

        // tests
        $this->assertEquals(['auth', 'csrf', 'honeypot'], $this->router->getRoute()->getFilters());
    }
}
