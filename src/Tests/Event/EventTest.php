<?php

declare(strict_types=1);

use Lightpack\Container\Container;
use Lightpack\Event\Event;
use PHPUnit\Framework\TestCase;

final class EventTest extends TestCase
{
    /** @var Container */
    private $container;

    /** @var Event */
    private $event;

    public function setUp(): void
    {
        $this->container = new Container();
        $this->event = new Event($this->container);
    }

    public function tearDown(): void
    {
        unset($this->container);
        unset($this->event);
    }

    public function testSubscribeMethod()
    {
        $this->event->subscribe('event1', 'EventHandler1');
        $this->event->subscribe('event1', 'EventHandler2');
        $this->event->subscribe('event2', 'EventHandler3');
        $this->event->subscribe('event2', 'EventHandler4');
        
        $this->assertEquals(
            [
                'event1'  => ['EventHandler1', 'EventHandler2'],
                'event2'  => ['EventHandler3', 'EventHandler4'],
            ], 
            $this->event->getSubscribers()
        );
    }
    public function testUnsubscribeMethod()
    {
        $this->event->subscribe('event1', 'EventHandler1');
        $this->event->subscribe('event1', 'EventHandler2');
        $this->event->subscribe('event2', 'EventHandler3');
        $this->event->subscribe('event2', 'EventHandler4');
        $this->event->subscribe('event2', 'EventHandler2');

        $this->event->unsubscribe('EventHandler2');
        $this->event->unsubscribe('EventHandler4');

        $this->assertEquals(
            [
                'event1'  => ['EventHandler1'],
                'event2'  => ['EventHandler3'],
            ], 
            $this->event->getSubscribers()
        );
    }
    public function testEventNotFoundException()
    {
        $this->expectException(\Lightpack\Exceptions\EventNotFoundException::class);
        $this->event->fire('event');
    }
    public function testEventHandlerMethodNotFoundException()
    {
        $mockEvent = $this->getMockBuilder(\stdClass::class)->getMock();
        $this->event->subscribe('event', get_class($mockEvent));
        $this->expectException(\TypeError::class);
        $this->event->fire('event');
    }
}