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

        $this->container = new Container;

        $this->container->register('request', function ($container) {
            return new \Lightpack\Http\Request('/lightpack');
        });

        $this->container->register('route', function ($container) {
            return new \Lightpack\Routing\RouteRegistry($container);
        });

        $this->container->register('router', function ($container) {
            return new \Lightpack\Routing\Router(
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

    public function testBindingResolvesModelByIdViaConstructor()
    {
        $_SERVER['REQUEST_URI'] = '/lightpack/items/42';

        $this->container->get('route')->get('/items/:id', 'BindableController', 'show')
            ->bind('id', MockBindable::class);
        $this->container->get('router')->parse('/items/42');
        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);

        $this->assertEquals(42, $dispatcher->dispatch());
    }

    public function testBindingResolvesModelViaCustomCallback()
    {
        $_SERVER['REQUEST_URI'] = '/lightpack/items/test-slug';

        $this->container->get('route')->get('/items/:id', 'BindableController', 'show')
            ->bind('id', MockBindable::class, fn ($id) => new MockBindable(999));
        $this->container->get('router')->parse('/items/test-slug');
        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);

        $this->assertEquals(999, $dispatcher->dispatch());
    }

    public function testBindingSkippedWhenRouteParamDoesNotExist()
    {
        $_SERVER['REQUEST_URI'] = '/lightpack/items/42';

        $this->container->get('route')->get('/items/:id', 'IdController', 'show')
            ->bind('nonexistent', MockBindable::class);
        $this->container->get('router')->parse('/items/42');
        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);

        $this->assertEquals('42', $dispatcher->dispatch());
    }

    public function testMultipleBindingsResolveIndependently()
    {
        $_SERVER['REQUEST_URI'] = '/lightpack/notes/5/comments/10';

        $this->container->get('route')->get('/notes/:note/comments/:comment', 'MultiBindController', 'show')
            ->bind('note', MockBindable::class)
            ->bind('comment', MockBindable::class);
        $this->container->get('router')->parse('/notes/5/comments/10');
        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);

        $this->assertEquals('5:10', $dispatcher->dispatch());
    }

    public function testGroupLevelBindingsResolveAtDispatchTime()
    {
        $_SERVER['REQUEST_URI'] = '/lightpack/posts/7';

        $this->container->get('route')->group(['bind' => ['id' => ['model' => MockBindable::class, 'resolver' => null]]], function ($route) {
            $route->get('/posts/:id', 'BindableController', 'show');
        });
        $this->container->get('router')->parse('/posts/7');
        $dispatcher = new \Lightpack\Routing\Dispatcher($this->container);

        $this->assertEquals(7, $dispatcher->dispatch());
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

class MockBindable
{
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }
}

class BindableController
{
    public function show(MockBindable $id)
    {
        return $id->id;
    }
}

class IdController
{
    public function show($id)
    {
        return $id;
    }
}

class MultiBindController
{
    public function show(MockBindable $note, MockBindable $comment)
    {
        return $note->id . ':' . $comment->id;
    }
}
