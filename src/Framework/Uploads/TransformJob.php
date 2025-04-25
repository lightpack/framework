<?php

namespace Lightpack\Uploads;

use Lightpack\Storage\Storage;
use Lightpack\Utils\Image;
use Lightpack\Container\Container;
use Lightpack\Storage\LocalStorage;

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

        if($storage instanceof LocalStorage) {
            $originalFilePath = DIR_STORAGE . '/' . $originalFilePath;
        }
        
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

        if($storage instanceof LocalStorage) {
            $transformedFilePath = DIR_STORAGE . '/' . $transformedFilePath;
        }

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
        
        // Determine the extension based on mime type
        $extension = $this->getExtensionFromMimeType();
        
        // Save the transformed image to a temporary file with proper extension
        $transformedTempFile = tempnam(sys_get_temp_dir(), 'transformed_');
        $transformedTempFileWithExt = $transformedTempFile . '.' . $extension;
        rename($transformedTempFile, $transformedTempFileWithExt);
        
        // Save the image with the proper extension
        $image->save($transformedTempFileWithExt);
        
        // Store the transformed file
        $transformedContent = file_get_contents($transformedTempFileWithExt);
        $storage->write($transformedFilePath, $transformedContent);
        
        // Clean up temporary files
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        
        if (file_exists($transformedTempFileWithExt)) {
            unlink($transformedTempFileWithExt);
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
        // For tests, use the container to get the mocked Image instance
        if (defined('PHPUNIT_TESTSUITE')) {
            $image = Container::getInstance()->resolve(Image::class);
            return $image;
        }
        
        // For production, create a real Image instance
        return new Image($path);
    }
    
    /**
     * Check if the upload is an image.
     *
     * @return bool
     */
    protected function isImage(): bool
    {
        $mimeType = $this->upload->getMimeType();
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
    
    /**
     * Get file extension from MIME type.
     *
     * @return string
     */
    protected function getExtensionFromMimeType(): string
    {
        $mimeType = $this->upload->getMimeType();

        return match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg', // Default to jpg if unknown
        };
    }
}
