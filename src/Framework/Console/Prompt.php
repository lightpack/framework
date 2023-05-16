<?php

namespace Lightpack\Console;

class Prompt
{
    public function ask(string $question, $default = null): string
    {
        $this->writePrompt($question, $default);
        return $this->readInput();
    }

    public function askHidden(string $question): string
    {
        $this->writePrompt($question);

        $sttyMode = shell_exec('stty -g');
        shell_exec('stty -echo');
        $input = $this->readInput();
        shell_exec('stty ' . $sttyMode);

        echo PHP_EOL;

        return trim($input);
    }

    public function confirm(string $question, bool $default = false): bool
    {
        $prompt = $default ? '[Y/n]' : '[y/N]';
        $response = strtolower($this->ask($question . ' ' . $prompt));

        if ($default) {
            return $response !== 'n';
        }

        return $response === 'y';
    }

    public function chooseMultiple(
        string $question,
        array $options,
        $default = null,
        bool $canSelectMultiple = true
    ): array {
        $this->writePrompt($question, $default);

        $optionKeys = array_keys($options);
        $selectedOptions = [];

        while (true) {
            $input = $this->readInput();

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

    public function askWithValidation(string $question, callable $validator, $default = null)
    {
        while (true) {
            $response = $this->ask($question, $default);

            if ($validator($response)) {
                return $response;
            }

            echo "Invalid input. Please try again." . PHP_EOL;
        }
    }

    public function chooseFromList(string $question, array $options, $default = null): ?string
    {
        $this->writePrompt($question, $default);

        $optionKeys = array_keys($options);

        while (true) {
            $input = $this->readInput();

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

    public function chooseFromListWithIndex(string $question, array $options, $default = null): ?string
    {
        $this->writePrompt($question, $default);

        foreach ($options as $index => $option) {
            echo "[" . $index . "] " . $option . PHP_EOL;
        }

        while (true) {
            $input = $this->readInput();

            if ($input === '' && $default !== null) {
                return $default;
            }

            if (isset($options[$input])) {
                return $input;
            }

            echo "Invalid option. Please try again: ";
        }
    }

    private function writePrompt(string $question, $default = null)
    {
        echo $question . ' ';

        if ($default !== null) {
            echo "[$default] ";
        }
    }

    private function readInput(): string
    {
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        return trim($line);
    }
}
