<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Cable\MessageBatcher;
use Lightpack\Cable\Cable;

final class MessageBatcherTest extends TestCase
{
    private $mockCable;
    private $batcher;

    public function setUp(): void
    {
        // Create a mock Cable instance
        $this->mockCable = $this->createMock(Cable::class);
        
        // Create MessageBatcher instance with mock Cable and a test channel
        $this->batcher = new MessageBatcher($this->mockCable, 'test-channel');
    }

    public function tearDown(): void
    {
        $this->mockCable = null;
        $this->batcher = null;
    }

    public function testAdd(): void
    {
        // Test that add() adds a message to the batch
        $this->batcher->add('test-event', ['message' => 'Hello, world!']);
        
        // Verify message was added to the batch
        $messages = $this->getPrivateProperty($this->batcher, 'messages');
        
        $this->assertCount(1, $messages);
        $this->assertEquals('test-event', $messages[0]['event']);
        $this->assertEquals(['message' => 'Hello, world!'], $messages[0]['payload']);
    }
    
    public function testAddMultiple(): void
    {
        // Add multiple messages to the batch
        $this->batcher->add('event-1', ['message' => 'Message 1']);
        $this->batcher->add('event-2', ['message' => 'Message 2']);
        $this->batcher->add('event-3', ['message' => 'Message 3']);
        
        // Verify all messages were added
        $messages = $this->getPrivateProperty($this->batcher, 'messages');
        
        $this->assertCount(3, $messages);
        $this->assertEquals('event-1', $messages[0]['event']);
        $this->assertEquals('event-2', $messages[1]['event']);
        $this->assertEquals('event-3', $messages[2]['event']);
    }
    
    public function testFlush(): void
    {
        // Add messages to the batch
        $this->batcher->add('event-1', ['message' => 'Message 1']);
        $this->batcher->add('event-2', ['message' => 'Message 2']);
        
        // Set expectations for the mock Cable
        $this->mockCable->expects($this->once())
            ->method('to')
            ->with($this->equalTo('test-channel'))
            ->willReturn($this->mockCable);
            
        $this->mockCable->expects($this->once())
            ->method('emit')
            ->with(
                $this->equalTo('batch'),
                $this->callback(function($payload) {
                    return count($payload['events']) === 2 &&
                           $payload['count'] === 2 &&
                           isset($payload['timestamp']);
                })
            );
        
        // Flush the batch
        $this->batcher->flush();
        
        // Verify batch was cleared
        $messages = $this->getPrivateProperty($this->batcher, 'messages');
        $this->assertEmpty($messages);
    }
    
    public function testFlushEmptyBatch(): void
    {
        // Set expectations for the mock Cable (should not be called)
        $this->mockCable->expects($this->never())
            ->method('to');
            
        $this->mockCable->expects($this->never())
            ->method('emit');
        
        // Flush an empty batch
        $this->batcher->flush();
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
