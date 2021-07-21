<?php

namespace Lightpack\Http;

use Lightpack\Exceptions\FileUploadException;

class UploadedFile
{
    private $name;
    private $size;
    private $type;
    private $error;
    private $tmpName;
    private $errors = [
        UPLOAD_ERR_OK => 'There is no error, the file uploaded with success',
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
    ];

    public function __construct($file)
    {
        $this->name = $file['name'];
        $this->size = $file['size'];
        $this->type = $file['type'];
        $this->error = $file['error'];
        $this->tmpName = $file['tmp_name'];
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

    public function getError(): string
    {
        return $this->errors[$this->error] ?? 'Unknown upload error';
    }

    public function getTmpName(): string
    {
        return $this->tmpName;
    }

    public function hasError(): bool
    {
        return UPLOAD_ERR_OK !== $this->error;
    }

    public function move(string $destination, string $name = null): void
    {
        if ($this->hasError()) {
            throw new FileUploadException($this->getError());
        }

        if (is_dir($destination)) {
            if (!is_writable($destination)) {
                throw new FileUploadException('Upload directory does not have sufficient write permission: ' . $destination);
            }
        } elseif (!mkdir($destination, 0777, true)) {
            throw new FileUploadException('Could not create upload directory: ' . $destination);
        }

        $this->processUpload($name ?? $this->name, $destination);
    }

    private function processUpload(string $name, string $destination): void
    {
        $targetPath = rtrim($destination, '\\/') . '/' . $name;
        $success = move_uploaded_file($this->tmpName, $targetPath);

        if (!$success) {
            throw new FileUploadException('Could not upload the file.');
        }
    }
}
