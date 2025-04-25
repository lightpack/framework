<?php

namespace Lightpack\Uploads;

use Lightpack\Http\Request;
use Lightpack\Container\Container;

/**
 * UploadService
 * 
 * Handles file uploads, storage, and database record creation.
 */
class UploadService
{
    /**
     * @var \Lightpack\Http\Request
     */
    protected $request;
    
    /**
     * @var \Lightpack\Uploads\UploadModel
     */
    protected $uploadModel;
    
    /**
     * @var \Lightpack\Storage\Storage
     */
    protected $storage;
    
    /**
     * Create a new upload service instance.
     *
     * @param \Lightpack\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->uploadModel = new UploadModel();
        $this->storage = Container::getInstance()->resolve('storage');
    }
    
    /**
     * Save a single file upload for a model.
     *
     * @param object $model The model to attach the upload to
     * @param string $key The form field name
     * @param array $config Configuration options
     */
    public function save($model, string $key, array $config = [])
    {
        // Get the uploaded file
        $file = $this->getUploadedFile($key);
        
        if (!$file) {
            throw new \RuntimeException("No file uploaded with key: {$key}");
        }
        
        // Check if this is a singleton upload (only one per collection)
        if (isset($config['singleton']) && $config['singleton']) {
            $collection = empty($config['collection']) ? 'default' : $config['collection'];
            $this->deleteAllUploadsForModel($model, $collection);
        }
        
        return $this->saveFile($model, $file, array_merge($config, ['key' => $key]));
    }
    
    /**
     * Save multiple file uploads for a model.
     *
     * @param object $model The model to attach the uploads to
     * @param string $key The form field name
     * @param array $config Configuration options
     */
    public function saveMultiple($model, string $key, array $config = [])
    {
        $files = $this->request->files($key);
        
        if (empty($files)) {
            throw new \RuntimeException("No files uploaded with key: {$key}");
        }
        
        $uploads = [];
        
        foreach ($files as $index => $file) {
            $uploads[] = $this->saveFile($model, $file, array_merge($config, ['key' => "{$key}_{$index}"]));
        }
        
        return $uploads;
    }
    
    /**
     * Save a file from a URL.
     *
     * @param object $model The model to attach the upload to
     * @param string $url The URL to download from
     * @param array $config Configuration options
     */
    public function saveFromUrl($model, string $url, array $config = [])
    {
        // Download the file
        $meta = $this->downloadFileFromUrl($url);
        
        // Get collection name
        $collection = empty($config['collection']) ? 'default' : $config['collection'];
        
        // Check if this is a singleton upload (only one per collection)
        if (isset($config['singleton']) && $config['singleton']) {
            $this->deleteAllUploadsForModel($model, $collection);
        }
        
        // Create the upload record
        $key = $config['key'] ?? basename($url);
        $upload = $this->createUploadEntry($model, $meta, $collection, $key);
        
        // Store the file
        $path = $upload->path;
        $filename = $meta['filename'];
        
        // Determine if this is a private file
        $isPrivate = !empty($config['private']);
        $visibility = $isPrivate ? 'private' : 'public';
        
        // Store the file in the appropriate location
        $this->storage->write("uploads/{$visibility}/{$path}/{$filename}", file_get_contents($meta['temp_filepath']));
        
        // Clean up temp file
        if (file_exists($meta['temp_filepath'])) {
            unlink($meta['temp_filepath']);
        }
        
        // Update the path in the upload record
        $upload->file_name = $filename;
        $upload->path = $path;
        $upload->is_private = $isPrivate;
        $upload->save();
        
        return $upload;
    }
    
    /**
     * Delete an upload by ID.
     *
     * @param int $uploadId
     */
    public function delete(int $uploadId)
    {
        /** @var UploadModel */
        $upload = $this->uploadModel->find($uploadId);
        
        if (!$upload) {
            return false;
        }
        
        // Delete the file and any transformations
        $path = $upload->getPath();
        $visibility = $upload->is_private ? 'private' : 'public';
        
        // Delete the original file
        $this->storage->delete($path);
        
        // Delete any transformed versions (look for files with prefixes)
        $files = $this->storage->files("uploads/{$visibility}/" . $upload->path);
        foreach ($files as $file) {
            $this->storage->delete($file);
        }
        
        // Delete the database record
        return $upload->delete();
    }
    
