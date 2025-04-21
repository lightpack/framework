<?php

namespace Lightpack\Uploads;

use Lightpack\Container\Container;

/**
 * HasUploads Trait
 * 
 * Provides file upload functionality to models.
 */
trait HasUploads
{
    /**
     * Define the relationship between the model and its uploads.
     *
     * @return \Lightpack\Database\Query\Builder
     */
    public function uploads(?string $collection = null)
    {
        $query = $this->hasMany(UploadModel::class, 'model_id')
            ->where('model_type', get_class($this));
            
        if ($collection) {
            $query->where('collection', $collection);
        }
        
        return $query;
    }
    
    /**
     * Get the first upload for a specific collection.
     *
     * @param string|null $collection
     * @return \Lightpack\Uploads\UploadModel|null
     */
    public function firstUpload(?string $collection = null)
    {
        return $this->uploads($collection)->one();
    }
    
    /**
     * Attach a file to the model.
     *
     * @param string $key The form field name
     * @param array $config Configuration options
     * @return \Lightpack\Uploads\UploadModel
     */
    public function attach(string $key, array $config = [])
    {
        $service = $this->getUploadService();
        $upload = $service->save($this, $key, $config);
        
        // Process transformations if defined
        if (isset($config['transformations'])) {
            $this->transformUpload($upload, $config['transformations']);
        }
        
        return $upload;
    }
    
    /**
     * Attach multiple files to the model.
     *
     * @param string $key The form field name
     * @param array $config Configuration options
     * @return array Array of UploadModel instances
     */
    public function attachMultiple(string $key, array $config = [])
    {
        $service = $this->getUploadService();
        $uploads = $service->saveMultiple($this, $key, $config);
        
        // Process transformations if defined
        if (isset($config['transformations'])) {
            foreach ($uploads as $upload) {
                $this->transformUpload($upload, $config['transformations']);
            }
        }
        
        return $uploads;
    }
    
    /**
     * Attach a file from a URL.
     *
     * @param string $url The URL to download from
     * @param array $config Configuration options
     * @return \Lightpack\Uploads\UploadModel
     */
    public function attachFromUrl(string $url, array $config = [])
    {
        $service = $this->getUploadService();
        $upload = $service->saveFromUrl($this, $url, $config);
        
        // Process transformations if defined
        if (isset($config['transformations'])) {
            $this->transformUpload($upload, $config['transformations']);
        }
        
        return $upload;
    }
    
    /**
     * Detach an upload from the model.
     *
     * @param int $uploadId The upload ID to detach
     * @return bool
     */
    public function detach(int $uploadId)
    {
        $service = $this->getUploadService();
        return $service->delete($uploadId);
    }
    
    /**
     * Transform an upload according to the specified configurations.
     *
     * @param $upload
     * @param array $transformations
     * @return void
     */
    protected function transformUpload($upload, array $transformations): void
    {
        $job = new TransformJob($upload, $transformations);
        $job->handle(); // Direct execution for now, could be queued in the future
    }
    
    /**
     * Get the upload service instance.
     *
     * @return \Lightpack\Uploads\UploadService
     */
    protected function getUploadService()
    {
        return Container::getInstance()->resolve(UploadService::class);
    }
}
