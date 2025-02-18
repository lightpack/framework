<?php

declare(strict_types=1);

namespace Lightpack\Validation\Rules\File;

class FileRule
{
    private string $message;
    
    private array $errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload',
    ];

    public function __construct()
    {
        $this->message = 'Invalid file upload';
    }

    public function __invoke($value, array $data = []): bool 
    {
        // Handle non-array value
        if (!is_array($value)) {
            return false;
        }

        // Handle nested file array structure
        if (isset($value['tmp_name']) && is_array($value['tmp_name'])) {
            // This is a nested file structure
            return $this->validateNestedFiles($value);
        }

        // Handle single file structure
        return $this->validateSingleFile($value);
    }

    private function validateSingleFile(array $value): bool
    {
        // Not a file upload
        if (!isset($value['tmp_name'], $value['error'])) {
            return false;
        }

        // For optional fields, UPLOAD_ERR_NO_FILE is valid
        if ($value['error'] === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Check for other upload errors
        if ($value['error'] !== UPLOAD_ERR_OK) {
            $this->message = $this->errors[$value['error']] ?? 'Unknown upload error';
            return false;
        }

        // Security check
        if (!is_uploaded_file($value['tmp_name'])) {
            $this->message = 'Invalid file upload';
            return false;
        }

        return true;
    }

    private function validateNestedFiles(array $value): bool
    {
        // Ensure all required keys exist
        if (!isset($value['tmp_name'], $value['error'])) {
            return false;
        }

        // For nested files, we'll validate each file
        $files = array_keys($value['tmp_name']);
        foreach ($files as $key) {
            $singleFile = [
                'tmp_name' => $value['tmp_name'][$key],
                'error' => $value['error'][$key],
                'name' => $value['name'][$key],
                'type' => $value['type'][$key],
                'size' => $value['size'][$key],
            ];

            if (!$this->validateSingleFile($singleFile)) {
                return false;
            }
        }

        return true;
    }

    public function getMessage(): string 
    {
        return $this->message;
    }
}
