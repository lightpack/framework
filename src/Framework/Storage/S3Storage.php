<?php

namespace Lightpack\Storage;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\CloudFront\UrlSigner;
use Lightpack\Container\Container;
use Lightpack\Exceptions\FileUploadException;

class S3Storage implements StorageInterface
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
     * @inheritDoc
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
     * @inheritDoc
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
        
        // Check if CloudFront is configured and if this is a public file
        $config = Container::getInstance()->get('config');
        $config = $config->get('storage.s3.cloudfront') ?? [];
        $cloudfrontDomain = $config['domain'] ?? null;
        $isPublicFile = strpos($path, 'uploads/public/') === 0;
        
        // Use CloudFront for public files if configured
        if ($cloudfrontDomain && $isPublicFile) {
            return 'https://' . $cloudfrontDomain . '/' . $path;
        }
        
        // Use CloudFront signed URLs for private files if configured and key is available
        $cloudfrontKeyPairId = $config['key_pair_id'] ?? null;
        $cloudfrontPrivateKey = $config['private_key'] ?? null;
        
        if ($cloudfrontDomain && $cloudfrontKeyPairId && $cloudfrontPrivateKey && !$isPublicFile) {
            // Use the dedicated UrlSigner class for better performance and cleaner API
            if (class_exists(UrlSigner::class)) {
                $signer = new UrlSigner($cloudfrontKeyPairId, $cloudfrontPrivateKey);
                return $signer->getSignedUrl(
                    'https://' . $cloudfrontDomain . '/' . $path,
                    time() + $expiration
                );
            }
        }
        
        // Fallback to S3 presigned URL
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
     * @inheritDoc
     */
    public function files(string $directory, bool $recursive = true): array
    {
        try {
            // Ensure directory ends with a trailing slash if not empty
            if (!empty($directory) && substr($directory, -1) !== '/') {
                $directory .= '/';
            }
            
            $directory = $this->getFullPath($directory);
            
            $params = [
                'Bucket' => $this->bucket,
                'Prefix' => $directory,
            ];
            
            // Add delimiter for non-recursive listing (only current directory)
            if (!$recursive) {
                $params['Delimiter'] = '/';
            }
            
            $result = $this->client->listObjects($params);
            
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
            logger()->error($e->getMessage());
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function removeDir(string $directory, bool $delete = true): void
    {
        $files = $this->files($directory);

        foreach ($files as $file) {
            $this->delete($file);
        }
    }
}
