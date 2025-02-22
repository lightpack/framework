<?php

namespace Lightpack\Storage;

use Lightpack\Exceptions\FileUploadException;
use Lightpack\File\File;

class LocalStorage extends File implements Storage
{
    /**
     * Store an uploaded file using move_uploaded_file() for security
     */
    public function store(string $source, string $destination): bool
    {
        // For test purposes.
        if(isset($_SERVER['X_LIGHTPACK_TEST_UPLOAD'])) {
            $success = copy($source, $destination);
        } else {
            $success = move_uploaded_file($source, $destination);
        }

        if (!$success) {
            throw new FileUploadException('Could not upload the file.');
        }

        return $success;
    }
}
