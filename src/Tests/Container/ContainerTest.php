<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require 'Services/A.php';
require 'Services/B.php';
require 'Services/C.php';
require 'Services/D.php';

final class ContainerTest extends TestCase
{
    public function setUp(): void
    {
        $this->container = new Lightpack\Container\Container();
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

        $this->assertInstanceOf(A::class, $a);
        $this->assertInstanceOf(B::class, $b);
        $this->assertInstanceOf(C::class, $c);
        $this->assertInstanceOf(D::class, $d);
        $this->assertInstanceOf(A::class, $d->a);
        $this->assertInstanceOf(B::class, $d->b);
        $this->assertInstanceOf(C::class, $d->c);
        $this->assertSame($a, $d->a);
        $this->assertSame($b, $d->b);
        $this->assertSame($c, $d->c);
        $this->assertCount(4, $this->container->getServices());
    }
}
