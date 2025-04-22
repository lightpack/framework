<?php

namespace Lightpack\Storage;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Lightpack\Exceptions\FileUploadException;

class S3Storage implements Storage
{
    /**
     * AWS S3 client instance
     */
    protected S3Client $client;

    /**
     * S3 bucket name
     */
    protected string $bucket;

    /**
     * Optional base path/prefix for all operations
     */
    protected string $basePath = '';

    /**
     * Create a new S3 storage instance
     */
    public function __construct(S3Client $client, string $bucket, string $basePath = '')
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->basePath = $this->normalizePath($basePath);
    }

    /**
     * Read contents from a file
     */
    public function read(string $path): ?string
    {
        $path = $this->getFullPath($path);

        try {
            $result = $this->client->getObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return (string) $result['Body'];
        } catch (S3Exception $e) {
            return null;
        }
    }

    /**
     * Write contents to a file
     */
    public function write(string $path, string $contents): bool
    {
        $path = $this->getFullPath($path);

        try {
            $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
                'Body' => $contents,
                // 'ACL' => 'private',
            ]);

            return true;
        } catch (S3Exception $e) {
            return false;
        }
    }

    /**
     * Delete a file
     */
    public function delete(string $path): bool
    {
        $path = $this->getFullPath($path);

        if (!$this->exists($path)) {
            return false;
        }

        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $path,
            ]);

            return true;
        } catch (S3Exception $e) {
            return false;
        }
    }

    /**
     * Check if a file exists
     */
    public function exists(string $path): bool
    {
        $path = $this->getFullPath($path);

        try {
            return $this->client->doesObjectExist($this->bucket, $path);
        } catch (S3Exception $e) {
            return false;
        }
    }

    /**
     * Copy a file from source to destination
     */
    public function copy(string $source, string $destination): bool
    {
        $source = $this->getFullPath($source);
        $destination = $this->getFullPath($destination);

        if (!$this->exists($source)) {
            return false;
        }

        try {
            $this->client->copyObject([
                'Bucket' => $this->bucket,
                'CopySource' => "{$this->bucket}/{$source}",
                'Key' => $destination,
                // 'ACL' => 'private',
            ]);

            return true;
        } catch (S3Exception $e) {
            return false;
        }
    }

    /**
     * Move a file from source to destination
     */
    public function move(string $source, string $destination): bool
    {
        if ($this->copy($source, $destination)) {
            return $this->delete($source);
        }

        return false;
    }

    /**
     * Store an uploaded file
     * 
     * @throws FileUploadException on failure
     */
    public function store(string $source, string $destination): void
    {
        $destination = $this->getFullPath($destination);
        
        // Determine if this is a public file based on the path
        $isPublic = strpos($destination, 'uploads/public/') === 0;

        try {
            // Read the uploaded file
            $contents = file_get_contents($source);
            
            if ($contents === false) {
                throw new FileUploadException('Could not read the uploaded file.');
            }

            // Upload to S3
            $result = $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $destination,
                'Body' => $contents,
                // 'ACL' => $isPublic ? 'public-read' : 'private',
            ]);

            if (!$result) {
                throw new FileUploadException('Could not upload the file to S3.');
            }
        } catch (S3Exception $e) {
            throw new FileUploadException('S3 error: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new FileUploadException('Error uploading file: ' . $e->getMessage());
        }
    }

    /**
     * Get a temporary URL for a file
     */
    public function url(string $path, int $expiration = 3600): string
    {
        $path = $this->getFullPath($path);
        
        $command = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);

        $request = $this->client->createPresignedRequest($command, "+{$expiration} seconds");

        return (string) $request->getUri();
    }

    /**
     * Get the full path including any base path
     */
    protected function getFullPath(string $path): string
    {
        $path = $this->normalizePath($path);
        
        if (empty($this->basePath)) {
            return $path;
        }

        return $this->basePath . '/' . $path;
    }

    /**
     * Normalize a path by removing leading/trailing slashes
     */
    protected function normalizePath(string $path): string
    {
        return trim($path, '/');
    }
    
    /**
     * Get the underlying S3Client instance
     * 
     * This provides access to S3-specific functionality not covered by the Storage interface.
     * 
     * Example:
     * ```php
     * // When you need S3-specific functionality
     * if ($storage instanceof S3Storage) {
     *     $s3Client = $storage->getClient();
     *     
     *     // Now use any S3Client method
     *     $presignedPost = $s3Client->createPresignedPost([
     *         'Bucket' => 'your-bucket',
     *         'Key' => 'uploads/user-file.jpg',
     *     ]);
     * }
     * ```
     * 
     * @return S3Client The AWS S3 client instance
     */
    public function getClient(): S3Client
    {
        return $this->client;
    }
    
    /**
     * Get the S3 bucket name
     * 
     * @return string The bucket name
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }

    /**
     * List all files in a directory
     * 
     * @param string $directory The directory path to list files from
     * @return array An array of file paths within the directory
     */
    public function files(string $directory): array
    {
        try {
            // Ensure directory ends with a trailing slash if not empty
            if (!empty($directory) && substr($directory, -1) !== '/') {
                $directory .= '/';
            }
            
            $result = $this->client->listObjects([
                'Bucket' => $this->bucket,
                'Prefix' => $directory,
                'Delimiter' => '/',
            ]);
            
            $files = [];
            
            // Get the objects (files)
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    // Skip the directory itself
                    if ($object['Key'] !== $directory) {
                        $files[] = $object['Key'];
                    }
                }
            }
            
            return $files;
        } catch (S3Exception $e) {
            // Log error or handle exception
            return [];
        }
    }
}
