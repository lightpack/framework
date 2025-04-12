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
     * Collection of asset groups
     */
    protected array $collections = [];

    /**
     * Import map definitions
     */
    protected array $imports = [];

    /**
     * CDN fallbacks for modules
     */
    protected array $fallbacks = [];

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
     * Create a named collection of assets
     */
    public function collection(string $name, array $files): self
    {
        $this->collections[$name] = $files;
        return $this;
    }

    /**
     * Get URLs for all assets in a collection
     */
    public function collect(string $name): array
    {
        if (!isset($this->collections[$name])) {
            throw new \InvalidArgumentException("Asset collection '{$name}' not found");
        }

        return array_map(fn($file) => $this->url($file), $this->collections[$name]);
    }

    /**
     * Get HTML for CSS files
     */
    public function css(string|array $files): string
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
    public function js(string|array $files, bool $defer = true): string
    {
        $files = is_array($files) ? $files : [$files];
        $html = '';

        foreach ($files as $file) {
            $url = $this->url($file);
            $defer = $defer ? ' defer' : '';
            $html .= "<script src='{$url}'{$defer}></script>\n";
        }

        return $html;
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
     * Add HTTP/2 preload headers for critical assets
     */
    public function preload(string|array $files): self
    {
        $files = is_array($files) ? $files : [$files];

        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            
            // Special handling for fonts and modules
            if (in_array($ext, ['woff', 'woff2', 'ttf'])) {
                $this->preloadLinks[] = "</{$file}>; rel=preload; as=font; crossorigin";
            } elseif ($ext === 'js' && str_contains($file, 'module')) {
                $this->preloadLinks[] = "</{$file}>; rel=modulepreload";
            } else {
                $type = $this->getMimeType($ext);
                $this->preloadLinks[] = "</{$file}>; rel=preload; as={$type}";
            }
        }

        return $this;
    }

    /**
     * Get all preload links
     */
    public function getPreloadLinks(): array
    {
        return $this->preloadLinks;
    }

    /**
     * Send preload headers
     */
    public function sendPreloadHeaders(): void
    {
        foreach ($this->preloadLinks as $link) {
            header("Link: {$link}", false);
        }
    }

    /**
     * Get all preload headers
     * 
     * @return array Array of Link headers for preloading
     */
    public function getPreloadHeaders(): array 
    {
        $headers = [];
        
        foreach ($this->preloadLinks as $link) {
            $headers[] = ['Link', $link];
        }
        
        return $headers;
    }

    /**
     * Get mime type for file extension
     */
    protected function getMimeType(string $ext): string
    {
        return match($ext) {
            'css' => 'style',
            'js' => 'script',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
            'woff', 'woff2', 'ttf' => 'font',
            default => 'file',
        };
    }

    /**
     * Define a module import
     */
    public function import(string $name, string $path, array $options = []): self
    {
        // If it's a full URL, use as is
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $url = $path;
        } else {
            // Local file, add version and base URL
            $shouldVersion = $options['version'] ?? true;
            $url = $this->url($path, $shouldVersion);
        }

        $this->imports[$name] = [
            'url' => $url,
            'integrity' => $options['integrity'] ?? null,
        ];

        // If preload is requested
        if (!empty($options['preload'])) {
            $this->preloadModules[] = $name;
        }

        return $this;
    }

    /**
     * Define a CDN fallback for a module
     */
    public function fallback(string $name, string $cdnUrl, ?string $integrity = null): self
    {
        $this->fallbacks[$name] = [
            'url' => $cdnUrl,
            'integrity' => $integrity,
        ];

        return $this;
    }

    /**
     * Generate the import map script tag
     */
    public function importMap(): string
    {
        $map = ['imports' => []];

        // Add primary imports
        foreach ($this->imports as $name => $import) {
            $map['imports'][$name] = $import['url'];
        }

        // Add fallbacks if defined
        if (!empty($this->fallbacks)) {
            $map['fallbacks'] = [];
            foreach ($this->fallbacks as $name => $fallback) {
                $map['fallbacks'][$name] = $fallback['url'];
            }
        }

        // Add integrity data if any exists
        $hasIntegrity = false;
        foreach ($this->imports as $import) {
            if (!empty($import['integrity'])) {
                $hasIntegrity = true;
                break;
            }
        }

        if ($hasIntegrity) {
            $map['integrity'] = [];
            foreach ($this->imports as $name => $import) {
                if (!empty($import['integrity'])) {
                    $map['integrity'][$name] = $import['integrity'];
                }
            }
        }

        // Generate preload tags for modules
        $preloads = '';
        foreach ($this->preloadModules as $name) {
            if (isset($this->imports[$name])) {
                $url = $this->imports[$name]['url'];
                $integrity = $this->imports[$name]['integrity'] ?? '';
                $integrityAttr = $integrity ? " integrity='{$integrity}'" : '';
                $preloads .= "<link rel='modulepreload' href='{$url}'{$integrityAttr}>\n";
            }
        }

        // Generate the import map script
        $json = json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return $preloads . "<script type='importmap'>\n{$json}\n</script>";
    }

    /**
     * Generate script tag for module
     */
    public function module(string $path, array $options = []): string
    {
        $url = $this->url($path);
        $type = ' type="module"';
        $defer = !empty($options['defer']) ? ' defer' : '';
        $async = !empty($options['async']) ? ' async' : '';
        
        return "<script{$type} src='{$url}'{$defer}{$async}></script>\n";
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
