<?php

namespace Lightpack\Http;

class UploadValidation
{
    private $errors;
    private $rules;

    public function __construct(array $rules = [])
    {
        $this->errors = [];
        $this->setRules($rules);
    }

    public function setRules(array $rules)
    {
        $this->rules = [
            'mimes' => $rules['mimes'] ?? null,
            'min_size' => $rules['min_size'] ?? null,
            'max_size' => $rules['max_size'] ?? null,
            'width' => $rules['width'] ?? null,
            'height' => $rules['height'] ?? null,
            'min_width' => $rules['min_width'] ?? null,
            'max_width' => $rules['max_width'] ?? null,
            'min_height' => $rules['min_height'] ?? null,
            'max_height' => $rules['max_height'] ?? null,
            'extensions' => $rules['extensions'] ?? null,
        ];
    }

    public function validateMimes(string $value): self
    {
        $mimes = $this->rules['mimes'];

        if(!$mimes) {
            return $this;
        }

        if (!is_array($mimes) || !in_array($value, $mimes)) {
            $this->errors['mimes'] = 'Uploaded file must be one of ' . implode(', ', $mimes);;
        }

        return $this;
    }

    public function validateMinSize(int $value): self
    {
        $minSize = $this->rules['min_size'];

        if(!$minSize) {
            return $this;
        }

        $valueInBytes = $this->toBytes($value);

        if ($valueInBytes < $minSize) {
            $this->errors['min_size'] = "File size must be atleast {$value}";
        }

        return $this;
    }

    public function validateMaxSize(int $value): self
    {
        $maxSize = $this->rules['max_size'];

        if(!$maxSize) {
            return $this;
        }

        $valueInBytes = $this->toBytes($value);

        if ($valueInBytes > $maxSize) {
            $this->errors['max_size'] = "File size must be smaller than {$value}";
        }

        return $this;
    }

    public function validateMinWidth(int $value): self
    {
        $minWidth = $this->rules['min_width'];

        if(!$minWidth) {
            return $this;
        }

        if ($value < $minWidth) {
            $this->errors['min_width'] = "Image width must be atleast {$value}px";
        }

        return $this;
    }

    public function validateMaxWidth(int $value): self
    {
        $maxWidth = $this->rules['max_width'];

        if(!$maxWidth) {
            return $this;
        }

        if ($value > $maxWidth) {
            $this->errors['max_width'] = "Image width must be smaller than {$value}px";
        }

        return $this;
    }

    public function validateMinHeight(int $value): self
    {
        $minHeight = $this->rules['min_height'];

        if(!$minHeight) {
            return $this;
        }

        if ($value < $minHeight) {
            $this->errors['min_height'] = "Image height must be atleast {$value}px";
        }

        return $this;
    }

    public function validateMaxHeight(int $value): self
    {
        $maxHeight = $this->rules['max_height'];

        if(!$maxHeight) {
            return $this;
        }

        if ($value > $maxHeight) {
            $this->errors['max_height'] = "Image height must be smaller than {$value}px";
        }

        return $this;
    }

    public function validateWidth(int $value): self
    {
        $width = $this->rules['width'];

        if(!$width) {
            return $this;
        }

        if ($value !== $width) {
            $this->errors['width'] = "Image width must be exactly {$value}px";
        }

        return $this;
    }

    public function validateHeight(int $value): self
    {
        $height = $this->rules['height'];

        if(!$height) {
            return $this;
        }

        if ($value !== $height) {
            $this->errors['height'] = "Image height must be exactly {$value}px";
        }

        return $this;
    }

    public function validateExtensions(string $value): self
    {
        $extensions = $this->rules['extensions'];

        if(!$extensions) {
            return $this;
        }

        if (!is_array($extensions) || !in_array($value, $extensions)) {
            $this->errors['extensions'] = 'Uploaded file type must be one of ' . implode(', ', $extensions);
        }

        return $this;
    }

    public function hasError()
    {
        return !empty($this->errors);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getError(string $key)
    {
        return $this->errors[$key] ?? null;
    }

    private function toBytes(string $value)
    {
        $units = ['b' => 1, 'kb' => 1024, 'mb' => 1048576, 'gb' => 1073741824];
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;

        if(!isset($units[$unit])) {
            return $value;
        }

        return $value * $units[$unit];
    }
}