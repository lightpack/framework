<?php

use Lightpack\Console\Color;

class ColorTest extends PHPUnit\Framework\TestCase
{
    public function testForegroundColors()
    {
        $this->assertEquals("\033[0;31mHello\033[0m", Color::format('<RED>Hello<RESET>'));
        $this->assertEquals("\033[0;32mHello\033[0m", Color::format('<GREEN>Hello<RESET>'));
        $this->assertEquals("\033[0;33mHello\033[0m", Color::format('<YELLOW>Hello<RESET>'));
        $this->assertEquals("\033[0;34mHello\033[0m", Color::format('<BLUE>Hello<RESET>'));
    }

    public function testBackgroundColors()
    {
        $this->assertEquals("\033[41mHello\033[49m", Color::format('<BG_RED>Hello<BG_RESET>'));
        $this->assertEquals("\033[42mHello\033[49m", Color::format('<BG_GREEN>Hello<BG_RESET>'));
        $this->assertEquals("\033[43mHello\033[49m", Color::format('<BG_YELLOW>Hello<BG_RESET>'));
        $this->assertEquals("\033[44mHello\033[49m", Color::format('<BG_BLUE>Hello<BG_RESET>'));
    }

    public function testForegroundAndBackgroundColors()
    {
        $this->assertEquals("\033[0;31m\033[42mHello\033[0m\033[49m", Color::format('<RED><BG_GREEN>Hello<RESET><BG_RESET>'));
        $this->assertEquals("\033[0;32m\033[41mHello\033[0m\033[49m", Color::format('<GREEN><BG_RED>Hello<RESET><BG_RESET>'));
        $this->assertEquals("\033[0;33m\033[44mHello\033[0m\033[49m", Color::format('<YELLOW><BG_BLUE>Hello<RESET><BG_RESET>'));
        $this->assertEquals("\033[0;34m\033[43mHello\033[0m\033[49m", Color::format('<BLUE><BG_YELLOW>Hello<RESET><BG_RESET>'));
    }

    public function testInvalidColorLabel()
    {
        $this->assertEquals('<INVALID>', Color::format('<INVALID>'));
        $this->assertEquals('<UNKNOWN>', Color::format('<UNKNOWN>'));
    }

    public function testColorError()
    {
        $this->assertEquals("\033[0;31mHello\033[0m", Color::error('Hello'));
    }

    public function testColorSuccess()
    {
        $this->assertEquals("\033[0;32mHello\033[0m", Color::success('Hello'));
    }

    public function testColorWarning()
    {
        $this->assertEquals("\033[0;33mHello\033[0m", Color::warning('Hello'));
    }

    public function testColorInfo()
    {
        $this->assertEquals("\033[0;34mHello\033[0m", Color::info('Hello'));
    }

    public function testColorErrorLabel()
    {
        $this->assertEquals("\033[41m ERROR \033[49m", Color::errorLabel());
        $this->assertEquals("\033[41mERROR\033[49m", Color::errorLabel('ERROR'));
    }

    public function testColorSuccessLabel()
    {
        $this->assertEquals("\033[42m SUCCESS \033[49m", Color::successLabel());
        $this->assertEquals("\033[42mSUCCESS\033[49m", Color::successLabel('SUCCESS'));
    }

    public function testColorWarningLabel()
    {
        $this->assertEquals("\033[43m WARNING \033[49m", Color::warningLabel());
        $this->assertEquals("\033[43mWARNING\033[49m", Color::warningLabel('WARNING'));
    }

    public function testColorInfoLabel()
    {
        $this->assertEquals("\033[44m INFO \033[49m", Color::infoLabel());
        $this->assertEquals("\033[44mINFO\033[49m", Color::infoLabel('INFO'));
    }
}
