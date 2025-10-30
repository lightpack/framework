<?php

declare(strict_types=1);

require_once __DIR__ . '/Models/Product.php';
require_once __DIR__ . '/Models/User.php';
require_once __DIR__ . '/Controllers/ProductController.php';

use Lightpack\Container\Container;
use Lightpack\Routing\Route;
use Lightpack\Routing\ModelBinder;
use PHPUnit\Framework\TestCase;

final class ModelBindingTest extends TestCase
{
    /** @var \Lightpack\Database\DB */
    private $db;

    /** @var Container */
    private $container;

    protected function setUp(): void
    {
        // Setup database
        $config = require __DIR__ . '/../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        
        // Drop tables if they exist first
        try {
            $sql = "DROP TABLE IF EXISTS products, options, owners, users, roles, role_user, permissions, permission_role, projects, tasks, comments, articles, managers, cast_models, cast_model_relations, polymorphic_comments, polymorphic_thumbnails, posts, videos";
            $this->db->query($sql);
        } catch (\Exception $e) {
            // Ignore errors if tables don't exist
        }
        
        // Create tables
        $sql = file_get_contents(__DIR__ . '/../Database/tmp/db.sql');
        $stmt = $this->db->query($sql);
        $stmt->closeCursor();

        // Configure container
        $this->container = Container::getInstance();

        $this->container->register('db', function () {
            return $this->db;
        });

        $this->container->register('logger', function () {
            return new class {
                public function error($message, $context = []) {}
                public function critical($message, $context = []) {}
            };
        });

        // Insert test data
        $this->seedTestData();
    }

    protected function tearDown(): void
    {
        $sql = "DROP TABLE products, options, owners, users, roles, role_user, permissions, permission_role, projects, tasks, comments, articles, managers, cast_models, cast_model_relations, polymorphic_comments, polymorphic_thumbnails, posts, videos";
        $this->db->query($sql);
        $this->db = null;
        Container::destroy();
    }