    /**
     * Delete all uploads for a model in a specific collection.
     *
     * @param object $model The model to delete uploads for
     * @param string $collection
     */
    public function deleteAllUploadsForModel($model, string $collection)
    {
        // Find all uploads for this model and collection
        $modelType = $model->getTableName();
        $modelId = $model->{$model->getPrimaryKey()};
        
        // Use the query builder to get uploads
        $uploads = UploadModel::query()
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->where('collection', $collection)
            ->all();
        
        if (empty($uploads)) {
            return true;
        }
        
        foreach ($uploads as $upload) {
            $this->delete($upload->id);
        }
        
        return true;
    }
    
    /**
     * Get an uploaded file from the request.
     *
     * @param string $key
     * @return \Lightpack\Http\UploadedFile|null
     */
    protected function getUploadedFile(string $key)
    {
        return $this->request->file($key);
    }
    
    /**
     * Get metadata for an uploaded file.
     *
     * @param \Lightpack\Http\UploadedFile $file
     * @return array
     */
    protected function getUploadedFileMeta($file): array
    {
        return [
            'name' => pathinfo($file->getName(), PATHINFO_FILENAME),
            'filename' => $file->getName(),
            'type' => $this->getFileType($file->getType()),
            'mime_type' => $file->getType(),
            'extension' => $file->getExtension(),
            'size' => $file->getSize(),
        ];
    }
    
    /**
     * Download a file from a URL.
     *
     * @param string $url
     * @return array
     */
    protected function downloadFileFromUrl(string $url): array
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_');
        
        // Download the file
        $fileContents = file_get_contents($url);
        file_put_contents($tempFile, $fileContents);
        
        // Get file info
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $mimeType = mime_content_type($tempFile);
        $size = filesize($tempFile);
        
        return [
            'name' => $name,
            'filename' => $filename,
            'type' => $this->getFileType($mimeType),
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => $size,
            'temp_filepath' => $tempFile,
        ];
    }
    
    /**
     * Create a new upload database entry.
     *
     * @param object $model The model to create an upload for
     * @param array $meta
     * @param string $collection
     * @param string $key
     */
    protected function createUploadEntry($model, array $meta, string $collection, string $key)
    {
        $upload = new UploadModel();
        
        $upload->model_type = $model->getTableName();
        $upload->model_id = $model->{$model->getPrimaryKey()};
        $upload->collection = $collection;
        $upload->name = $meta['name'];
        $upload->file_name = $meta['filename'];
        $upload->mime_type = $meta['mime_type'];
        $upload->extension = $meta['extension'];
        $upload->size = $meta['size'];
        
        // Set the path - this is required by the database schema
        $upload->path = "media/" . $model->{$model->getPrimaryKey()};
        
        $upload->save();
        
        return $upload;
    }
    
    /**
     * Internal method to save a file and create an upload record.
     *
     * @param object $model The model to attach the upload to
     * @param \Lightpack\Http\UploadedFile $file The uploaded file
     * @param array $config Configuration options
     * @return \Lightpack\Uploads\UploadModel
     */
    protected function saveFile($model, $file, array $config = [])
    {
        // Get collection name
        $collection = empty($config['collection']) ? 'default' : $config['collection'];
        
        // Get file metadata
        $meta = $this->getUploadedFileMeta($file);
        
        // Create the upload record
        $key = $config['key'] ?? 'default';
        $upload = $this->createUploadEntry($model, $meta, $collection, $key);
        
        // Store the file
        $path = $upload->path;
        
        // Check if this should be stored privately
        $isPrivate = !empty($config['private']);
        
        // Store the file in the appropriate location
        if ($isPrivate) {
            $storedPath = $file->storePrivate($path);
            $upload->is_private = 1;
        } else {
            $storedPath = $file->storePublic($path);
            $upload->is_private = 0;
        }
        
        // Update the path in the upload record
        $upload->file_name = basename($storedPath);
        $upload->path = $path;
        $upload->save();
        
        return $upload;
    }
    
    /**
     * Get the file type based on MIME type.
     *
     * @param string $mimeType
     * @return string
     */
    public function getFileType(string $mimeType): string
    {
        $types = [
            'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'],
            'video' => ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/webm'],
            'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp3'],
            'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'spreadsheet' => ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'presentation' => ['application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'archive' => ['application/zip', 'application/x-rar-compressed', 'application/x-tar', 'application/gzip', 'application/x-7z-compressed', 'application/x-bzip2'],
        ];
        
        foreach ($types as $type => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes)) {
                return $type;
            }
        }
        
        return 'other';
    }
}
