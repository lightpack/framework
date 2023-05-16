<?php

namespace Lightpack\Console;

class Prompt
{
    public static function ask(string $question, $default = null): string
    {
        self::writePrompt($question, $default);
        return self::readInput();
    }

    public static function askHidden(string $question): string
    {
        self::writePrompt($question);

        $sttyMode = shell_exec('stty -g');
        shell_exec('stty -echo');
        $input = self::readInput();
        shell_exec('stty ' . $sttyMode);

        echo PHP_EOL;

        return trim($input);
    }

    public static function confirm(string $question, bool $default = false): bool
    {
        $prompt = $default ? '[Y/n]' : '[y/N]';
        $response = strtolower(self::ask($question . ' ' . $prompt));

        if ($default) {
            return $response !== 'n';
        }

        return $response === 'y';
    }

    public static function chooseMultiple(
        string $question,
        array $options,
        $default = null,
        bool $canSelectMultiple = true
    ): array {
        self::writePrompt($question, $default);

        $optionKeys = array_keys($options);
        $selectedOptions = [];

        while (true) {
            $input = self::readInput();

            if ($input === '' && $default !== null) {
                return (array) $default;
            }

            if ($canSelectMultiple) {
                $selectedOptions = array_unique(array_merge($selectedOptions, explode(',', $input)));
            } else {
                $selectedOptions = [$input];
            }

            $invalidOptions = array_diff($selectedOptions, $optionKeys);

            if (empty($invalidOptions)) {
                return $selectedOptions;
            }

            echo 'Invalid option(s): ' . implode(', ', $invalidOptions) . '. Please try again: ';
        }
    }

    public static function askWithValidation(string $question, callable $validator, $default = null)
    {
        while (true) {
            $response = self::ask($question, $default);

            if ($validator($response)) {
                return $response;
            }

            echo "Invalid input. Please try again." . PHP_EOL;
        }
    }

    public static function chooseFromList(string $question, array $options, $default = null): ?string
    {
        self::writePrompt($question, $default);

        $optionKeys = array_keys($options);

        while (true) {
            $input = self::readInput();

            if ($input === '' && $default !== null) {
                return $default;
            }

            if (
                in_array($input, $optionKeys, true)
            ) {
                return $input;
            }

            echo "Invalid option. Please try again: ";
        }
    }

    public static function chooseFromListWithIndex(string $question, array $options, $default = null): ?string
    {
        self::writePrompt($question, $default);

        foreach ($options as $index => $option) {
            echo "[" . $index . "] " . $option . PHP_EOL;
        }

        while (true) {
            $input = self::readInput();

            if ($input === '' && $default !== null) {
                return $default;
            }

            if (isset($options[$input])) {
                return $input;
            }

            echo "Invalid option. Please try again: ";
        }
    }

    private static function writePrompt(string $question, $default = null)
    {
        echo $question . ' ';

        if ($default !== null) {
            echo "[$default] ";
        }
    }

    private static function readInput(): string
    {
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        return trim($line);
    }
}
