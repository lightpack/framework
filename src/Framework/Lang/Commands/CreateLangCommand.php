<?php

namespace Lightpack\Lang\Commands;

use Lightpack\Console\Command;

/**
 * Create a language file.
 *
 * Usage:
 *   php console create:lang [name] [--locale=en] [--force]
 *   php console create:lang --support=validation [--locale=en] [--force]
 *
 * Examples:
 *   php console create:lang                       # prompts for locale and file name
 *   php console create:lang forms --locale=hi      # creates lang/hi/forms.php
 *   php console create:lang --support=validation   # copies validation stub to lang/en/validation.php
 */
class CreateLangCommand extends Command
{
    public function run()
    {
        $force = $this->args->has('force');
        $support = $this->args->get('support');

        if ($support === '') {
            $this->showError(
                'You must provide a value for --support.',
                null,
                array_keys(self::getSupportedLangs())
            );

            return self::FAILURE;
        }

        if ($support) {
            $supported = self::getSupportedLangs();
            if (! isset($supported[$support])) {
                $this->showError(
                    "Unknown support lang: '{$support}'.",
                    null,
                    array_keys($supported)
                );

                return self::FAILURE;
            }
        }

        $locale = $this->args->get('locale');

        if ($locale === null || $locale === '') {
            $locale = $this->prompt->ask('Enter locale (default: en)');
            $locale = trim($locale);

            if ($locale === '') {
                $locale = 'en';
            }
        }

        if (! preg_match('/^[a-zA-Z_-]+$/', $locale)) {
            $this->output->error('Locale can only contain letters, underscores, and hyphens.');

            return self::FAILURE;
        }

        $langDir = DIR_ROOT . '/lang/' . $locale;
        $displayDir = './lang/' . $locale;

        if ($support === 'validation') {
            $sourcePath = __DIR__ . '/../stubs/validation.stub.php';
            $targetPath = $langDir . '/validation.php';

            if (! file_exists($sourcePath)) {
                $this->output->error("Validation stub not found: {$sourcePath}");

                return self::FAILURE;
            }

            if (file_exists($targetPath) && ! $force) {
                $this->output->error("File already exists: {$targetPath}");
                $this->output->line('Use --force to overwrite.');
                $this->output->newline();

                return self::FAILURE;
            }

            if (! is_dir($langDir)) {
                mkdir($langDir, 0755, true);
            }

            $content = file_get_contents($sourcePath);
            file_put_contents($targetPath, $content);
            $this->output->newline();
            $this->output->success("✓ Validation lang file created at {$displayDir}/validation.php");

            return self::SUCCESS;
        }

        $name = $this->args->argument(0);

        if (! $name) {
            $name = $this->prompt->ask('Enter lang file name (default: messages)');
            $name = trim($name);

            if ($name === '') {
                $name = 'messages';
            }
        }

        if (! preg_match('/^[\w_-]+$/', $name)) {
            $this->output->error('File name can only contain letters, numbers, underscores, and hyphens.');

            return self::FAILURE;
        }

        $targetPath = $langDir . '/' . $name . '.php';
        $displayPath = $displayDir . '/' . $name . '.php';

        if (file_exists($targetPath) && ! $force) {
            $this->output->error("File already exists: {$targetPath}");
            $this->output->line('Use --force to overwrite.');
            $this->output->newline();

            return self::FAILURE;
        }

        if (! is_dir($langDir)) {
            mkdir($langDir, 0755, true);
        }

        $template = $this->getTemplate($name);
        file_put_contents($targetPath, $template);
        $this->output->newline();
        $this->output->success("✓ Lang file created at {$displayPath}");

        return self::SUCCESS;
    }

    protected function showError(string $error, ?string $tip = null, ?array $supported = null): void
    {
        $this->output->newline();
        $this->output->error($error);

        if ($tip) {
            $this->output->newline();
            $this->output->info('[Tip]: ');
            $this->output->line($tip);
        }

        if ($supported) {
            $this->output->newline();
            $this->output->info('[Supported]: ');
            $this->output->line(implode(', ', $supported));
        }
    }

    protected function getTemplate(string $name): string
    {
        return <<<PHP
<?php

return [
    //
];
PHP;
    }

    /**
     * Returns an array of supported language stubs.
     * Key: support name, Value: stub file name.
     */
    protected static function getSupportedLangs(): array
    {
        return [
            'validation' => 'validation.stub.php',
        ];
    }
}
