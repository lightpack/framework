<?php

declare(strict_types=1);

use Lightpack\Container\Container;
use PHPUnit\Framework\TestCase;

final class DispatcherTest extends TestCase
{
    /** @var Container */
    private $container;

    public function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/lightpack/hello';

        $this->container = new Container();

        $this->container->register('request', function ($container) {
            return new \Lightpack\Http\Request('/lightpack');
        });

        $this->container->register('route', function ($container) {
            return new \Lightpack\Routing\RouteRegistry($container->get('request'));
        });

        $this->container->register('router', function ($container) {
            return new \Lightpack\Routing\Router(
                $container->get('request'),
                $container->get('route')
            );
        });
    }

    public function testRouteNotFoundException()
    {

        // assertions
        $this->expectException('\\Lightpack\\Exceptions\\RouteNotFoundException');
        new \Lightpack\Routing\Dispatcher($this->container);
        $this->fail('404: Route not found exception');
    }

    public function testControllerNotFoundException()
    {
        $this->container->get('route')->get('/users/:num', 'UserController', 'index');
        $this->container->get('router')->parse('/users/23');
        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);

        // assertions
        $this->expectException('\\Lightpack\\Exceptions\\ControllerNotFoundException');
        $dispatcher->dispatch();
        $this->fail('404: Controller not found exception');
    }

    public function testActionNotFoundException()
    {
        $controller = $this->getMockBuilder('UserController')->getMock();
        $this->container->get('route')->get('/users/:num', get_class($controller), 'index');
        $this->container->get('router')->parse('/users/23');
        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);

        // assertions
        $this->expectException('\\Lightpack\\Exceptions\\ActionNotFoundException');
        $dispatcher->dispatch();
        $this->fail('404: Action not found exception');
    }

    public function testControllerActionInvocation()
    {
        $_SERVER['REQUEST_URI'] = '/lightpack/hello';

        $this->container->get('route')->get('/hello', 'MockController', 'greet');
        $this->container->get('router')->parse('/hello');
        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);
        $this->assertEquals('hello', $dispatcher->dispatch());
    }

    public function testControllerActionInvocationWithOptionalParams()
    {
        $_SERVER['REQUEST_URI'] = '/lightpack/bar/hello/baz';

        $this->container->get('route')->get('/bar/:bar/baz/:baz?', 'MockController', 'foo');
        $this->container->get('router')->parse('/bar/hello/baz/world');
        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);
        $this->assertEquals('helloworld', $dispatcher->dispatch());
    }

    public function testControllerActionInvocationWithoutOptionalParams()
    {
        $_SERVER['REQUEST_URI'] = '/lightpack/bar/hello/baz';

        $this->container->get('route')->get('/bar/:bar/baz/:baz?', 'MockController', 'foo');
        $this->container->get('router')->parse('/bar/hello/baz');
        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);
        $this->assertEquals('hello', $dispatcher->dispatch());
    }
}

class MockController
{
    public function greet()
    {
        return 'hello';
    }

    public function foo($bar, $baz = null)
    {
        return $bar . $baz;
    }
}
