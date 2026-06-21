<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;
use Lightpack\Deploy\HasDeployConfigTrait;

/**
 * Remove an Nginx virtual host and its SSL certificate.
 *
 * Usage:
 *   php console server:site:remove production --domain=example.com
 */
class SiteRemoveCommand extends Command
{
    use HasDeployConfigTrait;

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
            $this->output->newline();
            $this->output->info("→ Removing site on {$env} ({$envConfig['host']})");
            $this->output->newline();

            $domain = $this->ask('Domain');

            if (!$this->validateDomain($domain)) {
                $this->output->error("Invalid domain name: {$domain}");
                return self::FAILURE;
            }
        }

        if (!$this->validateDomain($domain)) {
            $this->output->error("Invalid domain name: {$domain}");
            return self::FAILURE;
        }

        $this->output->warning("→ Removing site {$domain} ...");
        $this->output->newline();

        $remoteScript = $this->buildRemoveScript($domain);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        $result = $this->executeRemote($sshCommand, 60);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("✓ Site {$domain} removed.");
            return self::SUCCESS;
        }

        $this->output->error("Failed to remove site (exit code: {$result['exit_code']}).");
        return self::FAILURE;
    }

    /**
     * Prompt for a value, returning empty input as-is for validation.
     */

    private function buildRemoveScript(string $domain): string
    {
        return <<<BASH
domain="{$domain}"

# Disable and remove site config
sudo lp-nginx-disable "\${domain}.conf"

# Remove SSL certificate if it exists (ignore errors)
sudo certbot delete --cert-name "{$domain}" --non-interactive 2>/dev/null || true

# Reload Nginx
sudo systemctl reload nginx

echo "Site \${domain} removed."
BASH;
    }
}
