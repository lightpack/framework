<?php

namespace Lightpack\Tests\Database\Lucid;

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Lucid\CastHandler;
use InvalidArgumentException;
use DateTime;

class CastHandlerTest extends TestCase
{
    private CastHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new CastHandler();
    }

    public function testBasicTypeCasting()
    {
        // Integer casting
        $this->assertSame(123, $this->handler->cast('123', 'int'));
        $this->assertSame(123, $this->handler->cast('123.45', 'int'));
        $this->assertSame(0, $this->handler->cast('abc', 'int'));

        // Float casting
        $this->assertSame(123.45, $this->handler->cast('123.45', 'float'));
        $this->assertSame(123.0, $this->handler->cast('123', 'float'));
        $this->assertSame(0.0, $this->handler->cast('abc', 'float'));

        // String casting
        $this->assertSame('123', $this->handler->cast(123, 'string'));
        $this->assertSame('123.45', $this->handler->cast(123.45, 'string'));
        $this->assertSame('1', $this->handler->cast(true, 'string'));

        // Boolean casting
        $this->assertTrue($this->handler->cast(1, 'bool'));
        $this->assertTrue($this->handler->cast('1', 'bool'));
        $this->assertTrue($this->handler->cast('true', 'bool'));
        $this->assertFalse($this->handler->cast(0, 'bool'));
        $this->assertFalse($this->handler->cast('', 'bool'));
    }

    public function testArrayJsonCasting()
    {
        // Array to JSON
        $array = ['foo' => 'bar', 'baz' => [1, 2, 3]];
        $json = json_encode($array);

        // Cast JSON to array
        $this->assertEquals($array, $this->handler->cast($json, 'array'));

        // Cast array to JSON (uncast)
        $this->assertEquals($json, $this->handler->uncast($array, 'array'));

        // Invalid JSON string
        $this->expectException(InvalidArgumentException::class);
        $this->handler->cast('{invalid json}', 'array');
    }

    public function testDateCasting()
    {
        $date = '2025-03-18';
        $datetime = new DateTime($date);

        // Cast string to DateTime object
        $result = $this->handler->cast($date, 'date');
        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertEquals($date, $result->format('Y-m-d'));
        
        // Cast DateTime to DateTime (should return same instance)
        $this->assertSame($datetime, $this->handler->cast($datetime, 'date'));

        // Uncast DateTime to string
        $this->assertEquals($date, $this->handler->uncast($datetime, 'date'));

        // Invalid date string
        $this->expectException(InvalidArgumentException::class);
        $this->handler->cast('not-a-date', 'date');
    }

    public function testDateTimeCasting()
    {
        $datetimeStr = '2025-03-18 22:16:23';
        $datetime = new DateTime($datetimeStr);

        // Cast string to DateTime
        $result = $this->handler->cast($datetimeStr, 'datetime');
        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertEquals($datetimeStr, $result->format('Y-m-d H:i:s'));

        // Cast DateTime to DateTime (should return same instance)
        $this->assertSame($datetime, $this->handler->cast($datetime, 'datetime'));

        // Uncast DateTime to string
        $this->assertEquals($datetimeStr, $this->handler->uncast($datetime, 'datetime'));

        // Invalid datetime string
        $this->expectException(InvalidArgumentException::class);
        $this->handler->cast('not-a-datetime', 'datetime');
    }

    public function testTimestampCasting()
    {
        $timestamp = time();
        $datetime = new DateTime("@$timestamp");

        // Cast numeric timestamp
        $this->assertSame($timestamp, $this->handler->cast($timestamp, 'timestamp'));

        // Cast DateTime to timestamp
        $this->assertSame($timestamp, $this->handler->cast($datetime, 'timestamp'));

        // Cast string timestamp
        $this->assertSame($timestamp, $this->handler->cast((string)$timestamp, 'timestamp'));

        // Cast date string to timestamp
        $dateStr = '2025-03-18 22:16:23';
        $expected = strtotime($dateStr);
        $this->assertSame($expected, $this->handler->cast($dateStr, 'timestamp'));

        // Uncast timestamp
        $this->assertEquals((string)$timestamp, $this->handler->uncast($timestamp, 'timestamp'));

        // Invalid timestamp string
        $this->expectException(InvalidArgumentException::class);
        $this->handler->cast('not-a-timestamp', 'timestamp');
    }

    public function testUnknownTypeCasting()
    {
        // Unknown type should throw exception
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown cast type: 'unknown_type'");
        
        $this->handler->cast('test', 'unknown_type');
    }

    public function testUnknownTypeUncastingThrowsException()
    {
        // Unknown type should throw exception on uncast too
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown cast type: 'unknown_type'");
        
        $this->handler->uncast('test', 'unknown_type');
    }

    public function testNullValues()
    {
        // Null values should remain null for all types
        $this->assertNull($this->handler->cast(null, 'int'));
        $this->assertNull($this->handler->cast(null, 'float'));
        $this->assertNull($this->handler->cast(null, 'string'));
        $this->assertNull($this->handler->cast(null, 'bool'));
        $this->assertNull($this->handler->cast(null, 'array'));
        $this->assertNull($this->handler->cast(null, 'json'));
        $this->assertNull($this->handler->cast(null, 'date'));
        $this->assertNull($this->handler->cast(null, 'datetime'));
        $this->assertNull($this->handler->cast(null, 'timestamp'));
        $this->assertNull($this->handler->cast(null, 'unknown_type'));
    }

    public function testInvalidValues()
    {
        // These should throw exceptions as they can't handle invalid values
        $this->expectException(InvalidArgumentException::class);
        $this->handler->cast('not-an-array', 'array');
    }
}
