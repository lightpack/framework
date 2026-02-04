<?php

namespace Lightpack\Logger\Drivers;

use Lightpack\Logger\LoggerInterface;

class DailyFileLogger implements LoggerInterface
{
    private $basePath;
    private $daysToKeep;

    public function __construct(string $basePath, int $daysToKeep = 7)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->daysToKeep = $daysToKeep;
        
        // Create directory if it doesn't exist
        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    public function log($level, $message, array $context = [])
    {
        $logEntry = $this->formatLogEntry($level, $message, $context);
        $this->cleanupOldLogs();
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

    private function cleanupOldLogs(): void
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$this->daysToKeep} days"));
        $pattern = $this->basePath . '/lightpack-*.log';
        
        foreach (glob($pattern) as $file) {
            // Extract date from filename using regex
            if (preg_match('/lightpack-(\d{4}-\d{2}-\d{2})\.log$/', basename($file), $matches)) {
                $fileDate = $matches[1];
                
                // Delete if older than cutoff date
                if ($fileDate < $cutoffDate) {
                    @unlink($file);
                }
            }
        }
    }

    private function getCurrentLogFile(): string
    {
        return $this->basePath . '/lightpack-' . date('Y-m-d') . '.log';
    }

    private function writeLog($logEntry): void
    {
        file_put_contents($this->getCurrentLogFile(), $logEntry, FILE_APPEND | LOCK_EX);
    }
}
