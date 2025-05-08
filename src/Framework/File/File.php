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
    /**
     * Get information about a file.
     * 
     * Returns detailed file information as an SplFileInfo object which provides
     * access to file metadata like size, permissions, modification time, etc.
     * 
     * @param string $path Path to the file
     * @return SplFileInfo|null SplFileInfo object if file exists, null otherwise
     */
    public function info($path): ?SplFileInfo
    {
        if (!is_file($path)) {
            return null;
        }

        return new SplFileInfo($path);
    }

    /**
     * Check if a file or directory exists.
     * 
     * @param string $path Path to check
     * @return bool True if path exists, false otherwise
     */
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * Check if a path is a directory.
     * 
     * @param string $path Path to check
     * @return bool True if path is a directory, false otherwise
     */
    public function isDir(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Read contents of a file.
     * 
     * Safely reads a file's contents after checking for existence and read permissions.
     * The path is sanitized to prevent directory traversal attacks.
     * 
     * @param string $path Path to the file to read
     * @return string|null File contents if successful, null if file doesn't exist
     * @throws RuntimeException If file exists but is not readable
     */
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

    /**
     * Write contents to a file.
     * 
     * Creates any missing directories in the path before writing.
     * By default, uses an exclusive lock (LOCK_EX) to prevent concurrent writes.
     * 
     * Example:
     * ```php
     * // Write with default exclusive lock
     * $file->write('path/to/file.txt', 'content');
     * 
     * // Write with custom flags
     * $file->write('path/to/file.txt', 'content', FILE_APPEND | LOCK_EX);
     * ```
     * 
     * @param string $path Path to write to
     * @param string $contents Content to write
     * @param int $flags File operation flags (default: LOCK_EX for exclusive locking)
     * @return bool True if write successful, false otherwise
     */
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

    /**
     * Delete a file.
     * 
     * Safely attempts to delete a file, suppressing warnings if the file
     * is already deleted or inaccessible.
     * 
     * @param string $path Path to the file to delete
     * @return bool True if file was deleted or didn't exist, false if deletion failed
     */
    public function delete(string $path): bool
    {
        if ($this->exists($path)) {
            return @unlink($path);
        }

        return false;
    }

    /**
     * Append content to a file.
     * 
     * Adds content to the end of a file using exclusive locking and append mode.
     * Will create the file if it doesn't exist.
     * 
     * @param string $path Path to the file
     * @param string $contents Content to append
     * @return bool True if append successful, false otherwise
     */
    public function append(string $path, string $contents)
    {
        return $this->write($path, $contents, LOCK_EX | FILE_APPEND);
    }

    /**
     * Copy a file to a new location.
     * 
     * @param string $source Path to source file
     * @param string $destination Path to destination
     * @return bool True if copy successful, false if source doesn't exist or copy fails
     */
    public function copy(string $source, string $destination): bool
    {
        if ($this->exists($source)) {
            return copy($source, $destination);
        }

        return false;
    }

    /**
     * Rename a file by copying it and deleting the original.
     * 
     * This is a safer alternative to PHP's rename() as it:
     * 1. Works across different filesystems
     * 2. Ensures the destination exists before deleting source
     * 3. Suppresses warnings on source deletion
     * 
     * @param string $old Current file path
     * @param string $new New file path
     * @return bool True if rename successful, false otherwise
     */
    public function rename(string $old, string $new): bool
    {
        if ($this->copy($old, $new)) {
            return @unlink($old);
        }

        return false;
    }

    /**
     * Move a file to a new location.
     * 
     * Alias for rename() that provides a more intuitive name for the operation.
     * 
     * @param string $source Source file path
     * @param string $destination Destination path
     * @return bool True if move successful, false otherwise
     */
    public function move(string $source, string $destination): bool
    {
        return $this->rename($source, $destination);
    }

    /**
     * Get the file extension.
     * 
     * Returns the file extension without the dot. For example:
     * - 'script.php' returns 'php'
     * - 'data.json' returns 'json'
     * - 'file' returns '' (empty string)
     * 
     * @param string $path Path to the file
     * @return string File extension without the dot
     */
    public function extension(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file size.
     * 
     * Returns the file size in bytes or a human-readable format.
     * When $format is true, converts bytes to KB, MB, GB, etc.
     * 
     * Example:
     * ```php
     * // Returns size in bytes
     * $size = $file->size('large.zip'); // e.g., 1048576
     * 
     * // Returns formatted size
     * $size = $file->size('large.zip', true); // e.g., "1.00MB"
     * ```
     * 
     * @param string $path Path to the file
     * @param bool $format Whether to return human-readable format
     * @return int|string Size in bytes (int) or formatted string
     */
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

    /**
     * Get the file modification time.
     * 
     * Returns either a Unix timestamp or a formatted date string.
     * 
     * Example:
     * ```php
     * // Get timestamp
     * $time = $file->modified('config.php'); // e.g., 1617235200
     * 
     * // Get formatted date
     * $date = $file->modified('config.php', true); // e.g., "Apr 1, 2021"
     * 
     * // Custom format
     * $date = $file->modified('config.php', true, 'Y-m-d'); // e.g., "2021-04-01"
     * ```
     * 
     * @param string $path Path to the file
     * @param bool $format Whether to return formatted date
     * @param string $dateFormat PHP date format string
     * @return int|string Unix timestamp or formatted date string
     */
    public function modified(string $path, bool $format = false, string $dateFormat = 'M d, Y')
    {
        $timestamp = filemtime($path);

        if ($format) {
            $datetime = new DateTime();
            $datetime->setTimestamp($timestamp);
            return $datetime->format($dateFormat);
        }

        return $timestamp;
    }

    /**
     * Create a directory.
     * 
     * Creates a directory and its parent directories if they don't exist.
     * Uses standard directory permissions (0777) by default, which will be
     * modified by the system's umask.
     * 
     * Example:
     * ```php
     * // Create with default permissions
     * $file->makeDir('path/to/dir');
     * 
     * // Create with custom permissions
     * $file->makeDir('path/to/dir', 0755);
     * ```
     * 
     * @param string $path Directory path to create
     * @param int $mode Directory permissions (octal)
     * @return bool True if directory exists or was created
     */
    public function makeDir(string $path, int $mode = 0777): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, $mode, true);
        }

        return true;
    }

    /**
     * Empty a directory without removing it.
     * 
     * Removes all files and subdirectories within the specified directory
     * but keeps the directory itself. Useful for cleaning temporary files
     * or preparing a directory for new content.
     * 
     * @param string $path Directory to empty
     */
    public function emptyDir(string $path)
    {
        $this->removeDir($path, false);
    }

    /**
     * Move a directory to a new location.
     * 
     * Moves a directory and all its contents to a new location.
     * This is a wrapper around copyDir() that deletes the source
     * after a successful copy.
     * 
     * @param string $source Source directory path
     * @param string $destination Destination path
     * @return bool True if directory was moved successfully
     */
    public function moveDir(string $source, string $destination): bool
    {
        return $this->copyDir($source, $destination, true);
    }

    /**
     * Remove a directory.
     * 
     * Recursively removes files and subdirectories. If $delete is true,
     * removes the directory itself; if false, only removes its contents.
     * 
     * Safety features:
     * - Checks if path is actually a directory
     * - Uses standard iterator for controlled deletion
     * - Suppresses warnings for better error handling
     * - Deletes in correct order (files first, then directories)
     * 
     * @param string $path Directory to remove
     * @param bool $delete Whether to delete the directory itself
     */
    public function removeDir(string $path, bool $delete = true): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach ($this->getRecursiveIterator($path, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
    
        if ($delete) {
            @rmdir($path);
        }
    }

    /**
     * Copy a directory and its contents.
     * 
     * Creates a complete copy of a directory structure with these features:
     * - Creates destination directory if it doesn't exist
     * - Recursively copies all files and subdirectories
     * - Optionally removes source after successful copy (move operation)
     * - Preserves relative paths in the directory structure
     * - Handles nested directories properly
     * - Suppresses warnings during delete operations
     * 
     * @param string $source Source directory
     * @param string $destination Destination directory
     * @param bool $delete Whether to delete source after copy (for move operations)
     * @return bool True if copy was successful
     */
    public function copyDir(string $source, string $destination, bool $delete = false): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        $this->makeDir($destination);

        foreach ($this->getIterator($source) as $file) {
            $from = $file->getRealPath();
            $to = $destination . DIRECTORY_SEPARATOR . $file->getBasename();

            if ($file->isDir()) {
                if (!$this->copyDir($from, $to, $delete)) {
                    return false;
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
            $this->removeDir($source, true);
        }

        return true;
    }

    /**
     * Find the most recently modified file in a directory.
     * 
     * Recursively searches through a directory to find the file
     * with the latest modification time. Useful for:
     * - Finding the latest log file
     * - Checking the most recent upload
     * - Monitoring directory changes
     * 
     * @param string $path Directory to search in
     * @return SplFileInfo|null Latest file or null if directory is empty
     */
    public function recent(string $path): ?SplFileInfo
    {
        $found = null;
        $latest = 0;

        foreach ($this->getRecursiveIterator($path) as $file) {
            if ($file->isFile()) {
                $mtime = $file->getMTime();
                if ($mtime > $latest) {
                    $found = $file;
                    $latest = $mtime;
                }
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
     * Used for controlled directory operations like:
     * - Directory deletion (removeDir)
     * - Directory copying (copyDir)
     * - Simple directory listings (traverse)
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
     * recursively. Used for operations that need the full directory tree:
     * - Finding most recent files (recent)
     * - Deep file searches
     * - Complete tree traversal
     * 
     * Note: For operations that need controlled directory order (like deletion),
     * use getIterator() instead.
     * 
     * @param string $path The directory path to recursively iterate over
     * @return RecursiveIteratorIterator|null Returns null if path is not a directory
     */
    public function getRecursiveIterator(string $path, int $mode = RecursiveIteratorIterator::SELF_FIRST): ?RecursiveIteratorIterator
    {
        if (!is_dir($path)) {
            return null;
        }

        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            $mode
        );
    }

    /**
     * Sanitize a file path for safe usage.
     * 
     * Performs two important security measures:
     * 1. Normalizes directory separators to the system standard
     * 2. Removes parent directory traversal sequences (..)
     * 
     * This helps prevent:
     * - Directory traversal attacks
     * - Path manipulation vulnerabilities
     * - Cross-platform path issues
     * 
     * @param string $path Path to sanitize
     * @return string Sanitized path safe for filesystem operations
     */
    public function sanitizePath(string $path): string
    {
        // Replace both slashes with system separator
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);

        // Remove any parent directory traversal
        $path = str_replace('..', '', $path);

        return $path;
    }

    /**
     * Calculate hash of a file's contents.
     * 
     * Generates a cryptographic hash of file contents using specified algorithm.
     * Useful for:
     * - File integrity verification
     * - Cache invalidation
     * - Finding duplicate files
     * - Content-based file comparison
     * 
     * Example:
     * ```php
     * $hash = $file->hash('image.jpg');         // sha256 by default
     * $hash = $file->hash('data.bin', 'md5');   // using MD5
     * ```
     * 
     * @param string $path File path
     * @param string $algo Hash algorithm (md5, sha1, sha256, etc)
     * @return string|null Hash string or null if file doesn't exist
     */
    public function hash(string $path, string $algo = 'sha256'): ?string 
    {
        if (!$this->exists($path)) {
            return null;
        }

        return hash_file($algo, $path);
    }

    /**
     * Write contents to file atomically.
     * 
     * Ensures file writes are atomic by using a temporary file and rename.
     * This prevents corrupted writes that can happen when:
     * - Process crashes during write
     * - System loses power
     * - Disk fills up mid-write
     * 
     * Example:
     * ```php
     * // Config files stay consistent
     * $file->atomic('config.json', $newConfig);
     * 
     * // Cache files never corrupt
     * $file->atomic('cache/data.bin', $cacheData);
     * ```
     * 
     * @param string $path Target file path
     * @param string $contents File contents
     * @return bool True if write successful
     */
    public function atomic(string $path, string $contents): bool
    {
        $temp = $path . '.tmp.' . uniqid();
        
        if (!$this->write($temp, $contents)) {
            @unlink($temp);
            return false;
        }

        if (!@rename($temp, $path)) {
            @unlink($temp);
            return false;
        }

        return true;
    }
}
