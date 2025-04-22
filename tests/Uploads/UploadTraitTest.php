<?php

namespace Lightpack\Tests\Uploads;

use PHPUnit\Framework\TestCase;
use Lightpack\Uploads\UploadTrait;
use Lightpack\Uploads\UploadService;
use Lightpack\Container\Container;
use Lightpack\Http\UploadedFile;

// Import shared test classes
require_once __DIR__ . '/TestClasses.php';

// Mock Query Builder for testing
class MockQueryBuilder
{
    public function where()
    {
        return $this;
    }
    
    public function one()
    {
        $model = new TestUploadModel();
        $model->file_name = 'test.jpg';
        $model->collection = 'default';
        return $model;
    }
}

// Standalone test implementation of HasUploads trait
class TestHasUploads
{
    use UploadTrait;
    
    public $id = 1;

    public function getTableName(): string
    {
        return 'test_models';
    }
    
    public function hasMany($class, $foreignKey)
    {
        return new MockQueryBuilder();
    }
    
    public function getPrimaryKey()
    {
        return 'id';
    }
}

class UploadTraitTest extends TestCase
{
    protected $model;
    protected $uploadService;
    protected $uploadedFile;
    
    protected function setUp(): void
    {
        $this->model = new TestHasUploads();
        
        // Mock upload service
        $this->uploadService = $this->createMock(UploadService::class);
        
        // Mock uploaded file
        $this->uploadedFile = $this->createMock(UploadedFile::class);
        
        // Set up container
        $container = Container::getInstance();
        $container->register(UploadService::class, function() {
            return $this->uploadService;
        });
    }
    
    public function testUploadsMethodReturnsQueryBuilder()
    {
        $result = $this->model->uploads();
        
        $this->assertInstanceOf(MockQueryBuilder::class, $result);
    }
    
    public function testFirstUploadMethodReturnsUploadModel()
    {
        $result = $this->model->firstUpload();
        
        $this->assertInstanceOf(TestUploadModel::class, $result);
    }
    
    public function testAttachMethodCallsUploadService()
    {
        // Expect the upload service to be called
        $this->uploadService->expects($this->once())
            ->method('save')
            ->with(
                $this->equalTo($this->model),
                $this->equalTo('avatar'),
                $this->equalTo([])
            )
            ->willReturn(new TestUploadModel());
        
        // Act
        $result = $this->model->attach('avatar');
        
        // Assert
        $this->assertInstanceOf(TestUploadModel::class, $result);
    }
    
    public function testAttachMultipleMethodCallsUploadService()
    {
        // Expect the upload service to be called
        $this->uploadService->expects($this->once())
            ->method('saveMultiple')
            ->with(
                $this->equalTo($this->model),
                $this->equalTo('gallery'),
                $this->equalTo([])
            )
            ->willReturn([new TestUploadModel()]);
        
        // Act
        $result = $this->model->attachMultiple('gallery');
        
        // Assert
        $this->assertIsArray($result);
        $this->assertInstanceOf(TestUploadModel::class, $result[0]);
    }
    
    public function testAttachFromUrlMethodCallsUploadService()
    {
        // Expect the upload service to be called
        $this->uploadService->expects($this->once())
            ->method('saveFromUrl')
            ->with(
                $this->equalTo($this->model),
                $this->equalTo('https://example.com/image.jpg'),
                $this->equalTo([])
            )
            ->willReturn(new TestUploadModel());
        
        // Act
        $result = $this->model->attachFromUrl('https://example.com/image.jpg');
        
        // Assert
        $this->assertInstanceOf(TestUploadModel::class, $result);
    }
    
    public function testDetachMethodCallsUploadService()
    {
        // Expect the upload service to be called
        $this->uploadService->expects($this->once())
            ->method('delete')
            ->with($this->equalTo(123))
            ->willReturn(true);
        
        // Act
        $result = $this->model->detach(123);
        
        // Assert
        $this->assertTrue($result);
    }
}
