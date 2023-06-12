<?php

namespace Lightpack\Http;

class Files
{
    private $files;

    public function __construct(array $files = [])
    {
        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $this->files[$key] = $this->populateUploadedFiles($file);
            } else {
                $this->files[$key] = new UploadedFile($file);
            }
        }
    }

    public function get(?string $key = null)
    {
        if ($key === null) {
            return $this->files;
        }

        return $this->files[$key] ?? null;
    }

    public function has(string $key)
    {
        return isset($this->files[$key]);
    }

    /**
     * Checks if the file or files associated with the given key are empty. An empty 
     * file typically means that no file was selected for uploading.
     *
     * @param string $key The key associated with the file or files to be checked.
     * @return bool Returns true if the file or files are not empty, false otherwise.
     */
    public function isEmpty(string $key): bool
    {
        return $this->isNotEmpty($key) == false;
    }

    /**
      * Checks if the file or files associated with the given key are not empty. An empty 
     * file typically means that no file was selected for uploading.
     *
     * @param string $key The key associated with the file or files to be checked.
     * @return bool Returns true if the file or files are not empty, false otherwise.
     */
    public function isNotEmpty(string $key): bool
    {
        $file = $this->get($key);
        
        if ($file instanceof UploadedFile) {
            return $file->getError() != UPLOAD_ERR_NO_FILE;
        }

        if (is_array($file)) {
            foreach ($file as $uploadedFile) {
                if ($uploadedFile->getError() == UPLOAD_ERR_NO_FILE) {
                    return false;
                }
            }
        }

        return true;
    }

    private function populateUploadedFiles(array $files)
    {
        $uploads = [];

        foreach (array_keys($files['name']) as $key) {
            $uploads[] = new UploadedFile([
                'name' => $files['name'][$key],
                'size' => $files['size'][$key],
                'type' => $files['type'][$key],
                'error' => $files['error'][$key],
                'tmp_name' => $files['tmp_name'][$key],
            ]);
        }

        return $uploads;
    }
}
