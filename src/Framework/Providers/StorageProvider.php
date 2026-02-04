<?php

namespace Lightpack\Providers;

use Lightpack\Container\Container;
use Lightpack\Storage\LocalStorage;
use Lightpack\Storage\S3Storage;
use Lightpack\Storage\StorageInterface;
use Aws\S3\S3Client;

class StorageProvider implements ProviderInterface
{
    public function register(Container $container): void
    {
        $container->register('storage', function($container) {
            $config = $container->get('config');
            $driver = get_env('STORAGE_DRIVER') ?? 'local';
            
            return match ($driver) {
                's3' => $this->createS3Driver($config->get('s3')),
                default => new LocalStorage(DIR_STORAGE),
            };
        });

        $container->alias(StorageInterface::class, 'storage');
    }
    
    /**
     * Create an S3 storage driver instance
     */
    protected function createS3Driver(array $config): S3Storage
    {
        // Ensure required S3 config values are not empty
        if (
            empty($config['key']) || empty($config['secret']) || 
            empty($config['region']) || empty($config['bucket'])
        ) {
            throw new \InvalidArgumentException(
                'S3 storage driver requires non-empty key, secret, region, and bucket configuration.'
            );
        }
        
        $s3Config = [
            'version' => 'latest',
            'region' => $config['region'],
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
        ];
        
        // Add optional endpoint for S3-compatible services
        if (!empty($config['endpoint'])) {
            $s3Config['endpoint'] = $config['endpoint'];
        }
        
        // Add optional path style endpoint setting
        if (isset($config['use_path_style_endpoint'])) {
            $s3Config['use_path_style_endpoint'] = $config['use_path_style_endpoint'];
        }
        
        try {
            $client = new S3Client($s3Config);
            
            return new S3Storage(
                $client, 
                $config['bucket'],
                $config['prefix'] ?? ''
            );
        } catch (\Exception $e) {
            throw new \RuntimeException(
                'Failed to initialize S3 storage: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
