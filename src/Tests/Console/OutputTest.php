<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Console\Output;

class OutputTest extends TestCase
{
    public function testLineMethod()
    {
        $expected = "\nHello\n";

        ob_start();
        (new Output)->line('Hello');
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);
    }

    public function testErrorMethod()
    {
        $expected = "\n\033[0;31mHello\033[0m\n";

        ob_start();
        (new Output)->error('Hello');
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);
    }

    public function testSuccessMethod()
    {
        $expected = "\n\033[0;32mHello\033[0m\n";

        ob_start();
        (new Output)->success('Hello');
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);
    }

    public function testWarningMethod()
    {
        $expected = "\n\033[0;33mHello\033[0m\n";

        ob_start();
        (new Output)->warning('Hello');
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);
    }

    public function testInfoMethod()
    {
        $expected = "\n\033[0;34mHello\033[0m\n";

        ob_start();
        (new Output)->info('Hello');
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);
    }

    public function testErrorLabelMethod()
    {
        // Test 1
        $expected = "\033[41m ERROR \033[0m";

        ob_start();
        (new Output)->errorLabel();
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);

        // Test 1
        $expected = "\033[41mERROR\033[0m";

        ob_start();
        (new Output)->errorLabel('ERROR');
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);
    }

    public function testSuccessLabelMethod()
    {
        // Test 1
        $expected = "\033[42m SUCCESS \033[0m";

        ob_start();
        (new Output)->successLabel();
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);

        // Test 2
        $expected = "\033[42mSUCCESS\033[0m";

        ob_start();
        (new Output)->successLabel('SUCCESS');
        $actual = ob_get_clean();
        
        $this->assertEquals($expected, $actual);
    }

    public function testWarningLabelMethod()
    {
        // Test 1
        $expected = "\033[43m WARNING \033[0m";

        ob_start();
        (new Output)->warningLabel();
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);

        // Test 2
        $expected = "\033[43mWARNING\033[0m";

        ob_start();
        (new Output)->warningLabel('WARNING');
        $actual = ob_get_clean();
        
        $this->assertEquals($expected, $actual);
    }

    public function testInfoLabelMethod()
    {
        // Test 1
        $expected = "\033[44m INFO \033[0m";

        ob_start();
        (new Output)->infoLabel();
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);

        // Test 2
        $expected = "\033[44mINFO\033[0m";

        ob_start();
        (new Output)->infoLabel('INFO');
        $actual = ob_get_clean();

        $this->assertEquals($expected, $actual);
    }
}
