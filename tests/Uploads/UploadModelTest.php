<?php

namespace Lightpack\Tests\Uploads;

use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Storage\LocalStorage;

// Import shared test classes
require_once __DIR__ . '/TestClasses.php';

// Extend TestUploadModel for specific URL and path methods needed in this test
class UploadModelTestClass extends TestUploadModel
{
    public function url(?string $variant = null): string
    {
        $path = $this->getPath($variant);
        
        return Container::getInstance()->resolve('storage')->url("{$path}");
    }
    
    public function getPath(?string $variant = null): string
    {
        if ($variant) {
            return "uploads/public/media/{$this->id}/{$variant}/test.jpg";
        }
        
        return "uploads/public/media/{$this->id}/test.jpg";
    }
}

class UploadModelTest extends TestCase
{
    protected $storage;
    protected $model;
    
    protected function setUp(): void
    {
        // Mock storage
        $this->storage = $this->createMock(LocalStorage::class);
        $this->storage->method('url')->willReturnCallback(function($path) {
            return "https://example.com/{$path}";
        });
        
        // Set up container
        $container = Container::getInstance();
        $container->register('storage', function() {
            return $this->storage;
        });
        
        // Create a test model
        $this->model = new UploadModelTestClass();
        $this->model->id = 123;
        $this->model->file_name = 'test.jpg';
    }
    
    public function testUrlMethodReturnsCorrectUrl()
    {
        $result = $this->model->url();
        
        $this->assertEquals('https://example.com/uploads/public/media/123/test.jpg', $result);
    }
    
    public function testUrlMethodWithTransformationReturnsCorrectUrl()
    {
        $result = $this->model->url('thumbnail');
        
        $this->assertEquals('https://example.com/uploads/public/media/123/thumbnail/test.jpg', $result);
    }
    
    public function testPathMethodReturnsCorrectPath()
    {
        $result = $this->model->getPath();
        
        $this->assertEquals('uploads/public/media/123/test.jpg', $result);
    }
    
    public function testPathMethodWithTransformationReturnsCorrectPath()
    {
        $result = $this->model->getPath('thumbnail');
        
        $this->assertEquals('uploads/public/media/123/thumbnail/test.jpg', $result);
    }
}
