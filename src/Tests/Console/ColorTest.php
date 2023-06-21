<?php

use Lightpack\Console\Color;
use PHPUnit\Framework\TestCase;

class ColorTest extends TestCase
{
    public function testForegroundColors()
    {
        $this->assertEquals("\033[0;31mHello\033[0m", (new Color)->format('<RED>Hello</RED>'));
        $this->assertEquals("\033[0;32mHello\033[0m", (new Color)->format('<GREEN>Hello</GREEN>'));
        $this->assertEquals("\033[0;33mHello\033[0m", (new Color)->format('<YELLOW>Hello</YELLOW>'));
        $this->assertEquals("\033[0;34mHello\033[0m", (new Color)->format('<BLUE>Hello</BLUE>'));
    }

    public function testBackgroundColors()
    {
        $this->assertEquals("\033[41mHello\033[0m", (new Color)->format('<BG_RED>Hello</BG_RED>'));
        $this->assertEquals("\033[42mHello\033[0m", (new Color)->format('<BG_GREEN>Hello</BG_GREEN>'));
        $this->assertEquals("\033[43mHello\033[0m", (new Color)->format('<BG_YELLOW>Hello</BG_YELLOW>'));
        $this->assertEquals("\033[44mHello\033[0m", (new Color)->format('<BG_BLUE>Hello</BG_BLUE>'));
    }

    public function testForegroundAndBackgroundColors()
    {
        $color = new Color;
        $red = '<RED>Hello</RED>';
        $green = '<GREEN>Hello</GREEN>';
        $yellow = '<YELLOW>Hello</YELLOW>';
        $blue = '<BLUE>Hello</BLUE>';

        $this->assertEquals("\033[42m\033[0;31mHello\033[0m\033[0m", $color->format('<BG_GREEN>' . $color->format($red) . '</BG_GREEN>'));
        $this->assertEquals("\033[41m\033[0;32mHello\033[0m\033[0m", $color->format('<BG_RED>' . $color->format($green) . '</BG_RED>'));
        $this->assertEquals("\033[44m\033[0;33mHello\033[0m\033[0m", $color->format('<BG_BLUE>' . $color->format($yellow) . '</BG_BLUE>'));
        $this->assertEquals("\033[43m\033[0;34mHello\033[0m\033[0m", $color->format('<BG_YELLOW>' . $color->format($blue) . '</BG_YELLOW>'));
    }

    public function testInvalidColorLabel()
    {
        $this->assertEquals('<INVALID>', (new Color)->format('<INVALID>'));
        $this->assertEquals('<UNKNOWN>', (new Color)->format('<UNKNOWN>'));
    }

    public function testColorError()
    {
        $this->assertEquals("\033[0;31mHello\033[0m", (new Color)->error('Hello'));
    }

    public function testColorSuccess()
    {
        $this->assertEquals("\033[0;32mHello\033[0m", (new Color)->success('Hello'));
    }

    public function testColorWarning()
    {
        $this->assertEquals("\033[0;33mHello\033[0m", (new Color)->warning('Hello'));
    }

    public function testColorInfo()
    {
        $this->assertEquals("\033[0;34mHello\033[0m", (new Color)->info('Hello'));
    }

    public function testColorErrorLabel()
    {
        $this->assertEquals("\033[41m ERROR \033[0m", (new Color)->errorLabel());
        $this->assertEquals("\033[41mERROR\033[0m", (new Color)->errorLabel('ERROR'));
    }

    public function testColorSuccessLabel()
    {
        $this->assertEquals("\033[42m SUCCESS \033[0m", (new Color)->successLabel());
        $this->assertEquals("\033[42mSUCCESS\033[0m", (new Color)->successLabel('SUCCESS'));
    }

    public function testColorWarningLabel()
    {
        $this->assertEquals("\033[43m WARNING \033[0m", (new Color)->warningLabel());
        $this->assertEquals("\033[43mWARNING\033[0m", (new Color)->warningLabel('WARNING'));
    }

    public function testColorInfoLabel()
    {
        $this->assertEquals("\033[44m INFO \033[0m", (new Color)->infoLabel());
        $this->assertEquals("\033[44mINFO\033[0m", (new Color)->infoLabel('INFO'));
    }
}
