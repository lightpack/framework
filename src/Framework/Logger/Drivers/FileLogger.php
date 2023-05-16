<?php

namespace Lightpack\Logger\Drivers;

use Lightpack\Logger\ILogger;

class FileLogger implements ILogger
{
    private $filename;
    private $maxFileSize; // Maximum file size in bytes (default: 10 MB)
    private $maxLogFiles; // Maximum number of log files to keep

    public function __construct(string $filename, int $maxFileSize = 200, int $maxLogFiles = 5)
    {
        $this->filename = $filename;
        $this->maxFileSize = $maxFileSize;
        $this->maxLogFiles = $maxLogFiles;
    }

    public function log($level, $message, array $context = [])
    {
        $content = date('Y-m-d H:i:s') . " $level : " . $message . PHP_EOL;

        if ($context) {
            $content .= 'Context: ' . json_encode($context) . PHP_EOL;
        }

        $this->rotateLog();

        file_put_contents($this->filename, $content, LOCK_EX | FILE_APPEND);
    }

    private function rotateLog()
    {
        if (file_exists($this->filename) && filesize($this->filename) >= $this->maxFileSize) {
            // Rename the current log file
            $rotatedFile = $this->filename . '.' . date('YmdHis');
            rename($this->filename, $rotatedFile);

            // Delete excess log files if the maximum limit is reached
            $this->deleteExcessLogFiles();

            // Create a new log file
            touch($this->filename);
        }
    }

    private function deleteExcessLogFiles()
    {
        $logFiles = glob($this->filename . '.*');
        $logFilesCount = count($logFiles);

        if ($logFilesCount > $this->maxLogFiles) {
            // Sort log files by creation time (oldest first)
            usort($logFiles, function ($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            $filesToDelete = $logFilesCount - $this->maxLogFiles;

            for ($i = 0; $i < $filesToDelete; $i++) {
                unlink($logFiles[$i]);
            }
        }
    }
}
