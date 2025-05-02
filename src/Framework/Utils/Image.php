<?php

namespace Lightpack\Utils;

class Image
{
    private int $width;
    private int $height;
    private ?\GdImage $loadedImage;
    private string $mime;

    private const AVATAR_SIZES = [
        'small'  => ['size' => 48],   // Comments, lists
        'medium' => ['size' => 96],   // Profile preview
        'large'  => ['size' => 192]   // Profile page
    ];
    
    private const THUMBNAIL_SIZES = [
        'small' => ['width' => 300, 'height' => 300],
        'medium' => ['width' => 600, 'height' => 400],
        'large' => ['width' => 1200, 'height' => 800]
    ];

    public function __construct(string $filepath)
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('GD extension is required for image processing.');
        }

        $this->loadedImage = null;
        $this->loadImage($filepath);
    }

    public function resize(int $width, int $height): self
    {
        $newDimensions = $this->calculateNewDimensions($width, $height);
        $resizedImage = imagecreatetruecolor($newDimensions['width'], $newDimensions['height']);

        if ($this->mime == 'image/png') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $background = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagecolortransparent($resizedImage, $background);
        } elseif ($this->mime == 'image/webp') {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $background = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagecolortransparent($resizedImage, $background);
        } else {
            $background = imagecolorallocate($resizedImage, 255, 255, 255);
        }

        imagefilledrectangle($resizedImage, 0, 0, $width, $height, $background);
        imagecopyresampled($resizedImage, $this->loadedImage, 0, 0, 0, 0, $newDimensions['width'], $newDimensions['height'], $this->width, $this->height);

        $this->replaceCurrentImage($resizedImage, $newDimensions['width'], $newDimensions['height']);
        
        return $this;
    }

    public function save(string $file, int $quality = 90): void
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        if (!is_dir(dirname($file))) {
            throw new \Exception('Directory does not exist: ' . dirname($file));
        }
        
        if (!is_writable(dirname($file))) {
            throw new \Exception('Directory is not writable: ' . dirname($file));
        }

        if (!$this->loadedImage instanceof \GdImage) {
            throw new \Exception('No valid image resource to save');
        }

        $this->saveImageByExtension($extension, $file, $quality);
        imagedestroy($this->loadedImage);
        $this->loadedImage = null;
    }

    private function loadImage(string $file): void
    {
        if (filter_var($file, FILTER_VALIDATE_URL)) {
            $fileContents = file_get_contents($file);

            if ($fileContents === false) {
                throw new \Exception('Failed to load image file from URL: ' . $file);
            }

            $tempFilePath = tempnam(sys_get_temp_dir(), 'image');
            if ($tempFilePath === false) {
                throw new \Exception('Failed to create temporary file');
            }

            if (file_put_contents($tempFilePath, $fileContents) === false) {
                throw new \Exception('Failed to write image data to temporary file');
            }
            $file = $tempFilePath;
        }

        if (!is_file($file)) {
            throw new \Exception('Image file not found: ' . $file);
        }

        $imageInfo = @getimagesize($file);
        if ($imageInfo === false) {
            throw new \Exception('Invalid image file or unsupported format: ' . $file);
        }

        $mime = $imageInfo['mime'];
        $this->width = $imageInfo[0];
        $this->height = $imageInfo[1];
        $this->mime = $mime;

        $image = match($mime) {
            'image/jpeg' => imagecreatefromjpeg($file),
            'image/png' => imagecreatefrompng($file),
            'image/webp' => imagecreatefromwebp($file),
            default => throw new \Exception('Unsupported image type: ' . $mime),
        };

        if ($image === false) {
            throw new \Exception('Failed to create image from file: ' . $file);
        }

        $this->loadedImage = $image;

        if (isset($tempFilePath)) {
            unlink($tempFilePath);
        }
    }

    private function calculateNewDimensions(int $width, int $height): array
    {
        $aspectRatio = $this->width / $this->height;

        if ($width > 0 && $height > 0) {
            $newWidth = $width;
            $newHeight = $height;
        } elseif ($width > 0) {
            $newWidth = $width;
            $newHeight = $width / $aspectRatio;
        } elseif ($height > 0) {
            $newHeight = $height;
            $newWidth = $height * $aspectRatio;
        } else {
            $newWidth = $this->width;
            $newHeight = $this->height;
        }

        return [
            'width' => (int)$newWidth,
            'height' => (int)$newHeight,
        ];
    }

    private function replaceCurrentImage(object $newImage, int $width, int $height): void
    {
        if (is_object($this->loadedImage) || is_resource($this->loadedImage)) {
            imagedestroy($this->loadedImage);
        }

        $this->loadedImage = $newImage;
        $this->width = $width;
        $this->height = $height;
    }

    private function saveImageByExtension(string $extension, string $file, int $quality): void
    {
        if ($extension === 'webp' && !function_exists('imagewebp')) {
            throw new \Exception('WebP support is not enabled in your PHP GD extension.');
        }
        $result = match($extension) {
            'jpg', 'jpeg' => imagejpeg($this->loadedImage, $file, $quality),
            'png' => imagepng($this->loadedImage, $file, (int)(9 - min(9, $quality / 10))), // Convert quality to PNG compression (0-9)
            'webp' => imagewebp($this->loadedImage, $file, $quality),
            default => throw new \Exception('Unsupported image extension: ' . $extension),
        };

        if ($result === false) {
            $error = error_get_last();
            throw new \Exception('Failed to save image to file: ' . $file . 
                               ($error ? ' - ' . $error['message'] : ''));
        }
    }

    /**
     * Generate avatar images in standard sizes
     *
     * @param string $filename Base filename without extension
     * @param array $sizes Sizes to generate (small, medium, large)
     * @return array Array of generated file paths
     */
    public function avatar(string $filename, array $sizes = ['small', 'medium', 'large']): array {
        $paths = [];
        
        foreach ($sizes as $size) {
            if (!isset(self::AVATAR_SIZES[$size])) {
                throw new \InvalidArgumentException("Invalid avatar size: $size");
            }
            
            $dimensions = self::AVATAR_SIZES[$size];
            $outputPath = $filename . '_avatar_' . $size . '.webp';
            
            // Clone image to avoid modifying original
            $clone = clone $this;
            $clone->resize($dimensions['size'], $dimensions['size'])
                  ->save($outputPath);
                  
            $paths[$size] = $outputPath;
        }
        
        return $paths;
    }
    
    /**
     * Generate thumbnail images in standard sizes
     *
     * @param string $filename Base filename without extension
     * @param array $sizes Sizes to generate (small, medium, large)
     * @return array Array of generated file paths
     */
    public function thumbnail(string $filename, array $sizes = ['small', 'medium', 'large']): array {
        $paths = [];
        
        foreach ($sizes as $size) {
            if (!isset(self::THUMBNAIL_SIZES[$size])) {
                throw new \InvalidArgumentException("Invalid thumbnail size: $size");
            }
            
            $dimensions = self::THUMBNAIL_SIZES[$size];
            $outputPath = $filename . '_thumb_' . $size . '.jpg';
            
            // Clone image to avoid modifying original
            $clone = clone $this;
            $clone->resize($dimensions['width'], $dimensions['height'])
                  ->save($outputPath);
                  
            $paths[$size] = $outputPath;
        }
        
        return $paths;
    }
    
    public function __clone()
    {
        // Create a new GD resource for the clone
        $width = imagesx($this->loadedImage);
        $height = imagesy($this->loadedImage);
        
        $newImage = imagecreatetruecolor($width, $height);
        imagecopy($newImage, $this->loadedImage, 0, 0, 0, 0, $width, $height);
        
        $this->loadedImage = $newImage;
    }
}
