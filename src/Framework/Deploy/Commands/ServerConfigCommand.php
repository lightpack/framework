<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * Update PHP and Nginx runtime configuration on a remote server.
 *
 * Usage:
 *   php console server:config production --upload=100M
 *   php console server:config production --memory=512M --timeout=120
 *   php console server:config production --upload=64M --memory=256M --timeout=60
 */
class ServerConfigCommand extends Command
{
    use HasDeployConfig;

    public function run()
    {
        $config = $this->loadConfig();

        if ($config === null) {
            return self::FAILURE;
        }

        $env = $this->resolveEnvironment($config);
        $envConfig = $this->getEnvConfig($config, $env);

        if ($envConfig === null) {
            $this->printEnvironmentError($config, $env);
            return self::FAILURE;
        }

        $upload  = $this->args->get('upload');
        $memory  = $this->args->get('memory');
        $timeout = $this->args->get('timeout');

        if ($upload === null && $memory === null && $timeout === null) {
            $this->output->error('No options provided. Use at least one of:');
            $this->output->line('  --upload=100M');
            $this->output->line('  --memory=512M');
            $this->output->line('  --timeout=120');
            $this->output->newline();
            return self::FAILURE;
        }

        // Validate formats
        foreach (['upload' => $upload, 'memory' => $memory] as $name => $value) {
            if ($value !== null && !preg_match('/^\d+[KMG]$/i', $value)) {
                $this->output->error("Invalid --{$name} value: '{$value}'. Expected format: 100M, 1G, 512K");
                return self::FAILURE;
            }
        }

        if ($timeout !== null && !ctype_digit((string) $timeout)) {
            $this->output->error("Invalid --timeout value: '{$timeout}'. Must be a positive integer (seconds).");
            return self::FAILURE;
        }

        $this->output->info("Updating server configuration for {$env} ...");
        $this->output->newline();

        if ($upload) {
            $this->output->line("  Upload limit:    {$upload}");
        }
        if ($memory) {
            $this->output->line("  Memory limit:    {$memory}");
        }
        if ($timeout) {
            $this->output->line("  Execution time:  {$timeout}s");
        }
        $this->output->newline();

        $remoteScript = $this->buildConfigScript($upload, $memory, $timeout);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 30);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success('Configuration updated and services reloaded.');
            return self::SUCCESS;
        }

        $this->output->error("Failed to update configuration (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

    private function buildConfigScript(?string $upload, ?string $memory, ?string $timeout): string
    {
        $nginxConf  = '/etc/nginx/nginx.conf';

        $sedCommands = [];

        if ($upload !== null) {
            $uploadUpper = strtoupper($upload);
            $sedCommands[] = "sed -i 's/^upload_max_filesize = .*/upload_max_filesize = {$uploadUpper}/' \${PHP_INI_FILE}";
            $sedCommands[] = "sed -i 's/^post_max_size = .*/post_max_size = {$uploadUpper}/' \${PHP_INI_FILE}";
            $sedCommands[] = "sed -i 's/client_max_body_size .*/client_max_body_size {$uploadUpper};/' {$nginxConf}";
        }

        if ($memory !== null) {
            $memoryUpper = strtoupper($memory);
            $sedCommands[] = "sed -i 's/^memory_limit = .*/memory_limit = {$memoryUpper}/' \${PHP_INI_FILE}";
        }

        if ($timeout !== null) {
            $sedCommands[] = "sed -i 's/^max_execution_time = .*/max_execution_time = {$timeout}/' \${PHP_INI_FILE}";
            $sedCommands[] = "sed -i 's/^max_input_time = .*/max_input_time = {$timeout}/' \${PHP_INI_FILE}";
        }

        $sedBlock = implode("\n", $sedCommands);

        return <<<BASH
set -e

PHP_VER=\$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null)
if [ -z "\$PHP_VER" ]; then
    echo "ERROR: Cannot determine PHP version on server" >&2
    exit 1
fi

PHP_INI_FILE="/etc/php/\${PHP_VER}/fpm/conf.d/99-lightpack.ini"

{$sedBlock}

echo "Reloading PHP-FPM..."
sudo systemctl reload php\${PHP_VER}-fpm

if sudo nginx -t 2>/dev/null; then
    echo "Reloading Nginx..."
    sudo systemctl reload nginx
else
    echo "Nginx config test failed. Reverting nginx.conf may be needed."
    exit 1
fi

echo "Done."
BASH;
    }
}
