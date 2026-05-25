<?php

use Lightpack\Utils\ChildProcess;
use Lightpack\Utils\Process;
use PHPUnit\Framework\TestCase;

class ChildProcessTest extends TestCase
{
    private Process $process;

    protected function setUp(): void
    {
        $this->process = new Process;
    }

    // -------------------------------------------------------------------------
    // spawn() factory
    // -------------------------------------------------------------------------

    public function testSpawnReturnsChildProcessInstance(): void
    {
        $child = $this->process->spawn(['true']);

        $this->assertInstanceOf(ChildProcess::class, $child);

        $child->wait();
    }

    public function testSpawnEmptyArrayThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->process->spawn([]);
    }

    public function testSpawnEmptyStringThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->process->spawn('');
    }

    // -------------------------------------------------------------------------
    // isRunning()
    // -------------------------------------------------------------------------

    public function testIsRunningReturnsTrueForActiveProcess(): void
    {
        $child = $this->process->spawn(['sleep', '5']);

        try {
            $this->assertTrue($child->isRunning());
        } finally {
            $child->kill();
            $child->wait();
        }
    }

    public function testIsRunningReturnsFalseAfterNaturalExit(): void
    {
        $child = $this->process->spawn(['true']);
        $child->wait();

        $this->assertFalse($child->isRunning());
    }

    public function testIsRunningReturnsFalseAfterKill(): void
    {
        $child = $this->process->spawn(['sleep', '30']);
        $child->kill();
        $child->wait();

        $this->assertFalse($child->isRunning());
    }

    // -------------------------------------------------------------------------
    // pid()
    // -------------------------------------------------------------------------

    public function testPidIsPositiveIntegerForRunningProcess(): void
    {
        $child = $this->process->spawn(['sleep', '5']);

        try {
            $this->assertIsInt($child->pid());
            $this->assertGreaterThan(0, $child->pid());
        } finally {
            $child->kill();
            $child->wait();
        }
    }

    public function testPidRemainsAvailableAfterProcessExits(): void
    {
        $child = $this->process->spawn(['true']);
        $child->wait();

        $this->assertIsInt($child->pid());
        $this->assertGreaterThan(0, $child->pid());
    }

    // -------------------------------------------------------------------------
    // wait() and exit codes
    // -------------------------------------------------------------------------

    public function testWaitReturnsZeroForSuccessfulProcess(): void
    {
        $child = $this->process->spawn(['true']);

        $this->assertSame(0, $child->wait());
    }

    public function testWaitReturnsNonZeroForFailedProcess(): void
    {
        $child = $this->process->spawn(['false']);

        $this->assertNotEquals(0, $child->wait());
    }

    public function testWaitReturnsSpecificExitCode(): void
    {
        $child = $this->process->spawn(['/bin/sh', '-c', 'exit 42']);

        $this->assertSame(42, $child->wait());
    }

    public function testWaitBlocksUntilProcessFinishes(): void
    {
        $child = $this->process->spawn(['/bin/sh', '-c', 'sleep 0.2']);
        $start = microtime(true);
        $child->wait();
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThan(0.1, $elapsed);
    }

    public function testWaitOnAlreadyExitedProcessIsIdempotent(): void
    {
        $child = $this->process->spawn(['true']);
        usleep(200000);

        $exitCode = $child->wait();

        $this->assertSame(0, $exitCode);
    }

    // -------------------------------------------------------------------------
    // exitCode()
    // -------------------------------------------------------------------------

    public function testExitCodeIsNullWhileProcessIsRunning(): void
    {
        $child = $this->process->spawn(['sleep', '10']);

        try {
            $this->assertNull($child->exitCode());
        } finally {
            $child->kill();
            $child->wait();
        }
    }

    public function testExitCodeIsZeroAfterSuccessfulWait(): void
    {
        $child = $this->process->spawn(['true']);
        $child->wait();

        $this->assertSame(0, $child->exitCode());
    }

    public function testExitCodeIsNonZeroAfterFailedWait(): void
    {
        $child = $this->process->spawn(['false']);
        $child->wait();

        $this->assertNotNull($child->exitCode());
        $this->assertNotEquals(0, $child->exitCode());
    }

    // -------------------------------------------------------------------------
    // terminate() and kill()
    // -------------------------------------------------------------------------

    public function testTerminateStopsRunningProcess(): void
    {
        $child = $this->process->spawn(['sleep', '30']);
        $this->assertTrue($child->isRunning());

        $child->terminate();
        $child->wait();

        $this->assertFalse($child->isRunning());
    }

    public function testKillForciblyStopsRunningProcess(): void
    {
        $child = $this->process->spawn(['sleep', '30']);
        $this->assertTrue($child->isRunning());

        $child->kill();
        $child->wait();

        $this->assertFalse($child->isRunning());
    }

    public function testExitCodeIsSetAfterTerminate(): void
    {
        $child = $this->process->spawn(['sleep', '30']);
        $child->terminate();
        $child->wait();

        $this->assertNotNull($child->exitCode());
    }

    public function testTerminateOnExitedProcessIsIdempotent(): void
    {
        $child = $this->process->spawn(['true']);
        $child->wait();

        $child->terminate();

        $this->assertFalse($child->isRunning());
        $this->assertSame(0, $child->exitCode());
    }

    public function testKillOnExitedProcessIsIdempotent(): void
    {
        $child = $this->process->spawn(['true']);
        $child->wait();

        $child->kill();

        $this->assertFalse($child->isRunning());
    }

    // -------------------------------------------------------------------------
    // Environment and directory
    // -------------------------------------------------------------------------

    public function testSpawnWithCustomEnvironmentVariable(): void
    {
        $env = array_merge(getenv(), ['LIGHTPACK_TEST_VAR' => 'hello']);

        $child = $this->process->spawn(
            ['/bin/sh', '-c', 'test "$LIGHTPACK_TEST_VAR" = "hello"'],
            $env
        );

        $this->assertSame(0, $child->wait());
    }

    public function testSpawnWithMissingEnvironmentVariableExitsNonZero(): void
    {
        $env = array_filter(getenv(), fn($key) => $key !== 'LIGHTPACK_TEST_VAR', ARRAY_FILTER_USE_KEY);

        $child = $this->process->spawn(
            ['/bin/sh', '-c', 'test "$LIGHTPACK_TEST_VAR" = "hello"'],
            $env
        );

        $this->assertNotEquals(0, $child->wait());
    }

    public function testSpawnInCustomWorkingDirectory(): void
    {
        $tmpDir = sys_get_temp_dir();

        $child = (new Process)->setDirectory($tmpDir)->spawn(['true']);

        $this->assertSame(0, $child->wait());
    }

    // -------------------------------------------------------------------------
    // Multiple independent spawns
    // -------------------------------------------------------------------------

    public function testMultipleSpawnedProcessesAreIndependent(): void
    {
        $child1 = $this->process->spawn(['sleep', '5']);
        $child2 = $this->process->spawn(['sleep', '5']);

        try {
            $this->assertTrue($child1->isRunning());
            $this->assertTrue($child2->isRunning());
            $this->assertNotEquals($child1->pid(), $child2->pid());
        } finally {
            $child1->kill();
            $child2->kill();
            $child1->wait();
            $child2->wait();
        }
    }

    public function testKillingOneSpawnDoesNotAffectOther(): void
    {
        $child1 = $this->process->spawn(['sleep', '10']);
        $child2 = $this->process->spawn(['sleep', '10']);

        try {
            $child1->kill();
            $child1->wait();

            $this->assertFalse($child1->isRunning());
            $this->assertTrue($child2->isRunning());
        } finally {
            $child2->kill();
            $child2->wait();
        }
    }

    // -------------------------------------------------------------------------
    // Sequentially reusing the same Process instance (mirrors the watch loop)
    // -------------------------------------------------------------------------

    public function testSequentialSpawnsOnSameProcessInstance(): void
    {
        $child1 = $this->process->spawn(['/bin/sh', '-c', 'exit 1']);
        $exit1 = $child1->wait();

        $child2 = $this->process->spawn(['/bin/sh', '-c', 'exit 2']);
        $exit2 = $child2->wait();

        $this->assertSame(1, $exit1);
        $this->assertSame(2, $exit2);
    }
}
