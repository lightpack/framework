<?php

namespace Lightpack\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Lightpack\Storage\S3Storage;
use Lightpack\Exceptions\FileUploadException;
use Aws\S3\S3Client;
use Aws\Result;
use Aws\MockHandler;
use Aws\CommandInterface;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;

class S3StorageTest extends TestCase
{
    private $s3Client;
    private $mockHandler;
    private $storage;
    
    protected function setUp(): void
    {
        // Skip if AWS SDK is not installed
        if (!class_exists('Aws\S3\S3Client')) {
            $this->markTestSkipped('AWS SDK not installed. Install it to run these tests.');
        }
        
        // Create a mock handler
        $this->mockHandler = new MockHandler();
        
        // Create a real S3Client with the mock handler
        $this->s3Client = new S3Client([
            'region' => 'us-east-1',
            'version' => 'latest',
            'credentials' => [
                'key' => 'mock-key',
                'secret' => 'mock-secret',
            ],
            'handler' => $this->mockHandler,
        ]);
        
        // Create the storage instance
        $this->storage = new S3Storage($this->s3Client, 'test-bucket', 'base/path');
    }
    
    public function testRead()
    {
        // Create a stream with content
        $stream = Utils::streamFor('file content');
        
        // Queue a successful response
        $this->mockHandler->append(new Result([
            'Body' => $stream,
        ]));
        
        // Test the read method
        $result = $this->storage->read('test.txt');
        $this->assertEquals('file content', $result);
    }
    
    public function testReadReturnsNullOnException()
    {
        // Queue an exception
        $this->mockHandler->append(new \Aws\S3\Exception\S3Exception(
            'Not found',
            $this->createMock(CommandInterface::class)
        ));
        
        // Test the read method with an exception
        $result = $this->storage->read('nonexistent.txt');
        $this->assertNull($result);
    }
    
    public function testWrite()
    {
        // Queue a successful response
        $this->mockHandler->append(new Result([]));
        
        // Test the write method
        $result = $this->storage->write('test.txt', 'file content');
        $this->assertTrue($result);
    }
    
    public function testExists()
    {
        // Queue a successful response for doesObjectExist
        // This is a bit tricky since doesObjectExist makes a HeadObject call internally
        $this->mockHandler->append(new Result([]));
        
        // Test the exists method
        $result = $this->storage->exists('test.txt');
        $this->assertTrue($result);
    }
    
    public function testDelete()
    {
        // Queue responses for exists check and delete
        $this->mockHandler->append(new Result([])); // For doesObjectExist
        $this->mockHandler->append(new Result([])); // For deleteObject
        
        // Test the delete method
        $result = $this->storage->delete('test.txt');
        $this->assertTrue($result);
    }
}
