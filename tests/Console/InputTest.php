<?php

use Lightpack\Console\Input;
use PHPUnit\Framework\TestCase;

class InputTest extends TestCase
{
    public function testArgumentsAreParsedCorrectly()
    {
        $input = new Input(['script.php', 'foo', 'bar']);
        $this->assertEquals('foo', $input->getArgument(0));
        $this->assertEquals('bar', $input->getArgument(1));
        $this->assertNull($input->getArgument(2));
        $this->assertEquals(['foo', 'bar'], $input->getArguments());
    }

    public function testLongOptionsAreParsedCorrectly()
    {
        $input = new Input(['script.php', '--alpha=1', '--beta=2', '--flag']);
        $this->assertEquals('1', $input->getOption('alpha'));
        $this->assertEquals('2', $input->getOption('beta'));
        $this->assertTrue($input->getOption('flag'));
        $this->assertTrue($input->hasOption('alpha'));
        $this->assertFalse($input->hasOption('gamma'));
        $this->assertEquals(['alpha' => '1', 'beta' => '2', 'flag' => true], $input->getOptions());
    }

    public function testShortOptionsAreParsedCorrectly()
    {
        $input = new Input(['script.php', '-a', '-b', '-c', 'foo']);
        $this->assertTrue($input->getOption('a'));
        $this->assertTrue($input->getOption('b'));
        $this->assertEquals('foo', $input->getOption('c'));
        $this->assertNull($input->getArgument(0));
    }

    public function testShortOptionWithValue()
    {
        $input = new Input(['script.php', '-f', 'bar']);
        $this->assertEquals('bar', $input->getOption('f'));
    }

    public function testMultipleShortFlagsTogether()
    {
        $input = new Input(['script.php', '-abc']);
        $this->assertTrue($input->getOption('a'));
        $this->assertTrue($input->getOption('b'));
        $this->assertTrue($input->getOption('c'));
    }

    public function testRepeatedLongOptionsAreArrays()
    {
        $input = new Input(['script.php', '--tag=foo', '--tag=bar', '--tag=baz']);
        $this->assertEquals(['foo', 'bar', 'baz'], $input->getOption('tag'));
    }

    public function testRepeatedShortOptionsAreArrays()
    {
        $input = new Input(['script.php', '-t', 'foo', '-t', 'bar']);
        $this->assertEquals(['foo', 'bar'], $input->getOption('t'));
    }

    public function testRequiredArgumentValidation()
    {
        $input = new Input(['script.php', 'foo']);
        $input->requireArgument(0, 'First argument');
        $input->requireArgument(1, 'Second argument');
        $errors = $input->validate();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Second argument', $errors[0]);
    }

    public function testRequiredOptionValidation()
    {
        $input = new Input(['script.php', '--foo=bar']);
        $input->requireOption('foo', 'Foo option');
        $input->requireOption('baz', 'Baz option');
        $errors = $input->validate();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Baz option', $errors[0]);
    }

    public function testGetUsage()
    {
        $input = new Input(['script.php']);
        $input->requireArgument(0, 'First argument');
        $input->requireOption('foo', 'Foo option');
        $usage = $input->getUsage('my:command');
        $this->assertStringContainsString('Usage: php script.php my:command <arg0> [--foo=value]', $usage);
    }

    public function testBooleanFlag()
    {
        $input = new Input(['script.php', '--verbose']);
        $this->assertTrue($input->getOption('verbose'));
    }

    public function testEdgeCases()
    {
        $input = new Input(['script.php', '--foo', '-b', 'bar', 'baz', '-x']);
        $this->assertTrue($input->getOption('foo'));
        $this->assertEquals('bar', $input->getOption('b'));
        $this->assertTrue($input->getOption('x'));
        $this->assertEquals('baz', $input->getArgument(0));
    }
}