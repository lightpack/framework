<?php

namespace Lightpack\Tests\File;

use Lightpack\File\File;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SplFileInfo;

class FileTest extends TestCase
{
    private File $file;
    private string $testDir;
    private string $testFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->file = new File();
        $this->testDir = __DIR__ . '/tmp';
        $this->testFile = $this->testDir . '/test.txt';
        
        // Create test directory
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up test files
        if (is_dir($this->testDir)) {
            $this->file->removeDir($this->testDir);
        }
    }

    public function testInfoReturnsNullForNonExistentFile()
    {
        $this->assertNull($this->file->info($this->testFile));
    }

    public function testInfoReturnsSplFileInfo()
    {
        file_put_contents($this->testFile, 'test');
        $info = $this->file->info($this->testFile);
        $this->assertInstanceOf(SplFileInfo::class, $info);
        $this->assertEquals('test.txt', $info->getFilename());
    }

    public function testExistsChecksFileExistence()
    {
        $this->assertFalse($this->file->exists($this->testFile));
        file_put_contents($this->testFile, 'test');
        $this->assertTrue($this->file->exists($this->testFile));
    }

    public function testIsDirChecksDirExistence()
    {
        $this->assertTrue($this->file->isDir($this->testDir));
        $this->assertFalse($this->file->isDir($this->testFile));
    }

    public function testReadReturnsNullForNonExistentFile()
    {
        $this->assertNull($this->file->read($this->testFile));
    }

    public function testReadReturnsFileContents()
    {
        file_put_contents($this->testFile, 'test content');
        $this->assertEquals('test content', $this->file->read($this->testFile));
    }

    public function testWriteCreatesFileWithContents()
    {
        $this->assertTrue($this->file->write($this->testFile, 'test content'));
        $this->assertEquals('test content', file_get_contents($this->testFile));
    }

    public function testWriteCreatesNestedDirectories()
    {
        $nestedFile = $this->testDir . '/nested/deep/test.txt';
        $this->assertTrue($this->file->write($nestedFile, 'nested content'));
        $this->assertTrue(is_dir($this->testDir . '/nested/deep'));
        $this->assertEquals('nested content', file_get_contents($nestedFile));
    }

    public function testDeleteRemovesFile()
    {
        file_put_contents($this->testFile, 'test');
        $this->assertTrue($this->file->delete($this->testFile));
        $this->assertFalse(file_exists($this->testFile));
    }

    public function testAppendAddsContentToFile()
    {
        $this->file->write($this->testFile, 'first');
        $this->file->append($this->testFile, ' second');
        $this->assertEquals('first second', file_get_contents($this->testFile));
    }

    public function testCopyDuplicatesFile()
    {
        $destFile = $this->testDir . '/copy.txt';
        $this->file->write($this->testFile, 'original');
        $this->assertTrue($this->file->copy($this->testFile, $destFile));
        $this->assertEquals('original', file_get_contents($destFile));
    }

    public function testMoveRelocatesFile()
    {
        $destFile = $this->testDir . '/moved.txt';
        $this->file->write($this->testFile, 'moving');
        $this->assertTrue($this->file->move($this->testFile, $destFile));
        $this->assertFalse(file_exists($this->testFile));
        $this->assertEquals('moving', file_get_contents($destFile));
    }

    public function testExtensionGetsFileExtension()
    {
        $this->assertEquals('txt', $this->file->extension($this->testFile));
        $this->assertEquals('php', $this->file->extension('test.php'));
    }

    public function testSizeGetsFileSize()
    {
        $content = str_repeat('a', 1024); // 1KB
        $this->file->write($this->testFile, $content);
        
        $this->assertEquals(1024, $this->file->size($this->testFile));
        $this->assertEquals('1KB', $this->file->size($this->testFile, true));
    }

    public function testModifiedGetsTimestamp()
    {
        $this->file->write($this->testFile, 'test');
        $this->assertIsInt($this->file->modified($this->testFile));
        $this->assertMatchesRegularExpression('/[A-Z][a-z]{2} \d{1,2}, \d{4}/', 
            $this->file->modified($this->testFile, true));
    }

    public function testMakeDirCreatesDirectory()
    {
        $newDir = $this->testDir . '/new';
        $this->assertTrue($this->file->makeDir($newDir));
        $this->assertTrue(is_dir($newDir));
    }

    public function testEmptyDirClearsDirectory()
    {
        $this->file->write($this->testFile, 'test');
        $this->file->emptyDir($this->testDir);
        $this->assertTrue(is_dir($this->testDir));
        $this->assertEmpty(glob($this->testDir . '/*'));
    }

    public function testCopyDirDuplicatesDirectory()
    {
        $destDir = $this->testDir . '_copy';
        $this->file->write($this->testFile, 'test');
        
        $this->assertTrue($this->file->copyDir($this->testDir, $destDir));
        $this->assertTrue(file_exists($destDir . '/test.txt'));
        
        // Clean up
        $this->file->removeDir($destDir);
    }

    public function testMoveDirRelocatesDirectory()
    {
        $destDir = $this->testDir . '_moved';
        $this->file->write($this->testFile, 'test');
        
        $this->assertTrue($this->file->moveDir($this->testDir, $destDir));
        $this->assertFalse(is_dir($this->testDir));
        $this->assertTrue(file_exists($destDir . '/test.txt'));
        
        // Clean up
        $this->file->removeDir($destDir);
    }

    public function testRecentGetsLatestFile()
    {
        $file1 = $this->testDir . '/old.txt';
        $file2 = $this->testDir . '/new.txt';
        
        $this->file->write($file1, 'old');
        sleep(1);
        $this->file->write($file2, 'new');
        
        $recent = $this->file->recent($this->testDir);
        $this->assertInstanceOf(SplFileInfo::class, $recent);
        $this->assertEquals('new.txt', $recent->getFilename());
    }

    public function testTraverseListsDirectoryContents()
    {
        $this->file->write($this->testFile, 'test');
        $files = $this->file->traverse($this->testDir);
        
        $this->assertIsArray($files);
        $this->assertArrayHasKey('test.txt', $files);
        $this->assertInstanceOf(SplFileInfo::class, $files['test.txt']);
    }
}
