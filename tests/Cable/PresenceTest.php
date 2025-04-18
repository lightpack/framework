<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Cable\Presence;
use Lightpack\Cable\Cable;
use Lightpack\Cable\Drivers\PresenceDriverInterface;

final class PresenceTest extends TestCase
{
    private $mockDriver;
    private $mockCable;
    private $presence;

    public function setUp(): void
    {
        // Create a mock presence driver
        $this->mockDriver = $this->createMock(PresenceDriverInterface::class);
        
        // Create a mock Cable instance
        $this->mockCable = $this->createMock(Cable::class);
        
        // Create Presence instance with mock driver and Cable
        $this->presence = new Presence($this->mockCable, $this->mockDriver);
    }

    public function tearDown(): void
    {
        $this->mockDriver = null;
        $this->mockCable = null;
        $this->presence = null;
    }

    public function testJoin(): void
    {
        // Set expectations on the mock driver
        $this->mockDriver->expects($this->once())
            ->method('join')
            ->with(
                $this->equalTo('123'),
                $this->equalTo('presence-room')
            );
        
        // Call join() and verify it calls the driver
        $this->presence->join('123', 'presence-room');
    }
    
    public function testLeave(): void
    {
        // Set expectations on the mock driver
        $this->mockDriver->expects($this->once())
            ->method('leave')
            ->with(
                $this->equalTo('123'),
                $this->equalTo('presence-room')
            );
        
        // Call leave() and verify it calls the driver
        $this->presence->leave('123', 'presence-room');
    }
    
    public function testHeartbeat(): void
    {
        // Set expectations on the mock driver
        $this->mockDriver->expects($this->once())
            ->method('heartbeat')
            ->with(
                $this->equalTo('123'),
                $this->equalTo('presence-room')
            );
        
        // Call heartbeat() and verify it calls the driver
        $this->presence->heartbeat('123', 'presence-room');
    }
    
    public function testGetUsers(): void
    {
        $expectedUsers = ['123', '456', '789'];
        
        // Set expectations on the mock driver
        $this->mockDriver->expects($this->once())
            ->method('getUsers')
            ->with($this->equalTo('presence-room'))
            ->willReturn($expectedUsers);
        
        // Call getUsers() and verify it returns the expected users
        $users = $this->presence->getUsers('presence-room');
        
        $this->assertEquals($expectedUsers, $users);
    }
    
    public function testGetChannels(): void
    {
        $expectedChannels = ['presence-room1', 'presence-room2'];
        
        // Set expectations on the mock driver
        $this->mockDriver->expects($this->once())
            ->method('getChannels')
            ->with($this->equalTo('123'))
            ->willReturn($expectedChannels);
        
        // Call getChannels() and verify it returns the expected channels
        $channels = $this->presence->getChannels('123');
        
        $this->assertEquals($expectedChannels, $channels);
    }
    
    public function testBroadcast(): void
    {
        $channel = 'presence-room';
        $users = ['user1', 'user2'];
        
        // Set up the mock driver to return users
        $this->mockDriver->expects($this->once())
            ->method('getUsers')
            ->with($this->equalTo($channel))
            ->willReturn($users);
        
        // Set up the mock Cable to verify emit is called
        $this->mockCable->expects($this->once())
            ->method('to')
            ->with($this->equalTo($channel))
            ->willReturn($this->mockCable);
            
        $this->mockCable->expects($this->once())
            ->method('emit')
            ->with(
                $this->equalTo('presence:update'),
                $this->callback(function($payload) use ($users) {
                    return $payload['users'] === $users &&
                           $payload['count'] === count($users) &&
                           isset($payload['timestamp']);
                })
            );
        
        // Call join to trigger broadcast indirectly
        $this->presence->join('user1', $channel);
    }
    
    public function testCleanup(): void
    {
        // Set expectations on the mock driver
        $this->mockDriver->expects($this->once())
            ->method('cleanup');
        
        // Call cleanup() and verify it calls the driver
        $this->presence->cleanup();
    }
    
    public function testDriver(): void
    {
        // Test that we can access the driver through reflection
        $reflection = new \ReflectionClass($this->presence);
        $property = $reflection->getProperty('driver');
        $property->setAccessible(true);
        $driver = $property->getValue($this->presence);
        
        $this->assertSame($this->mockDriver, $driver);
    }
}