    private function seedTestData(): void
    {
        // Truncate tables first to reset auto-increment
        $this->db->query("TRUNCATE TABLE products");
        $this->db->query("TRUNCATE TABLE users");
        
        // Insert products
        $this->db->query("
            INSERT INTO products (id, name, color, price) VALUES
            (1, 'Laptop', 'Silver', 999.99),
            (2, 'Mouse', 'Black', 29.99),
            (3, 'Keyboard', 'White', 79.99)
        ");

        // Insert users
        $this->db->query("
            INSERT INTO users (id, name, active) VALUES
            (1, 'John Doe', 1),
            (2, 'Jane Smith', 1),
            (3, 'Bob Wilson', 0)
        ");
    }

    // ========================================================================
    // PHASE 1: Basic Binding Tests
    // ========================================================================

    public function testRouteBindMethodExists()
    {
        $route = new Route();
        $this->assertTrue(
            method_exists($route, 'bind'),
            'Route should have bind() method'
        );
    }

    public function testRouteBindReturnsRouteInstance()
    {
        $route = new Route();
        $route->setUri('/products/:id');
        
        $result = $route->bind('id', Product::class);
        
        $this->assertInstanceOf(
            Route::class,
            $result,
            'bind() should return Route instance for method chaining'
        );
    }

    public function testRouteBindStoresBindingConfiguration()
    {
        $route = new Route();
        $route->setUri('/products/:id');
        $route->bind('id', Product::class);
        
        $bindings = $route->getBindings();
        
        $this->assertIsArray($bindings);
        $this->assertArrayHasKey('id', $bindings);
        $this->assertEquals(Product::class, $bindings['id']['model']);
    }

    public function testRouteHasBindingsReturnsTrueWhenBindingsExist()
    {
        $route = new Route();
        $route->setUri('/products/:id');
        
        $this->assertFalse($route->hasBindings());
        
        $route->bind('id', Product::class);
        
        $this->assertTrue($route->hasBindings());
    }

    public function testBindThrowsExceptionForNonExistentModelClass()
    {
        $route = new Route();
        $route->setUri('/products/:id');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Model class not found');
        
        $route->bind('id', 'NonExistentModel');
    }

    public function testBindThrowsExceptionForNonModelClass()
    {
        $route = new Route();
        $route->setUri('/products/:id');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('must extend');
        
        $route->bind('id', \stdClass::class);
    }

    public function testBindThrowsExceptionForNonExistentParameter()
    {
        $route = new Route();
        $route->setUri('/products/:id');
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not found in route');
        
        $route->bind('user_id', Product::class);
    }

    public function testModelBinderResolvesModelByPrimaryKey()
    {
        $binder = new ModelBinder();
        
        $bindings = [
            'id' => [
                'model' => Product::class,
                'field' => null,
            ]
        ];
        
        $params = ['id' => 1];
        
        $resolved = $binder->resolve($bindings, $params);
        
        $this->assertArrayHasKey('id', $resolved);
        $this->assertInstanceOf(Product::class, $resolved['id']);
        $this->assertEquals(1, $resolved['id']->id);
        $this->assertEquals('Laptop', $resolved['id']->name);
    }

    public function testModelBinderThrowsExceptionWhenModelNotFound()
    {
        $binder = new ModelBinder();
        
        $bindings = [
            'id' => [
                'model' => Product::class,
                'field' => null,
            ]
        ];
        
        $params = ['id' => 999];
        
        $this->expectException(\Lightpack\Exceptions\RecordNotFoundException::class);
        
        $binder->resolve($bindings, $params);
    }

    public function testModelBinderSkipsOptionalParameters()
    {
        $binder = new ModelBinder();
        
        $bindings = [
            'id' => [
                'model' => Product::class,
                'field' => null,
            ]
        ];
        
        $params = []; // No id provided
        
        $resolved = $binder->resolve($bindings, $params);
        
        $this->assertEmpty($resolved);
    }

    public function testModelBinderCachesResolvedModels()
    {
        $binder = new ModelBinder();
        
        $bindings = [
            'id' => [
                'model' => Product::class,
                'field' => null,
            ]
        ];
        
        $params = ['id' => 1];
        
        // First resolution
        $resolved1 = $binder->resolve($bindings, $params);
        
        // Second resolution (should use cache)
        $resolved2 = $binder->resolve($bindings, $params);
        
        // Should be the exact same instance
        $this->assertSame($resolved1['id'], $resolved2['id']);
    }

    // ========================================================================
    // PHASE 3: Custom Field Binding Tests
    // ========================================================================

    public function testBindWithCustomField()
    {
        $route = new Route();
        $route->setUri('/products/:name');
        $route->bind('name', Product::class, 'name');
        
        $bindings = $route->getBindings();
        
        $this->assertEquals('name', $bindings['name']['field']);
    }

    public function testModelBinderResolvesModelByCustomField()
    {
        $binder = new ModelBinder();
        
        $bindings = [
            'name' => [
                'model' => Product::class,
                'field' => 'name',
            ]
        ];
        
        $params = ['name' => 'Laptop'];
        
        $resolved = $binder->resolve($bindings, $params);
        
        $this->assertArrayHasKey('name', $resolved);
        $this->assertInstanceOf(Product::class, $resolved['name']);
        $this->assertEquals(1, $resolved['name']->id);
        $this->assertEquals('Laptop', $resolved['name']->name);
    }

    public function testModelBinderThrowsExceptionWhenCustomFieldNotFound()
    {
        $binder = new ModelBinder();
        
        $bindings = [
            'name' => [
                'model' => Product::class,
                'field' => 'name',
            ]
        ];
        
        $params = ['name' => 'NonExistentProduct'];
        
        $this->expectException(\Lightpack\Exceptions\RecordNotFoundException::class);
        
        $binder->resolve($bindings, $params);
    }

    // ========================================================================
    // Integration Tests: Full Request Flow
    // ========================================================================

    public function testFullDispatchWithModelBinding()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/products/1';

        $this->container->register('request', function () {
            return new \Lightpack\Http\Request();
        });

        $this->container->register('route', function ($container) {
            return new \Lightpack\Routing\RouteRegistry($container);
        });

        $this->container->register('router', function ($container) {
            return new \Lightpack\Routing\Router(
                $container->get('route')
            );
        });

        // Register route with binding
        $this->container->get('route')
            ->get('/products/:id', ProductController::class, 'show')
            ->bind('id', Product::class);

        $this->container->get('router')->parse('/products/1');

        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);
        $result = $dispatcher->dispatch();

        $this->assertInstanceOf(Product::class, $result);
        $this->assertNotNull($result->id, 'Product ID should not be null');
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Laptop', $result->name);
    }

    public function testDispatchWithMultipleModelBindings()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/products/1/users/2';

        $this->container->register('request', function () {
            return new \Lightpack\Http\Request();
        });

        $this->container->register('route', function ($container) {
            return new \Lightpack\Routing\RouteRegistry($container);
        });

        $this->container->register('router', function ($container) {
            return new \Lightpack\Routing\Router(
                $container->get('route')
            );
        });

        // Register route with multiple bindings
        $this->container->get('route')
            ->get('/products/:id/users/:user_id', ProductController::class, 'showMultiple')
            ->bind('id', Product::class)
            ->bind('user_id', User::class);

