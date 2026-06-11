<?php

namespace Lightpack\DevOps\Commands;

use Lightpack\Console\Command;

/**
 * Remove an Nginx virtual host and optionally its SSL certificate.
 *
 * Usage:
 *   php console server:site:remove production --domain=example.com
 *   php console server:site:remove --domain=example.com --keep-ssl
 */
class SiteRemoveCommand extends Command
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

        $domain = $this->args->get('domain');

        if (empty($domain)) {
            $this->output->error('Domain is required. Use --domain=example.com');
            return self::FAILURE;
        }

        if (!$this->validateDomain($domain)) {
            $this->output->error("Invalid domain name: {$domain}");
            return self::FAILURE;
        }

        $keepSsl = $this->args->has('keep-ssl');

        $this->output->warning("Removing site {$domain} ...");
        $this->output->newline();

        $remoteScript = $this->buildRemoveScript($domain, $keepSsl);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 60);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("Site {$domain} removed.");
            return self::SUCCESS;
        }

        $this->output->error("Failed to remove site (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

    private function buildRemoveScript(string $domain, bool $keepSsl): string
    {
        $sslCleanup = '';

        if (!$keepSsl) {
            $sslCleanup = <<<SSL

# Remove SSL certificate if it exists (ignore errors)
sudo certbot delete --cert-name "{$domain}" --non-interactive 2>/dev/null || true
SSL;
        }

        return <<<BASH
domain="{$domain}"

# Disable and remove site config
sudo rm -f "/etc/nginx/sites-enabled/\${domain}.conf"
sudo rm -f "/etc/nginx/sites-available/\${domain}.conf"
{$sslCleanup}

# Reload Nginx
sudo systemctl reload nginx

echo "Site \${domain} removed."
BASH;
    }
}
