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

    public function __construct(?string $publicPath = null)
    {
        $this->publicPath = $publicPath ?? DIR_ROOT . '/public';
        $this->baseUrl = get_env('ASSET_URL') ?? get_env('APP_URL');
        $this->manifestPath = $this->publicPath . '/assets.json';
    }

    /**
     * Get URL for an asset with optional versioning
     */
    public function url(string $path, array $options = []): string
    {
        $path = trim($path, '/ ');
        
        if (($options['version'] ?? true)) {
            $version = $this->getVersion($path);
            if ($version) {
                $path .= '?v=' . $version;
            }
        }

        return ($this->baseUrl ? rtrim($this->baseUrl, '/') : '') . '/' . $path;
    }

    /**
     * Get version for an asset
     */
    protected function getVersion(string $path): ?string
    {
        // Load versions if not loaded
        if (empty($this->versions)) {
            $this->loadVersions();
        }

        return $this->versions[$path] ?? null;
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
    public function preload(string|array $files): void
    {
        $files = is_array($files) ? $files : [$files];

        foreach ($files as $file) {
            $url = $this->url($file);
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            
            // Special handling for fonts and modules
            if (in_array($ext, ['woff', 'woff2', 'ttf'])) {
                header("Link: <{$url}>; rel=preload; as=font; crossorigin", false);
            } elseif ($ext === 'js' && str_contains($file, 'module')) {
                header("Link: <{$url}>; rel=modulepreload", false);
            } else {
                $type = $this->getMimeType($ext);
                header("Link: <{$url}>; rel=preload; as={$type}", false);
            }
        }
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
            $url = $this->url($path, $options);
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
        $url = $this->url($path, $options);
        $type = ' type="module"';
        $defer = !empty($options['defer']) ? ' defer' : '';
        $async = !empty($options['async']) ? ' async' : '';
        
        return "<script{$type} src='{$url}'{$defer}{$async}></script>\n";
    }

    /**
     * Download and setup Google Font
     */
    public function googleFont(string $family, array $weights = ['400']): self
    {
        $fontDir = $this->publicPath . '/fonts';
        $cssDir = $this->publicPath . '/css';

        // Create directories if they don't exist
        if (!is_dir($fontDir)) mkdir($fontDir, 0755, true);
        if (!is_dir($cssDir)) mkdir($cssDir, 0755, true);

        // Build Google Fonts URL
        $url = sprintf(
            'https://fonts.googleapis.com/css2?family=%s:wght@%s&display=swap',
            str_replace(' ', '+', $family),
            implode(';', $weights)
        );

        // Get and parse CSS
        $css = file_get_contents($url);
        preg_match_all('/url\((.*?)\)/', $css, $matches);
        
        // Download fonts and generate CSS
        $localCss = '';
        foreach ($weights as $weight) {
            // Find matching font URL for this weight
            $fontUrl = '';
            foreach ($matches[1] as $url) {
                if (strpos($url, $weight . '.woff2') !== false) {
                    $fontUrl = $url;
                    break;
                }
            }

            if (!$fontUrl) continue;

            // Download font
            $fontContent = file_get_contents($fontUrl);
            $fileName = strtolower($family) . '-' . $weight . '.woff2';
            file_put_contents($fontDir . '/' . $fileName, $fontContent);

            // Generate CSS
            $localCss .= sprintf(
                "@font-face {\n" .
                "    font-family: '%s';\n" .
                "    font-weight: %s;\n" .
                "    src: local('%s'),\n" .
                "         url('/fonts/%s') format('woff2');\n" .
                "    font-display: swap;\n" .
                "}\n\n",
                $family,
                $weight,
                $family,
                $fileName
            );
        }

        // Save CSS
        $cssFile = $cssDir . '/fonts.css';
        file_put_contents($cssFile, $localCss);

        // Update version manifest
        $this->generateVersions();

        return $this;
    }
}
