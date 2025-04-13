<?php

namespace Lightpack\Utils;

class Asset
{
    /**
     * Base path for public assets
     */
    protected string $publicPath;

    /**
     * Base URL for assets (can be CDN)
     */
    protected ?string $baseUrl;

    /**
     * Modules to preload
     */
    protected array $preloadModules = [];

    /**
     * Version cache for assets
     */
    protected array $versions = [];

    /**
     * Path to version manifest
     */
    protected string $manifestPath;

    /**
     * Directories to track for versioning
     */
    protected array $trackDirs = [
        'css',    // Stylesheets
        'js',     // JavaScript
        'fonts',  // Web fonts
        'img'     // Images
    ];

    /**
     * Store preload links
     */
    protected array $preloadLinks = [];

    public function __construct(?string $publicPath = null)
    {
        $this->publicPath = $publicPath ?? DIR_ROOT . '/public';
        $this->baseUrl = get_env('ASSET_URL') ?? get_env('APP_URL');
        $this->manifestPath = $this->publicPath . '/assets.json';
    }

    /**
     * Get URL for an asset with optional versioning
     * 
     * @param string $path Path to the asset relative to public directory
     * @param bool $version Enable/disable versioning
     * @return string Generated asset URL
     */
    public function url(string $path, bool $version = true): string
    {
        $path = trim($path, '/ ');
        
        if ($version) {
            $versionStamp = $this->getVersion($path);
            if ($versionStamp) {
                $path .= '?v=' . $versionStamp;
            }
        }

        return ($this->baseUrl ? rtrim($this->baseUrl, '/') : '') . '/' . $path;
    }

    /**
     * Get version for an asset
     */
    protected function getVersion(string $path): ?string
    {
        $realPath = $this->publicPath . '/' . $path;
        if (!file_exists($realPath)) {
            return null;
        }

        // Load versions if not loaded
        if (empty($this->versions)) {
            $this->loadVersions();
        }

        // Use manifest version if available, otherwise use file modification time
        return $this->versions[$path] ?? (string)filemtime($realPath);
    }

    /**
     * Load versions from manifest
     */
    protected function loadVersions(): void
    {
        if (file_exists($this->manifestPath)) {
            $this->versions = json_decode(file_get_contents($this->manifestPath), true) ?? [];
        }
    }

