<?php

namespace Lightpack\Http;

use Lightpack\Exceptions\FileUploadException;
use Lightpack\Validator\Validator;

class UploadedFile
{
    private $name;
    private $size;
    private $type;
    private $error;
    private $tmpName;
    private $validation;
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
        $this->validation = new UploadValidation([]);
    }

    public function setRules(array $rules): self
    {
        $this->validation->setRules($rules);

        return $this;
    }

    public function failsValidation(): bool
    {
        $this->validation->validateMimes($this->getType());
        $this->validation->validateExtensions($this->getExtension());
        $this->validation->validateMinWidth($this->getWidth());
        $this->validation->validateMaxWidth($this->getWidth());
        $this->validation->validateMinHeight($this->getHeight());
        $this->validation->validateMaxHeight($this->getHeight());
        $this->validation->validateWidth($this->getWidth());
        $this->validation->validateHeight($this->getHeight());
        $this->validation->validateMinSize($this->getSize());
        $this->validation->validateMaxSize($this->getSize());

        if ($this->validation->hasError()) {
            return true;
        }

        return false;
    }

    public function getValidationErrors()
    {
        return $this->validation->getErrors();
    }

    public function getValidationError(string $rule)
    {
        return $this->validation->getError($rule);
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

    public function getRules()
    {
        return $this->rules;
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

    public function isEmpty(): bool
    {
        return empty($this->getName());
    }

    public function hasError(): bool
    {
        return UPLOAD_ERR_OK !== $this->error;
    }

    public function move(string $destination, string $name = null): void
    {
        if($this->failsValidation()) {
            throw new FileUploadException('Uploaded file fails validation rules.');
        }

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
