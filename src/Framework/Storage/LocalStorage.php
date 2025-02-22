<?php

namespace Lightpack\Storage;

use Lightpack\Exceptions\FileUploadException;
use Lightpack\File\File;

class LocalStorage extends File implements Storage
{
    /**
     * Store an uploaded file using move_uploaded_file() for security
     * 
     * @throws FileUploadException on failure
     */
    public function store(string $source, string $destination): void
    {
        // For test purposes.
        $success = isset($_SERVER['X_LIGHTPACK_TEST_UPLOAD']) 
            ? copy($source, $destination)
            : move_uploaded_file($source, $destination);

        if (!$success) {
            throw new FileUploadException('Could not upload the file.');
        }
    }
}
