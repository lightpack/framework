<?php

declare(strict_types=1);

namespace Lightpack\Validation\Traits;

trait FileUploadValidationTrait
{
    private function isSingleFileUpload($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        // Check for required keys in single file upload
        $requiredKeys = ['name', 'type', 'tmp_name', 'error', 'size'];
        foreach ($requiredKeys as $key) {
            if (!isset($value[$key])) {
                return false;
            }
            // For single file upload, these values should not be arrays
            if (is_array($value[$key])) {
                return false;
            }
        }

        return true;
    }

    private function isMultiUpload($value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        // Empty array is considered a multi-upload with no files
        if (empty($value)) {
            return true;
        }

        // Check for HTML5 multiple file input format
        // ['name' => ['file1.jpg', 'file2.jpg'], 'type' => [...], ...]
        if (isset($value['name']) && is_array($value['name'])) {
            // Check all required keys are arrays
            $requiredKeys = ['name', 'type', 'tmp_name', 'error', 'size'];
            foreach ($requiredKeys as $key) {
                if (!isset($value[$key]) || !is_array($value[$key])) {
                    return false;
                }
                // All arrays should have the same length
                if (count($value[$key]) !== count($value['name'])) {
                    return false;
                }
            }
            return true;
        }

        // Check for single file with UPLOAD_ERR_NO_FILE
        // This is also considered a valid multi-upload with no files
        if (isset($value['error']) && $value['error'] === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        return false;
    }

    private function isEmptySingleFileUpload($value): bool
    {
        if (!$this->isSingleFileUpload($value)) {
            return false;
        }

        // Check for UPLOAD_ERR_NO_FILE
        if ($value['error'] === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Check for empty values in all fields
        return empty($value['name']) &&
            empty($value['type']) &&
            empty($value['tmp_name']) &&
            $value['size'] === 0;
    }

    private function isEmptyMultiFileUpload($value): bool
    {
        if (!$this->isMultiUpload($value)) {
            return false;
        }

        // Empty array is considered empty
        if (empty($value)) {
            return true;
        }

        // Single file with UPLOAD_ERR_NO_FILE is considered empty
        if (isset($value['error']) && !is_array($value['error']) && $value['error'] === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // For HTML5 multiple file input format
        if (isset($value['name']) && is_array($value['name'])) {
            // Empty if no files in the arrays
            if (empty($value['name'])) {
                return true;
            }

            // Or if all files have UPLOAD_ERR_NO_FILE
            if (isset($value['error']) && is_array($value['error'])) {
                foreach ($value['error'] as $error) {
                    if ($error !== UPLOAD_ERR_NO_FILE) {
                        return false;
                    }
                }
                return true;
            }
        }

        return false;
    }
}
