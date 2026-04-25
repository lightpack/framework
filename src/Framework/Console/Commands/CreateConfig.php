<?php

namespace Lightpack\Console\Commands;

use Lightpack\Console\Command;

class CreateConfig extends Command
{
    public function run()
    {
        $force = $this->args->has('force');
        $support = $this->args->get('support');
        if ($support === '') {
            $this->showError("You must provide a value for --support.", null, array_keys(self::getSupportedConfigs()));
            return self::FAILURE;
        }

        if ($support) {
            $supported = self::getSupportedConfigs();
            if (!isset($supported[$support])) {
                $this->showError(
                    "Unknown support config: '{$support}'.",
                    null,
                    array_keys($supported)
                );
                return self::FAILURE;
            }
            $viewClass = $supported[$support];
            $targetPath = './config/' . $support . '.php';
            if (!class_exists($viewClass) || !method_exists($viewClass, 'getTemplate')) {
                $this->showError(
                    "View class or getTemplate() not found for '{$support}' (expected: {$viewClass}).",
                    null,
                    array_keys($supported)
                );
                return self::FAILURE;
            }
            $template = $viewClass::getTemplate();
        } else {
            $name = $this->args->argument(0);
            if (!$name) {
                $this->showError("Please provide a config file name.", "You can use --support=<name> for a supported config template.");
                return self::FAILURE;
            }
            if (!preg_match('/^[\w_]+$/', $name)) {
                $this->showError("Config file name can only contain alphanumeric characters and underscores.");
                return self::FAILURE;
            }
            $targetPath = './config/' . $name . '.php';
            $template = $this->getDefaultTemplate($name);
        }

        if (file_exists($targetPath) && !$force) {
            $this->showError("Config already exists: {$targetPath}");
            $this->output->line('Use --force to overwrite.');
            $this->output->newline();
            return self::FAILURE;
        }

        if ($force && file_exists($targetPath)) {
            $this->output->infoLabel('Overwrite');
            $this->output->info(" Overwriting existing config at {$targetPath}");
        }

        file_put_contents($targetPath, $template);
        $this->output->newline();
        $this->output->success("✓ Config created at {$targetPath}");
        $this->output->newline();
        
        return self::SUCCESS;
    }

    protected function showError(string $error, ?string $tip = null, ?array $supported = null): void
    {
        $this->output->newline();
        $this->output->error($error);
        $this->output->newline();

        if ($tip) {
            $this->output->newline();
            $this->output->info('[Tip]: ');
            $this->output->line($tip);
        }
        if ($supported) {
            $this->output->newline();
            $this->output->info("[Supported]: ");
            $this->output->line(implode(', ', $supported));
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
