<?php

namespace Tests\Utils;

use PHPUnit\Framework\TestCase;
use Lightpack\Utils\Asset;

class AssetTest extends TestCase
{
    private string $publicPath;
    private Asset $asset;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->publicPath = __DIR__ . '/fixtures/public';
        
        // Create test directories
        mkdir($this->publicPath . '/css', 0777, true);
        mkdir($this->publicPath . '/js', 0777, true);
        mkdir($this->publicPath . '/img', 0777, true);
        mkdir($this->publicPath . '/fonts', 0777, true);
        
        // Create test files
        file_put_contents($this->publicPath . '/css/app.css', 'body { color: black; }');
        file_put_contents($this->publicPath . '/js/app.js', 'console.log("test");');
        file_put_contents($this->publicPath . '/img/logo.png', 'fake-image-content');
        
        $this->asset = new Asset($this->publicPath);
    }
    
    protected function tearDown(): void
    {
        // Clean up test files
        $this->deleteDirectory($this->publicPath);
        parent::tearDown();
    }
    
    private function deleteDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Basic Asset URL Generation Tests
     */
    public function testBasicAssetUrlGeneration(): void
    {
        $url = $this->asset->url('css/app.css');
        $this->assertStringContainsString('css/app.css?v=', $url);
    }

    public function testAssetUrlWithoutVersioning(): void
    {
        $url = $this->asset->url('css/app.css', false);
        $this->assertEquals('/css/app.css', $url);
    }

    public function testAssetUrlWithBaseUrl(): void
    {
        putenv('ASSET_URL=https://cdn.example.com');
        $asset = new Asset($this->publicPath);
        $url = $asset->url('css/app.css', false);
        $this->assertEquals('https://cdn.example.com/css/app.css', $url);
        putenv('ASSET_URL');
    }

    /**
     * Collection Loading Tests
     */
    public function testLoadCollectionWithCssAndJs(): void
    {
        $html = $this->asset->load([
            'css/app.css',
            'js/app.js'
        ]);
        
        // Should contain both CSS and JS tags
        $this->assertStringContainsString("<link rel='stylesheet'", $html);
        $this->assertStringContainsString("<script src='", $html);
        $this->assertStringContainsString("css/app.css", $html);
        $this->assertStringContainsString("js/app.js", $html);
    }

    public function testLoadCollectionWithOnlyCss(): void
    {
        $html = $this->asset->load([
            'css/app.css',
            'css/other.css'
        ]);

        // Should only contain CSS tags
        $this->assertEquals(2, substr_count($html, "<link rel='stylesheet'"));
        $this->assertStringNotContainsString("<script", $html);
    }

    public function testLoadCollectionWithOnlyJs(): void
    {
        $html = $this->asset->load([
            'js/app.js',
            'js/other.js'
        ]);

        // Should only contain JS tags
        $this->assertEquals(2, substr_count($html, "<script"));
        $this->assertStringNotContainsString("<link rel='stylesheet'", $html);
    }

    /**
     * CSS Helper Tests
     */
    public function testCssHelper(): void
    {
        $html = $this->asset->load('css/app.css');
        $this->assertStringContainsString("<link rel='stylesheet'", $html);
        $this->assertStringContainsString("href='", $html);
        $this->assertStringContainsString("css/app.css", $html);
    }

    public function testMultipleCssFiles(): void
    {
        $html = $this->asset->load(['css/app.css', 'css/other.css']);
        $this->assertEquals(2, substr_count($html, "<link rel='stylesheet'"));
    }

    /**
     * JavaScript Helper Tests
     */
    public function testJsHelper(): void
    {
        $html = $this->asset->load('js/app.js');
        $this->assertStringContainsString("<script src='", $html);
        $this->assertStringContainsString("js/app.js", $html);
        $this->assertStringContainsString("defer", $html);
    }

    public function testJsHelperWithAsync(): void
    {
        $html = $this->asset->load(['js/app.js' => 'async']);
        $this->assertStringContainsString("<script src='", $html);
        $this->assertStringContainsString("js/app.js", $html);
        $this->assertStringContainsString("async", $html);
    }

    public function testJsHelperWithoutDefer(): void
    {
        $html = $this->asset->load(['js/app.js' => null]);
        $this->assertStringNotContainsString("defer", $html);
    }

    public function testJsModuleScript(): void
    {
        $html = $this->asset->module('js/app.js');
        $this->assertStringContainsString('<script type="module"', $html);
        $this->assertStringContainsString("js/app.js", $html);
        $this->assertStringNotContainsString("async", $html);
    }

    public function testJsModuleScriptWithAsync(): void
    {
        $html = $this->asset->module(['js/app.js' => 'async']);
        $this->assertStringContainsString('<script type="module"', $html);
        $this->assertStringContainsString("js/app.js", $html);
        $this->assertStringContainsString("async", $html);
    }

    public function testMultipleJsModuleScripts(): void
    {
        $html = $this->asset->module([
            'js/app.js',
            'js/utils.js' => 'async',
            'js/vendor.js'
        ]);
        
        $this->assertEquals(3, substr_count($html, '<script type="module"'));
        $this->assertEquals(1, substr_count($html, 'async'));
        $this->assertStringContainsString("js/app.js", $html);
        $this->assertStringContainsString("js/utils.js", $html);
        $this->assertStringContainsString("js/vendor.js", $html);
    }

    /**
     * Image Helper Tests
     */
    public function testImgHelper(): void
    {
        $html = $this->asset->img('img/logo.png', ['alt' => 'Logo']);
        $this->assertStringContainsString("<img src='", $html);
        $this->assertStringContainsString("img/logo.png", $html);
        $this->assertStringContainsString("alt='Logo'", $html);
    }

    /**
     * Import Map Tests
     */
    public function testImportMapGeneration(): void
    {
        $html = $this->asset->importMap([
            'uikit' => 'js/uikit.js',
            'app' => 'js/app.js'
        ]);

        $this->assertStringContainsString('"imports":', $html);
        $this->assertStringContainsString('"uikit":', $html);
        $this->assertStringContainsString('"app":', $html);
        $this->assertStringContainsString('/js/uikit.js', $html);
        $this->assertStringContainsString('/js/app.js', $html);
    }

    public function testImportMapWithSingleImport(): void
    {
        $html = $this->asset->importMap([
            'app' => 'js/app.js'
        ]);

        $this->assertStringContainsString('"imports":', $html);
        $this->assertStringContainsString('"app":', $html);
        $this->assertStringContainsString('/js/app.js', $html);
    }

    /**
     * Version Manifest Tests
     */
    public function testVersionManifestGeneration(): void
    {
        $this->asset->generateVersions();
        
        $manifest = $this->publicPath . '/assets.json';
        $this->assertFileExists($manifest);
        
        $versions = json_decode(file_get_contents($manifest), true);
        $this->assertArrayHasKey('css/app.css', $versions);
        $this->assertArrayHasKey('js/app.js', $versions);
    }

    public function testVersionManifestReading(): void
    {
        $this->asset->generateVersions();
        
        // Clear internal versions cache
        $this->asset = new Asset($this->publicPath);
        
        $url = $this->asset->url('css/app.css');
        $this->assertStringContainsString('?v=', $url);
    }

    public function testVersionManifestOnlyTracksAllowedDirectories(): void
    {
        // Create a file outside tracked directories
        file_put_contents($this->publicPath . '/random.txt', 'test');
        
        $this->asset->generateVersions();
        $versions = json_decode(
            file_get_contents($this->publicPath . '/assets.json'),
            true
        );
        
        $this->assertArrayNotHasKey('random.txt', $versions);
    }
}
