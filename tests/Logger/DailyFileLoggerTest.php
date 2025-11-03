<?php

declare(strict_types=1);

use Lightpack\Logger\Logger;
use PHPUnit\Framework\TestCase;
use Lightpack\Logger\Drivers\DailyFileLogger;

final class DailyFileLoggerTest extends TestCase
{
    private $logDir;

    public function setUp(): void
    {
        $this->logDir = __DIR__ . '/tmp';
        mkdir($this->logDir);
    }

    public function tearDown(): void
    {
        array_map('unlink', glob($this->logDir . '/*'));
        rmdir($this->logDir);
    }

    public function testCreatesLogFileWithTodaysDate(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->info('Test message');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFile));
    }

    public function testWritesLogEntryToFile(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->info('Test message');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('INFO: Test message', $content);
    }

    public function testAppendsMultipleLogEntries(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->info('First message');
        $logger->error('Second message');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('INFO: First message', $content);
        $this->assertStringContainsString('ERROR: Second message', $content);
    }

    public function testLogsWithContext(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->error('Database error', [
            'host' => 'localhost',
            'port' => 3306,
        ]);
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('ERROR: Database error', $content);
        $this->assertStringContainsString('Context:', $content);
        $this->assertStringContainsString('localhost', $content);
        $this->assertStringContainsString('3306', $content);
    }

    public function testLogsWithStackTrace(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->critical('Exception occurred', [
            'stack_trace' => [
                'file' => '/path/to/file.php',
                'line' => 42,
                'trace' => 'Stack trace here',
            ],
        ]);
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('CRITICAL: Exception occurred', $content);
        $this->assertStringContainsString('File: /path/to/file.php:42', $content);
        $this->assertStringContainsString('Stack Trace:', $content);
        $this->assertStringContainsString('Stack trace here', $content);
    }

    public function testDeletesLogsOlderThanRetentionPeriod(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 3); // Keep 3 days
        $logger = new Logger($dailyLogger);
        
        // Create old log files
        $oldDate1 = date('Y-m-d', strtotime('-5 days'));
        $oldDate2 = date('Y-m-d', strtotime('-4 days'));
        $recentDate = date('Y-m-d', strtotime('-2 days'));
        
        touch($this->logDir . '/lightpack-' . $oldDate1 . '.log');
        touch($this->logDir . '/lightpack-' . $oldDate2 . '.log');
        touch($this->logDir . '/lightpack-' . $recentDate . '.log');
        
        // Trigger cleanup by logging
        $logger->info('Test message');
        
        // Old files should be deleted
        $this->assertFalse(file_exists($this->logDir . '/lightpack-' . $oldDate1 . '.log'));
        $this->assertFalse(file_exists($this->logDir . '/lightpack-' . $oldDate2 . '.log'));
        
        // Recent file should still exist
        $this->assertTrue(file_exists($this->logDir . '/lightpack-' . $recentDate . '.log'));
        
        // Today's file should exist
        $this->assertTrue(file_exists($this->logDir . '/lightpack-' . date('Y-m-d') . '.log'));
    }

    public function testKeepsExactNumberOfDays(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        // Create log files for the last 10 days
        for ($i = 10; $i >= 1; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            touch($this->logDir . '/lightpack-' . $date . '.log');
        }
        
        // Trigger cleanup
        $logger->info('Test message');
        
        // Should keep only last 7 days + today (8 files total)
        $logFiles = glob($this->logDir . '/lightpack-*.log');
        $this->assertCount(8, $logFiles);
    }

    public function testHandlesInvalidFileNamesGracefully(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        // Create files with invalid names
        touch($this->logDir . '/lightpack-invalid.log');
        touch($this->logDir . '/other-file.log');
        touch($this->logDir . '/lightpack-2024-13-45.log'); // Invalid date
        
        // Should not throw exception
        $logger->info('Test message');
        
        // Invalid files should not be deleted (only valid date patterns)
        $this->assertTrue(file_exists($this->logDir . '/lightpack-invalid.log'));
        $this->assertTrue(file_exists($this->logDir . '/other-file.log'));
    }

    public function testCreatesDirectoryIfNotExists(): void
    {
        $newLogDir = $this->logDir . '/nested/logs';
        
        $dailyLogger = new DailyFileLogger($newLogDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->info('Test message');
        
        $this->assertTrue(is_dir($newLogDir));
        $this->assertTrue(file_exists($newLogDir . '/lightpack-' . date('Y-m-d') . '.log'));
        
        // Cleanup
        unlink($newLogDir . '/lightpack-' . date('Y-m-d') . '.log');
        rmdir($newLogDir);
        rmdir($this->logDir . '/nested');
    }

    public function testCanLogEmergency(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->emergency('System failure');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('EMERGENCY: System failure', $content);
    }

    public function testCanLogAlert(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->alert('Action required');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('ALERT: Action required', $content);
    }

    public function testCanLogCritical(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->critical('Critical condition');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('CRITICAL: Critical condition', $content);
    }

    public function testCanLogError(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->error('Error message');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('ERROR: Error message', $content);
    }

    public function testCanLogWarning(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->warning('Warning message');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('WARNING: Warning message', $content);
    }

    public function testCanLogNotice(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->notice('Notice message');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('NOTICE: Notice message', $content);
    }

    public function testCanLogInfo(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->info('Info message');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('INFO: Info message', $content);
    }

    public function testCanLogDebug(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->debug('Debug message');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        $this->assertStringContainsString('DEBUG: Debug message', $content);
    }

    public function testIncludesTimestampInLogEntry(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->info('Test message');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        // Should contain timestamp in format [YYYY-MM-DD HH:MM:SS]
        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $content);
    }

    public function testIncludesSeparatorLines(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir, 7);
        $logger = new Logger($dailyLogger);
        
        $logger->info('Test message');
        
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $content = file_get_contents($expectedFile);
        
        // Should contain separator line (80 dashes)
        $this->assertStringContainsString(str_repeat('-', 80), $content);
    }

    public function testDefaultRetentionIs7Days(): void
    {
        $dailyLogger = new DailyFileLogger($this->logDir);
        $logger = new Logger($dailyLogger);
        
        // Create log files for the last 10 days
        for ($i = 10; $i >= 1; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            touch($this->logDir . '/lightpack-' . $date . '.log');
        }
        
        // Trigger cleanup
        $logger->info('Test message');
        
        // Should keep only last 7 days + today (8 files total)
        $logFiles = glob($this->logDir . '/lightpack-*.log');
        $this->assertCount(8, $logFiles);
    }
}
