<?php

declare(strict_types=1);

use Lightpack\Logger\Logger;
use PHPUnit\Framework\TestCase;
use Lightpack\Logger\Drivers\FileLogger;

final class LoggerTest extends TestCase
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

    public function testConstructor(): void
    {
        $fileLogger = new FileLogger($this->logDir . '/log.txt');
        $logger = new Logger($fileLogger);
        $logger->info('hello world');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogEmergency()
    {
        $fileLogger = new FileLogger($this->logDir . '/log.txt');
        $logger = new Logger($fileLogger);
        $logger->emergency('Emergency log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogAlert()
    {
        $fileLogger = new FileLogger($this->logDir . '/log.txt');
        $logger = new Logger($fileLogger);
        $logger->alert('Alert log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogCritical()
    {
        $fileLogger = new FileLogger($this->logDir . '/log.txt');
        $logger = new Logger($fileLogger);
        $logger->critical('Critical log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogError()
    {
        $fileLogger = new FileLogger($this->logDir . '/log.txt');
        $logger = new Logger($fileLogger);
        $logger->error('Error log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogWarning()
    {
        $fileLogger = new FileLogger($this->logDir . '/log.txt');
        $logger = new Logger($fileLogger);
        $logger->warning('Warning log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogNotice()
    {
        $fileLogger = new FileLogger($this->logDir . '/log.txt');
        $logger = new Logger($fileLogger);
        $logger->notice('Notice log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogInfo()
    {
        $fileLogger = new FileLogger($this->logDir . '/log.txt');
        $logger = new Logger($fileLogger);
        $logger->info('Info log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogDebug()
    {
        $fileLogger = new FileLogger($this->logDir . '/log.txt');
        $logger = new Logger($fileLogger);
        $logger->debug('Debug log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }
}