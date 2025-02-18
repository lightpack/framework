<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules\File;

class ImageRule
{
    private string $message;
    private array $constraints;
    private array $dimensions;

    public function __construct(array $constraints = [])
    {
        $this->constraints = array_merge([
            'min_width' => null,
            'max_width' => null,
            'min_height' => null,
            'max_height' => null,
        ], $constraints);
        
        $this->message = 'Invalid image dimensions';
    }

    public function __invoke($value, array $data = []): bool 
    {
        if (!is_array($value)) {
            return false;
        }

        // For optional fields, no file is valid
        if (isset($value['error']) && $value['error'] === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Single file upload
        if (isset($value['tmp_name']) && !is_array($value['tmp_name'])) {
            return $this->validateSingleImage($value['tmp_name']);
        }

        // Multiple file upload
        if (isset($value['tmp_name']) && is_array($value['tmp_name'])) {
            foreach ($value['tmp_name'] as $tmp_name) {
                if (!$this->validateSingleImage($tmp_name)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    private function validateSingleImage(string $path): bool
    {
        if (!$this->isImage($path)) {
            $this->message = 'Invalid image file';
            return false;
        }

        $this->dimensions = $this->getDimensions($path);

        if ($this->constraints['min_width'] && $this->dimensions['width'] < $this->constraints['min_width']) {
            $this->message = sprintf('Image width must be at least %d pixels', $this->constraints['min_width']);
            return false;
        }

        if ($this->constraints['max_width'] && $this->dimensions['width'] > $this->constraints['max_width']) {
            $this->message = sprintf('Image width must not exceed %d pixels', $this->constraints['max_width']);
            return false;
        }

        if ($this->constraints['min_height'] && $this->dimensions['height'] < $this->constraints['min_height']) {
            $this->message = sprintf('Image height must be at least %d pixels', $this->constraints['min_height']);
            return false;
        }

        if ($this->constraints['max_height'] && $this->dimensions['height'] > $this->constraints['max_height']) {
            $this->message = sprintf('Image height must not exceed %d pixels', $this->constraints['max_height']);
            return false;
        }

        return true;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }

    protected function isImage(string $path): bool
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $type = $finfo->file($path);
        return $type && str_starts_with($type, 'image/');
    }

    protected function getDimensions(string $path): array
    {
        $info = @getimagesize($path);
        
        if ($info === false) {
            return ['width' => 0, 'height' => 0];
        }

        return ['width' => $info[0], 'height' => $info[1]];
    }
}
