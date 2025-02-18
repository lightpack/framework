<?php

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Validation\Rules\File\Base;
use Lightpack\Validation\Rules\File\Size;
use Lightpack\Validation\Rules\File\Type;
use Lightpack\Validation\Rules\File\Image;
use Lightpack\Validation\Rules\File\Extension;
use Lightpack\Validation\Rules\File\Multiple;
use PHPUnit\Framework\TestCase;

class FileValidationTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a temporary file for testing
        $this->tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($this->tempFile, 'test content');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up temporary file
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    private function createUploadedFile(array $attributes = []): array
    {
        return array_merge([
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024,
        ], $attributes);
    }

    public function testBaseFileValidation()
    {
        $rule = new Base();
        
        // Invalid array structure
        $this->assertFalse($rule(['name' => 'test.jpg']));
        
        // Upload error
        $file = $this->createUploadedFile(['error' => UPLOAD_ERR_INI_SIZE]);
        $this->assertFalse($rule($file));
        $this->assertStringContainsString('exceeds', $rule->getMessage());
    }

    public function testFileSizeValidation()
    {
        $rule = new Size('2K');
        
        // File too large
        $file = $this->createUploadedFile(['size' => 3 * 1024]);
        $this->assertFalse($rule($file));
        
        // File within limit
        $file = $this->createUploadedFile(['size' => 1024]);
        $this->assertTrue($rule($file));
    }

    public function testFileTypeValidation()
    {
        // Create a mock class extending Type to override getMimeType
        $rule = new class(['image/jpeg', 'image/png']) extends Type {
            protected function getMimeType(string $path): string 
            {
                return 'image/jpeg';
            }
        };
        
        // Test valid type
        $file = $this->createUploadedFile();
        $this->assertTrue($rule($file));
        
        // Test invalid type
        $rule = new class(['image/png']) extends Type {
            protected function getMimeType(string $path): string 
            {
                return 'image/jpeg';
            }
        };
        $this->assertFalse($rule($file));
    }

    public function testImageValidation()
    {
        // Create a mock class extending Image to override detection methods
        $rule = new class([
            'min_width' => 100,
            'max_width' => 1000,
            'min_height' => 100,
            'max_height' => 1000,
        ]) extends Image {
            protected function isImage(string $path): bool 
            {
                return true;
            }
            
            protected function getDimensions(string $path): array 
            {
                return ['width' => 800, 'height' => 600];
            }
        };
        
        // Test valid image
        $file = $this->createUploadedFile();
        $this->assertTrue($rule($file));
        
        // Test invalid dimensions
        $rule = new class([
            'min_width' => 1000,
            'max_width' => 2000,
        ]) extends Image {
            protected function isImage(string $path): bool 
            {
                return true;
            }
            
            protected function getDimensions(string $path): array 
            {
                return ['width' => 800, 'height' => 600];
            }
        };
        $this->assertFalse($rule($file));
    }

    public function testFileExtensionValidation()
    {
        $rule = new Extension(['jpg', 'png']);
        
        // Valid extension
        $file = $this->createUploadedFile(['name' => 'test.jpg']);
        $this->assertTrue($rule($file));
        
        // Invalid extension
        $file = $this->createUploadedFile(['name' => 'test.gif']);
        $this->assertFalse($rule($file));
    }

    public function testMultipleFileValidation()
    {
        $rule = new Multiple(1, 3);
        
        // Single file
        $file = $this->createUploadedFile();
        $this->assertTrue($rule($file));
        
        // Multiple files within limit
        $files = [
            'name' => ['test1.jpg', 'test2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [$this->tempFile, $this->tempFile],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [1024, 1024],
        ];
        $this->assertTrue($rule($files));
        
        // Too many files
        $files['name'][] = 'test3.jpg';
        $files['name'][] = 'test4.jpg';
        $this->assertFalse($rule($files));
    }
}
