<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\CommandInterface;
use Lightpack\Console\Output;

class CreateConfig implements CommandInterface
{
    public function run(array $arguments = [])
    {
        $output = new Output();
        $force = $this->parseForceArgument($arguments);

        // Parse --support argument
        $support = $this->parseSupportArgument($arguments);
        if ($support === '') {
            $this->showError($output, "You must provide a value for --support.", null, array_keys(self::getSupportedConfigs()));
            return;
        }

        if ($support) {
            $supported = self::getSupportedConfigs();
            if (!isset($supported[$support])) {
                $this->showError(
                    $output,
                    "Unknown support config: '{$support}'.",
                    null,
                    array_keys($supported)
                );
                return;
            }
            $viewClass = $supported[$support];
            $targetPath = './config/' . $support . '.php';
            if (!class_exists($viewClass) || !method_exists($viewClass, 'getTemplate')) {
                $this->showError(
                    $output,
                    "View class or getTemplate() not found for '{$support}' (expected: {$viewClass}).",
                    null,
                    array_keys($supported)
                );
                return;
            }
            $template = $viewClass::getTemplate();
        } else {
            $name = $arguments[0] ?? null;
            if (!$name) {
                $this->showError($output, "Please provide a config file name.", "You can use --support=<name> for a supported config template.");
                return;
            }
            if (!preg_match('/^[\w_]+$/', $name)) {
                $this->showError($output, "Config file name can only contain alphanumeric characters and underscores.");
                return;
            }
            $targetPath = './config/' . $name . '.php';
            $template = $this->getDefaultTemplate($name);
        }

        if (file_exists($targetPath) && !$force) {
            $this->showError($output, "Config file already exists: {$targetPath}");
            return;
        }

        if ($force && file_exists($targetPath)) {
            $output->infoLabel('Overwrite');
            $output->info(" Overwriting existing config at {$targetPath}");
        }

        file_put_contents($targetPath, $template);
        $output->newline();
        $output->success("✓ Config created at {$targetPath}");
        $output->newline();
    }

    protected function parseForceArgument(array $arguments): bool
    {
        foreach ($arguments as $arg) {
            if ($arg === '--force') {
                return true;
            }
        }
        return false;
    }

    protected function parseSupportArgument(array $arguments): ?string
    {
        foreach ($arguments as $arg) {
            if ($arg === '--support' || $arg === '--support=') {
                return '';
            }
            if (strpos($arg, '--support=') === 0) {
                return substr($arg, strlen('--support='));
            }
        }
        return null;
    }

    protected function showError(Output $output, string $error, ?string $tip = null, ?array $supported = null): void
    {
        $output->newline();
        $output->error($error);
        $output->newline();

        if ($tip) {
            $output->newline();
            $output->info('[Tip]: ');
            $output->line($tip);
        }
        if ($supported) {
            $output->newline();
            $output->info("[Supported]: ");
            $output->line(implode(', ', $supported));
        }
    }

    protected function getDefaultTemplate(string $name): string
    {
        return <<<PHP
<?php

return [
    '{$name}' => [
        // ...
    ]
];
PHP;
    }

    /**
     * Returns an array of supported config view classes.
     * Key: support name, Value: fully qualified class name
     */
    protected static function getSupportedConfigs(): array
    {
        return [
            'ai'        => \Lightpack\Console\Views\Config\AiView::class,
            'app'       => \Lightpack\Console\Views\Config\AppView::class,
            'auth'      => \Lightpack\Console\Views\Config\AuthView::class,
            'cable'     => \Lightpack\Console\Views\Config\CableView::class,
            'captcha'   => \Lightpack\Console\Views\Config\CaptchaView::class,
            'cookies'   => \Lightpack\Console\Views\Config\CookiesView::class,
            'cors'      => \Lightpack\Console\Views\Config\CorsView::class,
            'db'        => \Lightpack\Console\Views\Config\DbView::class,
            'logs'        => \Lightpack\Console\Views\Config\LogsView::class,
            'mfa'       => \Lightpack\Console\Views\Config\MfaView::class,
            'redis'     => \Lightpack\Console\Views\Config\RedisView::class,
            'S3'        => \Lightpack\Console\Views\Config\S3View::class,
            'session'   => \Lightpack\Console\Views\Config\SessionView::class,
            'settings'  => \Lightpack\Console\Views\Config\SettingsView::class,
            'sms'       => \Lightpack\Console\Views\Config\SmsView::class,
            'social'    => \Lightpack\Console\Views\Config\SocialView::class,
            'storage'   => \Lightpack\Console\Views\Config\StorageView::class,
            'uploads'   => \Lightpack\Console\Views\Config\UploadsView::class,
            'webhooks'   => \Lightpack\Console\Views\Config\WebhooksView::class,
        ];
    }
}
