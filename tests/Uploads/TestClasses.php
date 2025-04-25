<?php

namespace Lightpack\Tests\Uploads;

/**
 * Test-specific implementation of model for testing
 */
class TestModel
{
    public $id = 1;
    
    public function getPrimaryKey()
    {
        return 'id';
    }
    
    public function getTableName()
    {
        return 'test_models';
    }
}

/**
 * Test-specific implementation of upload model for testing
 */
class TestUploadModel
{
    public $id = 123;
    public $model_type;
    public $model_id;
    public $collection;
    public $name;
    public $file_name;
    public $mime_type = 'image/jpeg';
    public $extension;
    public $size;
    public $disk = 'public';
    public $path = 'media/123';

    public function getTableName()
    {
        return 'test_models';
    }
    
    public function save()
    {
        return true;
    }
    
    public function find($id)
    {
        $this->id = $id;
        return $this;
    }
    
    public function delete()
    {
        return true;
    }
    
    public function getPath(?string $variant = null): string
    {
        if ($variant) {
            return "uploads/public/media/{$this->id}/{$variant}/test.jpg";
        }
        
        return "uploads/public/media/{$this->id}/test.jpg";
    }
    
    public function getFilename()
    {
        return 'test.jpg';
    }
    
    public function getMimeType()
    {
        return $this->mime_type;
    }
}

/**
 * Test-specific implementation of Image for testing
 */
class TestImage
{
    public function __construct(string $filepath = null) 
    {
        // No-op for testing, but we need to match the signature
    }
    
    public function resize($width, $height)
    {
        return $this;
    }
    
    public function save($path)
    {
        // No-op for testing
    }
}
