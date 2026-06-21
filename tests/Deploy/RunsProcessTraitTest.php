<?php

declare(strict_types=1);

use Lightpack\Deploy\RunsProcessTrait;
use PHPUnit\Framework\TestCase;

/**
 * Test double that exposes private trait methods for unit testing.
 */
final class RunsProcessTraitTestDouble
{
    use RunsProcessTrait;

    public function testResolveKeyPath(string $key): string
    {
        return $this->resolveKeyPath($key);
    }
}

final class RunsProcessTraitTest extends TestCase
{
    private RunsProcessTraitTestDouble $helper;

    public function setUp(): void
    {
        $this->helper = new RunsProcessTraitTestDouble();
    }

    public function testResolveKeyPathExpandsTilde(): void
    {
        $home = $_SERVER['HOME'] ?? getenv('HOME') ?? '';
        $this->assertEquals("{$home}/.ssh/id_rsa", $this->helper->testResolveKeyPath('~/.ssh/id_rsa'));
    }

    public function testResolveKeyPathLeavesAbsoluteUntouched(): void
    {
        $this->assertEquals('/home/user/.ssh/id_rsa', $this->helper->testResolveKeyPath('/home/user/.ssh/id_rsa'));
    }

    public function testResolveKeyPathLeavesRelativeUntouched(): void
    {
        $this->assertEquals('.ssh/id_rsa', $this->helper->testResolveKeyPath('.ssh/id_rsa'));
    }

    public function testResolveKeyPathHandlesTildeOnly(): void
    {
        $home = $_SERVER['HOME'] ?? getenv('HOME') ?? '';
        $this->assertEquals($home, $this->helper->testResolveKeyPath('~'));
    }
}
