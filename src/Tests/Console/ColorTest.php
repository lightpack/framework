<?php

use Lightpack\Console\Color;

class ColorTest extends PHPUnit\Framework\TestCase
{
    public function testForegroundColors()
    {
        $this->assertEquals("\033[0;31mHello\033[0m", (new Color)->format('<RED>Hello<RESET>'));
        $this->assertEquals("\033[0;32mHello\033[0m", (new Color)->format('<GREEN>Hello<RESET>'));
        $this->assertEquals("\033[0;33mHello\033[0m", (new Color)->format('<YELLOW>Hello<RESET>'));
        $this->assertEquals("\033[0;34mHello\033[0m", (new Color)->format('<BLUE>Hello<RESET>'));
    }

    public function testBackgroundColors()
    {
        $this->assertEquals("\033[41mHello\033[49m", (new Color)->format('<BG_RED>Hello<BG_RESET>'));
        $this->assertEquals("\033[42mHello\033[49m", (new Color)->format('<BG_GREEN>Hello<BG_RESET>'));
        $this->assertEquals("\033[43mHello\033[49m", (new Color)->format('<BG_YELLOW>Hello<BG_RESET>'));
        $this->assertEquals("\033[44mHello\033[49m", (new Color)->format('<BG_BLUE>Hello<BG_RESET>'));
    }

    public function testForegroundAndBackgroundColors()
    {
        $this->assertEquals("\033[0;31m\033[42mHello\033[0m\033[49m", (new Color)->format('<RED><BG_GREEN>Hello<RESET><BG_RESET>'));
        $this->assertEquals("\033[0;32m\033[41mHello\033[0m\033[49m", (new Color)->format('<GREEN><BG_RED>Hello<RESET><BG_RESET>'));
        $this->assertEquals("\033[0;33m\033[44mHello\033[0m\033[49m", (new Color)->format('<YELLOW><BG_BLUE>Hello<RESET><BG_RESET>'));
        $this->assertEquals("\033[0;34m\033[43mHello\033[0m\033[49m", (new Color)->format('<BLUE><BG_YELLOW>Hello<RESET><BG_RESET>'));
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
        $this->assertEquals("\033[41m ERROR \033[49m", (new Color)->errorLabel());
        $this->assertEquals("\033[41mERROR\033[49m", (new Color)->errorLabel('ERROR'));
    }

    public function testColorSuccessLabel()
    {
        $this->assertEquals("\033[42m SUCCESS \033[49m", (new Color)->successLabel());
        $this->assertEquals("\033[42mSUCCESS\033[49m", (new Color)->successLabel('SUCCESS'));
    }

    public function testColorWarningLabel()
    {
        $this->assertEquals("\033[43m WARNING \033[49m", (new Color)->warningLabel());
        $this->assertEquals("\033[43mWARNING\033[49m", (new Color)->warningLabel('WARNING'));
    }

    public function testColorInfoLabel()
    {
        $this->assertEquals("\033[44m INFO \033[49m", (new Color)->infoLabel());
        $this->assertEquals("\033[44mINFO\033[49m", (new Color)->infoLabel('INFO'));
    }
}
