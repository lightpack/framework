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

    public function __construct(?string $publicPath = null)
    {
        $this->publicPath = $publicPath ?? DIR_ROOT . '/public';
        $this->baseUrl = get_env('ASSET_URL') ?? get_env('APP_URL');
    }

    /**
     * Get URL for an asset with optional versioning
     */
    public function url(string $path, array $options = []): string
    {
        $path = trim($path, '/ ');
        $realPath = $this->publicPath . '/' . $path;

        // Add version if file exists and versioning is enabled
        if (file_exists($realPath) && ($options['version'] ?? true)) {
            $version = filemtime($realPath);
            $path .= '?v=' . $version;
        }

        return ($this->baseUrl ? rtrim($this->baseUrl, '/') : '') . '/' . $path;
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
            $type = $this->getMimeType($ext);

            header("Link: <{$url}>; rel=preload; as={$type}", false);
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
}
