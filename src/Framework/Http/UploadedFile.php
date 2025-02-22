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

    public function store(string $destination, array $options = []): void
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

        if($this->storage instanceof LocalStorage) {
            $this->ensureDirectoryChecks($destination);
        }

        $this->storage->store($this->tmpName, $targetPath);
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

    /**
     * @deprecated use store() method
     */
    public function move(string $destination, string $name = null): void
    {
        $this->ensureDirectoryChecks($destination);

        $this->processUpload($name ?? $this->name, $destination);
    }

    /**
     * @deprecated
     */
    private function processUpload(string $name, string $destination): void
    {
        $targetPath = rtrim($destination, '\\/') . '/' . $name;

        // For test purposes.
        if(isset($_SERVER['X_LIGHTPACK_TEST_UPLOAD'])) {
            $success = copy($this->tmpName, $targetPath);
        } else {
            $success = move_uploaded_file($this->tmpName, $targetPath);
        }

        if (!$success) {
            throw new FileUploadException('Could not upload the file.');
        }
    }

    private function ensureDirectoryChecks(string $destination)
    {
        if (is_dir($destination)) {
            if (!is_writable($destination)) {
                throw new FileUploadException('Upload directory does not have sufficient write permission: ' . $destination);
            }
        } elseif (!mkdir($destination, 0777, true)) {
            throw new FileUploadException('Could not create upload directory: ' . $destination);
        }
    }
}
