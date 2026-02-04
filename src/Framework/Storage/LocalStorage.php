<?php

namespace Lightpack\Storage;

use Lightpack\Exceptions\FileUploadException;
use Lightpack\File\File;

class LocalStorage extends File implements StorageInterface
{
    public function __construct(protected string $storageDir)
    {
        // ...
    }

    /**
     * Store an uploaded file using move_uploaded_file() for security
     * 
     * @throws FileUploadException on failure
     */
    public function store(string $source, string $destination): void
    {
        $destination = $this->storageDir . '/' . $destination;
        $this->ensureDirectoryChecks($destination);

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
     * @param bool $recursive Whether to include files in subdirectories
     * @return array An array of file paths within the directory
     */
    public function files(string $directory, bool $recursive = true): array
    {
        $directory = $this->storageDir . '/' . trim($directory, '/');
        
        if (!is_dir($directory)) {
            return [];
        }
        
        $files = [];
        
        if ($recursive) {
            // Use RecursiveIteratorIterator to get all files recursively
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isDir()) {
                    $files[] = $fileInfo->getPathname();
                }
            }
        } else {
            // Only get files in the current directory
            $dir = new \DirectoryIterator($directory);
            
            foreach ($dir as $fileInfo) {
                if (!$fileInfo->isDot() && !$fileInfo->isDir()) {
                    $files[] = $directory . '/' . $fileInfo->getFilename();
                }
            }
        }
        
        return $files;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $path): bool
    {
        if (str_starts_with($path, $this->storageDir)) {
            return parent::exists($path);
        }

        return parent::exists($this->storageDir . '/' . ltrim($path, '/'));
    }

    /**
     * @inhertiDoc
     */
    public function read(string $path): ?string
    {
        return parent::read(
            $this->storageDir . '/' . trim($path, '/')
        );
    }

    /**
     * @inheritDoc
     */
    public function write(string $path, string $contents, $flags = LOCK_EX): bool
    {
        $path = $this->storageDir . '/' . trim($path, '/');

        return parent::write($path, $contents, $flags);
    }

     /**
     * @inheritDoc
     */
    public function removeDir(string $directory, bool $delete = true): void
    {
        $directory = $this->storageDir . '/' . trim($directory, '/');

        parent::removeDir($directory);
    }

    private function ensureDirectoryChecks(string $destination)
    {
        // Remove the end filename portion to get the target directory
        $targetDir = dirname($destination);

        if (is_dir($targetDir)) {
            if (!is_writable($targetDir)) {
                throw new FileUploadException('Storage directory does not have sufficient write permission: ' . $targetDir);
            }
        } elseif (!mkdir($targetDir, 0777, true)) {
            throw new FileUploadException('Could not create storage directory: ' . $targetDir);
        }
    }
}
