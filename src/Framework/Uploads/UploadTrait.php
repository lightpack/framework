<?php

namespace Lightpack\Uploads;

use Lightpack\Container\Container;
use Lightpack\Database\Query\Query;

trait UploadTrait
{
    /**
     * Define the relationship between the model and its uploads.
     */
    public function uploads(?string $collection = null): Query
    {
        $query = $this->hasMany(UploadModel::class, 'model_id')
            ->where('model_type', $this->getTableName());
            
        if ($collection) {
            $query->where('collection', $collection);
        }
        
        return $query;
    }
    
    /**
     * Get the first upload for a specific collection.
     */
    public function firstUpload(?string $collection = null): ?UploadModel
    {
        return $this->uploads($collection)->one();
    }
    
    /**
     * Attach a file to the model.
     *
     * @param string $key The form field name
     * @param array $config Configuration options
     *                      - collection: The collection name (default: 'default')
     *                      - singleton: Whether to replace existing uploads in this collection (default: false)
     *                      - visibility: private or public
     *                      - transformations: Array of image transformations to apply
     */
    public function attach(string $key, array $config = []): UploadModel
    {
        $upload = $this->getUploadService()->save($this, $key, $config);
        
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
     *                      - collection: The collection name (default: 'default')
     *                      - visibility: private or public
     *                      - transformations: Array of image transformations to apply
     * @return array Array of UploadModel instances
     */
    public function attachMultiple(string $key, array $config = []): array
    {
        $uploads = $this->getUploadService()->saveMultiple($this, $key, $config);
        
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
     *                      - collection: The collection name (default: 'default')
     *                      - singleton: Whether to replace existing uploads in this collection (default: false)
     *                      - visibility: private or public
     *                      - transformations: Array of image transformations to apply
     */
    public function attachFromUrl(string $url, array $config = []): UploadModel
    {
        $upload = $this->getUploadService()->saveFromUrl($this, $url, $config);
        
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
     */
    public function detach(int $uploadId)
    {
        $upload = new UploadModel($uploadId);
        $this->getUploadService()->delete($upload);
    }
    
    /**
     * Transform an upload according to the specified configurations.
     */
    protected function transformUpload(UploadModel $upload, array $transformations)
    {
        (new TransformJob)->dispatch([
            'upload_id' => $upload->id, 
            'transformations' => $transformations
        ]);
    }
    
    /**
     * Get the upload service instance.
     */
    protected function getUploadService(): UploadHandler
    {
        return Container::getInstance()->resolve(UploadHandler::class);
    }
}
