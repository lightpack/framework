<?php

namespace Lightpack\Uploads;

use Lightpack\Storage\StorageInterface;
use Lightpack\Utils\Image;
use Lightpack\Container\Container;
use Lightpack\Jobs\Job;

/**
 * TransformJob
 * 
 * Handles image transformations for uploaded files.
 */
class TransformJob extends Job
{
    /**
     * @inheritdoc
     */
    protected $queue = 'uploads';

    /**
     * Override onQueue() to use config value.
     */
    public function onQueue(): string
    {
        return Container::getInstance()
            ->get('config')
            ->get('uploads.queue', 'default');
    }

    /**
     * Override maxAttempts to use config value.
     */
    public function maxAttempts(): int
    {
        return (int) Container::getInstance()
            ->get('config')
            ->get('uploads.max_attempts', 1);
    }

    /**
     * Override retryAfter to use config value.
     */
    public function retryAfter(): string
    {
        return Container::getInstance()
            ->get('config')
            ->get('uploads.retry_after', '60 seconds');
    }

    /**
     * @var \Lightpack\Uploads\UploadModel
     */
    protected $upload;
    
    /**
     * @var array
     */
    protected $transformations;
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function run()
    {
        $this->upload = new UploadModel($this->payload['upload_id']);
        $this->transformations = $this->payload['transformations'];

        // Only process image files
        if (!$this->isImage()) {
            return;
        }
        
        $storage = $this->getStorage();
        $originalFilePath = $this->upload->getPath();
        $fileContent = $storage->read($originalFilePath);
        
        if(!$fileContent) {
            return;
        }

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

        // Expect: [method, param1, param2, ...] OR chained: [[method, ...], [method, ...], ...]
        if (isset($options[0]) && is_array($options[0])) {
            // Chained transformations
            foreach ($options as $step) {
                $method = $step[0];
                $params = array_slice($step, 1);
                if (method_exists($image, $method)) {
                    $image->{$method}(...$params);
                }
            }
        } else {
            $method = $options[0];
            $params = array_slice($options, 1);
            if (method_exists($image, $method)) {
                $image->{$method}(...$params);
            }
        }
        
        // Save the transformed image to a temporary file with proper extension
        $extension = $this->upload->extension;
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
        return new Image($path);
    }
    
    /**
     * Check if the upload is one of the supported image formats.
     *
     * @return bool
     */
    protected function isImage(): bool
    {
        $mimeType = $this->upload->mime_type;
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        return in_array($mimeType, $imageTypes);
    }
    
    /**
     * Get the storage instance.
     *
     * @return \Lightpack\Storage\StorageInterface
     */
    protected function getStorage(): StorageInterface
    {
        return Container::getInstance()->resolve('storage');
    }
}
