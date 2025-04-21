<?php

namespace Lightpack\Storage;

use Lightpack\Exceptions\FileUploadException;
use Lightpack\File\File;

class LocalStorage extends File implements Storage
{
    /**
     * Store an uploaded file using move_uploaded_file() for security
     * 
     * @throws FileUploadException on failure
     */
    public function store(string $source, string $destination): void
    {
        // For test purposes.
        $success = isset($_SERVER['X_LIGHTPACK_TEST_UPLOAD']) 
            ? copy($source, $destination)
            : move_uploaded_file($source, $destination);

        if (!$success) {
            throw new FileUploadException('Could not upload the file.');
        }
    }
    
    /**
     * Get a URL for accessing the file
     * 
     * For local storage, this assumes files in storage/uploads/public are
     * accessible via /uploads in the web root (via symlink)
     * 
     * @param string $path The path to the file
     * @param int $expiration Expiration time in seconds (ignored for local storage)
     * @return string The URL to access the file
     */
    public function url(string $path, int $expiration = 3600): string
    {
        // If path starts with 'uploads/public', make it accessible via /uploads
        if (strpos($path, 'uploads/public/') === 0) {
            return '/uploads/' . substr($path, strlen('uploads/public/'));
        }
        
        // For other paths, return a route to a controller that can serve the file
        // with proper access control
        return '/files/serve?path=' . urlencode($path);
    }
    
    /**
     * List all files in a directory
     * 
     * @param string $directory The directory path to list files from
     * @return array An array of file paths within the directory
     */
    public function files(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        
        $files = [];
        $dir = new \DirectoryIterator($directory);
        
        foreach ($dir as $fileInfo) {
            if (!$fileInfo->isDot() && !$fileInfo->isDir()) {
                $files[] = $directory . '/' . $fileInfo->getFilename();
            }
        }
        
        return $files;
    }
}
