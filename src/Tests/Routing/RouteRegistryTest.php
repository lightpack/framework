<?php

declare(strict_types=1);

use Lightpack\Http\Request;
use Lightpack\Routing\Route;
use Lightpack\Routing\RouteRegistry;
use PHPUnit\Framework\TestCase;

final class RouteRegistryTest extends TestCase
{
    private const HTTP_GET = 'GET';
    private const HTTP_POST = 'POST';
    private const HTTP_PUT = 'PUT';
    private const HTTP_PATCH = 'PATCH';
    private const HTTP_DELETE = 'DELETE';
    private $verbs = [self::HTTP_GET, self::HTTP_POST, self::HTTP_PUT, self::HTTP_PATCH, self::HTTP_DELETE];

    private function getRouteRegistry()
    {
        $request = new Request();
        return new RouteRegistry($request);
    }

    public function testRoutePathException()
    {
        $routeRegistry = $this->getRouteRegistry();
        
        $this->expectException('\\Exception');
        $routeRegistry->get('', 'UserController', 'index');
        $this->fail('Empty route path');
    }

    public function testRoutePathsRegisteredForMethod()
    {
        $routeRegistry = $this->getRouteRegistry();

        $routeRegistry->get('/users', 'UserController', 'index');
        $routeRegistry->get('/users/23', 'UserController', 'edit');
        $routeRegistry->post('/users/23', 'UserController', 'delete');
        $routeRegistry->put('/users', 'UserController', 'index');
        $routeRegistry->patch('/users', 'UserController', 'index');
        $routeRegistry->delete('/users', 'UserController', 'index');

        $this->assertCount(
            2,
            $routeRegistry->paths(self::HTTP_GET),
            "Route should have registered 2 paths for GET"
        );

        $this->assertCount(
            1,
            $routeRegistry->paths(self::HTTP_POST),
            "Route should have registered 1 path for POST"
        );

        $this->assertCount(
            1,
            $routeRegistry->paths(self::HTTP_PUT),
            "Route should have registered 1 path for PUT"
        );

        $this->assertCount(
            1,
            $routeRegistry->paths(self::HTTP_PATCH),
            "Route should have registered 1 path for PATCH"
        );

        $this->assertCount(
            1,
            $routeRegistry->paths(self::HTTP_DELETE),
            "Route should have registered 1 path for DELETE"
        );
    }

    public function testRouteMatchesUrl()
    {
        $_SERVER['REQUEST_METHOD'] = self::HTTP_GET;
        $routeRegistry = $this->getRouteRegistry();
        $routeRegistry->get('/users', 'UserController', 'index');

        $this->assertTrue(
            $routeRegistry->matches('/users') instanceof Route,
            'Route should match request GET /users'
        );

        $_SERVER['REQUEST_METHOD'] = self::HTTP_GET;
        $routeRegistry = $this->getRouteRegistry();
        $routeRegistry->get('/users/:num/role/:alpha', 'UserController', 'showForm');

        $_SERVER['REQUEST_METHOD'] = self::HTTP_GET;
        $this->assertTrue(
            $routeRegistry->matches('/users/23/role/editor') instanceof Route,
            'Route should match request GET /users/23/role/editor'
        );

        $_SERVER['REQUEST_METHOD'] = self::HTTP_POST;
        $routeRegistry = $this->getRouteRegistry();        
        $routeRegistry->post('/users/:num/profile', 'UserController', 'submitForm');

        $this->assertTrue(
            $routeRegistry->matches('/users/23/profile') instanceof Route,
            'Route should match request POST /users/23/profile'
        );
    }