        $this->container->get('router')->parse('/products/1/users/2');

        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);
        $result = $dispatcher->dispatch();

        $this->assertIsArray($result);
        $this->assertInstanceOf(Product::class, $result['product']);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertEquals(1, $result['product']->id);
        $this->assertEquals(2, $result['user']->id);
    }

    public function testDispatchWithCustomFieldBinding()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/products/Laptop';

        $this->container->register('request', function () {
            return new \Lightpack\Http\Request();
        });

        $this->container->register('route', function ($container) {
            return new \Lightpack\Routing\RouteRegistry($container);
        });

        $this->container->register('router', function ($container) {
            return new \Lightpack\Routing\Router(
                $container->get('route')
            );
        });

        // Register route with custom field binding
        $this->container->get('route')
            ->get('/products/:name', ProductController::class, 'showByName')
            ->bind('name', Product::class, 'name');

        $this->container->get('router')->parse('/products/Laptop');

        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);
        $result = $dispatcher->dispatch();

        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals('Laptop', $result->name);
    }

    public function testDispatchThrowsRecordNotFoundExceptionFor404()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/products/999';

        $this->container->register('request', function () {
            return new \Lightpack\Http\Request();
        });

        $this->container->register('route', function ($container) {
            return new \Lightpack\Routing\RouteRegistry($container);
        });

        $this->container->register('router', function ($container) {
            return new \Lightpack\Routing\Router(
                $container->get('route')
            );
        });

        $this->container->get('route')
            ->get('/products/:id', ProductController::class, 'show')
            ->bind('id', Product::class);

        $this->container->get('router')->parse('/products/999');

        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);

        $this->expectException(\Lightpack\Exceptions\RecordNotFoundException::class);
        
        $dispatcher->dispatch();
    }

    public function testDispatchWithMixedScalarAndModelParameters()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/products/1/extra/test-value';

        $this->container->register('request', function () {
            return new \Lightpack\Http\Request();
        });

        $this->container->register('route', function ($container) {
            return new \Lightpack\Routing\RouteRegistry($container);
        });

        $this->container->register('router', function ($container) {
            return new \Lightpack\Routing\Router(
                $container->get('route')
            );
        });

        // Register route with one binding and one scalar param
        $this->container->get('route')
            ->get('/products/:id/extra/:extra', ProductController::class, 'showWithScalar')
            ->bind('id', Product::class);

        $this->container->get('router')->parse('/products/1/extra/test-value');

        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);
        $result = $dispatcher->dispatch();

        $this->assertIsArray($result);
        $this->assertInstanceOf(Product::class, $result['product']);
        $this->assertEquals('test-value', $result['extra']);
    }

    // ========================================================================
    // Performance & Correctness Tests
    // ========================================================================

    public function testBindingDoesNotAffectRoutesWithoutBindings()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/hello';

        $this->container->register('request', function () {
            return new \Lightpack\Http\Request();
        });

        $this->container->register('route', function ($container) {
            return new \Lightpack\Routing\RouteRegistry($container);
        });

        $this->container->register('router', function ($container) {
            return new \Lightpack\Routing\Router(
                $container->get('route')
            );
        });

        // Route without binding
        $this->container->get('route')
            ->get('/hello', 'MockControllerForBinding', 'greet');

        $this->container->get('router')->parse('/hello');

        $route = $this->container->get('router')->getRoute();
        
        $this->assertFalse($route->hasBindings());
    }

    public function testBindingValidationHappensAtRouteDefinitionTime()
    {
        $route = new Route();
        $route->setUri('/products/:id');
        
        // This should throw immediately, not during request
        $exceptionThrown = false;
        
        try {
            $route->bind('id', 'NonExistentModel');
        } catch (\Exception $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('Model class not found', $e->getMessage());
        }
        
        $this->assertTrue($exceptionThrown, 'Exception should be thrown at route definition time');
    }

    public function testModelBinderClearCacheMethod()
    {
        $binder = new ModelBinder();
        
        $bindings = [
            'id' => [
                'model' => Product::class,
                'field' => null,
            ]
        ];
        
        $params = ['id' => 1];
        
        // Resolve to populate cache
        $resolved1 = $binder->resolve($bindings, $params);
        
        // Clear cache
        $binder->clearCache();
        
        // Resolve again (should query again, not use cache)
        $resolved2 = $binder->resolve($bindings, $params);
        
        // Should be different instances after cache clear
        $this->assertNotSame($resolved1['id'], $resolved2['id']);
        $this->assertEquals($resolved1['id']->id, $resolved2['id']->id);
    }
}

class MockControllerForBinding
{
    public function greet()
    {
        return 'hello';
    }
}
