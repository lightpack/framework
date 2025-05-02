<?php

namespace Lightpack\Utils;

class Image
{
    private int $width;
    private int $height;
    private ?\GdImage $loadedImage;
    private string $mime;

    private const AVATAR_SIZES = [
        'small'  => ['width' => 48, 'height' => 48],   // Comments, lists
        'medium' => ['width' => 96, 'height' => 96],   // Profile preview
        'large'  => ['width' => 192, 'height' => 192]   // Profile page
    ];
    
    private const THUMBNAIL_SIZES = [
        'small'  => ['width' => 300,  'height' => 0], // Blog post preview, gallery grid
        'medium' => ['width' => 600,  'height' => 0], // Article main image, cards
        'large'  => ['width' => 1200, 'height' => 0], // Feature banners, full-width sections
    ];

    // Default quality settings for all images
    private int $defaultJpegQuality = 80;
    private int $defaultWebpQuality = 80;
    private int $defaultPngCompression = 7; // 0 (none) - 9 (max)

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

        // No-op optimization: skip if dimensions unchanged
        if ($newDimensions['width'] === $this->width && $newDimensions['height'] === $this->height) {
            return $this;
        }

        $resizedImage = imagecreatetruecolor($newDimensions['width'], $newDimensions['height']);
        if ($resizedImage === false) {
            throw new \Exception('Failed to create new image resource for resizing.');
        }

        if (in_array($this->mime, ['image/png', 'image/webp'])) {
            imagealphablending($resizedImage, false);
            imagesavealpha($resizedImage, true);
            $background = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
            imagecolortransparent($resizedImage, $background);
        } else {
            $background = imagecolorallocate($resizedImage, 255, 255, 255);
        }

        imagefilledrectangle($resizedImage, 0, 0, $newDimensions['width'], $newDimensions['height'], $background);

        if (!imagecopyresampled(
            $resizedImage, $this->loadedImage,
            0, 0, 0, 0,
            $newDimensions['width'], $newDimensions['height'],
            $this->width, $this->height
        )) {
            throw new \Exception('Failed to resize image.');
        }

        $this->replaceCurrentImage($resizedImage, $newDimensions['width'], $newDimensions['height']);
        return $this;
    }

    public function save(string $file, ?int $quality = null): void
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

    public function setDefaultJpegQuality(int $quality): void
    {
        $this->defaultJpegQuality = $quality;
    }

    public function setDefaultWebpQuality(int $quality): void
    {
        $this->defaultWebpQuality = $quality;
    }

    public function setDefaultPngCompression(int $compression): void
    {
        $this->defaultPngCompression = max(0, min(9, $compression));
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
            $newHeight = (int) round($width / $aspectRatio);
        } elseif ($height > 0) {
            $newHeight = $height;
            $newWidth = (int) round($height * $aspectRatio);
        } else {
            $newWidth = $this->width;
            $newHeight = $this->height;
        }

        // Final sanity check
        if ($newWidth <= 0 || $newHeight <= 0) {
            throw new \Exception('Calculated dimensions are invalid. Please check your resize parameters.');
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

    private function saveImageByExtension(string $extension, string $file, ?int $quality = null): void
    {
        if ($extension === 'webp' && !function_exists('imagewebp')) {
            throw new \Exception('WebP support is not enabled in your PHP GD extension.');
        }
        $result = match($extension) {
            'jpg', 'jpeg' => imagejpeg(
                $this->loadedImage,
                $file,
                $quality !== null ? $quality : $this->defaultJpegQuality
            ),
            'png' => imagepng(
                $this->loadedImage,
                $file,
                $this->defaultPngCompression
            ),
            'webp' => imagewebp(
                $this->loadedImage,
                $file,
                $quality !== null ? $quality : $this->defaultWebpQuality
            ),
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
            $clone->resize($dimensions['width'], $dimensions['height'])
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

    /**
     * Crop the image to the given width/height at (x, y).
     */
    public function crop(int $width, int $height, int $x = 0, int $y = 0): self
    {
        $cropped = imagecrop($this->loadedImage, [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height
        ]);
        if ($cropped === false) {
            throw new \Exception('Failed to crop image.');
        }
        imagedestroy($this->loadedImage);
        $this->loadedImage = $cropped;
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    /**
     * Rotate the image by the given angle (degrees).
     * Positive values rotate counter-clockwise.
     */
    public function rotate(float $angle, int $bgColor = 0): self
    {
        $rotated = imagerotate($this->loadedImage, $angle, $bgColor);
        if ($rotated === false) {
            throw new \Exception('Failed to rotate image.');
        }
        imagedestroy($this->loadedImage);
        $this->loadedImage = $rotated;
        // Swap width/height if angle is 90 or 270
        if (abs($angle) % 180 === 90) {
            [$this->width, $this->height] = [$this->height, $this->width];
        }
        return $this;
    }

    /**
     * Flip the image horizontally or vertically.
     * @param string $direction 'horizontal' or 'vertical'
     */
    public function flip(string $direction = 'horizontal'): self
    {
        $mode = $direction === 'vertical' ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL;
        if (!imageflip($this->loadedImage, $mode)) {
            throw new \Exception('Failed to flip image.');
        }
        return $this;
    }

    /**
     * Convert the image to grayscale.
     */
    public function grayscale(): self
    {
        if (!imagefilter($this->loadedImage, IMG_FILTER_GRAYSCALE)) {
            throw new \Exception('Failed to apply grayscale filter.');
        }
        return $this;
    }

    /**
     * Adjust brightness (-255 to 255).
     */
    public function brightness(int $level): self
    {
        if (!imagefilter($this->loadedImage, IMG_FILTER_BRIGHTNESS, $level)) {
            throw new \Exception('Failed to adjust brightness.');
        }
        return $this;
    }

    /**
     * Adjust contrast (-100 to 100).
     */
    public function contrast(int $level): self
    {
        if (!imagefilter($this->loadedImage, IMG_FILTER_CONTRAST, $level)) {
            throw new \Exception('Failed to adjust contrast.');
        }
        return $this;
    }

    /**
     * Apply a simple Gaussian blur to the image.
     * @param int $passes Number of times to apply the blur (default 1)
     */
    public function blur(int $passes = 1): self
    {
        for ($i = 0; $i < $passes; $i++) {
            if (!imagefilter($this->loadedImage, IMG_FILTER_GAUSSIAN_BLUR)) {
                throw new \Exception('Failed to apply blur.');
            }
        }
        return $this;
    }

    /**
     * Sharpen the image using a convolution matrix.
     * @param float $amount Sharpen amount (default 1.0)
     */
    public function sharpen(float $amount = 1.0): self
    {
        // Basic sharpen matrix
        $matrix = [
            [-1, -1, -1],
            [-1, 8 + $amount, -1],
            [-1, -1, -1],
        ];
        $divisor = array_sum(array_map('array_sum', $matrix));
        if ($divisor == 0) $divisor = 1;
        if (!imageconvolution($this->loadedImage, $matrix, $divisor, 0)) {
            throw new \Exception('Failed to sharpen image.');
        }
        return $this;
    }
}
