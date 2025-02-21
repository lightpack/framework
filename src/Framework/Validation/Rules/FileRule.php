<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules;

class FileRule
{
    private string $message = 'Invalid file upload';
    private array $constraints;

    public function __construct(array $constraints = [])
    {
        $this->constraints = array_merge([
            'size' => null,
            'types' => [],
            'extensions' => [],
            'min_files' => null,
            'max_files' => null,
            'dimensions' => null,
        ], $constraints);
    }

    public function __invoke(mixed $value, array $data): bool
    {
        if (empty($value)) {
            return false;
        }

        // Handle both single and multiple files
        $files = $this->normalizeFiles($value);

        // Check min/max files if specified
        if ($this->constraints['min_files'] !== null && count($files) < $this->constraints['min_files']) {
            $this->message = "Minimum {$this->constraints['min_files']} files required";
            return false;
        }

        if ($this->constraints['max_files'] !== null && count($files) > $this->constraints['max_files']) {
            $this->message = "Maximum {$this->constraints['max_files']} files allowed";
            return false;
        }

        foreach ($files as $file) {
            if (!$this->validateFile($file)) {
                return false;
            }
        }

        return true;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function addConstraint(string $key, mixed $value): void
    {
        if ($key === 'types' && isset($this->constraints['types'])) {
            // Merge types instead of replacing
            $this->constraints['types'] = array_merge($this->constraints['types'], $value);
        } elseif ($key === 'extensions' && isset($this->constraints['extensions'])) {
            // Merge extensions instead of replacing
            $this->constraints['extensions'] = array_merge($this->constraints['extensions'], $value);
        } else {
            $this->constraints[$key] = $value;
        }
    }

    private function normalizeFiles(mixed $value): array 
    {
        // Handle single file upload
        if (isset($value['name']) && !is_array($value['name'])) {
            return [$value];
        }

        // Handle multiple file upload
        if (isset($value['name']) && is_array($value['name'])) {
            $files = [];
            $keys = ['name', 'type', 'tmp_name', 'error', 'size'];
            
            foreach ($value['name'] as $i => $name) {
                $file = [];
                foreach ($keys as $key) {
                    $file[$key] = $value[$key][$i];
                }
                $files[] = $file;
            }
            
            return $files;
        }

        // Handle array of files
        if (is_array($value) && isset($value[0]['name'])) {
            return $value;
        }

        return [];
    }

    private function validateFile(array $file): bool
    {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->message = $this->getUploadErrorMessage($file['error']);
            return false;
        }

        // Check file size
        if ($this->constraints['size'] !== null) {
            $maxSize = $this->parseSize($this->constraints['size']);
            if ($file['size'] > $maxSize) {
                $this->message = "File size must not exceed {$this->constraints['size']}";
                return false;
            }
        }

        // Check MIME type
        if (!empty($this->constraints['types'])) {
            $types = (array) $this->constraints['types'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            
            if (!in_array($mimeType, $types)) {
                $this->message = 'Invalid file type';
                return false;
            }
        }

        // Check extension
        if (!empty($this->constraints['extensions'])) {
            $extensions = (array) $this->constraints['extensions'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($extension, $extensions)) {
                $this->message = 'Invalid file extension';
                return false;
            }
        }

        // Check image dimensions if specified
        if ($this->constraints['dimensions'] !== null && $this->isImage($file)) {
            if (!$this->validateImageDimensions($file)) {
                return false;
            }
        }

        return true;
    }

    private function parseSize(string $size): int
    {
        $size = strtoupper($size);
        if (preg_match('/^(\d+)(B|K|KB|M|MB|G|GB)?$/', $size, $matches)) {
            $value = (int) $matches[1];
            $unit = $matches[2] ?? 'B';
            
            return match($unit) {
                'B' => $value,
                'K', 'KB' => $value * 1024,
                'M', 'MB' => $value * 1024 * 1024,
                'G', 'GB' => $value * 1024 * 1024 * 1024,
                default => $value
            };
        }
        
        return (int) $size;
    }

    private function isImage(array $file): bool
    {
        return str_starts_with(mime_content_type($file['tmp_name']), 'image/');
    }

    private function validateImageDimensions(array $file): bool
    {
        $dimensions = $this->constraints['dimensions'];
        $imageInfo = getimagesize($file['tmp_name']);
        
        if ($imageInfo === false) {
            $this->message = 'Invalid image file';
            return false;
        }

        [$width, $height] = $imageInfo;

        if (isset($dimensions['min_width']) && $width < $dimensions['min_width']) {
            $this->message = "Image width must be at least {$dimensions['min_width']}px";
            return false;
        }

        if (isset($dimensions['max_width']) && $width > $dimensions['max_width']) {
            $this->message = "Image width must not exceed {$dimensions['max_width']}px";
            return false;
        }

        if (isset($dimensions['min_height']) && $height < $dimensions['min_height']) {
            $this->message = "Image height must be at least {$dimensions['min_height']}px";
            return false;
        }

        if (isset($dimensions['max_height']) && $height > $dimensions['max_height']) {
            $this->message = "Image height must not exceed {$dimensions['max_height']}px";
            return false;
        }

        return true;
    }

    private function getUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
            default => 'Unknown upload error'
        };
    }
}
