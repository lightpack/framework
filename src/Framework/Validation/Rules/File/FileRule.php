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
        // Not a file upload
        if (!is_array($value) || !isset($value['tmp_name'], $value['error'])) {
            return false;
        }

        // Check for upload errors
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

    public function getMessage(): string 
    {
        return $this->message;
    }
}
