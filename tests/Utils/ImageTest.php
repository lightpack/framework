<?php

namespace Lightpack\Utils;

use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    private string $fixturesDir;
    private string $outputDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->fixturesDir =    __DIR__ . '/fixtures';
        $this->outputDir = $this->fixturesDir . '/output';
        
        // Ensure directories exist with proper permissions
        foreach ([$this->fixturesDir, $this->outputDir] as $dir) {
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0777, true)) {
                    throw new \RuntimeException("Failed to create directory: $dir");
                }
                chmod($dir, 0777); // Ensure directory is writable
            }
        }

        // Create test images
        $this->createTestImages();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up output directory
        if (is_dir($this->outputDir)) {
            $files = glob($this->outputDir . '/*.*') ?: [];
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    private function createTestImages(): void
    {
        $testFiles = [
            $this->fixturesDir . '/test.jpg',
            $this->fixturesDir . '/test.png',
            $this->fixturesDir . '/test.webp'
        ];
        
        // Only create if none of the test files exist
        if (!file_exists($testFiles[0])) {
            $image = imagecreatetruecolor(100, 100);
            $bg = imagecolorallocate($image, 255, 255, 255);
            $text_color = imagecolorallocate($image, 0, 0, 0);
            imagefilledrectangle($image, 0, 0, 100, 100, $bg);
            imagestring($image, 5, 10, 40, "Test", $text_color);
            
            foreach ($testFiles as $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                switch ($ext) {
                    case 'jpg':
                        imagejpeg($image, $file, 90);
                        break;
                    case 'png':
                        imagepng($image, $file, 6);
                        break;
                    case 'webp':
                        imagewebp($image, $file, 80);
                        break;
                }
                chmod($file, 0666); // Ensure files are readable/writable
            }
            
            imagedestroy($image);
        }
    }

    public function testConstructorThrowsExceptionForInvalidFile()
    {
        $this->expectException(\Exception::class);
        new Image('nonexistent.jpg');
    }

    public function testLoadImageFromUrl()
    {
        $this->markTestSkipped('URL test requires network access');
        
        // to test URL functionality, uncomment and use a reliable test image URL
        $testImageUrl = '';
        $image = new Image($testImageUrl);
        $outputPath = $this->outputDir . '/from_url.jpg';
        $image->save($outputPath);
        $this->assertFileExists($outputPath);
    }

    /**
     * @dataProvider imageTypeProvider
     */
    public function testLoadAndSaveImage(string $inputFile, string $outputFile)
    {
        $image = new Image($this->fixturesDir . '/' . $inputFile);
        $outputPath = $this->outputDir . '/' . $outputFile;
        
        $image->save($outputPath);
        
        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath), 'Output file is empty');
        
        $imageInfo = @getimagesize($outputPath);
        $this->assertNotFalse($imageInfo, 'Invalid image file created');
        
        $this->assertEquals(100, $imageInfo[0]);
        $this->assertEquals(100, $imageInfo[1]);
    }

    public function testResizeImage()
    {
        $image = new Image($this->fixturesDir . '/test.jpg');
        $outputPath = $this->outputDir . '/resized.jpg';
        
        $image->resize(50, 50);
        $image->save($outputPath);
        
        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath), 'Output file is empty');
        
        $imageInfo = getimagesize($outputPath);
        $this->assertEquals(50, $imageInfo[0]);
        $this->assertEquals(50, $imageInfo[1]);
    }

    public function testResizePreservesAspectRatio()
    {
        $image = new Image($this->fixturesDir . '/test.jpg');
        $outputPath = $this->outputDir . '/aspect_ratio.jpg';
        
        $image->resize(50, 0);
        $image->save($outputPath);
        
        $this->assertFileExists($outputPath);
        $imageInfo = getimagesize($outputPath);
        $this->assertEquals(50, $imageInfo[0]);
        $this->assertEquals(50, $imageInfo[1]);
    }

    public function testSaveWithQuality()
    {
        $sourceImage = $this->fixturesDir . '/test.jpg';
        $highQualityPath = $this->outputDir . '/high_quality.jpg';
        $lowQualityPath = $this->outputDir . '/low_quality.jpg';
        
        // Save high quality
        $image1 = new Image($sourceImage);
        $image1->save($highQualityPath, 100);
        $this->assertFileExists($highQualityPath, 'High quality file was not created');
        $highQualitySize = filesize($highQualityPath);
        
        // Save low quality with a new Image instance
        $image2 = new Image($sourceImage);
        $image2->save($lowQualityPath, 10);
        $this->assertFileExists($lowQualityPath, 'Low quality file was not created');
        $lowQualitySize = filesize($lowQualityPath);
        
        // Verify file sizes
        $this->assertGreaterThan(0, $highQualitySize, 'High quality file is empty');
        $this->assertGreaterThan(0, $lowQualitySize, 'Low quality file is empty');
        $this->assertGreaterThan($lowQualitySize, $highQualitySize, 
            sprintf('High quality file (%d bytes) should be larger than low quality file (%d bytes)',
                $highQualitySize, $lowQualitySize)
        );
    }

    public function testWebpSupport()
    {
        $image = new Image($this->fixturesDir . '/test.webp');
        $outputPath = $this->outputDir . '/converted.webp';
        
        $image->save($outputPath);
        
        $this->assertFileExists($outputPath);
        $this->assertGreaterThan(0, filesize($outputPath), 'Output file is empty');
        
        $imageInfo = getimagesize($outputPath);
        $this->assertEquals('image/webp', $imageInfo['mime']);
    }

    /**
     * @test
     */
    public function testMethodChaining()
    {
        $sourceImage = $this->fixturesDir . '/test.jpg';
        $outputPath = $this->outputDir . '/chained.jpg';
        $outputPath2 = $this->outputDir . '/chained2.jpg';
        
        // Test chaining before save
        $image = new Image($sourceImage);
        $image->resize(800, 600)
              ->save($outputPath, 90);
                       
        $this->assertFileExists($outputPath);
        list($width1, $height1) = getimagesize($outputPath);
        $this->assertEquals(800, $width1);
        $this->assertEquals(600, $height1);
        
        // Test multiple operations with new instances
        $image2 = new Image($sourceImage);
        $image2->resize(400, 300)
               ->save($outputPath2);
                         
        $this->assertFileExists($outputPath2);
        list($width2, $height2) = getimagesize($outputPath2);
        $this->assertEquals(400, $width2);
        $this->assertEquals(300, $height2);
        
        // Test error handling
        $image3 = new Image($sourceImage);
        try {
            $image3->resize(800, 600)
                  ->save('/nonexistent/path/test.jpg');
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Directory does not exist', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function testAvatarGenerationWithAllSizes()
    {
        $sourceImage = $this->fixturesDir . '/test.jpg';
        $image = new Image($sourceImage);
        
        // Test all sizes
        $paths = $image->avatar($this->outputDir . '/user123');
        
        $this->assertCount(3, $paths);
        $this->assertStringEndsWith('_avatar_small.webp', $paths['small']);
        $this->assertStringEndsWith('_avatar_medium.webp', $paths['medium']);
        $this->assertStringEndsWith('_avatar_large.webp', $paths['large']);
        
        $this->assertFileExists($paths['small']);
        $this->assertFileExists($paths['medium']);
        $this->assertFileExists($paths['large']);
        
        // Verify dimensions
        list($width, $height) = getimagesize($paths['small']);
        $this->assertEquals(48, $width);
        $this->assertEquals(48, $height);
        
        list($width, $height) = getimagesize($paths['medium']);
        $this->assertEquals(96, $width);
        $this->assertEquals(96, $height);
        
        // Test specific sizes
        $paths = $image->avatar($this->outputDir . '/user456', ['small', 'medium']);
        $this->assertCount(2, $paths);
        $this->assertStringEndsWith('_avatar_small.webp', $paths['small']);
        $this->assertStringEndsWith('_avatar_medium.webp', $paths['medium']);
        
        // Test invalid size
        $this->expectException(\InvalidArgumentException::class);
        $image->avatar($this->outputDir . '/invalid', ['invalid']);
    }
    
    /**
     * @test
     */
    public function testThumbnailGenerationWithDefaultSizes()
    {
        $sourceImage = $this->fixturesDir . '/test.jpg';
        $image = new Image($sourceImage);
        
        // Test default sizes
        $paths = $image->thumbnail($this->outputDir . '/photo123');
        
        $this->assertCount(3, $paths);
        $this->assertStringEndsWith('_thumb_small.jpg', $paths['small']);
        $this->assertStringEndsWith('_thumb_medium.jpg', $paths['medium']);
        
        $this->assertFileExists($paths['small']);
        $this->assertFileExists($paths['medium']);
        
        // Verify dimensions
        list($width, $height) = getimagesize($paths['small']);
        $this->assertEquals(300, $width);
        
        list($width, $height) = getimagesize($paths['medium']);
        $this->assertEquals(600, $width);
        
        // Test all sizes
        $paths = $image->thumbnail($this->outputDir . '/photo456', ['small', 'medium']);
        $this->assertCount(2, $paths);
        $this->assertStringEndsWith('_thumb_medium.jpg', $paths['medium']);
        
        list($width, $height) = getimagesize($paths['medium']);
        $this->assertEquals(600, $width);
        
        // Test invalid size
        $this->expectException(\InvalidArgumentException::class);
        $image->thumbnail($this->outputDir . '/invalid', ['invalid']);
    }

    public function imageTypeProvider(): array
    {
        return [
            'JPEG' => ['test.jpg', 'output.jpg'],
            'PNG' => ['test.png', 'output.png'],
            'WebP' => ['test.webp', 'output.webp'],
        ];
    }
}