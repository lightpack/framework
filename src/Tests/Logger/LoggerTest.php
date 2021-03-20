<?php

declare(strict_types=1);

use Psr\Log\LogLevel;
use Lightpack\Logger\Logger;
use PHPUnit\Framework\TestCase;

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
        $logger = new Logger($this->logDir . '/log.txt');
        $logger->log(LogLevel::INFO, 'hello world');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogEmergency()
    {
        $logger = new Logger($this->logDir . '/log.txt');
        $logger->log(LogLevel::EMERGENCY, 'Emergency log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogAlert()
    {
        $logger = new Logger($this->logDir . '/log.txt');
        $logger->log(LogLevel::ALERT, 'Alert log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogCritical()
    {
        $logger = new Logger($this->logDir . '/log.txt');
        $logger->log(LogLevel::CRITICAL, 'Critical log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogError()
    {
        $logger = new Logger($this->logDir . '/log.txt');
        $logger->log(LogLevel::ERROR, 'Error log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogWarning()
    {
        $logger = new Logger($this->logDir . '/log.txt');
        $logger->log(LogLevel::WARNING, 'Warning log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogNotice()
    {
        $logger = new Logger($this->logDir . '/log.txt');
        $logger->log(LogLevel::NOTICE, 'Notice log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogInfo()
    {
        $logger = new Logger($this->logDir . '/log.txt');
        $logger->log(LogLevel::INFO, 'Info log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }

    public function testCanLogDebug()
    {
        $logger = new Logger($this->logDir . '/log.txt');
        $logger->log(LogLevel::DEBUG, 'Debug log message.');
        $this->assertTrue(file_exists($this->logDir . '/log.txt'));
    }
}