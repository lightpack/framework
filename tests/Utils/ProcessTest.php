<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Utils\Process;

class ProcessTest extends TestCase
{
    protected Process $process;
    protected string $tmpDir;

    protected function setUp(): void
    {
        $this->process = new Process();
        $this->tmpDir = rtrim(sys_get_temp_dir(), '/');
    }

    public function testExecuteSingleCommand(): void
    {
        $this->process->execute('echo "Hello World"');
        $this->assertStringContainsString('Hello World', $this->process->getOutput());
        $this->assertEquals('', $this->process->getError());
        $this->assertEquals(0, $this->process->getExitCode());
        $this->assertFalse($this->process->failed());
    }

    public function testExecuteMultipleCommands(): void
    {
        $testFile = $this->tmpDir . '/process_test_' . uniqid();
        touch($testFile);

        $this->process->execute(['ls', $testFile]);
        $this->assertStringContainsString(basename($testFile), $this->process->getOutput());
        $this->assertEquals('', $this->process->getError());
        $this->assertEquals(0, $this->process->getExitCode());
        $this->assertFalse($this->process->failed());

        unlink($testFile);
    }

    public function testCommandWithSpaces(): void
    {
        $this->process->execute('echo "Hello World"');
        $this->assertStringContainsString('Hello World', $this->process->getOutput());
        $this->assertEquals('', $this->process->getError());
        $this->assertEquals(0, $this->process->getExitCode());
        $this->assertFalse($this->process->failed());
    }

    public function testCommandWithQuotes(): void
    {
        $this->process->execute('echo \'Hello World\'');
        $this->assertStringContainsString('Hello World', $this->process->getOutput());
        $this->assertEquals('', $this->process->getError());
        $this->assertEquals(0, $this->process->getExitCode());
        $this->assertFalse($this->process->failed());
    }

    public function testCommandWithError(): void
    {
        $nonExistentDir = $this->tmpDir . '/definitely_does_not_exist_' . uniqid();
        $this->process->execute('ls ' . escapeshellarg($nonExistentDir));
        
        $this->assertEmpty(trim($this->process->getOutput()));
        $this->assertNotEmpty($this->process->getError());
        $this->assertNotEquals(0, $this->process->getExitCode());
        $this->assertTrue($this->process->failed());
    }

    public function testCommandWithNonZeroExitCode(): void
    {
        $this->process->execute('exit 1');
        $this->assertEmpty(trim($this->process->getOutput()));
        $this->assertEquals('', $this->process->getError());
        $this->assertEquals(1, $this->process->getExitCode());
        $this->assertTrue($this->process->failed());
    }

    public function testProcessTimeout(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Process timed out');

        $this->process
            ->setTimeout(1)
            ->execute('sleep 3');
    }

    public function testProcessCleanupAfterTimeout(): void
    {
        try {
            $this->process
                ->setTimeout(1)
                ->execute('sleep 3');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('timed out', $e->getMessage());
            
            // Try running another command to verify process is in clean state
            $this->process->execute('echo "test"');
            $this->assertStringContainsString('test', $this->process->getOutput());
            return;
        }
        
        $this->fail('Process should have timed out');
    }

    public function testInvalidTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must be greater than 0 seconds');
        
        $this->process->setTimeout(0);
    }

    public function testEmptyCommand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Command string cannot be empty');
        
        $this->process->execute('');
    }

    public function testEmptyArrayCommand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Command array cannot be empty');
        
        $this->process->execute([]);
    }

    public function testShellOperatorsAccepted(): void
    {
        $this->process->execute('echo "hello" | grep "o"');
        $this->assertStringContainsString('hello', $this->process->getOutput());
    }

    public function testLongRunningCommandOutput(): void
    {
        $this->process->execute('for i in $(seq 1 5); do echo $i; sleep 0.1; done');
        
        $output = trim($this->process->getOutput());
        $lines = explode("\n", $output);
        
        $this->assertCount(5, $lines);
        $this->assertEquals('1', $lines[0]);
        $this->assertEquals('5', $lines[4]);
        $this->assertEquals('', $this->process->getError());
        $this->assertEquals(0, $this->process->getExitCode());
    }

    public function testEmptyArrayArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Command argument cannot be empty');
        
        $this->process->execute(['ls', '']);
    }

    public function testZeroAsValidArgument(): void
    {
        $this->process->execute(['echo', '0']);
        $this->assertStringContainsString('0', $this->process->getOutput());
    }

    public function testInvalidWorkingDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid working directory');
        
        $this->process->setDirectory($this->tmpDir . '/non_existing_dir_' . uniqid());
    }

    public function testExecuteWithCallback(): void
    {
        $lines = [];
        $callback = function(string $line, string $type) use (&$lines) {
            $lines[] = [$type, trim($line)];
        };

        $this->process->execute(
            'for i in $(seq 1 3); do echo "line $i"; done',
            $callback
        );

        $this->assertCount(3, $lines);
        $this->assertEquals(['stdout', 'line 1'], $lines[0]);
        $this->assertEquals(['stdout', 'line 2'], $lines[1]);
        $this->assertEquals(['stdout', 'line 3'], $lines[2]);
    }

    public function testExecuteWithCallbackError(): void
    {
        $errors = [];
        $callback = function(string $line, string $type) use (&$errors) {
            if ($type === 'stderr') {
                $errors[] = trim($line);
            }
        };

        $nonExistentDir = $this->tmpDir . '/definitely_does_not_exist_' . uniqid();
        $this->process->execute('ls ' . escapeshellarg($nonExistentDir), $callback);

        $this->assertNotEmpty($errors);
        $this->assertTrue($this->process->failed());
    }
}
