<?php

declare(strict_types=1);

use Lightpack\Container\Container;
use Lightpack\Http\Request;
use Lightpack\Routing\Route;
use Lightpack\Routing\RouteRegistry;
use Lightpack\Utils\Crypto;
use Lightpack\Utils\Url;
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
        $container = Container::getInstance();
        $container->instance('request', $request);
        return new RouteRegistry($container);
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
        $routeRegistry->map(['getty'], '/users', 'UserController', 'index');
        $this->fail('Unsupported HTTP request method: ' . 'getty');
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
        $routeRegistry = $this->getRouteRegistry();

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
        $container = Container::getInstance();
        $container->instance('request', $request);
        $routeRegistry = new RouteRegistry($container);

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
        $container = Container::getInstance();
        $container->instance('request', $request);
        $routeRegistry = new RouteRegistry($container);

        $routeRegistry->group(['prefix' => '/admin', 'filter' => ['auth']], function ($route) {
            $route->group(['prefix' => '/posts', 'filter' => ['auth', 'admin']], function ($route) {
                $route->get('/edit/:num', 'PostController', 'edit');
            });
            $route->group(['prefix' => '/dashboard', 'filter' => ['staff']], function ($route) {
                $route->group(['prefix' => '/charts', 'filter' => ['charts', 'staff']], function ($route) {
                    $route->get('/:num', 'ChartController', 'index');
                });
            });
        });

        // non prefix
        $routeRegistry->get('/users/:num/profile', 'UserController', 'profile');

        // Assert filters are merged for nested groups and are unique
        $route = $routeRegistry->matches('/admin/posts/edit/23');
        $this->assertEquals(['auth', 'admin'], $route->getFilters(), 'Filters for /admin/posts/edit/:num should be unique and merged from parent and child');
        $route = $routeRegistry->matches('/admin/dashboard/charts/23');
        $this->assertEquals(['auth', 'staff', 'charts'], $route->getFilters(), 'Filters for /admin/dashboard/charts/:num should be unique and merged from all levels');

        // Test a case where the same filter appears at every level
        $routeRegistry->group(['prefix' => '/dupe', 'filter' => ['auth']], function ($route) {
            $route->group(['prefix' => '/foo', 'filter' => ['auth']], function ($route) {
                $route->group(['prefix' => '/bar', 'filter' => ['auth']], function ($route) {
                    $route->get('/baz', 'BazController', 'index');
                });
            });
        });
        $route = $routeRegistry->matches('/dupe/foo/bar/baz');
        $this->assertEquals(['auth'], $route->getFilters(), 'Duplicate filters should only appear once, preserving order');

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

    public function testRouteMatchesWildcardSubdomain()
    {
        $_SERVER['REQUEST_METHOD'] = self::HTTP_GET;
        $_SERVER['HTTP_HOST'] = 'tenant1.example.com';
        
        $request = new Request();
        $container = Container::getInstance();
        $container->instance('request', $request);
        $routeRegistry = new RouteRegistry($container);

        $routeRegistry->group(['host' => ':subdomain.example.com'], function ($route) {
            $route->get('/dashboard', 'DashboardController', 'index');
        });

        $route = $routeRegistry->matches('/dashboard');

        $this->assertTrue(
            $route instanceof Route,
            'Route should match wildcard subdomain request'
        );

        $this->assertEquals(
            'tenant1',
            $route->getParams()['subdomain'] ?? null,
            'Route should extract subdomain parameter correctly'
        );

        $this->assertEquals(
            'DashboardController',
            $route->getController(),
            'Route should have correct controller'
        );
    }

    public function testRouteMatchesMultipleWildcardSubdomains()
    {
        $_SERVER['REQUEST_METHOD'] = self::HTTP_GET;
        
        $subdomains = ['tenant1', 'tenant2', 'admin', 'api'];
        
        foreach ($subdomains as $subdomain) {
            $_SERVER['HTTP_HOST'] = $subdomain . '.example.com';
            
            $request = new Request();
            $container = Container::getInstance();
            $container->instance('request', $request);
            $routeRegistry = new RouteRegistry($container);

            $routeRegistry->group(['host' => ':subdomain.example.com'], function ($route) {
                $route->get('/dashboard', 'DashboardController', 'index');
            });

            $route = $routeRegistry->matches('/dashboard');

            $this->assertTrue(
                $route instanceof Route,
                "Route should match subdomain: {$subdomain}"
            );

            $this->assertEquals(
                $subdomain,
                $route->getParams()['subdomain'] ?? null,
                "Route should extract subdomain '{$subdomain}' correctly"
            );
        }
    }

    public function testRouteMatchesWildcardSubdomainWithPathParameters()
    {
        $_SERVER['REQUEST_METHOD'] = self::HTTP_GET;
        $_SERVER['HTTP_HOST'] = 'tenant1.example.com';
        
        $request = new Request();
        $container = Container::getInstance();
        $container->instance('request', $request);
        $routeRegistry = new RouteRegistry($container);

        $routeRegistry->group(['host' => ':subdomain.example.com'], function ($route) {
            $route->get('/users/:id', 'UserController', 'show');
        });

        $route = $routeRegistry->matches('/users/42');

        $this->assertTrue(
            $route instanceof Route,
            'Route should match wildcard subdomain with path parameters'
        );

        $params = $route->getParams();
        
        $this->assertEquals(
            'tenant1',
            $params['subdomain'] ?? null,
            'Route should extract subdomain parameter'
        );

        $this->assertEquals(
            '42',
            $params['id'] ?? null,
            'Route should extract path parameter'
        );
    }

    public function testRouteDoesNotMatchWrongHost()
    {
        $_SERVER['REQUEST_METHOD'] = self::HTTP_GET;
        $_SERVER['HTTP_HOST'] = 'wrongdomain.com';
        
        $request = new Request();
        $container = Container::getInstance();
        $container->instance('request', $request);
        $routeRegistry = new RouteRegistry($container);

        $routeRegistry->group(['host' => ':subdomain.example.com'], function ($route) {
            $route->get('/dashboard', 'DashboardController', 'index');
        });

        $route = $routeRegistry->matches('/dashboard');

        $this->assertFalse(
            $route,
            'Route should not match when host does not match pattern'
        );
    }

    public function testRouteMatchesStaticHost()
    {
        $_SERVER['REQUEST_METHOD'] = self::HTTP_GET;
        $_SERVER['HTTP_HOST'] = 'admin.example.com';
        
        $request = new Request();
        $container = Container::getInstance();
        $container->instance('request', $request);
        $routeRegistry = new RouteRegistry($container);

        $routeRegistry->group(['host' => 'admin.example.com'], function ($route) {
            $route->get('/dashboard', 'AdminController', 'dashboard');
        });

        $route = $routeRegistry->matches('/dashboard');

        $this->assertTrue(
            $route instanceof Route,
            'Route should match static host'
        );

        $this->assertEquals(
            'AdminController',
            $route->getController(),
            'Route should have correct controller'
        );

        // Static host should not create subdomain parameter
        $this->assertEmpty(
            $route->getParams(),
            'Static host should not create parameters'
        );
    }

    public function testRouteMatchesNestedWildcardSubdomainGroups()
    {
        $_SERVER['REQUEST_METHOD'] = self::HTTP_GET;
        $_SERVER['HTTP_HOST'] = 'tenant1.example.com';
        
        $request = new Request();
        $container = Container::getInstance();
        $container->instance('request', $request);
        $routeRegistry = new RouteRegistry($container);

        $routeRegistry->group(['host' => ':subdomain.example.com'], function ($route) {
            $route->group(['prefix' => '/admin'], function ($route) {
                $route->get('/users/:id', 'AdminUserController', 'show');
            });
        });

        $route = $routeRegistry->matches('/admin/users/99');

        $this->assertTrue(
            $route instanceof Route,
            'Route should match nested groups with wildcard subdomain'
        );

        $params = $route->getParams();
        
        $this->assertEquals(
            'tenant1',
            $params['subdomain'] ?? null,
            'Route should extract subdomain from nested group'
        );

        $this->assertEquals(
            '99',
            $params['id'] ?? null,
            'Route should extract path parameter from nested group'
        );
    }

    public function testRouteRegistryUrlMethod()
    {
        $container = Container::getInstance();
        $container->instance('request', new Request());
        $container->instance('url', new Url());
        
        $routeRegistry = new RouteRegistry($container);
        $routeRegistry->get('/foo', 'DummyController')->name('foo');
        $routeRegistry->get('/foo/:num', 'DummyController')->name('foo.num');
        $routeRegistry->get('/foo/:num/bar/:slug?', 'DummyController')->name('foo.num.bar');
        $routeRegistry->bootRouteNames();

        // Test basic route
        $this->assertEquals('/foo', $routeRegistry->url('foo'));
        
        // Test route with required parameter
        $this->assertEquals('/foo/23', $routeRegistry->url('foo.num', ['num' => 23]));
        
        // Test route with optional parameter (not provided)
        $this->assertEquals('/foo/23/bar', $routeRegistry->url('foo.num.bar', ['num' => 23]));
        
        // Test route with optional parameter (provided)
        $this->assertEquals('/foo/23/bar/baz', $routeRegistry->url('foo.num.bar', ['num' => 23, 'slug' => 'baz']));
        
        // Test route with query parameters
        $this->assertEquals('/foo/23/bar/baz?p=1&r=2', $routeRegistry->url('foo.num.bar', ['num' => 23, 'slug' => 'baz', 'p' => 1, 'r' => 2]));
        
        // Test route with optional parameter as null and query params
        $this->assertEquals('/foo/23/bar?p=1&r=2', $routeRegistry->url('foo.num.bar', ['num' => 23, 'slug' => null, 'p' => 1, 'r' => 2]));
    }

    public function testRouteRegistryUrlMethodThrowsExceptionForNonexistentRoute()
    {
        $container = Container::getInstance();
        $container->instance('request', new Request());
        $routeRegistry = new RouteRegistry($container);
        $routeRegistry->bootRouteNames();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Route with name 'nonexistent' not found.");
        $routeRegistry->url('nonexistent');
    }

    public function testRouteRegistryUrlMethodThrowsExceptionForMissingRequiredParams()
    {
        $container = Container::getInstance();
        $container->instance('request', new Request());
        $routeRegistry = new RouteRegistry($container);
        $routeRegistry->get('/users/:id', 'UserController')->name('users.show');
        $routeRegistry->bootRouteNames();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Invalid number of parameters for route 'users.show'. Expected 1 but got 0");
        $routeRegistry->url('users.show');
    }

    public function testRouteRegistrySignMethod()
    {
        $container = Container::getInstance();
        $container->instance('request', new Request());
        $container->instance('url', new Url());
        
        // Set up the Crypto class mock
        $cryptoMock = $this->getMockBuilder(Crypto::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cryptoMock->expects($this->once())
            ->method('hash')
            ->willReturn('encryptedSignature');
        
        $container->instance('crypto', $cryptoMock);
        
        $routeRegistry = new RouteRegistry($container);
        $routeRegistry->get('/users', 'DummyController')->name('users');
        $routeRegistry->bootRouteNames();

        // Generate the signed URL
        $signedUrl = $routeRegistry->sign('users', ['sort' => 'asc', 'status' => 'active'], 3600);

        // Verify the generated URL
        $this->assertStringContainsString('/users?sort=asc&status=active', $signedUrl);
        $this->assertStringContainsString('&signature=encryptedSignature', $signedUrl);
        $this->assertStringMatchesFormat('%s&expires=%s', $signedUrl);
    }
}
