<?php

declare(strict_types=1);

use Lightpack\Cache\Drivers\NullDriver;
use PHPUnit\Framework\TestCase;

final class NullDriverTest extends TestCase
{
    public function testCanStoreItem()
    {
        $nullStorage = new NullDriver;
        $nullStorage->set('name', 'Lightpack', time() + (5 * 60));

        $this->assertFalse($nullStorage->has('name'));
        $this->assertTrue($nullStorage->get('name') === null);
    }

    public function testCanDeleteItem()
    {
        $nullStorage = new NullDriver;
        $nullStorage->set('name', 'Lightpack', time() + (5 * 60));

        $this->assertFalse($nullStorage->has('name'));
        $nullStorage->delete('name');
        $this->assertFalse($nullStorage->has('name'));
    }
}
