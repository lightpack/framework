<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Exceptions\BindingNotFoundException;
use Lightpack\Exceptions\ServiceNotFoundException;

require 'Services/A.php';
require 'Services/B.php';
require 'Services/C.php';
require 'Services/D.php';
require 'Services/E.php';
require 'Services/Service.php';
require 'Services/ServiceA.php';
require 'Services/ServiceB.php';
require 'Services/InterfaceFoo.php';
require 'Services/FooA.php';
require 'Services/FooB.php';

final class ContainerTest extends TestCase
{
    /** @var Container */
    private $container;

    public function setUp(): void
    {
        $this->container = new Container();
    }

    public function tearDown(): void
    {
        $this->container = null;
    }

    public function testContainerHasMethod()
    {
        $this->container->register('service', function () {
            return new stdClass();
        });
        $this->assertTrue($this->container->has('service'));
        $this->assertFalse($this->container->has('mailer'));
    }
    public function testContainerGetMethod()
    {
        $this->container->register('service', function () {
            return new stdClass();
        });
        $this->assertInstanceOf(stdClass::class, $this->container->get('service'));
        $this->assertSame($this->container->get('service'), $this->container->get('service'));
        $this->expectException(\Lightpack\Exceptions\ServiceNotFoundException::class);
        $this->container->get('mailer');
    }
    public function testContainerFactoryMethod()
    {
        $this->container->factory('service', function () {
            return new stdClass();
        });
        $this->assertNotSame($this->container->get('service'), $this->container->get('service'));
        $this->assertInstanceOf(stdClass::class, $this->container->get('service'));
    }

    public function testContainerCanResolveConcreteServices()
    {
        $a = $this->container->resolve(A::class);
        $b = $this->container->resolve(B::class);
        $c = $this->container->resolve(C::class);
        $d = $this->container->resolve(D::class);

        // Assertions
        $this->assertInstanceOf(A::class, $a);
        $this->assertInstanceOf(B::class, $b);
        $this->assertInstanceOf(C::class, $c);
        $this->assertInstanceOf(D::class, $d);
        $this->assertInstanceOf(A::class, $d->a);
        $this->assertInstanceOf(B::class, $d->b);
        $this->assertInstanceOf(C::class, $d->c);
        $this->assertCount(4, $this->container->getServices());
        $this->assertCount(4, $this->container->getServices());
        $this->assertSame($a, $d->a);
        $this->assertSame($b, $d->b);
        $this->assertSame($c, $d->c);
    }

    public function testContainerCanResolveAbstractBoundServices()
    {
        $this->container->bind(Service::class, ServiceA::class);
        $e = $this->container->resolve(E::class);

        $this->assertInstanceOf(E::class, $e);
        $this->assertInstanceOf(ServiceA::class, $e->service);
        $this->assertCount(1, $this->container->getBindings());
    }

    public function testContainerCanResolveInterfaceBoundServices()
    {
        $this->container->bind(InterfaceFoo::class, FooA::class);
        $this->container->bind(InterfaceFoo::class, FooB::class);
        $foo = $this->container->resolve(InterfaceFoo::class);

        // Assertions
        $this->assertNotInstanceOf(FooA::class, $foo);
        $this->assertInstanceOf(FooB::class, $foo);
        $this->assertCount(1, $this->container->getBindings());
    }

    public function testContainerThrowsBindingNotFoundException()
    {
        $this->expectException(BindingNotFoundException::class);
        $this->container->resolve(Service::class);
    }

    public function testContainerCanReset()
    {
        $this->container->bind(Service::class, ServiceA::class);
        $this->container->resolve(A::class);
        $this->container->resolve(B::class);
        $this->container->resolve(E::class);

        // Assertions
        $this->assertCount(1, $this->container->getBindings());
        $this->assertCount(4, $this->container->getServices());

        // Reset container
        $this->container->reset();

        // Assertions
        $this->assertCount(0, $this->container->getBindings());
        $this->assertCount(0, $this->container->getServices());
    }

    public function testContainerCanResolveMethodCallsForClass()
    {
        $this->container->bind(Service::class, ServiceA::class);
        $this->container->bind(InterfaceFoo::class, FooA::class);

        // Assertions
        $this->assertCount(2, $this->container->getBindings());
        $this->assertCount(0, $this->container->getServices());

        // Call method
        $result = $this->container->call(E::class, 'foo', ['Bar', 'Baz']);

        // Assertions
        $this->assertEquals([
            'foo' => 'FooA',
            'bar' => 'Bar',
            'baz' => 'Baz',
        ], $result);
    }

    public function testContainerCanResolveMethodCallForInstanceObject()
    {
        $this->container->bind(Service::class, ServiceA::class);
        $this->container->bind(InterfaceFoo::class, FooA::class);
        $e = $this->container->resolve(E::class);

        // Assertions
        $this->assertCount(2, $this->container->getBindings());
        $this->assertCount(2, $this->container->getServices());

        // Call method
        $result = $this->container->call($e, 'foo', ['Bar', 'Baz']);

        // Assertions
        $this->assertEquals([
            'foo' => 'FooA',
            'bar' => 'Bar',
            'baz' => 'Baz',
        ], $result);
    }
}
