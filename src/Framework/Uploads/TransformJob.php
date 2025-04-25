<?php

namespace Lightpack\Uploads;

use Lightpack\Storage\Storage;
use Lightpack\Utils\Image;
use Lightpack\Container\Container;

/**
 * TransformJob
 * 
 * Handles image transformations for uploaded files.
 */
class TransformJob
{
    /**
     * @var object The upload model instance
     */
    protected $upload;
    
    /**
     * @var array
     */
    protected $transformations;
    
    /**
     * Create a new transform job instance.
     *
     * @param object $upload The upload model instance
     * @param array $transformations
     */
    public function __construct($upload, array $transformations)
    {
        $this->upload = $upload;
        $this->transformations = $transformations;
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        // Only process image files
        if (!$this->isImage()) {
            return;
        }
        
        $storage = $this->getStorage();
        $originalFilePath = $this->upload->getPath();
        
        // Check if the file exists
        if (!$storage->exists($originalFilePath)) {
            return;
        }
        
        // Get the file content
        $fileContent = $storage->read($originalFilePath);
        
        // Process each transformation
        foreach ($this->transformations as $variant => $options) {
            $this->processTransformation($variant, $options, $fileContent);
        }
    }
    
    /**
     * Process a single transformation.
     *
     * @param string $variant
     * @param array $options
     * @param string $fileContent
     * @return void
     */
    protected function processTransformation(string $variant, array $options, string $fileContent): void
    {
        $storage = $this->getStorage();
        $transformedFilePath = $this->upload->getPath($variant);
        
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'transform_');
        file_put_contents($tempFile, $fileContent);
        
        // Create image instance
        $image = $this->createImage($tempFile);
        
        // Apply transformations
        foreach ($options as $method => $params) {
            if (method_exists($image, $method)) {
                $image->{$method}(...$params);
            }
        }
        
        // Save the transformed image to a temporary file
        $transformedTempFile = tempnam(sys_get_temp_dir(), 'transformed_');
        $image->save($transformedTempFile);
        
        // Store the transformed file
        $transformedContent = file_get_contents($transformedTempFile);
        $storage->write($transformedFilePath, $transformedContent);
        
        // Clean up temporary files
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        if (file_exists($transformedTempFile)) {
            unlink($transformedTempFile);
        }
    }
    
    /**
     * Create an image instance.
     *
     * @param string $path
     * @return object An image object with load, resize, and save methods
     */
    protected function createImage(string $path): object
    {
        $image = Container::getInstance()->resolve(Image::class);
        $image->load($path);
        
        return $image;
    }
    
    /**
     * Check if the upload is an image.
     *
     * @return bool
     */
    protected function isImage(): bool
    {
        // If the upload model has a getMimeType method, use it
        if (method_exists($this->upload, 'getMimeType')) {
            $mimeType = $this->upload->getMimeType();
        } else {
            // Otherwise, try to access the mime_type property directly
            $mimeType = $this->upload->mime_type ?? 'application/octet-stream';
        }
        
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        return in_array($mimeType, $imageTypes);
    }
    
    /**
     * Get the storage instance.
     *
     * @return \Lightpack\Storage\Storage
     */
    protected function getStorage(): Storage
    {
        return Container::getInstance()->resolve('storage');
    }
}
