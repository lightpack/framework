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
            'min_width' => 0,
            'max_width' => PHP_INT_MAX,
            'min_height' => 0,
            'max_height' => PHP_INT_MAX,
            'ratio' => null,
        ], $constraints);
        
        $this->message = 'Invalid image dimensions';
    }

    public function __invoke($value, array $data = []): bool 
    {
        if (!is_array($value) || !isset($value['tmp_name'])) {
            return false;
        }

        if (!$this->isImage($value['tmp_name'])) {
            $this->message = 'File must be an image';
            return false;
        }

        $this->dimensions = $this->getDimensions($value['tmp_name']);
        
        if (!$this->validateDimensions()) {
            return false;
        }

        if ($this->constraints['ratio'] && !$this->validateRatio()) {
            $this->message = sprintf('Image aspect ratio must be %s', $this->constraints['ratio']);
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

    private function validateDimensions(): bool
    {
        $width = $this->dimensions['width'];
        $height = $this->dimensions['height'];

        if ($width < $this->constraints['min_width']) {
            $this->message = sprintf('Image width must be at least %dpx', $this->constraints['min_width']);
            return false;
        }

        if ($width > $this->constraints['max_width']) {
            $this->message = sprintf('Image width must not exceed %dpx', $this->constraints['max_width']);
            return false;
        }

        if ($height < $this->constraints['min_height']) {
            $this->message = sprintf('Image height must be at least %dpx', $this->constraints['min_height']);
            return false;
        }

        if ($height > $this->constraints['max_height']) {
            $this->message = sprintf('Image height must not exceed %dpx', $this->constraints['max_height']);
            return false;
        }

        return true;
    }

    private function validateRatio(): bool
    {
        if (empty($this->constraints['ratio'])) {
            return true;
        }

        $width = $this->dimensions['width'];
        $height = $this->dimensions['height'];

        if ($width === 0 || $height === 0) {
            return false;
        }

        list($ratioWidth, $ratioHeight) = explode(':', $this->constraints['ratio']);
        
        return abs(($width / $height) - ($ratioWidth / $ratioHeight)) < 0.01;
    }
}
