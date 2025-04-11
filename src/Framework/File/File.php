<?php

namespace Lightpack\File;

use DateTime;
use SplFileInfo;
use RuntimeException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class File
{
    public function info($path): ?SplFileInfo
    {
        if (!is_file($path)) {
            return null;
        }

        return new SplFileInfo($path);
    }

    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function isDir(string $path): bool
    {
        return is_dir($path);
    }

    public function read(string $path): ?string
    {
        $path = $this->sanitizePath($path);

        if (!$this->exists($path)) {
            return null;
        }

        if (!is_readable($path)) {
            throw new RuntimeException(
                sprintf("Permission denied to read file contents: %s", $path)
            );
        }

        return file_get_contents($path);
    }

    public function write(string $path, string $contents, $flags = LOCK_EX): bool
    {
        // Get directory path
        $directory = dirname($path);

        // Create directory if it doesn't exist
        if (!is_dir($directory)) {
            // recursive = true to create nested directories
            // 0755 = standard directory permissions
            mkdir($directory, 0755, true);
        }

        return file_put_contents($path, $contents, $flags) !== false;
    }

    public function delete(string $path): bool
    {
        if ($this->exists($path)) {
            return @unlink($path);
        }

        return false;
    }

    public function append(string $path, string $contents)
    {
        return $this->write($path, $contents, LOCK_EX | FILE_APPEND);
    }

    public function copy(string $source, string $destination): bool
    {
        if ($this->exists($source)) {
            return copy($source, $destination);
        }

        return false;
    }

    public function rename(string $old, string $new): bool
    {
        if ($this->copy($old, $new)) {
            return @unlink($old);
        }

        return false;
    }

    public function move(string $source, string $destination): bool
    {
        return $this->rename($source, $destination);
    }

    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    public function size(string $path, bool $format = false)
    {
        $bytes = filesize($path);

        if ($format === false) {
            return $bytes;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < 4; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . $units[$i];
    }

    public function modified(string $path, bool $format = false, string $dateFormat = 'M d, Y')
    {
        $timestamp = filemtime($path);

        if ($format) {
            $date = DateTime::createFromFormat('U', $timestamp);
            $timestamp = $date->format($dateFormat);
        }

        return $timestamp;
    }

    public function makeDir(string $path, int $mode = 0777): bool
    {
        if (!is_dir($path)) {
            if (!mkdir($path, $mode, true)) {
                throw new RuntimeException(
                    sprintf("Unable to create directory: %s", $path)
                );
            }
        }

        return true;
    }

    public function emptyDir(string $path)
    {
        $this->removeDir($path, false);
    }

    public function moveDir(string $source, string $destination): bool
    {
        return $this->copyDir($source, $destination, true);
    }

    public function removeDir(string $path, bool $delete = true)
    {
        if (!is_dir($path)) {
            return;
        }

        foreach ($this->getIterator($path) as $file) {
            if ($file->isDir()) {
                $this->removeDir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }

        if ($delete) {
            @rmdir($path);
        }
    }

    public function copyDir(string $source, string $destination, bool $delete = false): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        $this->makeDir($destination);

        foreach ($this->getRecursiveIterator($source) as $file) {
            $from = $file->getRealPath();
            $to = $destination . DIRECTORY_SEPARATOR . $file->getBasename();

            if ($file->isDir()) {
                if (!$this->copyDir($from, $to, $delete)) {
                    return false;
                }

                if ($delete) {
                    $this->removeDir($from);
                }
            } else {
                if (!copy($from, $to)) {
                    return false;
                }

                if ($delete) {
                    @unlink($from);
                }
            }
        }

        if ($delete) {
            $this->removeDir($source);
        }

        return true;
    }

    public function recent(string $path): ?SplFileInfo
    {
        $found = null;
        $timestamp = 0;

        foreach ($this->getIterator($path) as $file) {
            if ($timestamp < $file->getMTime()) {
                $found = $file;
                $timestamp = $file->getMTime();
            }
        }

        return $found;
    }

    /**
     * Get a list of all files in a directory.
     * 
     * This method returns an array of SplFileInfo objects for all files in the specified
     * directory. Note that this is a non-recursive listing - it only returns files in
     * the immediate directory.
     * 
     * Use cases:
     * - Getting a quick list of files in a directory
     * - When you need file information (size, permissions, etc.) for each file
     * - When you need to process files in a specific order (array can be sorted)
     * 
     * Example:
     * ```php
     * $files = $file->traverse('/path/to/dir');
     * foreach ($files as $filename => $fileInfo) {
     *     echo $filename . ': ' . $fileInfo->getSize();
     * }
     * ```
     * 
     * Note: For recursive directory traversal, use getRecursiveIterator() instead.
     * 
     * @param string $path The directory path to list files from
     * @return array<string,SplFileInfo>|null Array of SplFileInfo objects keyed by filename,
     *                                        or null if path is not a directory
     */
    public function traverse(string $path): ?array
    {
        if (!$this->isDir($path)) {
            return null;
        }

        $files = [];

        foreach ($this->getIterator($path) as $file) {
            $files[$file->getFilename()] = $file;
        }

        return $files;
    }

    /**
     * Get a non-recursive iterator for files in a directory.
     * 
     * This method returns an iterator that lists only the immediate contents
     * of a directory (files and subdirectories) without traversing into subdirectories.
     * It's useful for:
     * - Simple directory listings
     * - Performance-critical operations that only need top-level files
     * - Controlled manual traversal of directory structures
     * 
     * @param string $path The directory path to iterate over
     * @return FilesystemIterator|null Returns null if path is not a directory
     */
    public function getIterator(string $path): ?FilesystemIterator
    {
        if (!is_dir($path)) {
            return null;
        }

        return new FilesystemIterator($path);
    }

    /**
     * Get a recursive iterator for traversing a directory structure.
     * 
     * This method returns an iterator that traverses through all files and subdirectories
     * recursively. It's designed for operations that need to process the entire
     * directory tree, such as:
     * - File watching/monitoring
     * - Deep directory searches
     * - Complete directory backups
     * - Recursive file operations
     * 
     * The iterator uses SELF_FIRST mode, meaning it will visit directories
     * before their contents, which is useful for creating/copying directory structures.
     * 
     * @param string $path The directory path to recursively iterate over
     * @return RecursiveIteratorIterator|null Returns null if path is not a directory
     */
    public function getRecursiveIterator(string $path): ?RecursiveIteratorIterator
    {
        if (!is_dir($path)) {
            return null;
        }

        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
    }

    public function sanitizePath(string $path): string
    {
        // Replace both slashes with system separator
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);

        // Remove any parent directory traversal
        $path = str_replace('..', '', $path);

        return $path;
    }
}
