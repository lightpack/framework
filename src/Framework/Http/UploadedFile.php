<?php

namespace Lightpack\Http;

use Lightpack\Container\Container;
use Lightpack\Utils\Str;
use Lightpack\Storage\LocalStorage;
use Lightpack\Exceptions\FileUploadException;

class UploadedFile
{
    private $storage;
    private $name;
    private $size;
    private $type;
    private $error;
    private $tmpName;

    public function __construct($file)
    {
        $this->storage = Container::getInstance()->get('storage');
        $this->name = $file['name'];
        $this->size = $file['size'];
        $this->type = $file['type'];
        $this->error = $file['error'];
        $this->tmpName = $file['tmp_name'];
    }

    public function isImage()
    {
        return in_array($this->type, [
            'image/gif',
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/webp',
        ]);
    }

    public function getDimensions(): ?array
    {
        if (!$this->isImage()) {
            return ['width' => 0, 'height' => 0];
        }

        list($width, $height) = getimagesize($this->tmpName);

        return ['width' => $width, 'height' => $height];
    }

    public function getWidth(): int
    {
        $dimensions = $this->getDimensions();

        return $dimensions['width'];
    }

    public function getHeight(): int
    {
        $dimensions = $this->getDimensions();

        return $dimensions['height'];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getExtension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function getTmpName(): string
    {
        return $this->tmpName;
    }

    public function isEmpty(): bool
    {
        return empty($this->getName());
    }

    /**
     * Store the uploaded file in the specified destination
     * 
     * @param string $destination Relative path of target directory
     * @param array $options Storage options:
     *                      - name: Custom filename (string|callable)
     *                      - unique: Generate unique filename (bool)
     *                      - preserve_name: Keep original name as prefix when unique (bool)
     * @throws FileUploadException If file cannot be uploaded or directory issues
     * @return string The path where the file was stored
     */
    public function store(string $destination, array $options = []): string
    {
        // Default options
        $options = array_merge([
            'name' => null,        // string|callable
            'unique' => false,     // bool
            'preserve_name' => false, // bool
        ], $options);

        // Get filename
        $filename = $this->resolveFilename($options);

        // Build full path
        $targetPath = rtrim($destination, '\\/') . '/' . $filename;
        $this->storage->store($this->tmpName, $targetPath);
        
        // Return the path where the file was stored
        return $targetPath;
    }
    
    /**
     * Store the uploaded file in the public uploads directory
     * Files stored with this method will be directly accessible via URL
     * 
     * @param string $path Path within the public uploads directory
     * @param array $options Storage options
     * @return string The relative path where the file was stored
     * @throws FileUploadException If file cannot be uploaded
     */
    public function storePublic(string $path = '', array $options = []): string
    {
        $path = trim($path, '/\\');
        $storagePath = 'uploads/public/' . ($path ? $path . '/' : '');

        return $this->store($storagePath, $options);
    }
    
    /**
     * Store the uploaded file in the private uploads directory
     * Files stored with this method require access control
     * 
     * @param string $path Path within the private uploads directory
     * @param array $options Storage options
     * @return string The relative path where the file was stored
     * @throws FileUploadException If file cannot be uploaded
     */
    public function storePrivate(string $path = '', array $options = []): string
    {
        $path = trim($path, '/\\');
        $storagePath = 'uploads/private/' . ($path ? $path . '/' : '');

        return $this->store($storagePath, $options);
    }

    private function resolveFilename(array $options): string 
    {
        $str = new Str();

        // 1. Use callback if provided
        if (isset($options['name']) && is_callable($options['name'])) {
            $filename = $options['name']($this);
        } 
        // 2. Use provided name
        elseif (isset($options['name']) && is_string($options['name'])) {
            $filename = $options['name'];
        }
        // 3. Use original name
        else {
            $filename = $this->name;
        }

        // Get name and extension using Str helpers
        $name = $str->slug($str->stem($filename));
        $ext = $str->ext($filename);

        // Make unique if requested
        if (!empty($options['unique'])) {            
            // Prefix with original name if requested
            if (!empty($options['preserve_name'])) {
                $name .= '-' .  $str->random(32);
            } else {
                $name = $str->random(32);
            }
        }

        // Combine name and extension
        return $name . '.' . $ext;
    }
}
