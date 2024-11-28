<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Cache\Drivers\ArrayDriver;

final class ArrayDriverTest extends TestCase
{
    public function testCanStoreItem()
    {
        $arrayStorage = new ArrayDriver();
        $arrayStorage->set('name', 'Lightpack', time() + (5 * 60));

        $this->assertTrue($arrayStorage->has('name'));
        $this->assertTrue($arrayStorage->get('name') === 'Lightpack');
    }

    public function testCanDeleteItem()
    {
        $arrayStorage = new ArrayDriver();
        $arrayStorage->set('name', 'Lightpack', time() + (5 * 60));

        $this->assertTrue($arrayStorage->has('name'));
        $arrayStorage->delete('name');
        $this->assertFalse($arrayStorage->has('name'));
    }
}