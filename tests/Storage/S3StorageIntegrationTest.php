<?php

namespace Lightpack\Tests\Storage;

use Aws\S3\S3Client;
use PHPUnit\Framework\TestCase;
use Lightpack\Storage\S3Storage;

/**
 * Integration tests for S3Storage with real AWS
 * 
 * To run this test:
 * 1. Set environment variables:
 *    - AWS_ACCESS_KEY
 *    - AWS_SECRET_KEY
 *    - AWS_BUCKET
 *    - AWS_REGION (optional, defaults to us-east-1)
 * 2. Run: vendor/bin/phpunit --filter S3StorageIntegrationTest
 * 
 * Note: This test is marked as skipped by default unless AWS credentials are provided
 */
class S3StorageIntegrationTest extends TestCase
{
    private ?S3Storage $storage = null;
    private string $testDir;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip tests if AWS credentials are not set
        $key = getenv('AWS_ACCESS_KEY');
        $secret = getenv('AWS_SECRET_KEY');
        $bucket = getenv('AWS_BUCKET');
        
        if (empty($key) || empty($secret) || empty($bucket)) {
            $this->markTestSkipped(
                'AWS credentials not found. Set AWS_ACCESS_KEY, AWS_SECRET_KEY, and AWS_BUCKET environment variables to run this test.'
            );
        }
        
        $region = getenv('AWS_REGION') ?: 'us-east-1';
        
        // Create S3 client
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
        ]);
        
        // Create storage instance
        $this->storage = new S3Storage($s3Client, $bucket);
        
        // Create a unique test directory to avoid conflicts
        $this->testDir = 'test-' . date('Ymd-His') . '-' . uniqid();
    }
    
    public function testWrite()
    {
        $testFile = $this->testDir . '/test-write.txt';
        $testContent = 'Test content ' . uniqid();
        
        $result = $this->storage->write($testFile, $testContent);
        $this->assertTrue($result);
        
        // Clean up
        $this->storage->delete($testFile);
    }
    
    public function testRead()
    {
        $testFile = $this->testDir . '/test-read.txt';
        $testContent = 'Test content ' . uniqid();
        
        // Write first
        $this->storage->write($testFile, $testContent);
        
        // Then read
        $content = $this->storage->read($testFile);
        $this->assertEquals($testContent, $content);
        
        // Clean up
        $this->storage->delete($testFile);
    }
    
    public function testExists()
    {
        $testFile = $this->testDir . '/test-exists.txt';
        $testContent = 'Test content ' . uniqid();
        
        // File should not exist initially
        $this->assertFalse($this->storage->exists($testFile));
        
        // Write file
        $this->storage->write($testFile, $testContent);
        
        // File should exist now
        $this->assertTrue($this->storage->exists($testFile));
        
        // Clean up
        $this->storage->delete($testFile);
    }
    
    public function testDelete()
    {
        $testFile = $this->testDir . '/test-delete.txt';
        $testContent = 'Test content ' . uniqid();
        
        // Write file
        $this->storage->write($testFile, $testContent);
        
        // File should exist
        $this->assertTrue($this->storage->exists($testFile));
        
        // Delete file
        $result = $this->storage->delete($testFile);
        $this->assertTrue($result);
        
        // File should not exist after deletion
        $this->assertFalse($this->storage->exists($testFile));
    }
    
    public function testCopy()
    {
        $sourceFile = $this->testDir . '/test-copy-source.txt';
        $destFile = $this->testDir . '/test-copy-dest.txt';
        $testContent = 'Test content ' . uniqid();
        
        // Write source file
        $this->storage->write($sourceFile, $testContent);
        
        // Copy file
        $result = $this->storage->copy($sourceFile, $destFile);
        $this->assertTrue($result);
        
        // Both files should exist
        $this->assertTrue($this->storage->exists($sourceFile));
        $this->assertTrue($this->storage->exists($destFile));
        
        // Content should be the same
        $this->assertEquals(
            $this->storage->read($sourceFile),
            $this->storage->read($destFile)
        );
        
        // Clean up
        $this->storage->delete($sourceFile);
        $this->storage->delete($destFile);
    }
    
    public function testMove()
    {
        $sourceFile = $this->testDir . '/test-move-source.txt';
        $destFile = $this->testDir . '/test-move-dest.txt';
        $testContent = 'Test content ' . uniqid();
        
        // Write source file
        $this->storage->write($sourceFile, $testContent);
        
        // Move file
        $result = $this->storage->move($sourceFile, $destFile);
        $this->assertTrue($result);
        
        // Source file should not exist
        $this->assertFalse($this->storage->exists($sourceFile));
        
        // Destination file should exist
        $this->assertTrue($this->storage->exists($destFile));
        
        // Content should be preserved
        $this->assertEquals($testContent, $this->storage->read($destFile));
        
        // Clean up
        $this->storage->delete($destFile);
    }
    
    public function testUrl()
    {
        $testFile = $this->testDir . '/test-url.txt';
        $testContent = 'Test content ' . uniqid();
        
        // Write file
        $this->storage->write($testFile, $testContent);
        
        // Generate URL
        $url = $this->storage->url($testFile, 3600);
        
        // URL should be a string
        $this->assertIsString($url);
        
        // URL should contain the bucket and file path
        $this->assertStringContainsString($this->storage->getBucket(), $url);
        
        // Check that URL contains the path (AWS S3 uses / not %2F in URLs)
        $this->assertStringContainsString($this->testDir . '/test-url.txt', $url);
        
        // Clean up
        $this->storage->delete($testFile);
    }
    
    public function testGetClient()
    {
        $client = $this->storage->getClient();
        $this->assertInstanceOf(S3Client::class, $client);
    }
    
    public function testGetBucket()
    {
        $bucket = $this->storage->getBucket();
        $this->assertIsString($bucket);
        $this->assertNotEmpty($bucket);
    }
}
