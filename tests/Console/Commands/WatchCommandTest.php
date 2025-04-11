<?php

namespace Lightpack\Tests\Console\Commands;

use Lightpack\Console\Commands\WatchCommand;
use PHPUnit\Framework\TestCase;

class WatchCommandTest extends TestCase
{
    private $command;
    private $tempDir;

    protected function setUp(): void
    {
        $this->command = new WatchCommand();
        $this->tempDir = sys_get_temp_dir() . '/watch_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testOptionParsing()
    {
        $args = ['--path=src,tests', '--ext=php,md', '--run=phpunit'];
        $result = $this->invokeMethod($this->command, 'getOptionValue', [$args, '--path']);
        $this->assertEquals('src,tests', $result);

        $result = $this->invokeMethod($this->command, 'getOptionValue', [$args, '--ext']);
        $this->assertEquals('php,md', $result);

        $result = $this->invokeMethod($this->command, 'getOptionValue', [$args, '--run']);
        $this->assertEquals('phpunit', $result);

        // Should return null for non-existent option
        $result = $this->invokeMethod($this->command, 'getOptionValue', [$args, '--foo']);
        $this->assertNull($result);
    }

    public function testPathHandling()
    {
        // Create test files
        $file1 = $this->tempDir . '/test1.php';
        file_put_contents($file1, 'test1');

        // Test path parsing
        $this->invokeMethod($this->command, 'addPaths', [$this->tempDir]);
        $paths = $this->getPrivateProperty($this->command, 'paths');
        
        $this->assertCount(1, $paths);
        $this->assertContains($this->tempDir, $paths);
    }

    public function testExtensionFiltering()
    {
        // Create test files
        $file1 = $this->tempDir . '/test1.php';
        $file2 = $this->tempDir . '/test2.txt';
        file_put_contents($file1, 'test1');
        file_put_contents($file2, 'test2');

        // Set extensions to watch
        $this->setPrivateProperty($this->command, 'extensions', ['php']);
        
        // Add paths and get initial hashes
        $this->invokeMethod($this->command, 'addPaths', [$this->tempDir]);
        $this->invokeMethod($this->command, 'updateFileHashes');
        
        $hashes = $this->getPrivateProperty($this->command, 'fileHashes');
        
        // Should only track .php files
        $this->assertCount(1, $hashes);
        $hasPhpFile = false;
        $hasTxtFile = false;
        
        foreach ($hashes as $path => $hash) {
            if (str_ends_with($path, '.php')) $hasPhpFile = true;
            if (str_ends_with($path, '.txt')) $hasTxtFile = true;
        }
        
        $this->assertTrue($hasPhpFile, 'Should track .php files');
        $this->assertFalse($hasTxtFile, 'Should not track .txt files');
    }

    private function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    private function getPrivateProperty($object, string $propertyName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    private function setPrivateProperty($object, string $propertyName, $value)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    private function removeDirectory(string $dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
