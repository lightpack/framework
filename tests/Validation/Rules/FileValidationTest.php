<?php

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Validation\Rules\File\FileRule;
use Lightpack\Validation\Rules\File\FileSizeRule;
use Lightpack\Validation\Rules\File\FileTypeRule;
use Lightpack\Validation\Rules\File\ImageRule;
use Lightpack\Validation\Rules\File\FileExtensionRule;
use Lightpack\Validation\Rules\File\MultipleFileRule;
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

    private function createTestImage(string $path, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        imagejpeg($image, $path);
        imagedestroy($image);
    }

    private function createTestPdf(string $path): void
    {
        file_put_contents($path, '%PDF-1.4
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj
3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj
xref
0 4
0000000000 65535 f
0000000009 00000 n
0000000056 00000 n
0000000111 00000 n
trailer<</Size 4/Root 1 0 R>>
startxref
190
%%EOF');
    }

    public function setUpFixtures(): void
    {
        // Create test fixtures directory
        $fixturesDir = __DIR__ . '/fixtures';
        if (!is_dir($fixturesDir)) {
            mkdir($fixturesDir);
        }

        // Create test images
        $this->createTestImage($fixturesDir . '/800x600.jpg', 800, 600);
        $this->createTestImage($fixturesDir . '/1024x768.jpg', 1024, 768);
        $this->createTestImage($fixturesDir . '/2048x1536.jpg', 2048, 1536);

        // Create test PDF
        $this->createTestPdf($fixturesDir . '/test.pdf');

        // Create test GIF (just a text file with .gif extension)
        file_put_contents($fixturesDir . '/test.gif', 'GIF89a');

        // Create test DOC (just a text file with .doc extension)
        file_put_contents($fixturesDir . '/test.doc', 'MS-WORD');
    }

    public function tearDownFixtures(): void
    {
        // Clean up test files
        $fixturesDir = __DIR__ . '/fixtures';
        foreach (glob($fixturesDir . '/*') as $file) {
            unlink($file);
        }
        rmdir($fixturesDir);
    }

    public function testBaseFileValidation()
    {
        $rule = new FileRule();
        
        // Invalid array structure
        $this->assertFalse($rule(['name' => 'test.jpg']));
        
        // Upload error
        $file = $this->createUploadedFile(['error' => UPLOAD_ERR_INI_SIZE]);
        $this->assertFalse($rule($file));
        $this->assertStringContainsString('exceeds', $rule->getMessage());
    }

    public function testFileSizeValidation()
    {
        $validator = new \Lightpack\Validation\Validator();
        
        // Test case-insensitive size units
        $validator->field('file')
            ->fileSize('2m')  // lowercase
            ->fileSize('500K') // uppercase
            ->fileSize('1g');  // lowercase
        
        $file = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => __DIR__ . '/fixtures/test.txt',
            'error' => 0,
            'size' => 1024 * 1024, // 1M
        ];

        $validator->setInput(['file' => $file]);
        $this->assertTrue($validator->validate()->passes());

        $rule = new FileSizeRule('2KB');
        
        // File too large
        $file = $this->createUploadedFile(['size' => 3 * 1024]);
        $this->assertFalse($rule($file));
        
        // File within limit
        $file = $this->createUploadedFile(['size' => 1024]);
        $this->assertTrue($rule($file));
    }

    public function testFileTypeValidation()
    {
        // Create a mock class extending FileTypeRule to override getMimeType
        $rule = new class(['image/jpeg', 'image/png']) extends FileTypeRule {
            protected function getMimeType(string $path): string 
            {
                return 'image/jpeg';
            }
        };
        
        // Test valid type
        $file = $this->createUploadedFile();
        $this->assertTrue($rule($file));
        
        // Test invalid type
        $rule = new class(['image/png']) extends FileTypeRule {
            protected function getMimeType(string $path): string 
            {
                return 'image/jpeg';
            }
        };
        $this->assertFalse($rule($file));
    }

    public function testImageValidation()
    {
        // Create a mock class extending ImageRule to override detection methods
        $rule = new class([
            'min_width' => 100,
            'max_width' => 1000,
            'min_height' => 100,
            'max_height' => 1000,
        ]) extends ImageRule {
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
        ]) extends ImageRule {
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
        $rule = new FileExtensionRule(['jpg', 'png']);
        
        // Valid extension
        $file = $this->createUploadedFile(['name' => 'test.jpg']);
        $this->assertTrue($rule($file));
        
        // Invalid extension
        $file = $this->createUploadedFile(['name' => 'test.gif']);
        $this->assertFalse($rule($file));
    }

    public function testMultipleFileValidation()
    {
        $rule = new MultipleFileRule(1, 3);
        
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
        $files = [
            'name' => ['test1.jpg', 'test2.jpg', 'test3.jpg', 'test4.jpg'],
            'type' => ['image/jpeg', 'image/jpeg', 'image/jpeg', 'image/jpeg'],
            'tmp_name' => [$this->tempFile, $this->tempFile, $this->tempFile, $this->tempFile],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [1024, 1024, 1024, 1024],
        ];
        $this->assertFalse($rule($files));
    }

    public function testComplexMultiFileValidation()
    {
        $this->setUpFixtures();

        $validator = new \Lightpack\Validation\Validator();
        $validator
            // Optional technical specs (PDF)
            ->field('tech_specs')->fileType('application/pdf')->fileSize('5M')
            // Required product images (2-5 images)
            ->field('product_images')->required()->multipleFiles(2, 5)->fileSize('2M')->fileType(['image/jpeg', 'image/png'])->image([
                'min_width' => 800,
                'max_width' => 2048,
                'min_height' => 600,
                'max_height' => 2048
            ]);

        $validFiles = [
            'product_images' => [
                'name' => ['product1.jpg', 'product2.jpg', 'product3.jpg'],
                'type' => ['image/jpeg', 'image/jpeg', 'image/jpeg'],
                'tmp_name' => [
                    __DIR__ . '/fixtures/800x600.jpg',
                    __DIR__ . '/fixtures/1024x768.jpg',
                    __DIR__ . '/fixtures/2048x1536.jpg'
                ],
                'error' => [0, 0, 0],
                'size' => [500 * 1024, 800 * 1024, 1000 * 1024]
            ],
            'tech_specs' => [
                'name' => 'specs.pdf',
                'type' => 'application/pdf',
                'tmp_name' => __DIR__ . '/fixtures/test.pdf',
                'error' => 0,
                'size' => 1024 * 1024
            ]
        ];

        $validator->setInput($validFiles);
        $this->assertTrue($validator->validate()->passes());

        $this->tearDownFixtures();
    }
}
