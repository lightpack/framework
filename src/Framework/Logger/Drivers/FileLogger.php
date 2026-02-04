<?php

namespace Lightpack\Logger\Drivers;

use Lightpack\Logger\LoggerInterface;

class FileLogger implements LoggerInterface
{
    private $filename;
    private $maxFileSize; // max log file in bytes
    private $maxLogFiles; // max log files to retain

    public function __construct(string $filename, int $maxFileSize = 10 * 1024 * 1024, int $maxLogFiles = 10)
    {
        $this->filename = $filename;
        $this->maxFileSize = $maxFileSize;
        $this->maxLogFiles = $maxLogFiles;
    }

    public function log($level, $message, array $context = [])
    {
        $logEntry = $this->formatLogEntry($level, $message, $context);
        $this->rotateLog();
        $this->writeLog($logEntry);
    }

    private function formatLogEntry($level, $message, $context)
    {
        $level = strtoupper($level);
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = str_repeat('-', 80) . PHP_EOL;
        $logEntry .= "[$timestamp] $level: $message" . PHP_EOL;

        if (!empty($context)) {
            if (isset($context['stack_trace'])) {
                $trace = $context['stack_trace'];
                unset($context['stack_trace']);
                $logEntry .= "File: {$trace['file']}:{$trace['line']}" . PHP_EOL;
                $logEntry .= "Stack Trace:" . PHP_EOL . $trace['trace'] . PHP_EOL;
            }
            
            if (!empty($context)) {
                $logEntry .= "Context:" . PHP_EOL;
                $logEntry .= json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
            }
        }
        
        return $logEntry;
    }

    private function rotateLog()
    {
        if (file_exists($this->filename) && filesize($this->filename) >= $this->maxFileSize) {
            $this->rotateLogFile();
            $this->deleteExcessLogFiles();
            $this->createNewLogFile();
        }
    }

    private function rotateLogFile()
    {
        $timestamp = date('YmdHis');
        $fileInfo = pathinfo($this->filename);
        $baseName = $fileInfo['filename'];
        $rotatedFile = "{$fileInfo['dirname']}/{$baseName}.{$timestamp}.{$fileInfo['extension']}";
        rename($this->filename, $rotatedFile);
    }

    private function deleteExcessLogFiles()
    {
        $fileInfo = pathinfo($this->filename);
        $baseName = $fileInfo['filename'];
        $logFiles = glob("{$fileInfo['dirname']}/{$baseName}.*");
        $logFilesCount = count($logFiles);

        if ($logFilesCount > $this->maxLogFiles) {
            $this->sortLogFilesByCreationTime($logFiles);
            $this->deleteOldestLogFiles($logFiles, $logFilesCount);
        }
    }

    private function sortLogFilesByCreationTime(array &$logFiles)
    {
        usort($logFiles, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });
    }

    private function deleteOldestLogFiles(array $logFiles, int $logFilesCount)
    {
        $filesToDelete = $logFilesCount - $this->maxLogFiles;

        for ($i = 0; $i < $filesToDelete; $i++) {
            unlink($logFiles[$i]);
        }
    }

    private function createNewLogFile()
    {
        touch($this->filename);
    }

    private function writeLog($logEntry)
    {
        file_put_contents($this->filename, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
