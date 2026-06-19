<?php

namespace Lightpack\Deploy\Commands;

use Lightpack\Console\Command;

/**
 * Obtain and install an SSL certificate via Certbot.
 *
 * Usage:
 *   php console server:site:ssl production --domain=example.com
 *   php console server:site:ssl --domain=example.com --email=admin@example.com --www
 */
class SslCommand extends Command
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

        $email = $this->args->get('email');
        $includeWww = $this->args->has('www');

        if (empty($email)) {
            $input = trim((string) $this->prompt->ask('  Email for SSL renewal notices (Enter to skip)'));
            $email = $input !== '' ? $input : null;
        }

        if (empty($email)) {
            $this->output->warning('No email provided. Running without email (not recommended — you will miss expiry notices).');
            $this->output->newline();
        }

        $this->output->info("Obtaining SSL certificate for {$domain} ...");
        $this->output->newline();

        $remoteScript = $this->buildCertbotScript($domain, $email, $includeWww, $env);
        $sshCommand = $this->buildSshCommand($envConfig, $remoteScript);

        // Certbot can take a while to validate and obtain certificates
        $result = $this->executeRemote($sshCommand, 180);

        $this->output->newline();

        if ($result['success']) {
            $this->output->success("SSL certificate installed for {$domain}.");
            $this->output->line("HTTPS should now be active.");
            return self::SUCCESS;
        }

        $this->output->error("SSL setup failed (exit code: {$result['exit_code']}).");
        $this->output->newline();
        $this->output->line('Common causes:');
        $this->output->line('  - Domain DNS does not point to this server');
        $this->output->line('  - Port 80 is blocked by firewall');
        $this->output->line('  - Nginx site config does not exist (run server:site:add first)');

        return self::FAILURE;
    }

    private function buildCertbotScript(string $domain, ?string $email, bool $includeWww, string $env): string
    {
        $domains = [$domain];
        if ($includeWww) {
            $domains[] = "www.{$domain}";
        }
        $domainFlags = implode(' ', array_map(fn($d) => "-d {$d}", $domains));

        $emailFlag = '';
        if (!empty($email)) {
            $emailFlag = "--email {$email}";
        } else {
            $emailFlag = '--register-unsafely-without-email';
        }

        return <<<BASH
set -e

# Ensure Nginx site config exists before running certbot
if [ ! -f "/etc/nginx/sites-available/{$domain}.conf" ]; then
    echo "ERROR: Nginx site config not found for {$domain}"
    echo "Run: php console server:site:add {$env} --domain={$domain}"
    exit 1
fi

# Run Certbot with Nginx plugin
sudo certbot --nginx \
    {$domainFlags} \
    {$emailFlag} \
    --non-interactive \
    --agree-tos \
    --redirect \
    --hsts \
    --staple-ocsp
BASH;
    }
}
