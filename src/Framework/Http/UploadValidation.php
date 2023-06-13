<?php

namespace Lightpack\Http;

class UploadValidation
{
    private array $errors = [];
    private array $rules = [];

    public function __construct(?string $rules = null)
    {
        if ($rules) {
            $this->setRules($rules);
        }
    }

    public function setRules(string $rules)
    {
        if(empty($rules)) {
            return;
        }

        $rulePairs = explode('|', $rules);
        $parsedRules = [];

        foreach ($rulePairs as $rulePair) {
            if (strpos($rulePair, ':') !== false) {
                $parts = explode(':', $rulePair, 2);

                if (count($parts) !== 2) {
                    throw new \InvalidArgumentException("Invalid rule format: $rulePair");
                }

                [$rule, $value] = $parts;
                $parsedRules[$rule] = $value;
            } else {
                $parsedRules[$rulePair] = null;
            }
        }

        $this->rules = [
            'mimes' => $parsedRules['mimes'] ?? null,
            'min_size' => $parsedRules['min_size'] ?? null,
            'max_size' => $parsedRules['max_size'] ?? null,
            'ratio' => $parsedRules['ratio'] ?? null,
            'width' => $parsedRules['width'] ?? null,
            'height' => $parsedRules['height'] ?? null,
            'min_width' => $parsedRules['min_width'] ?? null,
            'max_width' => $parsedRules['max_width'] ?? null,
            'min_height' => $parsedRules['min_height'] ?? null,
            'max_height' => $parsedRules['max_height'] ?? null,
            'extensions' => $parsedRules['extensions'] ?? null,
        ];
    }

    public function getRules(): array
    {
        return $this->rules;
    }
    public function validateMimes(string $value): self
    {
        $mimes = $this->rules['mimes'] ?? null;

        if (!$mimes) {
            return $this;
        }

        $mimes = explode(',', $mimes);
        $value = trim($value);

        if (!in_array($value, $mimes)) {
            $this->errors['mimes'] = 'Uploaded file must be one of ' . implode(', ', $mimes);
        }

        return $this;
    }

    public function validateExtensions(string $value): self
    {
        $extensions = $this->rules['extensions'] ?? null;

        if (!$extensions) {
            return $this;
        }

        $extensions = explode(',', $extensions);
        $value = trim($value);

        if (!in_array($value, $extensions)) {
            $this->errors['extensions'] = 'Uploaded file type must be one of ' . implode(', ', $extensions);
        }

        return $this;
    }

    public function validateMinSize($value, string $unit = 'kb'): self
    {
        $minSize = $this->rules['min_size'] ?? null;

        if (!$minSize) {
            return $this;
        }

        $minSize = $this->formatSize($minSize, $unit);

        if ($value < $minSize) {
            $this->errors['min_size'] = "File size must be at least {$this->formatSizeForDisplay($minSize)}";
        }

        return $this;
    }

    public function validateMaxSize($value, string $unit = 'kb'): self
    {
        $maxSize = $this->rules['max_size'] ?? null;

        if (!$maxSize) {
            return $this;
        }

        $maxSize = $this->formatSize($maxSize, $unit);

        if ($value > $maxSize) {
            $this->errors['max_size'] = "File size must be smaller than {$this->formatSizeForDisplay($maxSize)}";
        }

        return $this;
    }

    public function validateMinWidth(int $value): self
    {
        $minWidth = $this->rules['min_width'] ?? null;

        if (!$minWidth) {
            return $this;
        }

        if ($value < $minWidth) {
            $this->errors['min_width'] = "Image width must be atleast {$value}px";
        }

        return $this;
    }

    public function validateMaxWidth(int $value): self
    {
        $maxWidth = $this->rules['max_width'] ?? null;

        if (!$maxWidth) {
            return $this;
        }

        if ($value > $maxWidth) {
            $this->errors['max_width'] = "Image width must be smaller than {$value}px";
        }

        return $this;
    }

    public function validateMinHeight(int $value): self
    {
        $minHeight = $this->rules['min_height'] ?? null;

        if (!$minHeight) {
            return $this;
        }

        if ($value < $minHeight) {
            $this->errors['min_height'] = "Image height must be atleast {$value}px";
        }

        return $this;
    }

    public function validateMaxHeight(int $value): self
    {
        $maxHeight = $this->rules['max_height'] ?? null;

        if (!$maxHeight) {
            return $this;
        }

        if ($value > $maxHeight) {
            $this->errors['max_height'] = "Image height must be smaller than {$value}px";
        }

        return $this;
    }

    public function validateWidth(int $value): self
    {
        $width = $this->rules['width'] ?? null;

        if (!$width) {
            return $this;
        }

        if ($value !== $width) {
            $this->errors['width'] = "Image width must be exactly {$value}px";
        }

        return $this;
    }

    public function validateHeight(int $value): self
    {
        $height = $this->rules['height'] ?? null;

        if (!$height) {
            return $this;
        }

        if ($value !== $height) {
            $this->errors['height'] = "Image height must be exactly {$value}px";
        }

        return $this;
    }

    public function validateRatio(float $width, float $height): self
    {
        $ratio = $this->rules['ratio'] ?? null;

        if (empty($ratio)) {
            return $this;
        }

        [$numerator, $denominator] = explode('/', $ratio);

        if ($denominator == 0) {
            return $this;
        }

        $expectedRatio = $numerator / $denominator;
        $calculatedRatio = $width / $height;

        if ($calculatedRatio !== $expectedRatio) {
            $this->errors['ratio'] = "Image ratio must be {$ratio}";
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

    private function formatSize($value, string $unit)
    {
        $unit = strtolower($unit);
        $units = [
            'bytes' => 1,
            'kb' => 1024,
            'mb' => 1048576,
            'gb' => 1073741824,
        ];

        return $value * $units[$unit];
    }

    private function formatSizeForDisplay($value)
    {
        $units = ['bytes', 'KB', 'MB', 'GB'];
        $step = 1024;
        $i = 0;

        while (($value / $step) >= 1) {
            $value /= $step;
            $i++;
        }

        return round($value, 2) . ' ' . $units[$i];
    }
}
