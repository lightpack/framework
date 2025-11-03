<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Container\Container;
use Lightpack\Providers\LogProvider;
use Lightpack\Logger\Logger;
use Lightpack\Logger\Drivers\FileLogger;
use Lightpack\Logger\Drivers\DailyFileLogger;
use Lightpack\Logger\Drivers\NullLogger;

final class LogProviderTest extends TestCase
{
    private $container;
    private $logDir;

    public function setUp(): void
    {
        $this->container = new Container();
        $this->logDir = __DIR__ . '/tmp';
        
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir);
        }
        
        // Mock config
        $config = new class {
            private $data = [];
            
            public function set($key, $value) {
                $this->data[$key] = $value;
            }
            
            public function get($key, $default = null) {
                return $this->data[$key] ?? $default;
            }
        };
        
        $config->set('logs.path', __DIR__ . '/tmp');
        $config->set('logs.max_file_size', 10 * 1024 * 1024);
        $config->set('logs.max_log_files', 10);
        $config->set('logs.days_to_keep', 7);
        
        $this->container->register('config', fn() => $config);
    }

    public function tearDown(): void
    {
        if (is_dir($this->logDir)) {
            array_map('unlink', glob($this->logDir . '/*'));
            rmdir($this->logDir);
        }
    }

    public function testRegistersNullLoggerByDefault(): void
    {
        $_ENV['LOG_DRIVER'] = 'null';
        
        $provider = new LogProvider();
        $provider->register($this->container);
        
        $logger = $this->container->get('logger');
        
        $this->assertInstanceOf(Logger::class, $logger);
        
        // Verify it uses NullLogger by checking no file is created
        $logger->info('Test message');
        $files = glob($this->logDir . '/*');
        $this->assertEmpty($files);
    }

    public function testRegistersFileLogger(): void
    {
        $_ENV['LOG_DRIVER'] = 'file';
        
        $provider = new LogProvider();
        $provider->register($this->container);
        
        $logger = $this->container->get('logger');
        
        $this->assertInstanceOf(Logger::class, $logger);
        
        // Verify it creates a file log
        $logger->info('Test message');
        $this->assertTrue(file_exists($this->logDir . '/lightpack.log'));
    }

    public function testRegistersDailyFileLogger(): void
    {
        $_ENV['LOG_DRIVER'] = 'daily';
        
        $provider = new LogProvider();
        $provider->register($this->container);
        
        $logger = $this->container->get('logger');
        
        $this->assertInstanceOf(Logger::class, $logger);
        
        // Verify it creates a daily log file
        $logger->info('Test message');
        $expectedFile = $this->logDir . '/lightpack-' . date('Y-m-d') . '.log';
        $this->assertTrue(file_exists($expectedFile));
    }

    public function testUsesConfigurationValues(): void
    {
        $_ENV['LOG_DRIVER'] = 'daily';
        
        // Update config with custom values
        $config = $this->container->get('config');
        $config->set('logs.days_to_keep', 14);
        
        $provider = new LogProvider();
        $provider->register($this->container);
        
        $logger = $this->container->get('logger');
        
        // Log something to trigger cleanup with custom retention
        $logger->info('Test message');
        
        // Verify the logger was created successfully
        $this->assertInstanceOf(Logger::class, $logger);
    }
}
