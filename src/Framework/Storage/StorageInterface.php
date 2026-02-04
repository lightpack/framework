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

    /**
     * Store an uploaded file
     * 
     * @throws FileUploadException on failure
     */
    public function store(string $source, string $destination): void;
    
    /**
     * Get a URL for accessing the file
     * 
     * For public files, this returns a direct URL
     * For private files, this returns a temporary URL with the specified expiration
     * 
     * @param string $path The path to the file
     * @param int $expiration Expiration time in seconds (for private files)
     * @return string The URL to access the file
     */
    public function url(string $path, int $expiration = 3600): string;
    
    /**
     * List all files in a directory
     * 
     * @param string $directory The directory path to list files from
     * @param bool $recursive Whether to include files in subdirectories
     * @return array An array of file paths within the directory
     */
    public function files(string $directory, bool $recursive = true): array;

    /**
     * Remove a directory and its contents.
     *
     * Recursively deletes all files and subdirectories within the given directory.
     * For remote storage (e.g., S3), deletes all objects with the specified prefix.
     *
     * @param string $directory The directory path or prefix to remove
     * @param bool $delete Whether to remove the directory itself (if applicable)
     * @return void
     */
    public function removeDir(string $directory, bool $delete = true): void;
}
