<?php

declare(strict_types=1);

use Lightpack\Deploy\Commands\ServerQueueSetupCommand;
use PHPUnit\Framework\TestCase;

final class ServerQueueSetupCommandTest extends TestCase
{
    public function testBuildSupervisorConfigStructure(): void
    {
        $command = new ServerQueueSetupCommand(['production']);
        $method = new \ReflectionMethod($command, 'buildSupervisorConfig');
        $method->setAccessible(true);

        $config = $method->invoke($command, 'emails', '/var/www/app', 'emails', 2, 3600, 60);

        $this->assertStringContainsString('[program:lightpack-emails]', $config);
        $this->assertStringContainsString('process_name=%(program_name)s_%(process_num)02d', $config);
        $this->assertStringContainsString('command=/usr/bin/env php /var/www/app/console jobs:run --queue=emails --cooldown=3600', $config);
        $this->assertStringContainsString('directory=/var/www/app', $config);
        $this->assertStringContainsString('user=deploy', $config);
        $this->assertStringContainsString('numprocs=2', $config);
        $this->assertStringContainsString('autostart=false', $config);
        $this->assertStringContainsString('autorestart=true', $config);
        $this->assertStringContainsString('stopasgroup=true', $config);
        $this->assertStringContainsString('killasgroup=true', $config);
        $this->assertStringContainsString('stopwaitsecs=60', $config);
        $this->assertStringContainsString('redirect_stderr=true', $config);
        $this->assertStringContainsString('stdout_logfile=/var/log/supervisor/lightpack-emails.log', $config);
    }

    public function testBuildSupervisorConfigEscapesShellArg(): void
    {
        $command = new ServerQueueSetupCommand(['production']);
        $method = new \ReflectionMethod($command, 'buildSupervisorConfig');
        $method->setAccessible(true);

        $config = $method->invoke($command, 'reports', '/var/www/app', 'reports', 1, 1800, 30);

        // The whole config is wrapped in escapeshellarg, so it should be a quoted string
        $this->assertStringStartsWith("'", $config);
        $this->assertStringEndsWith("'", $config);
    }

    public function testBuildSupervisorConfigWithDifferentWorkers(): void
    {
        $command = new ServerQueueSetupCommand(['production']);
        $method = new \ReflectionMethod($command, 'buildSupervisorConfig');
        $method->setAccessible(true);

        $config = $method->invoke($command, 'default', '/var/www/app', 'default', 4, 7200, 120);

        $this->assertStringContainsString('numprocs=4', $config);
        $this->assertStringContainsString('stopwaitsecs=120', $config);
        $this->assertStringContainsString('--cooldown=7200', $config);
    }
}
