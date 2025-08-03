<?php

namespace Lightpack\Console;

class Output
{
    public function line(string $text)
    {
        echo "\n{$text}\n";
    }

    public function info(string $text)
    {
        echo "\n";
        echo (new Color)->format("<BLUE>{$text}</BLUE>");
        echo "\n";
    }

    public function success(string $text)
    {
        echo "\n";
        echo (new Color)->format("<GREEN>{$text}</GREEN>");
        echo "\n";
    }

    public function error(string $text)
    {
        echo "\n";
        echo (new Color)->format("<RED>{$text}</RED>");
        echo "\n";
    }

    public function warning(string $text)
    {
        echo "\n";
        echo (new Color)->format("<YELLOW>{$text}</YELLOW>");
        echo "\n";
    }

    public function infoLabel(?string $text = null)
    {
        $text = $text ?? ' INFO ';

        echo (new Color)->format("<BG_BLUE>{$text}</BG_BLUE>");
    }

    public function successLabel(?string $text = null)
    {
        $text = $text ?? ' SUCCESS ';

        echo (new Color)->format("<BG_GREEN>{$text}</BG_GREEN>");
    }

    public function errorLabel(?string $text = null)
    {
        $text = $text ?? ' ERROR ';

        echo (new Color)->format("<BG_RED>{$text}</BG_RED>");
    }

    public function warningLabel(?string $text = null)
    {
        $text = $text ?? ' WARNING ';

        echo (new Color)->format("<BG_YELLOW>{$text}</BG_YELLOW>");
    }

    public function pad(string $leftText, string $rightText, int $length = 20, string $character = '.')
    {
        $paddingLength = max(0, $length - strlen($leftText));
        $padding = str_repeat($character, $paddingLength);

        echo "\n{$leftText}{$padding} {$rightText}\n";
    }

    public function newline(int $count = 1)
    {
        echo str_repeat(PHP_EOL, $count);
    }
}
