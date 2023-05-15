<?php

use Lightpack\Console\Color;

class ColorTest extends PHPUnit\Framework\TestCase
{
    public function testForegroundColors()
    {
        $this->assertEquals("\033[0;31mHello\033[0m", Color::format('<RED>Hello<reset>'));
        $this->assertEquals("\033[0;32mHello\033[0m", Color::format('<GREEN>Hello<reset>'));
        $this->assertEquals("\033[0;33mHello\033[0m", Color::format('<YELLOW>Hello<reset>'));
        $this->assertEquals("\033[0;34mHello\033[0m", Color::format('<BLUE>Hello<reset>'));
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
        $this->assertEquals("\033[0;31m\033[42mHello\033[0m\033[49m", Color::format('<RED><BG_GREEN>Hello<reset><BG_RESET>'));
        $this->assertEquals("\033[0;32m\033[41mHello\033[0m\033[49m", Color::format('<GREEN><BG_RED>Hello<reset><BG_RESET>'));
        $this->assertEquals("\033[0;33m\033[44mHello\033[0m\033[49m", Color::format('<YELLOW><BG_BLUE>Hello<reset><BG_RESET>'));
        $this->assertEquals("\033[0;34m\033[43mHello\033[0m\033[49m", Color::format('<BLUE><BG_YELLOW>Hello<reset><BG_RESET>'));
    }

    public function testInvalidColorLabel()
    {
        $this->assertEquals('<INVALID>', Color::format('<INVALID>'));
        $this->assertEquals('<UNKNOWN>', Color::format('<UNKNOWN>'));
    }
}
