<?php

namespace Lightpack\Tests\Uploads;

// Define constant to indicate we're running in PHPUnit
define('PHPUNIT_TESTSUITE', true);

use PHPUnit\Framework\TestCase;
use Lightpack\Uploads\TransformJob;
use Lightpack\Storage\Storage;
use Lightpack\Utils\Image;
use Lightpack\Container\Container;

// Import shared test classes
require_once __DIR__ . '/TestClasses.php';

class TransformJobTest extends TestCase
{
    protected $storage;
    protected $image;
    protected $upload;
    protected $validImageData;
    
    protected function setUp(): void
    {
        // Create a small valid 1x1 pixel JPEG image
        $this->validImageData = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAIBAQIBAQICAgICAgICAwUDAwMDAwYEBAMFBwYHBwcGBwcICQsJCAgKCAcHCg0KCgsMDAwMBwkODw0MDgsMDAz/2wBDAQICAgMDAwYDAwYMCAcIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAz/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD9/KKKKAP/2Q==');
        
        // Mock storage
        $this->storage = $this->createMock(Storage::class);
        $this->storage->method('exists')->willReturn(true);
        $this->storage->method('read')->willReturn($this->validImageData);
        
        // Set up container
        $container = Container::getInstance();
        $container->register('storage', function() {
            return $this->storage;
        });
        
        // Create a test image instance that we'll mock
        $this->image = $this->getMockBuilder(Image::class)
            ->onlyMethods(['resize', 'save'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->image->method('resize')->willReturnSelf();
        
        // Register our test image in the container
        $container->register(Image::class, function() {
            return $this->image;
        });
        
        // Create a test upload model
        $this->upload = new TestUploadModel();
    }
    
    public function testHandleMethodWithResizeTransformation()
    {
        // Arrange
        $transformations = [
            'thumbnail' => [
                'resize' => [200, 200],
            ],
        ];
        
        $job = new TransformJob($this->upload, $transformations);
        
        // Expect storage operations
        $this->storage->expects($this->once())
            ->method('exists')
            ->with($this->stringContains('uploads/public/media/123/test.jpg'))
            ->willReturn(true);
            
        $this->storage->expects($this->once())
            ->method('read')
            ->with($this->stringContains('uploads/public/media/123/test.jpg'))
            ->willReturn($this->validImageData);
            
        $this->storage->expects($this->once())
            ->method('write')
            ->with(
                $this->stringContains('uploads/public/media/123/thumbnail/test.jpg'),
                $this->anything()
            );
            
        // Expect image operations
        $this->image->expects($this->once())
            ->method('resize')
            ->with($this->equalTo(200), $this->equalTo(200));
            
        $this->image->expects($this->once())
            ->method('save')
            ->with($this->stringContains('transformed_'));
            
        // Act
        $job->handle();
        
        // No explicit assertions needed - we're testing the expectations
        $this->addToAssertionCount(1);
    }
    
    public function testHandleMethodWithMultipleTransformations()
    {
        // Arrange
        $transformations = [
            'thumbnail' => [
                'resize' => [200, 200],
            ],
            'square' => [
                'resize' => [400, 300],
            ],
        ];
        
        $job = new TransformJob($this->upload, $transformations);
        
        // Expect storage operations
        $this->storage->expects($this->once())
            ->method('exists')
            ->with($this->stringContains('uploads/public/media/123/test.jpg'))
            ->willReturn(true);
            
        $this->storage->expects($this->once())
            ->method('read')
            ->with($this->stringContains('uploads/public/media/123/test.jpg'))
            ->willReturn($this->validImageData);
            
        $this->storage->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                [$this->stringContains('uploads/public/media/123/thumbnail/test.jpg'), $this->anything()],
                [$this->stringContains('uploads/public/media/123/square/test.jpg'), $this->anything()]
            );
            
        // Expect image operations
        $this->image->expects($this->exactly(2))
            ->method('resize')
            ->withConsecutive(
                [$this->equalTo(200), $this->equalTo(200)],
                [$this->equalTo(400), $this->equalTo(300)]
            );
            
        $this->image->expects($this->exactly(2))
            ->method('save')
            ->with($this->stringContains('transformed_'));
            
        // Act
        $job->handle();
        
        // No explicit assertions needed - we're testing the expectations
        $this->addToAssertionCount(1);
    }
}
