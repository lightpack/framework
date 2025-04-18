<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Cable\Cable;
use Lightpack\Cable\DriverInterface;

final class CableTest extends TestCase
{
    private $mockDriver;
    private $cable;

    public function setUp(): void
    {
        // Create a mock driver
        $this->mockDriver = $this->createMock(DriverInterface::class);
        
        // Create Cable instance with mock driver
        $this->cable = new Cable($this->mockDriver);
    }

    public function tearDown(): void
    {
        $this->mockDriver = null;
        $this->cable = null;
    }

    public function testTo(): void
    {
        // Test that to() returns a Cable instance (a clone)
        $result = $this->cable->to('test-channel');
        
        // It should be a different instance but same class
        $this->assertInstanceOf(Cable::class, $result);
        $this->assertNotSame($this->cable, $result);
        
        // The channel should be set on the returned instance
        $this->assertEquals('test-channel', $this->getPrivateProperty($result, 'channel'));
        
        // Original instance should not be modified
        $this->assertNull($this->getPrivateProperty($this->cable, 'channel'));
    }
    
    public function testEmit(): void
    {
        // Set expectations on the mock driver
        $this->mockDriver->expects($this->once())
            ->method('emit')
            ->with(
                $this->equalTo('test-channel'),
                $this->equalTo('test-event'),
                $this->equalTo(['message' => 'Hello, world!'])
            );
        
        // Call emit() and verify it calls the driver
        $this->cable->to('test-channel')->emit('test-event', ['message' => 'Hello, world!']);
    }
    
    public function testEmitWithoutChannel(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No channel specified. Use to() method first.');
        
        // Try to emit without setting a channel first
        $this->cable->emit('test-event', ['message' => 'Hello, world!']);
    }
    
    public function testUpdate(): void
    {
        // Set expectations on the mock driver
        $this->mockDriver->expects($this->once())
            ->method('emit')
            ->with(
                $this->equalTo('test-channel'),
                $this->equalTo('dom-update'),
                $this->equalTo([
                    'selector' => '#test-element',
                    'html' => '<p>Updated content</p>'
                ])
            );
        
        // Call update() and verify it emits a dom-update event
        $this->cable->to('test-channel')->update('#test-element', '<p>Updated content</p>');
    }
    
    public function testUpdateWithoutChannel(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No channel specified. Use to() method first.');
        
        // Try to update without setting a channel first
        $this->cable->update('#content', '<p>Hello, world!</p>');
    }
    
    public function testCleanup(): void
    {
        // Set expectations on the mock driver
        $this->mockDriver->expects($this->once())
            ->method('cleanup')
            ->with($this->equalTo(3600));
        
        // Call cleanup() and verify it calls the driver
        $this->cable->cleanup(3600);
    }
    
    public function testCleanupWithDefaultValue(): void
    {
        // Set expectations on the mock driver
        $this->mockDriver->expects($this->once())
            ->method('cleanup')
            ->with($this->equalTo(86400)); // Default is 24 hours
        
        // Call cleanup() without arguments
        $this->cable->cleanup();
    }
    
    public function testGetDriver(): void
    {
        // Test that getDriver() returns the driver instance
        $driver = $this->cable->getDriver();
        
        $this->assertSame($this->mockDriver, $driver);
    }
    
    /**
     * Helper method to access private properties
     */
    private function getPrivateProperty($object, $propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        
        return $property->getValue($object);
    }
}
