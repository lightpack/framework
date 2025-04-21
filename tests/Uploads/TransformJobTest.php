<?php

namespace Lightpack\Tests\Uploads;

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
    
    protected function setUp(): void
    {
        // Mock storage
        $this->storage = $this->createMock(Storage::class);
        $this->storage->method('exists')->willReturn(true);
        $this->storage->method('read')->willReturn('file content');
        
        // Set up container
        $container = Container::getInstance();
        $container->register('storage', function() {
            return $this->storage;
        });
        
        // Create a test image instance that we'll mock
        $this->image = $this->getMockBuilder(TestImage::class)
            ->onlyMethods(['load', 'resize', 'save'])
            ->getMock();
            
        $this->image->method('load')->willReturnSelf();
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
            ->willReturn('file content');
            
        $this->storage->expects($this->once())
            ->method('write')
            ->with(
                $this->stringContains('uploads/public/media/123/thumbnail/test.jpg'),
                $this->anything()
            );
            
        // Expect image operations
        $this->image->expects($this->once())
            ->method('load')
            ->with($this->stringContains('transform_'));
            
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
                'resize' => [100, 100],
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
            ->willReturn('file content');
            
        $this->storage->expects($this->exactly(2))
            ->method('write')
            ->withConsecutive(
                [$this->stringContains('uploads/public/media/123/thumbnail/test.jpg'), $this->anything()],
                [$this->stringContains('uploads/public/media/123/square/test.jpg'), $this->anything()]
            );
            
        // Expect image operations
        $this->image->expects($this->exactly(2))
            ->method('load')
            ->with($this->stringContains('transform_'));
            
        $this->image->expects($this->exactly(2))
            ->method('resize')
            ->withConsecutive(
                [$this->equalTo(200), $this->equalTo(200)],
                [$this->equalTo(100), $this->equalTo(100)]
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
