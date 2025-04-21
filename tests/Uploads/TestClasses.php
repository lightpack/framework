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
    
    public function getTable()
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
    public $path;
    
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
    
    public function path($variant = null)
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
    public function __construct() {}
    
    public function load($path)
    {
        return $this;
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