    public function testRouteShouldMapMultipleVerbs()
    {
        $_SERVER['REQUEST_METHOD'] = self::HTTP_GET;
        $routeRegistry = $this->getRouteRegistry();  
        $routeRegistry->map(['GET', 'POST'], '/users/:num', 'UserController', 'index');

        $this->assertTrue(
            $routeRegistry->matches('/users/23') instanceof Route,
            'Route should match request GET /users/23'
        );

        $_SERVER['REQUEST_METHOD'] = self::HTTP_POST;
        $routeRegistry = $this->getRouteRegistry();  
        $routeRegistry->map(['GET', 'POST'], '/users/:num', 'UserController', 'index');

        $this->assertTrue(
            $routeRegistry->matches('/users/23') instanceof Route,
            'Route should match request POST /users/23'
        );

        $_SERVER['REQUEST_METHOD'] = self::HTTP_PUT;
        $routeRegistry = $this->getRouteRegistry();  
        $routeRegistry->map(['GET', 'POST'], '/users/:num', 'UserController', 'index');

        $this->assertFalse(
            $routeRegistry->matches('/users/23'),
            'Route should not match request PUT /users/23'
        );

        $this->expectException('\\Exception');
        $routeRegistry->map(['get'], '/users', 'UserController', 'index');
        $this->fail('Unsupported HTTP request method: ' . 'get');
    }

    public function testRouteShouldMapAnyAllowedVerb()
    {
        foreach ($this->verbs as $verb) {
            $_SERVER['REQUEST_METHOD'] = $verb;
            $routeRegistry = $this->getRouteRegistry();  
            $routeRegistry->any('/users/:num', 'UserController', 'index');

            $this->assertTrue(
                $routeRegistry->matches('/users/23') instanceof Route,
                "Route should match {$verb} request"
            );
        }
    }

    public function testRouteMatchesGroupedUrls()
    {
        $request = new Request();
        $routeRegistry = new RouteRegistry($request);

        $routeRegistry->group(
            ['prefix' => '/admin'],
            function ($route) {
                $route->get('/users/:num', 'UserController', 'index');
                $route->post('/users/:num', 'UserController', 'index');
                $route->put('/users/:num', 'UserController', 'index');
                $route->patch('/users/:num', 'UserController', 'index');
                $route->delete('/users/:num', 'UserController', 'index');
                $route->any('/pages/:num', 'PageController', 'index');
            }
        );

        foreach ($this->verbs as $verb) {
            $request->setMethod($verb);

            $this->assertTrue(
                $routeRegistry->matches('/admin/users/23') instanceof Route,
                "Route should match group request {$verb} /admin/users/23"
            );

            $this->assertTrue(
                $routeRegistry->matches('/admin/pages/23') instanceof Route,
                "Route should match group request {$verb} /admin/pages/23"
            );
        }
    }

    public function testRouteShouldResetScopeToDefault()
    {
        $request = new Request();
        $request->setMethod(self::HTTP_GET);
        $routeRegistry = new RouteRegistry($request);

        $routeRegistry->group(['prefix' => '/admin'], function ($route) {
            $route->get('/users/:num', 'UserController', 'index');
        });

        // new routes should reset scope from '/admin' to default
        $routeRegistry->get('/users/:num', 'UserController', 'index');

        $this->assertTrue(
            $routeRegistry->matches('/users/23') instanceof Route,
            'Route should reset scope to match request GET /users/23'
        );
    }

    public function testRouteShouldMatchNestedGroupUrls()
    {
        $request = new Request();
        $request->setMethod(self::HTTP_GET);
        $routeRegistry = new RouteRegistry($request);

        $routeRegistry->group(['prefix' => '/admin'], function ($route) {
            $route->group(['prefix' => '/posts'], function ($route) {
                $route->get('/edit/:num', 'PostController', 'edit');
            });
            $route->group(['prefix' => '/dashboard'], function ($route) {
                $route->group(['prefix' => '/charts'], function ($route) {
                    $route->get('/:num', 'ChartController', 'index');
                });
            });
        });

        // non prefix
        $routeRegistry->get('/users/:num/profile', 'UserController', 'profile');

        $this->assertTrue(
            $routeRegistry->matches('/admin/posts/edit/23') instanceof Route,
            'Route should match nested group url: /admin/posts/edit/23'
        );

        $this->assertTrue(
            $routeRegistry->matches('/admin/dashboard/charts/23') instanceof Route,
            'Route should match nested group url: /admin/dashboard/charts/23'
        );

        $this->assertTrue(
            $routeRegistry->matches('/users/23/profile') instanceof Route,
            'Route should match non-nested url: /users/23/profile'
        );
    }
}
