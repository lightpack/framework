<?php

namespace Lightpack\Storage;

interface StorageInterface
{
    /**
     * Read contents from a file
     */
    public function read(string $path): ?string;

    /**
     * Write contents to a file
     */
    public function write(string $path, string $contents): bool;

    /**
     * Delete a file
     */
    public function delete(string $path): bool;

    /**
     * Check if a file exists
     */
    public function exists(string $path): bool;

    /**
     * Copy a file from source to destination
     */
    public function copy(string $source, string $destination): bool;

    /**
     * Move a file from source to destination
     */
    public function move(string $source, string $destination): bool;
}
