<?php

namespace Lightpack\Lang\Commands;

use Lightpack\Console\Command;

class CreateLangCommand extends Command
{
    public function run()
    {
        $locale = $this->args->get('locale', 'en');
        $support = $this->args->get('support');
        $force = $this->args->has('force');

        $langDir = DIR_ROOT . '/lang/' . $locale;

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
            $this->output->success("✓ Validation lang file created at {$targetPath}");

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
        $this->output->success("✓ Lang file created at {$targetPath}");

        return self::SUCCESS;
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
}
