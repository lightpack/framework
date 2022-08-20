<?php

declare(strict_types=1);

use Lightpack\Utils\Arr;
use PHPUnit\Framework\TestCase;

final class ArrTest extends TestCase
{
    public function testArrayHasKey()
    {
        $array = ['a' => ['b' => ['c' => 'd']]];

        $this->assertTrue(Arr::has('a', $array));
        $this->assertTrue(Arr::has('a.b', $array));
        $this->assertTrue(Arr::has('a.b.c', $array));
    }

    public function testArrayGetByKey()
    {
        $array = ['a' => ['b' => ['c' => 'd']]];

        $this->assertEquals('d', Arr::get('a.b.c', $array));
        $this->assertEquals(['c' => 'd'], Arr::get('a.b', $array));
        $this->assertEquals('default', Arr::get('a.b.c.d', $array, 'default'));
    }

    public function testArrayFlatten()
    {
        $array = [
            'a' => 'A',
            'b' => 'B',
            'ab' => ['AB', 'BA'],
            'abc' => ['c' => 'ABC'],
            'd' => ['e' => ['f' => 'D']]
        ];

        $flattenedArray = Arr::flatten($array);

        // Assertions
        $this->assertIsArray($flattenedArray);
        $this->assertEquals(['A', 'B', 'AB', 'BA', 'ABC', 'D'] , $flattenedArray);
    }
}