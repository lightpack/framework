<?php

namespace Lightpack\Console;

class Prompt
{
    public function ask(string $question): string
    {
        $this->writePrompt($question);
        return trim($this->readInput());
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

    public function askWithValidation(string $question, callable $validator, ?string $errorMessage = null)
    {
        while (true) {
            $response = $this->ask($question);

            if ($validator($response)) {
                return $response;
            }

            echo PHP_EOL . ($errorMessage ?? "Invalid input. Please try again.") . "\n\n";
        }
    }

    public function chooseMultiple(string $question, array $options, bool $canSelectMultiple = false): ?array
    {
        $optionKeys = array_keys($options);
        $this->writePrompt($question . PHP_EOL);

        echo PHP_EOL;

        foreach ($options as $index => $option) {
            echo "[" . $index . "] " . $option . PHP_EOL;
        }

        echo PHP_EOL;

        while (true) {
            $input = trim($this->readInput());

            if ($canSelectMultiple) {
                $selectedOptions = array_unique(explode(',', $input));
                $selectedOptions = array_map(fn($item) => trim($item), $selectedOptions);
            } else {
                $selectedOptions = [$input];
            }

            $invalidOptions = array_diff($selectedOptions, $optionKeys);

            if (empty($invalidOptions)) {
                return $selectedOptions;
            }

            echo "Invalid option. Please try again: ";
        }
    }

    private function writePrompt(string $question)
    {
        echo $question . ' ';
    }

    private function readInput(): string
    {
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        return trim($line);
    }
}
