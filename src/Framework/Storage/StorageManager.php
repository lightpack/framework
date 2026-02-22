<?php

namespace Lightpack\Storage;

use Lightpack\Manager\BaseManager;
use Lightpack\Container\Container;
use Lightpack\Storage\LocalStorage;
use Lightpack\Storage\S3Storage;
use Aws\S3\S3Client;

class StorageManager extends BaseManager
{
    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->registerBuiltInDrivers();
        $this->setDefaultFromConfig();
    }
    
    /**
     * Register built-in storage drivers
     */
    protected function registerBuiltInDrivers(): void
    {
        $this->register('local', function ($container) {
            return new LocalStorage(DIR_STORAGE);
        });
        
        $this->register('s3', function ($container) {
            return $this->createS3Driver($container->get('config')->get('s3'));
        });
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
    
    /**
     * Set default driver from config
     */
    protected function setDefaultFromConfig(): void
    {
        $default = get_env('STORAGE_DRIVER', 'local');
        $this->setDefaultDriver($default);
    }
    
    /**
     * Get storage driver instance
     */
    public function driver(?string $name = null): StorageInterface
    {
        $name = $name ?? $this->defaultDriver;
        return $this->resolve($name);
    }
    
    protected function getErrorMessage(string $name): string
    {
        return "Storage driver not found: {$name}";
    }
}
