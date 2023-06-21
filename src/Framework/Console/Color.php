<?php

namespace Lightpack\Console;

class Color
{
    const RESET = "\033[0m";

    const COLORS = [
        'RED' => "\033[0;31m",
        'GREEN' => "\033[0;32m",
        'YELLOW' => "\033[0;33m",
        'BLUE' => "\033[0;34m"
    ];

    const BG_COLORS = [
        'BG_RED' => "\033[41m",
        'BG_GREEN' => "\033[42m",
        'BG_YELLOW' => "\033[43m",
        'BG_BLUE' => "\033[44m"
    ];

    public function format($string)
    {
        $string = preg_replace_callback('/<(\w+)>(.*?)<\/\1>/i', function ($matches) {
            $tag = strtoupper($matches[1]);
            if (array_key_exists($tag, self::COLORS)) {
                return self::COLORS[$tag] . $matches[2] . self::RESET;
            } elseif (array_key_exists($tag, self::BG_COLORS)) {
                return self::BG_COLORS[$tag] . $matches[2] . self::RESET;
            }
            return $matches[0];
        }, $string);

        return $string;
    }

    public function info(string $text)
    {
        return Color::format("<BLUE>{$text}</BLUE>");
    }

    public function success(string $text)
    {
        return Color::format("<GREEN>{$text}</GREEN>");
    }

    public function error(string $text)
    {
        return Color::format("<RED>{$text}</RED>");
    }

    public function warning(string $text)
    {
        return Color::format("<YELLOW>{$text}</YELLOW>");
    }

    public function infoLabel(?string $text = null)
    {
        $text = $text ?? ' INFO ';

        return Color::format("<BG_BLUE>{$text}</BG_BLUE>");
    }

    public function successLabel(?string $text = null)
    {
        $text = $text ?? ' SUCCESS ';

        return Color::format("<BG_GREEN>{$text}</BG_GREEN>");
    }

    public function errorLabel(?string $text = null)
    {
        $text = $text ?? ' ERROR ';

        return Color::format("<BG_RED>{$text}</BG_RED>");
    }

    public function warningLabel(?string $text = null)
    {
        $text = $text ?? ' WARNING ';

        return Color::format("<BG_YELLOW>{$text}</BG_YELLOW>");
    }
}