    /**
     * Generate version manifest
     */
    public function generateVersions(): void
    {
        $versions = [];
        
        foreach ($this->trackDirs as $dir) {
            $path = $this->publicPath . '/' . $dir;
            if (!is_dir($path)) continue;

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && !$file->isLink()) {
                    $relativePath = str_replace($this->publicPath . '/', '', $file->getPathname());
                    $versions[$relativePath] = (string)filemtime($file->getPathname());
                }
            }
        }

        file_put_contents($this->manifestPath, json_encode($versions, JSON_PRETTY_PRINT));
    }

    /**
     * Load and render all assets in a collection as HTML tags
     */
    public function load(string|array $assets): string
    {
        if(is_string($assets)) {
            $assets = [$assets];
        }

        $html = '';
        foreach ($assets as $file => $options) {
            if (is_numeric($file)) {
                $file = $options;
                $options = 'defer'; // default to defer for better performance
            }

            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if ($ext === 'css') {
                $html .= $this->css($file);
            } elseif ($ext === 'js') {
                $html .= $this->js($file, $options);
            }
        }
        
        return $html;
    }

    /**
     * Get HTML for CSS files
     */
    protected function css(string|array $files): string
    {
        $files = is_array($files) ? $files : [$files];
        $html = '';

        foreach ($files as $file) {
            $url = $this->url($file);
            $html .= "<link rel='stylesheet' href='{$url}'>\n";
        }

        return $html;
    }

    /**
     * Get HTML for JS files
     */
    protected function js(string $file, ?string $mode = 'defer'): string
    {
        $url = $this->url($file);
        $attribute = match($mode) {
            'async' => ' async',
            'defer' => ' defer',
            null => '',     // No attribute = blocking script
            default => ' defer'
        };
        return "<script src='{$url}'{$attribute}></script>\n";
    }

    /**
     * Get HTML for an image with optional attributes
     */
    public function img(string $file, array $attributes = []): string
    {
        $url = $this->url($file);
        $attrs = '';

        foreach ($attributes as $key => $value) {
            $attrs .= " {$key}='" . htmlspecialchars($value, ENT_QUOTES) . "'";
        }

        return "<img src='{$url}'{$attrs}>";
    }

    /**
     * Generate script tag for module(s)
     */
    public function module(string|array $paths): string
    {
        if (is_string($paths)) {
            $paths = [$paths => null];
        }

        $html = '';
        foreach ($paths as $path => $mode) {
            if (is_numeric($path)) {
                $path = $mode;
                $mode = null;
            }
            
            $url = $this->url($path);
            $type = ' type="module"';
            $async = $mode === 'async' ? ' async' : '';
            $html .= "<script{$type} src='{$url}'{$async}></script>\n";
        }
        
        return $html;
    }

    /**
     * Add imports to the import map
     * 
     * @param array<string,mixed> $imports Array of imports where key is specifier and value is path to local file
     * @return string Import map script tag
     */
    public function importMap(array $imports = []): string
    {
        $map = ['imports' => []];

        // Process imports
        foreach ($imports as $name => $path) {
            $map['imports'][$name] = $this->url($path);
        }
        
        return sprintf(
            "<script type='importmap'>\n%s\n</script>",
            json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * Download and setup Google Font
     */
    public function googleFont(string $family, array $weights = [400]): self
    {
        $fontDir = $this->publicPath . '/fonts';
        $cssDir = $this->publicPath . '/css';

        // Create directories if they don't exist
        if (!is_dir($fontDir)) mkdir($fontDir, 0755, true);
        if (!is_dir($cssDir)) mkdir($cssDir, 0755, true);

        // Build Google Fonts URL with user agent to get woff2 fonts
        $url = sprintf(
            'https://fonts.googleapis.com/css2?family=%s:%s&display=swap',
            str_replace(' ', '+', $family),
            'wght@' . implode(';', $weights)
        );

        // Set up context with user agent
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ]
        ]);

        // Get CSS content
        $css = @file_get_contents($url, false, $context);
        if ($css === false) {
            throw new \RuntimeException("Failed to download Google Font CSS from: {$url}");
        }

        // Parse font-face blocks
        preg_match_all('/@font-face\s*{([^}]+)}/', $css, $blocks);
        if (empty($blocks[1])) {
            throw new \RuntimeException("No @font-face blocks found in Google Font CSS");
        }

        // Group fonts by weight and find the latin version (usually the smallest and most complete)
        $fontsByWeight = [];
        foreach ($blocks[1] as $block) {
            // Extract properties
            preg_match('/font-weight:\s*(\d+)/', $block, $weightMatch);
            preg_match('/src:\s*url\((.*?)\)/', $block, $urlMatch);
            
            if (empty($weightMatch[1]) || empty($urlMatch[1])) {
                continue;
            }

            $weight = $weightMatch[1];
            $url = trim($urlMatch[1], "'\" ");

            // Only store the latin version of the font (usually the last one)
            // or update if we haven't found one yet
            if (!isset($fontsByWeight[$weight]) || 
                strpos($block, 'U+0000-00FF') !== false) {
                $fontsByWeight[$weight] = $url;
            }
        }

        // Generate CSS
        $localCss = '';
        foreach ($fontsByWeight as $weight => $url) {
            // Download font file
            $fontContent = @file_get_contents($url, false, $context);
            if ($fontContent === false) {
                throw new \RuntimeException("Failed to download font file from: {$url}");
            }

            // Save font with weight-based name
            $fileName = sprintf('%s-%d.woff2', 
                strtolower($family),
                $weight
            );
            
            $fontPath = $fontDir . '/' . $fileName;
            if (!@file_put_contents($fontPath, $fontContent)) {
                throw new \RuntimeException("Failed to save font file to: {$fontPath}");
            }

            $localCss .= sprintf(
                "@font-face {\n" .
                "    font-family: '%s';\n" .
                "    font-style: normal;\n" .
                "    font-weight: %s;\n" .
                "    font-display: swap;\n" .
                "    src: local('%s'),\n" .
                "         url('/fonts/%s') format('woff2');\n" .
                "}\n\n",
                $family,
                $weight,
                $family,
                $fileName
            );
        }

        // Save CSS
        $cssFile = $cssDir . '/fonts.css';
        if (!@file_put_contents($cssFile, $localCss)) {
            throw new \RuntimeException("Failed to save CSS file to: {$cssFile}");
        }

        // Update version manifest
        $this->generateVersions();

        return $this;
    }
}
