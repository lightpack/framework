<?php

namespace Lightpack\Http;

class Files
{
    private array $files = [];

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
